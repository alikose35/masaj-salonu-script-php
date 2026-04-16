<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (app_installed()) {
    redirect('index.php');
}

$checks = installation_checks();
$requiredOk = all_required_checks_passed($checks);
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post = $_POST;
    remember_old($post);

    if (!$requiredOk) {
        $errors[] = 'Required compatibility checks must pass before setup can continue.';
    }

    if (!verify_csrf($post['_csrf'] ?? null)) {
        $errors[] = 'Security validation failed.';
    }

    $dbHost = trim($post['db_host'] ?? '');
    $dbPort = trim($post['db_port'] ?? '3306');
    $dbName = trim($post['db_name'] ?? '');
    $dbUser = trim($post['db_user'] ?? '');
    $dbPass = trim($post['db_pass'] ?? '');
    $siteName = trim($post['site_name'] ?? '');
    $siteLogo = trim($post['site_logo'] ?? '');
    $siteUrl = trim($post['site_url'] ?? base_url());
    $seoTitle = trim($post['seo_title'] ?? '');
    $seoDescription = trim($post['seo_description'] ?? '');
    $seoKeywords = trim($post['seo_keywords'] ?? '');
    $currency = trim($post['currency'] ?? 'TRY');
    $timezone = trim($post['timezone'] ?? 'Europe/Istanbul');
    $adminName = trim($post['admin_name'] ?? '');
    $adminEmail = trim($post['admin_email'] ?? '');
    $adminPassword = trim($post['admin_password'] ?? '');

    $mandatoryFields = [
        'Database host' => $dbHost,
        'Database name' => $dbName,
        'Database username' => $dbUser,
        'Site name' => $siteName,
        'Site URL' => $siteUrl,
        'SEO title' => $seoTitle,
        'SEO description' => $seoDescription,
        'Currency' => $currency,
        'Timezone' => $timezone,
        'Admin name' => $adminName,
        'Admin email' => $adminEmail,
        'Admin password' => $adminPassword,
    ];

    foreach ($mandatoryFields as $label => $value) {
        if ($value === '') {
            $errors[] = $label . ' is required.';
        }
    }

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid admin email address.';
    }

    if (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Site URL must be a full URL.';
    }

    if (strlen($adminPassword) < 8) {
        $errors[] = 'Admin password must be at least 8 characters.';
    }

    if (!$errors) {
        try {
            $tempDb = new Database([
                'host' => $dbHost,
                'port' => (int) $dbPort,
                'database' => $dbName,
                'username' => $dbUser,
                'password' => $dbPass,
                'charset' => 'utf8mb4',
            ]);

            $tempDb->ensureSchema();
            $pdo = $tempDb->pdo();

            $settings = array_merge($tempDb->defaultSettings(), [
                'site_name' => $siteName,
                'text_logo' => $siteName,
                'site_logo' => $siteLogo,
                'site_url' => $siteUrl,
                'seo_title' => $seoTitle,
                'seo_description' => $seoDescription,
                'seo_keywords' => $seoKeywords,
                'og_title' => $seoTitle,
                'og_description' => $seoDescription,
                'currency' => $currency,
                'timezone' => $timezone,
                'contact_email' => $adminEmail,
            ]);

            $tempDb->saveSettings($settings);

            $pdo->exec('DELETE FROM admins');
            $adminInsert = $pdo->prepare('INSERT INTO admins (name, email, password, created_at) VALUES (:name, :email, :password, NOW())');
            $adminInsert->execute([
                'name' => $adminName,
                'email' => $adminEmail,
                'password' => password_hash($adminPassword, PASSWORD_DEFAULT),
            ]);

            if ((int) $pdo->query('SELECT COUNT(*) FROM services')->fetchColumn() === 0) {
                $pdo->exec(
                    "INSERT INTO services (name, slug, duration, price, description, image_url, is_active, sort_order) VALUES
                    ('Aromaterapi Masaji', 'aromaterapi-masaji', 60, 1500.00, 'Ucurucu yaglar ile rahatlatan premium terapi.', 'https://images.unsplash.com/photo-1519822473471-5b7d3b6f2e46?auto=format&fit=crop&w=900&q=80', 1, 1),
                    ('Derin Doku Masaji', 'derin-doku-masaji', 75, 1800.00, 'Kas gerginligi ve yogun toparlanma odakli uygulama.', 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=900&q=80', 1, 2),
                    ('Sicak Tas Ritueli', 'sicak-tas-ritueli', 90, 2200.00, 'Butik spa deneyimini one cikan luks bakim.', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=900&q=80', 1, 3)"
                );
            }

            if ((int) $pdo->query('SELECT COUNT(*) FROM therapists')->fetchColumn() === 0) {
                $pdo->exec(
                    "INSERT INTO therapists (name, title, bio, photo_url, is_active) VALUES
                    ('Elif Kaya', 'Kidemli Spa Terapisti', 'Rahatlatici terapi ve klasik masaj alaninda 8 yillik deneyim.', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=800&q=80', 1),
                    ('Mert Demir', 'Derin Doku Uzmani', 'Kas toparlanmasi ve spor terapisi odakli seanslar sunar.', 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=80', 1),
                    ('Selin Aras', 'Aromaterapi Uzmani', 'Koku ritueli ve butunsel gevseme seanslari yonetir.', 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&w=800&q=80', 1)"
                );
            }

            $configContent = "<?php\n\nreturn " . var_export([
                'database' => [
                    'host' => $dbHost,
                    'port' => (int) $dbPort,
                    'database' => $dbName,
                    'username' => $dbUser,
                    'password' => $dbPass,
                    'charset' => 'utf8mb4',
                ],
            ], true) . ";\n";

            if (file_put_contents(STORAGE_PATH . '/config.php', $configContent) === false) {
                throw new RuntimeException('Configuration file could not be written.');
            }

            if (file_put_contents(STORAGE_PATH . '/installed.lock', date('c')) === false) {
                throw new RuntimeException('Install lock file could not be written.');
            }

            clear_old();
            $success = true;
        } catch (Throwable $exception) {
            $errors[] = 'Setup failed: ' . $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kurulum Sihirbazi</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body class="installer-body">
<div class="installer-shell">
    <div class="installer-intro">
        <span class="eyebrow">Masaj Salonu Scripti</span>
        <h1>Profesyonel kurulum sihirbazi</h1>
        <p>Uyumluluk kontrolleri, marka ayarlari ve yonetici hesabi tek akista hazirlanir.</p>
    </div>

    <?php if ($success): ?>
        <div class="card success-box">
            <h2>Kurulum tamamlandi</h2>
            <p>Script yeni admin paneliyle kullanima hazir. Siteyi ve yonetim panelini hemen acabilirsiniz.</p>
            <div class="button-row">
                <a class="btn btn-primary" href="index.php">Siteyi Goruntule</a>
                <a class="btn btn-secondary" href="admin.php">Admin Paneli</a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="card alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="installer-grid">
            <div class="card">
                <h2>Uyumluluk kontrolleri</h2>
                <h3>Zorunlu</h3>
                <ul class="check-list">
                    <?php foreach ($checks['required'] as $item): ?>
                        <li class="<?= $item['status'] ? 'ok' : 'fail' ?>">
                            <strong><?= e($item['title']) ?></strong>
                            <span><?= e($item['details']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h3>Onerilenler</h3>
                <ul class="check-list">
                    <?php foreach ($checks['recommended'] as $item): ?>
                        <li class="<?= $item['status'] ? 'ok' : 'warn' ?>">
                            <strong><?= e($item['title']) ?></strong>
                            <span><?= e($item['details']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <p class="helper-text">Zorunlu alanlar eksikse kurulum ilerlemez. Onerilenler eksikse kurulum devam eder ama ilgili deneyim kisitli kalabilir.</p>
            </div>

            <form method="post" class="card installer-form">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

                <h2>1. Veritabani bilgileri</h2>
                <div class="form-grid">
                    <label>Host<input type="text" name="db_host" value="<?= old('db_host', 'db') ?>" required></label>
                    <label>Port<input type="number" name="db_port" value="<?= old('db_port', '3306') ?>" required></label>
                    <label>Veritabani adi<input type="text" name="db_name" value="<?= old('db_name', 'massage_app') ?>" required></label>
                    <label>Kullanici adi<input type="text" name="db_user" value="<?= old('db_user', 'massage_user') ?>" required></label>
                    <label>Parola<input type="password" name="db_pass" value="<?= old('db_pass', 'massage_pass') ?>"></label>
                </div>

                <h2>2. Site ve SEO bilgileri</h2>
                <div class="form-grid">
                    <label>Site adi<input type="text" name="site_name" value="<?= old('site_name', 'Serenity Massage Lounge') ?>" required></label>
                    <label>Logo URL<input type="text" name="site_logo" value="<?= old('site_logo') ?>" placeholder="https://..."></label>
                    <label>Site URL<input type="url" name="site_url" value="<?= old('site_url', base_url()) ?>" required></label>
                    <label>SEO baslik<input type="text" name="seo_title" value="<?= old('seo_title', 'Masaj Salonu ve Spa Randevu Sistemi') ?>" required></label>
                    <label>SEO aciklama<textarea name="seo_description" required><?= old('seo_description', 'Masaj salonunuz icin profesyonel, mobil uyumlu ve yonetilebilir rezervasyon altyapisi.') ?></textarea></label>
                    <label>SEO anahtar kelimeler<textarea name="seo_keywords"><?= old('seo_keywords', 'masaj salonu, spa rezervasyon, wellness, online randevu') ?></textarea></label>
                    <label>Para birimi<input type="text" name="currency" value="<?= old('currency', 'TRY') ?>" required></label>
                    <label>Saat dilimi<input type="text" name="timezone" value="<?= old('timezone', 'Europe/Istanbul') ?>" required></label>
                </div>

                <h2>3. Yonetici hesabi</h2>
                <div class="form-grid">
                    <label>Yonetici adi<input type="text" name="admin_name" value="<?= old('admin_name', 'Site Yonetici') ?>" required></label>
                    <label>Yonetici e-posta<input type="email" name="admin_email" value="<?= old('admin_email', 'admin@example.com') ?>" required></label>
                    <label>Yonetici sifresi<input type="password" name="admin_password" required></label>
                </div>

                <div class="submit-box">
                    <button class="btn btn-primary" type="submit" <?= $requiredOk ? '' : 'disabled' ?>>Kurulumu Tamamla</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
