require('dotenv').config({ path: require('path').join(__dirname, '..', '..', '.env') });

const { REST, Routes } = require('discord.js');
const fs   = require('fs');
const path = require('path');

const commands = [];
const commandsPath = path.join(__dirname, 'commands');

for (const file of fs.readdirSync(commandsPath).filter(f => f.endsWith('.js'))) {
    const command = require(path.join(commandsPath, file));
    if (command.data) {
        commands.push(command.data.toJSON());
        console.log(`[+] Commande prête : /${command.data.name}`);
    }
}

const edwardToken = process.env.EDWARD_BOT_TOKEN || process.env.DISCORD_BOT_TOKEN;
const edwardClientId = process.env.EDWARD_CLIENT_ID || process.env.DISCORD_CLIENT_ID;
const rest = new REST().setToken(edwardToken);

(async () => {
    try {
        const forceGlobal = process.argv.includes('--global') || process.env.DISCORD_DEPLOY_GLOBAL === '1';
        const guildId = forceGlobal ? null : process.env.EDWARD_TEST_GUILD_ID || process.env.DISCORD_TEST_GUILD_ID;

        if (guildId) {
            // Déploiement dans un serveur spécifique (instantané, pour les tests)
            console.log(`\nDéploiement de ${commands.length} commande(s) dans le serveur ${guildId}...`);
            await rest.put(
                Routes.applicationGuildCommands(edwardClientId, guildId),
                { body: commands }
            );
            console.log('✅ Commandes déployées dans le serveur de test !');
        } else {
            // Déploiement global (peut prendre jusqu'à 1h)
            console.log(`\nDéploiement global de ${commands.length} commande(s)...`);
            await rest.put(
                Routes.applicationCommands(edwardClientId),
                { body: commands }
            );
            console.log('✅ Commandes déployées globalement ! (Délai jusqu\'à 1h)');
        }
    } catch (error) {
        console.error('❌ Erreur lors du déploiement :', error);
    }
})();
