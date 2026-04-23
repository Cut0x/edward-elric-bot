const {
    ActionRowBuilder,
    AttachmentBuilder,
    ButtonBuilder,
    ButtonStyle,
    ContainerBuilder,
    MediaGalleryBuilder,
    MediaGalleryItemBuilder,
    MessageFlags,
    TextDisplayBuilder,
} = require('discord.js');
const fs = require('fs');

function textComponent(content) {
    return new TextDisplayBuilder().setContent(content);
}

function messageComponents(content, image = null, linkUrl = null) {
    const container = new ContainerBuilder().addTextDisplayComponents(textComponent(content));
    const files = [];

    if (image?.path && image?.name && fs.existsSync(image.path)) {
        files.push(new AttachmentBuilder(image.path, { name: image.name }));
        container.addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(
                new MediaGalleryItemBuilder().setURL(`attachment://${image.name}`)
            )
        );
    }

    if (linkUrl) {
        container.addActionRowComponents(
            new ActionRowBuilder().addComponents(
                new ButtonBuilder()
                    .setLabel('Allez au site')
                    .setStyle(ButtonStyle.Link)
                    .setURL(linkUrl)
            )
        );
    }

    return {
        components: [container],
        files,
        flags: MessageFlags.IsComponentsV2,
    };
}

module.exports = {
    messageComponents,
    textComponent,
};
