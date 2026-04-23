const { SlashCommandBuilder, ActionRowBuilder, ButtonBuilder, ButtonStyle } = require('discord.js');
const { getOrCreateUser } = require('../utils/db');
const { getUserCollection, getUserStats, RARITIES } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('collection')
        .setDescription(`${EMOJIS.collection} Voir votre collection de cartes`)
        .addStringOption(opt =>
            opt.setName('rareté')
               .setDescription('Filtrer par rareté')
               .addChoices(
                   { name: 'Commune', value: 'commune' },
                   { name: 'Peu Commune', value: 'peu_commune' },
                   { name: 'Rare', value: 'rare' },
                   { name: 'Épique', value: 'epique' },
                   { name: 'Légendaire', value: 'legendaire' },
               )
        ),

    async execute(interaction) {
        await interaction.deferReply();

        try {
            await getOrCreateUser(interaction.user);
            const rarityFilter = interaction.options.getString('rareté') || null;
            let page = 1;

            const buildPayload = async (currentPage) => {
                const stats = await getUserStats(interaction.user.id);
                const data = await getUserCollection(interaction.user.id, currentPage, 10, rarityFilter);
                const filled = Math.round((stats.owned / Math.max(stats.total, 1)) * 10);
                const bar = '█'.repeat(filled) + '░'.repeat(10 - filled);
                const lines = data.cards.map(c => {
                    const r = RARITIES[c.rarity] || RARITIES.commune;
                    return `${EMOJIS.star.repeat(r.stars)} **${c.name}** · ${c.character_name}`;
                });
                const payload = messageComponents([
                    `## ${EMOJIS.collection} Collection`,
                    `Progression : \`${bar}\` **${stats.owned}/${stats.total}** (${stats.percent}%)`,
                    rarityFilter ? `Filtre : **${RARITIES[rarityFilter]?.label || rarityFilter}**` : '',
                    '',
                    lines.length ? lines.join('\n') : '_Aucune carte dans cette catégorie._',
                    '',
                    `Page **${currentPage}/${Math.max(data.pages, 1)}**`,
                ].filter(Boolean).join('\n'));

                const row = new ActionRowBuilder();
                if (data.pages > 1) {
                    row.addComponents(
                        new ButtonBuilder()
                            .setCustomId('col_prev')
                            .setLabel('Précédent')
                            .setStyle(ButtonStyle.Secondary)
                            .setDisabled(currentPage <= 1),
                        new ButtonBuilder()
                            .setCustomId('col_next')
                            .setLabel('Suivant')
                            .setStyle(ButtonStyle.Secondary)
                            .setDisabled(currentPage >= data.pages),
                    );
                    payload.components.push(row);
                }

                return { payload, pages: data.pages };
            };

            const { payload } = await buildPayload(page);
            const msg = await interaction.editReply(payload);
            if (payload.components.length <= 1) return;

            const collector = msg.createMessageComponentCollector({ time: 120_000 });
            collector.on('collect', async btn => {
                if (btn.user.id !== interaction.user.id) {
                    return btn.reply({ content: 'Cette collection ne vous appartient pas.', ephemeral: true });
                }
                await btn.deferUpdate();
                if (btn.customId === 'col_prev') page = Math.max(1, page - 1);
                if (btn.customId === 'col_next') page++;
                const { payload: nextPayload, pages } = await buildPayload(page);
                page = Math.min(page, Math.max(pages, 1));
                await btn.editReply(nextPayload);
            });
            collector.on('end', async () => {
                const { payload: endPayload } = await buildPayload(page);
                endPayload.components = endPayload.components.slice(0, 1);
                interaction.editReply(endPayload).catch(() => {});
            });
        } catch (error) {
            console.error('[/collection] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue.`));
        }
    },
};
