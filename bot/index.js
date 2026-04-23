require('dotenv').config({ path: require('path').join(__dirname, '..', '.env') });

const { Client, GatewayIntentBits, Collection } = require('discord.js');
const fs   = require('fs');
const path = require('path');

const client = new Client({
    intents: [GatewayIntentBits.Guilds],
});

client.commands = new Collection();

// Charger les commandes
const commandsPath = path.join(__dirname, 'commands');
for (const file of fs.readdirSync(commandsPath).filter(f => f.endsWith('.js'))) {
    const command = require(path.join(commandsPath, file));
    if (command.data && command.execute) {
        client.commands.set(command.data.name, command);
        console.log(`[CMD] Commande chargée : /${command.data.name}`);
    }
}

// Charger les événements
const eventsPath = path.join(__dirname, 'events');
for (const file of fs.readdirSync(eventsPath).filter(f => f.endsWith('.js'))) {
    const event = require(path.join(eventsPath, file));
    if (event.once) {
        client.once(event.name, (...args) => event.execute(...args));
    } else {
        client.on(event.name, (...args) => event.execute(...args));
    }
    console.log(`[EVT] Événement enregistré : ${event.name}`);
}

client.login(process.env.DISCORD_BOT_TOKEN)
    .catch(err => {
        console.error('[ERREUR] Impossible de se connecter à Discord :', err.message);
        process.exit(1);
    });
