const { SlashCommandBuilder } = require('discord.js');
const path = require('path');
const { query, queryOne, getOrCreateUser } = require('../utils/db');
const { RARITIES, CARDS_DIR } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('carte')
        .setDescription(`${EMOJIS.search} Rechercher une carte par son nom`)
        .addStringOption(opt =>
            opt.setName('nom')
               .setDescription('Nom de la carte ou du personnage')
               .setRequired(true)
               .setAutocomplete(true)
        ),

    async autocomplete(interaction) {
        const focused = interaction.options.getFocused().toLowerCase();
        const cards = await query(
            'SELECT id, name, character_name FROM cards WHERE is_active = 1 AND (LOWER(name) LIKE ? OR LOWER(character_name) LIKE ?) LIMIT 25',
            [`%${focused}%`, `%${focused}%`]
        );
        await interaction.respond(cards.map(c => ({ name: `${c.name} — ${c.character_name}`, value: String(c.id) })));
    },

    async execute(interaction) {
        await interaction.deferReply();

        try {
            const input = interaction.options.getString('nom');
            const cardId = parseInt(input);
            await getOrCreateUser(interaction.user);

            let card = null;
            if (!Number.isNaN(cardId)) {
                card = await queryOne('SELECT * FROM cards WHERE id = ? AND is_active = 1', [cardId]);
            }
            if (!card) {
                card = await queryOne(
                    'SELECT * FROM cards WHERE is_active = 1 AND (name LIKE ? OR character_name LIKE ?) LIMIT 1',
                    [`%${input}%`, `%${input}%`]
                );
            }
            if (!card) {
                return interaction.editReply(messageComponents(`${EMOJIS.error} Aucune carte trouvée pour **${input}**.`));
            }

            const rarity = RARITIES[card.rarity] || RARITIES.commune;
            const owned = await queryOne('SELECT obtained_at FROM user_cards WHERE user_id = ? AND card_id = ?', [interaction.user.id, card.id]);
            const ownerCount = (await queryOne('SELECT COUNT(*) as c FROM user_cards WHERE card_id = ?', [card.id]))?.c || 0;
            const totalUsers = (await queryOne('SELECT COUNT(*) as c FROM users'))?.c || 1;
            const ownerPct = ((ownerCount / totalUsers) * 100).toFixed(1);
            const imagePath = path.join(CARDS_DIR, card.image_file);

            await interaction.editReply(messageComponents([
                `## ${EMOJIS.card} ${card.name}`,
                `${EMOJIS.star} Étoiles : **${rarity.stars} ${EMOJIS.star}** · ${rarity.label}`,
                `${EMOJIS.profile} Personnage : **${card.character_name}**`,
                `${EMOJIS.collection} Série : **${card.serie}**`,
                `${EMOJIS.info} Propriétaires : **${ownerCount}** (${ownerPct}%)`,
                owned ? `${EMOJIS.success} Vous possédez cette carte.` : `${EMOJIS.error} Vous ne possédez pas cette carte.`,
            ].join('\n'), card.image_file ? { path: imagePath, name: card.image_file } : null));
        } catch (error) {
            console.error('[/carte] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue.`));
        }
    },
};
