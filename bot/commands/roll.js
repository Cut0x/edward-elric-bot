const {
    SlashCommandBuilder,
    ContainerBuilder,
    MediaGalleryBuilder,
    MediaGalleryItemBuilder,
    MessageFlags,
    TextDisplayBuilder,
} = require('discord.js');
const { rollCard, RARITIES, MAX_DAILY_ROLLS } = require('../utils/cards');
const EMOJIS = require('../config/emojis');
const { messageComponents } = require('../utils/components');

function buildComponents(text, imageFile = null) {
    const container = new ContainerBuilder()
        .addTextDisplayComponents(
            new TextDisplayBuilder().setContent(text)
        );

    if (imageFile) {
        container.addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(
                new MediaGalleryItemBuilder().setURL(`attachment://${imageFile}`)
            )
        );
    }

    return [container];
}

module.exports = {
    data: new SlashCommandBuilder()
        .setName('roll')
        .setDescription(`${EMOJIS.card} Tirer une carte aléatoire depuis la collection`),

    async execute(interaction) {
        await interaction.deferReply();

        try {
            const result = await rollCard(interaction.user);

            if (!result.success) {
                if (result.reason === 'no_rolls') {
                    const text = [
                        `## ${EMOJIS.time} Plus de rolls disponibles`,
                        `Vous avez utilisé vos **${MAX_DAILY_ROLLS} rolls** d'aujourd'hui.`,
                        `Prochain rechargement dans **${result.timeLeft}**.`,
                    ].join('\n');

                    return interaction.editReply({
                        components: buildComponents(text),
                        flags: MessageFlags.IsComponentsV2,
                    });
                }

                if (result.reason === 'no_cards') {
                    return interaction.editReply(messageComponents(`${EMOJIS.error} Aucune carte disponible pour le moment.`));
                }
            }

            const { card, imagePath, xpGained } = result;
            const rarityInfo = RARITIES[card.rarity] || RARITIES.commune;
            const stars = `${rarityInfo.stars} ${EMOJIS.star}`;
            const text = [
                `## ${EMOJIS.card} Carte roll : ${card.name}`,
                `${EMOJIS.star} Étoiles : **${stars}**`,
                `${EMOJIS.exp} XP gagné : **+${xpGained}**`,
            ].join('\n');

            await interaction.editReply(messageComponents(
                text,
                card.image_file ? { path: imagePath, name: card.image_file } : null
            ));

        } catch (error) {
            console.error('[/roll] Erreur :', error);
            await interaction.editReply(messageComponents(`${EMOJIS.error} Une erreur est survenue. Réessayez dans quelques instants.`));
        }
    },
};
