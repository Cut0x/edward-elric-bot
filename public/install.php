<?php
define('PAGE_TITLE', 'Installation');
require_once __DIR__ . '/includes/config.php';

$steps  = [];
$errors = [];

function trySQL(PDO $pdo, string $sql): bool {
    try { $pdo->exec($sql); return true; }
    catch (Exception $e) { return false; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $dsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        trySQL($pdo, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        $steps[] = ['ok', 'Base de données créée/connectée'];

        $sql = file_get_contents(dirname(__DIR__) . '/database.sql');
        // Remove database creation lines since we already did that
        $sql = preg_replace('/CREATE DATABASE.*?;/si', '', $sql);
        $sql = preg_replace('/USE `[^`]+`;/i', '', $sql);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $stmt) {
            if (empty($stmt)) continue;
            $pdo->exec($stmt);
        }
        $steps[] = ['ok', 'Tables créées avec succès'];

        // Ensure cards directory exists
        if (!is_dir(CARDS_DIR)) {
            mkdir(CARDS_DIR, 0755, true);
            $steps[] = ['ok', 'Dossier /cards/ créé'];
        } else {
            $steps[] = ['ok', 'Dossier /cards/ existe déjà'];
        }

        $steps[] = ['success', 'Installation terminée ! Vous pouvez supprimer ce fichier.'];
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding:3rem 0 5rem;max-width:650px;">
    <div class="panel panel-gold">
        <div style="text-align:center;margin-bottom:2rem;">
            <i class="bi bi-gear-fill" style="font-size:3rem;color:var(--gold);"></i>
            <h1 style="font-family:'Cinzel',serif;font-size:1.8rem;color:var(--gold-bright);margin-top:.8rem;">Installation</h1>
            <p style="color:var(--text-muted);">Configuration de la base de données Edward Elric Bot</p>
        </div>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $e): ?>
                <div class="alert-alch alert-error" style="margin-bottom:1rem;">
                    <i class="bi bi-x-circle-fill"></i> <?= h($e) ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php foreach ($steps as [$type, $msg]): ?>
            <div class="alert-alch <?= $type === 'success' ? 'alert-success' : 'alert-info' ?>" style="margin-bottom:.6rem;">
                <i class="bi <?= $type === 'success' ? 'bi-check-circle-fill' : 'bi-check2' ?>"></i>
                <?= h($msg) ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($steps)): ?>
        <div class="alert-alch alert-info" style="margin-bottom:2rem;">
            <i class="bi bi-info-circle-fill"></i>
            Assurez-vous d'avoir rempli votre fichier <code>.env</code> correctement avant de continuer.
        </div>

        <div style="background:var(--bg-secondary);border-radius:6px;padding:1rem;margin-bottom:2rem;font-size:.88rem;">
            <div style="font-family:'Cinzel',serif;font-size:.75rem;color:var(--text-muted);margin-bottom:.8rem;letter-spacing:.1em;text-transform:uppercase;">Configuration actuelle</div>
            <div style="display:grid;grid-template-columns:auto 1fr;gap:.3rem .8rem;color:var(--text-secondary);">
                <span style="color:var(--text-dim);">Host</span>      <span><?= h(DB_HOST) ?></span>
                <span style="color:var(--text-dim);">Base</span>      <span><?= h(DB_NAME) ?></span>
                <span style="color:var(--text-dim);">User</span>      <span><?= h(DB_USER) ?></span>
                <span style="color:var(--text-dim);">Dossier cards</span> <span><?= h(CARDS_DIR) ?></span>
            </div>
        </div>

        <form method="POST">
            <button type="submit" class="btn-alch btn-alch-gold" style="width:100%;">
                <i class="bi bi-play-fill"></i> Lancer l'installation
            </button>
        </form>
        <?php else: ?>
            <?php if (empty($errors)): ?>
            <div style="margin-top:1.5rem;display:flex;gap:1rem;flex-wrap:wrap;">
                <a href="<?= APP_URL ?>/index.php" class="btn-alch btn-alch-gold" style="flex:1;justify-content:center;">
                    <i class="bi bi-house-fill"></i> Accueil
                </a>
                <a href="<?= APP_URL ?>/admin/index.php" class="btn-alch btn-alch-outline" style="flex:1;justify-content:center;">
                    <i class="bi bi-shield-fill"></i> Panel Admin
                </a>
            </div>
            <div class="alert-alch alert-error" style="margin-top:1rem;">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <strong>Sécurité :</strong> Supprimez ce fichier <code>install.php</code> après installation !
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
