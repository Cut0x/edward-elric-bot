const { SlashCommandBuilder, MessageFlags } = require('discord.js');
const { getOrCreateUser } = require('../utils/db');
const { MAX_DAILY_ROLLS } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('reroll')
        .setDescription('Voir vos rolls quotidiens restants'),

    async execute(interaction) {
        await interaction.deferReply({ flags: MessageFlags.Ephemeral });

        try {
            const user = await getOrCreateUser(interaction.user);
            const midnight = new Date();
            midnight.setUTCHours(24, 0, 0, 0);
            const diffMs = midnight - Date.now();
            const diffH = Math.floor(diffMs / 3600000);
            const diffM = Math.floor((diffMs % 3600000) / 60000);
            const filled = Math.round((user.rolls_remaining / MAX_DAILY_ROLLS) * 10);
            const bar = '█'.repeat(filled) + '░'.repeat(10 - filled);

            await interaction.editReply(messageComponents([
                `## ${EMOJIS.reroll} Rolls quotidiens`,
                `\`${bar}\` **${user.rolls_remaining}/${MAX_DAILY_ROLLS}**`,
                `Recharge dans **${diffH}h ${diffM}min**.`,
            ].join('\n')));
        } catch (error) {
            console.error('[/reroll] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue.`));
        }
    },
};
