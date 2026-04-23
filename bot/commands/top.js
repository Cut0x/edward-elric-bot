const { SlashCommandBuilder } = require('discord.js');
const { getLeaderboard } = require('../utils/cards');
const { queryOne } = require('../utils/db');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

const MEDALS = ['🥇', '🥈', '🥉'];

module.exports = {
    data: new SlashCommandBuilder()
        .setName('top')
        .setDescription(`${EMOJIS.rank} Voir le classement des meilleurs collectionneurs`),

    async execute(interaction) {
        await interaction.deferReply();

        try {
            const leaders = await getLeaderboard(10);
            const totalCards = (await queryOne('SELECT COUNT(*) as c FROM cards WHERE is_active = 1'))?.c || 0;

            if (!leaders.length) {
                return interaction.editReply(messageComponents(`${EMOJIS.rank} Aucun collectionneur pour l'instant.`));
            }

            const lines = leaders.map((u, i) => {
                const medal = MEDALS[i] || `#${i + 1}`;
                const name = u.global_name || u.username;
                const pct = totalCards > 0 ? ((u.owned / totalCards) * 100).toFixed(1) : '0.0';
                return `${medal} **${name}** · ${u.owned} cartes (${pct}%)`;
            });

            await interaction.editReply(messageComponents([
                `## ${EMOJIS.rank} Classement`,
                ...lines,
            ].join('\n')));
        } catch (error) {
            console.error('[/top] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue.`));
        }
    },
};
