const mysql = require('mysql2/promise');

const pool = mysql.createPool({
    host:             process.env.DB_HOST     || 'localhost',
    database:         process.env.DB_NAME     || 'edwardbot',
    user:             process.env.DB_USER     || 'root',
    password:         process.env.DB_PASS     || '',
    waitForConnections: true,
    connectionLimit:  10,
    queueLimit:       0,
    charset:          'utf8mb4',
    timezone:         '+00:00',
});

async function query(sql, params = []) {
    const [rows] = await pool.execute(sql, params);
    return rows;
}

async function queryOne(sql, params = []) {
    const rows = await query(sql, params);
    return rows[0] || null;
}

async function execute(sql, params = []) {
    const [result] = await pool.execute(sql, params);
    return result;
}

async function getOrCreateUser(discordUser) {
    const existing = await queryOne('SELECT * FROM users WHERE id = ?', [discordUser.id]);
    const today    = new Date().toISOString().split('T')[0];

    if (existing) {
        // Reset rolls if new day
        const lastReset = existing.last_roll_reset
            ? new Date(existing.last_roll_reset).toISOString().split('T')[0]
            : null;

        if (lastReset !== today) {
            await execute(
                'UPDATE users SET rolls_remaining = ?, last_roll_reset = ?, username = ?, global_name = ?, avatar = ? WHERE id = ?',
                [parseInt(process.env.MAX_DAILY_ROLLS || 10), today,
                 discordUser.username, discordUser.globalName || discordUser.username,
                 discordUser.avatar,
                 discordUser.id]
            );
            existing.rolls_remaining = parseInt(process.env.MAX_DAILY_ROLLS || 10);
        } else {
            await execute(
                'UPDATE users SET username = ?, global_name = ?, avatar = ? WHERE id = ?',
                [discordUser.username, discordUser.globalName || discordUser.username, discordUser.avatar, discordUser.id]
            );
        }
        return await queryOne('SELECT * FROM users WHERE id = ?', [discordUser.id]);
    } else {
        await execute(
            'INSERT INTO users (id, username, global_name, avatar, rolls_remaining, last_roll_reset) VALUES (?, ?, ?, ?, ?, ?)',
            [discordUser.id, discordUser.username,
             discordUser.globalName || discordUser.username,
             discordUser.avatar,
             parseInt(process.env.MAX_DAILY_ROLLS || 10), today]
        );
        return await queryOne('SELECT * FROM users WHERE id = ?', [discordUser.id]);
    }
}

module.exports = { query, queryOne, execute, getOrCreateUser };
