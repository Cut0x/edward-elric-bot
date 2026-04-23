const { sendEdwardLog } = require('../utils/logs');

module.exports = {
    name: 'guildCreate',

    async execute(guild) {
        await sendEdwardLog(
            guild.client,
            'Edward ajouté à un serveur',
            `Serveur : **${guild.name}**\nID : \`${guild.id}\`\nMembres : **${guild.memberCount ?? 'inconnu'}**`
        );
    },
};
