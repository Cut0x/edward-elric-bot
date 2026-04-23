const { SlashCommandBuilder } = require('discord.js');
const { getOrCreateUser } = require('../utils/db');
const { getUserStats, RARITIES, MAX_DAILY_ROLLS } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

module.exports = {
    data: new SlashCommandBuilder()
        .setName('profil')
        .setDescription(`${EMOJIS.profile} Voir un profil de collectionneur`)
        .addUserOption(opt =>
            opt.setName('utilisateur')
               .setDescription('Voir le profil d\'un autre utilisateur')
        ),

    async execute(interaction) {
        await interaction.deferReply();

        try {
            const target = interaction.options.getUser('utilisateur') || interaction.user;
            await getOrCreateUser(target);
            const stats = await getUserStats(target.id);
            const collectionFilled = Math.round((stats.owned / Math.max(stats.total, 1)) * 10);
            const collectionBar = '█'.repeat(collectionFilled) + '░'.repeat(10 - collectionFilled);
            const xpFilled = Math.round((stats.xpProgress / Math.max(stats.xpNeeded, 1)) * 10);
            const xpBar = '█'.repeat(xpFilled) + '░'.repeat(10 - xpFilled);
            const isSelf = target.id === interaction.user.id;
            const rarest = stats.rarest
                ? `${stats.rarest.name} (${RARITIES[stats.rarest.rarity]?.label || stats.rarest.rarity})`
                : 'Aucune carte';
            const rarityLine = ['legendaire', 'epique', 'rare', 'peu_commune', 'commune']
                .map(key => `${RARITIES[key].stars}⭐ ${stats.rarityCounts[key] || 0}`)
                .join(' · ');
            const profileUrl = process.env.APP_URL
                ? `\n${EMOJIS.info} Profil web : ${process.env.APP_URL.replace(/\/$/, '')}/user.php?id=${target.id}`
                : '';

            await interaction.editReply(messageComponents([
                `## ${EMOJIS.profile} ${target.globalName || target.username}`,
                `${EMOJIS.collection} Collection : \`${collectionBar}\` **${stats.owned}/${stats.total}** (${stats.percent}%)`,
                `${EMOJIS.exp} Niveau **${stats.level}** · \`${xpBar}\` **${stats.xpProgress}/${stats.xpNeeded} XP** (${stats.xp} total)`,
                `${EMOJIS.star} Meilleure carte : **${rarest}**`,
                `${EMOJIS.star} Raretés : ${rarityLine}`,
                `${EMOJIS.rank} Rang collection : **#${stats.rank}**`,
                isSelf
                    ? `${EMOJIS.reroll} Rolls : **${stats.rollsLeft}/${MAX_DAILY_ROLLS}** · Total : **${stats.totalRolls}** · Doublons : **${stats.duplicates}**`
                    : `${EMOJIS.reroll} Rolls totaux : **${stats.totalRolls}** · Doublons : **${stats.duplicates}**`,
                profileUrl,
            ].join('\n')));
        } catch (error) {
            console.error('[/profil] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue.`));
        }
    },
};
