<?php
define('PAGE_TITLE', 'Conditions d\'utilisation');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content" style="max-width:860px;">
    <div class="section-header mb-24">
        <div>
            <div class="section-title"><i class="bi bi-file-earmark-text-fill"></i> Conditions d'utilisation</div>
            <div class="section-meta">Dernière mise à jour : 23 avril 2026</div>
        </div>
    </div>

    <div class="panel" style="padding:24px;line-height:1.8;color:var(--text-3);">
        <h2 style="color:var(--text-1);font-size:18px;margin-bottom:10px;">Utilisation du service</h2>
        <p><?= h(getBotName()) ?> est un bot Discord non officiel de collection de cartes. En utilisant le bot ou le site, vous acceptez une utilisation normale, respectueuse et conforme aux règles de Discord ainsi qu'aux règles des serveurs où le bot est installé.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Compte et progression</h2>
        <p>Votre progression est liée à votre identifiant Discord. Les cartes, rolls, niveaux, XP et statistiques peuvent être modifiés, rééquilibrés ou réinitialisés si nécessaire pour corriger un bug, une exploitation abusive ou une maintenance.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Contenu et disponibilité</h2>
        <p>Le projet est fourni tel quel, sans garantie de disponibilité permanente. Les cartes, images, raretés, récompenses et fonctionnalités peuvent évoluer. Ce projet est non officiel et n'est pas affilié à Arakawa Hiromu, Square Enix, Bones ou tout ayant droit de Fullmetal Alchemist.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Abus</h2>
        <p>L'automatisation abusive, l'exploitation de bugs, le spam de commandes ou toute tentative d'altération du service peuvent entraîner une restriction d'accès au bot, au site ou à certaines fonctionnalités.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Contact</h2>
        <p>Pour une demande liée au service, contactez l'administrateur du bot ou le propriétaire du serveur Discord sur lequel vous l'utilisez.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
