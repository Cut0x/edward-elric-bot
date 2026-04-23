const path = require('path');
const fs = require('fs');
const fsPromises = require('fs/promises');
require('dotenv').config({ path: path.join(__dirname, '..', '..', '.env') });

const {
    ActionRowBuilder,
    AttachmentBuilder,
    ButtonBuilder,
    ButtonStyle,
    Client,
    ContainerBuilder,
    GatewayIntentBits,
    MediaGalleryBuilder,
    MediaGalleryItemBuilder,
    MessageFlags,
    PermissionFlagsBits,
    TextDisplayBuilder,
} = require('discord.js');
const { createCanvas } = require('@napi-rs/canvas');
const mysql = require('mysql2/promise');

const token = process.env.ALPHONSE_BOT_TOKEN;
if (!token) {
    console.error('[Alphonse] ALPHONSE_BOT_TOKEN est manquant.');
    process.exit(1);
}

const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    database: process.env.DB_NAME || 'edwardbot',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASS || '',
    waitForConnections: true,
    connectionLimit: 5,
    charset: 'utf8mb4',
    timezone: '+00:00',
});

const channelByType = {
    legendary_roll: process.env.ALPHONSE_CHANNEL_LEGENDARY_ROLL_ID,
    first_card: process.env.ALPHONSE_CHANNEL_FIRST_CARD_ID,
    collection_complete: process.env.ALPHONSE_CHANNEL_COLLECTION_COMPLETE_ID,
    community_post: process.env.ALPHONSE_CHANNEL_COMMUNITY_POST_ID,
    community_reply: process.env.ALPHONSE_CHANNEL_COMMUNITY_POST_ID,
    card_added: process.env.ALPHONSE_CHANNEL_ACTIVITY_ID,
    report: process.env.ALPHONSE_CHANNEL_REPORTS_ID,
};

const rarityWeights = {
    commune: 6000,
    peu_commune: 2500,
    rare: 1000,
    epique: 400,
    legendaire: 100,
};

function targetChannelId(type) {
    return channelByType[type] || process.env.ALPHONSE_CHANNEL_ACTIVITY_ID;
}

function iconFor(type) {
    return {
        legendary_roll: '[L]',
        first_card: '[1]',
        collection_complete: '[C]',
        community_post: '[P]',
        community_reply: '[R]',
        card_added: '[+]',
        report: '[!]',
    }[type] || '[i]';
}

function parseMetadata(event) {
    if (!event.metadata_json) return {};
    try {
        return JSON.parse(event.metadata_json);
    } catch {
        return {};
    }
}

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

function renderMessageImage(title, sections) {
    const width = 980;
    const padding = 42;
    const sectionGap = 24;
    const lineHeight = 28;
    const canvas = createCanvas(width, 420);
    const ctx = canvas.getContext('2d');
    ctx.font = '24px Arial';

    const prepared = sections.map(section => ({
        label: section.label,
        lines: wrapText(ctx, section.value || 'Aucun contenu.', width - padding * 2),
    }));
    const contentHeight = prepared.reduce((sum, section) => sum + 30 + section.lines.length * lineHeight + sectionGap, 0);
    const height = Math.max(300, 96 + contentHeight + padding);

    const finalCanvas = createCanvas(width, height);
    const finalCtx = finalCanvas.getContext('2d');
    finalCtx.fillStyle = '#1e1f22';
    finalCtx.fillRect(0, 0, width, height);
    finalCtx.fillStyle = '#2b2d31';
    finalCtx.roundRect(20, 20, width - 40, height - 40, 16);
    finalCtx.fill();
    finalCtx.strokeStyle = 'rgba(255,255,255,.12)';
    finalCtx.lineWidth = 2;
    finalCtx.stroke();

    finalCtx.fillStyle = '#f2f3f5';
    finalCtx.font = '700 32px Arial';
    finalCtx.fillText(title, padding, 68);

    let y = 116;
    for (const section of prepared) {
        finalCtx.fillStyle = '#e3aa3b';
        finalCtx.font = '700 18px Arial';
        finalCtx.fillText(section.label, padding, y);
        y += 30;

        finalCtx.fillStyle = '#dbdee1';
        finalCtx.font = '24px Arial';
        for (const line of section.lines.slice(0, 18)) {
            finalCtx.fillText(line, padding, y);
            y += lineHeight;
        }
        y += sectionGap;
    }

    return finalCanvas.toBuffer('image/png');
}

function localEventImagePath(event, metadata) {
    if (!metadata.image_file) return null;
    const rootDir = path.join(__dirname, '..', '..');
    if (event.type === 'community_post') {
        return path.join(rootDir, 'public', 'uploads', 'proposals', metadata.image_file);
    }
    if (event.type === 'card_added') {
        return path.join(rootDir, 'cards', metadata.image_file);
    }
    return null;
}

function addLinkButton(container, url, extraButtons = []) {
    if (!url && extraButtons.length === 0) return;
    const buttons = [...extraButtons];
    if (url) {
        buttons.push(
            new ButtonBuilder()
                .setLabel('Allez au site')
                .setStyle(ButtonStyle.Link)
                .setURL(url)
        );
    }
    container.addActionRowComponents(new ActionRowBuilder().addComponents(...buttons));
}

async function eventPayload(event) {
    const metadata = parseMetadata(event);
    const lines = [
        `## ${iconFor(event.type)} ${event.title}`,
    ];
    if (event.type === 'report') {
        lines.unshift('@everyone');
        lines.push('', `Cible : ${metadata.target_type || 'inconnue'} #${metadata.target_id || '?'}`);
    }

    let sections = [{ label: 'Message', value: event.message }];
    if (event.type === 'report') {
        sections = [
            { label: 'Raison', value: metadata.reason || 'Aucune raison fournie.' },
            { label: 'Contenu signalé', value: metadata.reported_content || event.message },
        ];
    } else if (event.type === 'community_post') {
        sections = [
            { label: 'Message', value: event.message },
            { label: 'Description', value: metadata.description || 'Aucune description.' },
        ];
    } else if (event.type === 'community_reply') {
        sections = [
            { label: 'Message', value: event.message },
            { label: 'Réponse', value: metadata.reply_content || 'Aucun contenu.' },
        ];
    } else if (event.type === 'card_added') {
        sections = [
            { label: 'Message', value: event.message },
            { label: 'Description', value: metadata.description || 'Aucune description.' },
        ];
    }

    const imageName = `event_${event.id}.png`;
    const image = new AttachmentBuilder(
        renderMessageImage(event.title, sections),
        { name: imageName }
    );

    const container = new ContainerBuilder()
        .addTextDisplayComponents(new TextDisplayBuilder().setContent(lines.join('\n')))
        .addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(new MediaGalleryItemBuilder().setURL(`attachment://${imageName}`))
        );

    const files = [image];
    const localImage = localEventImagePath(event, metadata);
    if (localImage && fs.existsSync(localImage)) {
        const attachmentName = `${event.type}_${event.id}${path.extname(localImage) || '.png'}`;
        files.push(new AttachmentBuilder(localImage, { name: attachmentName }));
        container.addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(new MediaGalleryItemBuilder().setURL(`attachment://${attachmentName}`))
        );
    } else if (metadata.image_url) {
        container.addMediaGalleryComponents(
            new MediaGalleryBuilder().addItems(new MediaGalleryItemBuilder().setURL(metadata.image_url))
        );
    }

    if (event.type === 'community_post' && metadata.proposal_id) {
        addLinkButton(container, event.url, [
                new ButtonBuilder()
                    .setCustomId(`proposal:add:${event.id}`)
                    .setLabel('Ajouter en brouillon')
                    .setStyle(ButtonStyle.Primary)
        ]);
    } else if (event.type !== 'report') {
        addLinkButton(container, event.url);
    }

    if (event.type === 'report') {
        addLinkButton(container, event.url, [
                new ButtonBuilder()
                    .setCustomId(`report:delete:${event.id}`)
                    .setLabel('Supprimer')
                    .setStyle(ButtonStyle.Danger),
                new ButtonBuilder()
                    .setCustomId(`report:ban:${event.id}`)
                    .setLabel('Supprimer et bannir')
                    .setStyle(ButtonStyle.Danger),
                new ButtonBuilder()
                    .setCustomId(`report:ignore:${event.id}`)
                    .setLabel('Ignorer')
                    .setStyle(ButtonStyle.Secondary)
        ]);
    }

    return {
        allowedMentions: event.type === 'report' ? { parse: ['everyone'] } : undefined,
        components: [container],
        files,
        flags: MessageFlags.IsComponentsV2,
    };
}

async function fetchPendingEvents() {
    const [rows] = await pool.execute(
        'SELECT * FROM activity_events WHERE delivered_at IS NULL ORDER BY created_at ASC LIMIT 10'
    );
    return rows;
}

async function markDelivered(id) {
    await pool.execute('UPDATE activity_events SET delivered_at = NOW() WHERE id = ?', [id]);
}

async function fetchEvent(id) {
    const [rows] = await pool.execute('SELECT * FROM activity_events WHERE id = ?', [id]);
    return rows[0] || null;
}

function userCanModerate(interaction) {
    return Boolean(interaction.memberPermissions?.has(PermissionFlagsBits.Administrator));
}

async function deleteReportedTarget(metadata) {
    const targetId = Number(metadata.target_id || 0);
    if (!targetId) return false;

    if (metadata.target_type === 'proposal') {
        await pool.execute('DELETE FROM card_proposals WHERE id = ?', [targetId]);
        return true;
    }

    if (metadata.target_type === 'reply') {
        await pool.execute('DELETE FROM card_proposal_replies WHERE id = ?', [targetId]);
        return true;
    }

    return false;
}

async function createDraftCardFromProposal(proposalId) {
    const [rows] = await pool.execute('SELECT * FROM card_proposals WHERE id = ? AND status = "open"', [proposalId]);
    const proposal = rows[0];
    if (!proposal) return null;

    const rarity = Object.hasOwn(rarityWeights, proposal.rarity) ? proposal.rarity : 'commune';
    let imageFile = '';

    if (proposal.image_file) {
        const rootDir = path.join(__dirname, '..', '..');
        const source = path.join(rootDir, 'public', 'uploads', 'proposals', proposal.image_file);
        const extension = path.extname(proposal.image_file) || '.png';
        imageFile = `draft_proposal_${proposal.id}_${Date.now()}${extension}`;
        const destinationDir = path.join(rootDir, 'cards');
        const destination = path.join(destinationDir, imageFile);

        await fsPromises.mkdir(destinationDir, { recursive: true });
        await fsPromises.copyFile(source, destination).catch(() => {
            imageFile = '';
        });
    }

    const [result] = await pool.execute(
        `INSERT INTO cards
            (name, character_name, description, serie, rarity, rarity_weight, image_file, author_id, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)`,
        [
            proposal.name,
            proposal.character_name || proposal.name,
            proposal.description || null,
            proposal.serie || 'Fullmetal Alchemist',
            rarity,
            rarityWeights[rarity],
            imageFile,
            proposal.user_id || null,
        ]
    );

    await pool.execute('UPDATE card_proposals SET status = "accepted" WHERE id = ?', [proposal.id]);
    await pool.execute(
        `INSERT INTO activity_events (type, user_id, title, message, url, metadata_json)
         VALUES (?, ?, ?, ?, ?, ?)`,
        [
            'card_added',
            proposal.user_id ? String(proposal.user_id) : null,
            'Carte ajoutée en brouillon',
            `La proposition ${proposal.name} a été transformée en carte non publique.`,
            (process.env.APP_URL || '').replace(/\/$/, '') ? `${(process.env.APP_URL || '').replace(/\/$/, '')}/admin/edit.php?id=${result.insertId}` : null,
            JSON.stringify({
                card_id: result.insertId,
                image_file: imageFile,
                description: proposal.description || '',
                is_active: 0,
            }),
        ]
    );
    return result.insertId;
}

async function moderationContainer(title, message, url = null) {
    const lines = [`## ${title}`, message];
    const container = new ContainerBuilder()
        .addTextDisplayComponents(new TextDisplayBuilder().setContent(lines.join('\n')));
    addLinkButton(container, url);
    return container;
}

async function handleReportButton(interaction) {
    if (!userCanModerate(interaction)) {
        return interaction.reply({ content: 'Action réservée aux administrateurs du serveur.', ephemeral: true });
    }

    const [, action, eventIdRaw] = interaction.customId.split(':');
    const event = await fetchEvent(Number(eventIdRaw));
    if (!event || event.type !== 'report') {
        return interaction.reply({ content: 'Report introuvable.', ephemeral: true });
    }

    const metadata = parseMetadata(event);
    let title = 'Report ignoré';
    let message = `Action effectuée par ${interaction.user.tag}.`;

    if (action === 'delete' || action === 'ban') {
        await deleteReportedTarget(metadata);
        title = action === 'ban' ? 'Contenu supprimé et utilisateur banni' : 'Contenu supprimé';

        if (action === 'ban' && metadata.reported_user_id) {
            await pool.execute('UPDATE users SET is_banned = 1 WHERE id = ?', [String(metadata.reported_user_id)]);
        }

        message = `Cible ${metadata.target_type || 'inconnue'} #${metadata.target_id || '?'} traitée par ${interaction.user.tag}.`;
    }

    await interaction.update({
        components: [await moderationContainer(title, message, event.url)],
        flags: MessageFlags.IsComponentsV2,
    });
}

async function handleProposalButton(interaction) {
    if (!userCanModerate(interaction)) {
        return interaction.reply({ content: 'Action réservée aux administrateurs du serveur.', ephemeral: true });
    }

    const [, action, eventIdRaw] = interaction.customId.split(':');
    if (action !== 'add') return;

    const event = await fetchEvent(Number(eventIdRaw));
    if (!event || event.type !== 'community_post') {
        return interaction.reply({ content: 'Proposition introuvable.', ephemeral: true });
    }

    const metadata = parseMetadata(event);
    const proposalId = Number(metadata.proposal_id || 0);
    const cardId = proposalId ? await createDraftCardFromProposal(proposalId) : null;

    if (!cardId) {
        return interaction.reply({ content: 'Cette proposition est déjà clôturée ou introuvable.', ephemeral: true });
    }

    const appUrl = (process.env.APP_URL || '').replace(/\/$/, '');
    const cardUrl = appUrl ? `${appUrl}/admin/edit.php?id=${cardId}` : event.url;
    await interaction.update({
        components: [
            await moderationContainer(
                'Carte ajoutée en brouillon',
                `La proposition a été clôturée et transformée en carte non publique par ${interaction.user.tag}.`,
                cardUrl
            ),
        ],
        flags: MessageFlags.IsComponentsV2,
    });
}

async function publishEvent(client, event) {
    const channelId = targetChannelId(event.type);
    if (!channelId) {
        console.warn(`[Alphonse] Aucun salon configuré pour ${event.type}.`);
        return false;
    }

    const channel = await client.channels.fetch(channelId).catch(() => null);
    if (!channel || !channel.isTextBased()) {
        console.warn(`[Alphonse] Salon introuvable ou non textuel : ${channelId}`);
        return false;
    }

    await channel.send(await eventPayload(event));
    return true;
}

async function poll(client) {
    const events = await fetchPendingEvents();
    for (const event of events) {
        try {
            const sent = await publishEvent(client, event);
            if (sent) await markDelivered(event.id);
        } catch (error) {
            console.error(`[Alphonse] Erreur évènement #${event.id}:`, error.message);
        }
    }
}

const client = new Client({ intents: [GatewayIntentBits.Guilds] });

client.on('interactionCreate', async interaction => {
    if (!interaction.isButton()) return;

    try {
        if (interaction.customId.startsWith('report:')) {
            await handleReportButton(interaction);
        } else if (interaction.customId.startsWith('proposal:')) {
            await handleProposalButton(interaction);
        }
    } catch (error) {
        console.error('[Alphonse] Erreur interaction bouton :', error.message);
        if (!interaction.replied && !interaction.deferred) {
            await interaction.reply({ content: 'Action impossible pour le moment.', ephemeral: true }).catch(() => {});
        }
    }
});

client.once('ready', async () => {
    console.log(`[Alphonse] Connecté en tant que ${client.user.tag}`);
    if (process.env.SUPPORT_GUILD_ID) {
        const guild = await client.guilds.fetch(process.env.SUPPORT_GUILD_ID).catch(() => null);
        if (!guild) console.warn('[Alphonse] SUPPORT_GUILD_ID est configuré mais le serveur est introuvable.');
    }

    const interval = parseInt(process.env.ALPHONSE_ACTIVITY_POLL_INTERVAL_MS || '15000', 10);
    await poll(client);
    setInterval(() => poll(client), Math.max(5000, interval));
});

client.login(token).catch(error => {
    console.error('[Alphonse] Connexion impossible :', error.message);
    process.exit(1);
});
