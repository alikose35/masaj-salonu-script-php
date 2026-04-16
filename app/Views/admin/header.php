<?php
/** @var array $settings */
/** @var string $pageKey */
/** @var string $pageTitle */
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> - Admin</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body class="admin-body admin-theme">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <span class="eyebrow">Dashboard</span>
        <h1><?= e(setting($settings, 'site_name')) ?></h1>
        <p><?= e($_SESSION['admin_name'] ?? '') ?></p>
        <nav class="admin-nav">
            <?php foreach (admin_nav_items() as $item): ?>
                <a class="<?= $pageKey === $item['key'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <a class="btn btn-secondary full-width" href="index.php">Siteye Don</a>
        <a class="btn btn-ghost full-width" href="logout.php">Cikis Yap</a>
    </aside>
    <main class="admin-main">
        <section class="admin-hero card">
            <div class="admin-hero-copy">
                <span class="eyebrow">Yonetim Merkezi</span>
                <h2><?= e($pageTitle) ?></h2>
                <p><?= e(setting($settings, 'site_name')) ?> icin moduler panel duzeni. Her yonetim alani ayri sayfada daha net ve hizli kullanilacak sekilde ayrildi.</p>
            </div>
            <div class="admin-hero-badges">
                <span>Sayfali panel</span>
                <span>SMTP e-posta</span>
                <span>SMS bildirim</span>
                <span>Ayrı randevu sayfasi</span>
            </div>
        </section>
