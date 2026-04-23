const { ActivityType } = require('discord.js');

module.exports = {
    name:  'ready',
    once:  true,

    async execute(client) {
        console.log(`\n✅ Bot connecté en tant que : ${client.user.tag}`);
        console.log(`   Présent sur ${client.guilds.cache.size} serveur(s)`);
        console.log(`   ${client.commands.size} commande(s) chargée(s)\n`);

        const activities = [
            { name: '/roll • Collectez vos cartes !', type: ActivityType.Playing },
            { name: 'Fullmetal Alchemist', type: ActivityType.Watching },
            { name: '/aide pour les commandes', type: ActivityType.Listening },
        ];

        let i = 0;
        const setActivity = () => {
            client.user.setActivity(activities[i % activities.length]);
            i++;
        };

        setActivity();
        setInterval(setActivity, 30_000);
    },
};
