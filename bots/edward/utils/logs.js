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
const { createCanvas } = require('@napi-rs/canvas');

function wrapText(ctx, text, maxWidth) {
    const words = String(text || '').replace(/\r/g, '').split(/\s+/);
    const lines = [];
    let line = '';

    for (const word of words) {
        const test = line ? `${line} ${word}` : word;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = word;
        } else {
            line = test;
        }
    }
    if (line) lines.push(line);
    return lines;
}

function renderMessageImage(title, message) {
    const width = 920;
    const padding = 40;
    const lineHeight = 30;
    const measureCanvas = createCanvas(width, 260);
    const measureCtx = measureCanvas.getContext('2d');
    measureCtx.font = '24px Arial';
    const lines = wrapText(measureCtx, message, width - padding * 2);
    const height = Math.max(260, 126 + lines.length * lineHeight + padding);

    const canvas = createCanvas(width, height);
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#1e1f22';
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = '#2b2d31';
    ctx.roundRect(20, 20, width - 40, height - 40, 16);
    ctx.fill();
    ctx.strokeStyle = 'rgba(255,255,255,.12)';
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.fillStyle = '#f2f3f5';
    ctx.font = '700 32px Arial';
    ctx.fillText(title, padding, 68);

    ctx.fillStyle = '#dbdee1';
    ctx.font = '24px Arial';
    let y = 126;
    for (const line of lines.slice(0, 18)) {
        ctx.fillText(line, padding, y);
        y += lineHeight;
    }

    return canvas.toBuffer('image/png');
}

async function sendEdwardLog(client, title, message, url = null, imageUrl = null) {
    const channelId = process.env.EDWARD_ADD_LOGS_CHANNEL_ID;
    if (!channelId) return;

    const channel = await client.channels.fetch(channelId).catch(() => null);
    if (!channel?.isTextBased()) return;

    const messageImageName = `edward_log_${Date.now()}.png`;
    const messageImage = new AttachmentBuilder(
        renderMessageImage(title, message),
        { name: messageImageName }
    );

    const container = new ContainerBuilder()
        .addTextDisplayComponents(new TextDisplayBuilder().setContent(`## ${title}`))
        .addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(new MediaGalleryItemBuilder().setURL(`attachment://${messageImageName}`))
        );

    if (imageUrl) {
        container.addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(new MediaGalleryItemBuilder().setURL(imageUrl))
        );
    }

    if (url) {
        container.addActionRowComponents(
            new ActionRowBuilder().addComponents(
                new ButtonBuilder()
                    .setLabel('Allez au site')
                    .setStyle(ButtonStyle.Link)
                    .setURL(url)
            )
        );
    }

    await channel.send({
        components: [container],
        files: [messageImage],
        flags: MessageFlags.IsComponentsV2,
    }).catch(error => console.warn('[Edward logs]', error.message));
}

module.exports = { sendEdwardLog };
