module.exports = {
    name: 'interactionCreate',

    async execute(interaction) {
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
