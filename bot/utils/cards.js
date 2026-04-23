const path      = require('path');
const { query, queryOne, execute, getOrCreateUser } = require('./db');

const MAX_DAILY_ROLLS = parseInt(process.env.MAX_DAILY_ROLLS || 10);
const CARDS_DIR       = path.join(__dirname, '..', '..', 'cards');
const XP_PER_ROLL     = parseInt(process.env.XP_PER_ROLL || 15);

const RARITIES = {
    commune:     { label: 'Commune',     color: 0x6b6b6b, emoji: '◆',     stars: 1, xp: 25  },
    peu_commune: { label: 'Peu Commune', color: 0x27ae60, emoji: '◆◆',    stars: 2, xp: 50  },
    rare:        { label: 'Rare',        color: 0x2980b9, emoji: '◆◆◆',   stars: 3, xp: 100 },
    epique:      { label: 'Épique',      color: 0x8e44ad, emoji: '★★★★',  stars: 4, xp: 200 },
    legendaire:  { label: 'Légendaire',  color: 0xf39c12, emoji: '✦✦✦✦✦', stars: 5, xp: 500 },
};

function xpToLevel(xp) {
    return Math.max(1, Math.floor(Math.sqrt(Math.max(0, xp) / 100)) + 1);
}

function selectByWeight(cards) {
    const total = cards.reduce((s, c) => s + c.rarity_weight, 0);
    let rand    = Math.random() * total;
    for (const card of cards) {
        rand -= card.rarity_weight;
        if (rand <= 0) return card;
    }
    return cards[cards.length - 1];
}

async function rollCard(discordUser) {
    const user  = await getOrCreateUser(discordUser);
    const today = new Date().toISOString().split('T')[0];

    if (user.rolls_remaining <= 0) {
        const midnight = new Date();
        midnight.setUTCHours(24, 0, 0, 0);
        const diffMs   = midnight - Date.now();
        const diffH    = Math.floor(diffMs / 3600000);
        const diffM    = Math.floor((diffMs % 3600000) / 60000);
        return {
            success: false,
            reason:  'no_rolls',
            timeLeft: `${diffH}h${diffM}m`,
        };
    }

    const cards = await query('SELECT * FROM cards WHERE is_active = 1');
    if (!cards.length) {
        return { success: false, reason: 'no_cards' };
    }

    const card   = selectByWeight(cards);
    const rarity = RARITIES[card.rarity] || RARITIES.commune;

    const existing = await queryOne(
        'SELECT id FROM user_cards WHERE user_id = ? AND card_id = ?',
        [discordUser.id, card.id]
    );
    const isDuplicate = !!existing;

    if (!isDuplicate) {
        await execute('INSERT INTO user_cards (user_id, card_id) VALUES (?, ?)', [discordUser.id, card.id]);
    }

    const xpGained = XP_PER_ROLL + (!isDuplicate ? rarity.xp : 0);
    const currentUser = await queryOne('SELECT xp FROM users WHERE id = ?', [discordUser.id]);
    const newXp = parseInt(currentUser?.xp || 0) + xpGained;
    const newLevel = xpToLevel(newXp);

    await execute(
        'INSERT INTO roll_history (user_id, card_id, was_duplicate, xp_gained) VALUES (?, ?, ?, ?)',
        [discordUser.id, card.id, isDuplicate ? 1 : 0, xpGained]
    );

    await execute(
        'UPDATE users SET rolls_remaining = rolls_remaining - 1, total_rolls = total_rolls + 1, xp = ?, level = ? WHERE id = ?',
        [newXp, newLevel, discordUser.id]
    );

    const updated = await queryOne('SELECT rolls_remaining FROM users WHERE id = ?', [discordUser.id]);

    return {
        success:       true,
        card,
        rarity,
        isDuplicate,
        rollsRemaining: updated.rolls_remaining,
        maxRolls:       MAX_DAILY_ROLLS,
        xpGained,
        level:          newLevel,
        imagePath:      path.join(CARDS_DIR, card.image_file),
    };
}

async function getUserStats(userId) {
    const owned      = await queryOne('SELECT COUNT(*) as c FROM user_cards WHERE user_id = ?', [userId]);
    const total      = await queryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1');
    const user       = await queryOne('SELECT rolls_remaining, total_rolls, xp, level FROM users WHERE id = ?', [userId]);
    const duplicates = await queryOne('SELECT COUNT(*) as c FROM roll_history WHERE user_id = ? AND was_duplicate = 1', [userId]);
    const rank       = await queryOne(
        `SELECT COUNT(*) + 1 as c
         FROM (
             SELECT u.id, COUNT(uc.card_id) as owned_count
             FROM users u
             LEFT JOIN user_cards uc ON uc.user_id = u.id
             GROUP BY u.id
         ) ranked
         WHERE ranked.owned_count > ?`,
        [parseInt(owned?.c || 0)]
    );
    const rarest     = await queryOne(
        `SELECT c.name, c.rarity FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = ?
         ORDER BY FIELD(c.rarity,'legendaire','epique','rare','peu_commune','commune') ASC
         LIMIT 1`,
        [userId]
    );
    const rarityRows = await query(
        `SELECT c.rarity, COUNT(*) as c
         FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = ?
         GROUP BY c.rarity`,
        [userId]
    );

    const ownedCount = parseInt(owned?.c || 0);
    const totalCount = parseInt(total?.c || 0);
    const percent    = totalCount > 0 ? ((ownedCount / totalCount) * 100).toFixed(1) : '0.0';
    const xp          = parseInt(user?.xp || 0);
    const level       = xpToLevel(xp);
    const currentMin  = (level - 1) ** 2 * 100;
    const nextMin     = level ** 2 * 100;
    const xpProgress  = xp - currentMin;
    const xpNeeded    = nextMin - currentMin;
    const rarityCounts = Object.fromEntries(rarityRows.map(row => [row.rarity, parseInt(row.c || 0)]));

    return {
        owned:      ownedCount,
        total:      totalCount,
        percent,
        rollsLeft:  user?.rolls_remaining ?? MAX_DAILY_ROLLS,
        totalRolls: user?.total_rolls ?? 0,
        duplicates: parseInt(duplicates?.c || 0),
        rank:       parseInt(rank?.c || 1),
        xp,
        level,
        xpProgress,
        xpNeeded,
        rarityCounts,
        rarest:     rarest || null,
    };
}

async function getUserCollection(userId, page = 1, perPage = 10, rarityFilter = null) {
    const params = [userId];
    let rarityWhere = '';
    if (rarityFilter) {
        rarityWhere = 'AND c.rarity = ?';
        params.push(rarityFilter);
    }

    const offset = (page - 1) * perPage;
    const totalRow = await queryOne(
        `SELECT COUNT(*) as c FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = ? ${rarityWhere}`,
        params
    );
    const total = parseInt(totalRow?.c || 0);
    const pages = Math.ceil(total / perPage);

    const cards = await query(
        `SELECT c.name, c.character_name, c.rarity, c.serie, uc.obtained_at
         FROM user_cards uc
         JOIN cards c ON c.id = uc.card_id
         WHERE uc.user_id = ? ${rarityWhere}
         ORDER BY FIELD(c.rarity,'legendaire','epique','rare','peu_commune','commune'), c.name
         LIMIT ? OFFSET ?`,
        [...params, perPage, offset]
    );

    return { cards, total, pages, page };
}

async function getLeaderboard(limit = 10) {
    return query(
        `SELECT u.username, u.global_name, COUNT(uc.card_id) as owned
         FROM users u
         LEFT JOIN user_cards uc ON uc.user_id = u.id
         GROUP BY u.id
         ORDER BY owned DESC
         LIMIT ?`,
        [limit]
    );
}

module.exports = {
    RARITIES,
    CARDS_DIR,
    XP_PER_ROLL,
    rollCard,
    getUserStats,
    getUserCollection,
    getLeaderboard,
    MAX_DAILY_ROLLS,
};
