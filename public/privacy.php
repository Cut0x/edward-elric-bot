<?php
define('PAGE_TITLE', 'Politique de confidentialité');
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container page-content" style="max-width:860px;">
    <div class="section-header mb-24">
        <div>
            <div class="section-title"><i class="bi bi-shield-lock-fill"></i> Politique de confidentialité</div>
            <div class="section-meta">Dernière mise à jour : 23 avril 2026</div>
        </div>
    </div>

    <div class="panel" style="padding:24px;line-height:1.8;color:var(--text-3);">
        <h2 style="color:var(--text-1);font-size:18px;margin-bottom:10px;">Données collectées</h2>
        <p>Le bot et le site enregistrent les données nécessaires au fonctionnement du jeu : identifiant Discord, nom d'utilisateur, nom global, avatar, cartes obtenues, rolls restants, historique des rolls, XP, niveau et statistiques de collection.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Utilisation des données</h2>
        <p>Ces données servent à afficher votre collection, votre progression, les classements, votre profil et les résultats des commandes Discord. Elles ne sont pas vendues et ne sont pas utilisées à des fins publicitaires.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Connexion Discord</h2>
        <p>La connexion au site utilise OAuth2 Discord pour vous identifier. Le site ne reçoit pas votre mot de passe Discord. Les informations de session sont utilisées uniquement pour maintenir votre connexion au site.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Conservation</h2>
        <p>Les données sont conservées tant que le service existe ou jusqu'à suppression manuelle par l'administrateur. Vous pouvez demander la suppression de vos données de jeu auprès de l'administrateur du bot.</p>

        <h2 style="color:var(--text-1);font-size:18px;margin:22px 0 10px;">Sécurité</h2>
        <p>Les données sont stockées dans la base de données configurée par l'administrateur du projet. L'accès administratif doit rester limité aux personnes autorisées.</p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
