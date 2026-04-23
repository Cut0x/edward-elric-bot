const { SlashCommandBuilder, MessageFlags } = require('discord.js');
const { MAX_DAILY_ROLLS } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('aide')
        .setDescription('Aide et liste des commandes du bot'),

    async execute(interaction) {
        const payload = messageComponents([
            `## ${EMOJIS.info} Edward Elric Bot`,
            `**/roll** · Tirer une carte`,
            `**/reroll** · Voir vos rolls restants`,
            `**/carte** · Rechercher une carte`,
            `**/collection** · Voir votre collection`,
            `**/profil** · Voir un profil`,
            `**/top** · Classement`,
            '',
            `${EMOJIS.reroll} **${MAX_DAILY_ROLLS} rolls** par jour, recharge à minuit UTC.`,
        ].join('\n'));

        await interaction.reply({
            ...payload,
            flags: MessageFlags.IsComponentsV2 | MessageFlags.Ephemeral,
        });
    },
};
