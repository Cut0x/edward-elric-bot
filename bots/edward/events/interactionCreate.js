const { queryOne } = require('../utils/db');

module.exports = {
    name: 'interactionCreate',

    async execute(interaction) {
        const bannedUser = await queryOne('SELECT is_banned FROM users WHERE id = ?', [interaction.user.id]).catch(() => null);
        if (bannedUser?.is_banned) {
            if (interaction.isAutocomplete()) {
                return interaction.respond([]).catch(() => {});
            }
            if (interaction.isRepliable()) {
                return interaction.reply({ content: 'Votre accès au bot est suspendu.', ephemeral: true }).catch(() => {});
            }
            return;
        }

        // Autocomplete
        if (interaction.isAutocomplete()) {
            const command = interaction.client.commands.get(interaction.commandName);
            if (command?.autocomplete) {
                try {
                    await command.autocomplete(interaction);
                } catch (error) {
                    console.error(`[Autocomplete] /${interaction.commandName} :`, error);
                }
            }
            return;
        }

        // Slash commands
        if (!interaction.isChatInputCommand()) return;

        const command = interaction.client.commands.get(interaction.commandName);
        if (!command) {
            console.warn(`[WARN] Commande inconnue : /${interaction.commandName}`);
            return;
        }

        try {
            await command.execute(interaction);
        } catch (error) {
            console.error(`[ERROR] /${interaction.commandName} :`, error);

            const errMsg = {
                content: '❌ Une erreur est survenue. Réessayez dans quelques instants.',
                ephemeral: true,
            };

            if (interaction.replied || interaction.deferred) {
                await interaction.followUp(errMsg).catch(() => {});
            } else {
                await interaction.reply(errMsg).catch(() => {});
            }
        }
    },
};
