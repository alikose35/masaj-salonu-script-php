<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (!app_installed()) {
    redirect('install.php');
}

$database = db();
$settings = admin_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        flash('error', 'Guvenlik dogrulamasi basarisiz.');
        redirect('admin.php');
    }

    if (!Auth::attempt(trim($_POST['email'] ?? ''), trim($_POST['password'] ?? ''))) {
        flash('error', 'E-posta veya sifre hatali.');
        redirect('admin.php');
    }

    flash('success', 'Yonetim paneline hos geldiniz.');
    redirect('admin.php');
}

$error = flash('error');
$success = flash('success');

if (!Auth::check()):
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Girisi</title>
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body class="admin-login-body">
<div class="login-shell card">
    <span class="eyebrow">Yonetim Paneli</span>
    <h1><?= e(setting($settings, 'site_name')) ?></h1>
    <p>Randevulari, icerikleri, SMTP e-posta ve SMS ayarlarini tek panelden yonetin.</p>

    <?php if ($error): ?><div class="alert alert-danger"><p><?= e($error) ?></p></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><p><?= e($success) ?></p></div><?php endif; ?>

    <form method="post" class="installer-form">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <div class="form-grid">
            <label>E-posta<input type="email" name="email" required></label>
            <label>Sifre<input type="password" name="password" required></label>
        </div>
        <button type="submit" name="login_submit" value="1" class="btn btn-primary">Giris Yap</button>
    </form>
</div>
</body>
</html>
<?php
exit;
endif;

$page = admin_page_key();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        flash('error', 'Guvenlik dogrulamasi basarisiz.');
        redirect($_SERVER['REQUEST_URI'] ?? 'admin.php');
    }

    try {
        switch ($_POST['admin_action']) {
            case 'save_settings':
                $database->saveSettings([
                    'site_name' => trim($_POST['site_name'] ?? ''),
                    'site_url' => trim($_POST['site_url'] ?? ''),
                    'text_logo' => trim($_POST['text_logo'] ?? ''),
                    'logo_type' => trim($_POST['logo_type'] ?? 'text'),
                    'site_logo' => trim($_POST['site_logo'] ?? ''),
                    'theme_mode' => trim($_POST['theme_mode'] ?? 'serene'),
                    'color_scheme' => trim($_POST['color_scheme'] ?? 'sand'),
                    'currency' => trim($_POST['currency'] ?? 'TRY'),
                    'timezone' => trim($_POST['timezone'] ?? 'Europe/Istanbul'),
                    'site_tagline' => trim($_POST['site_tagline'] ?? ''),
                    'hero_badge' => trim($_POST['hero_badge'] ?? ''),
                    'hero_title' => trim($_POST['hero_title'] ?? ''),
                    'hero_text' => trim($_POST['hero_text'] ?? ''),
                    'hero_metric_label' => trim($_POST['hero_metric_label'] ?? ''),
                    'hero_metric_value' => trim($_POST['hero_metric_value'] ?? ''),
                    'feature_1' => trim($_POST['feature_1'] ?? ''),
                    'feature_2' => trim($_POST['feature_2'] ?? ''),
                    'feature_3' => trim($_POST['feature_3'] ?? ''),
                    'services_title' => trim($_POST['services_title'] ?? ''),
                    'services_text' => trim($_POST['services_text'] ?? ''),
                    'therapists_title' => trim($_POST['therapists_title'] ?? ''),
                    'therapists_text' => trim($_POST['therapists_text'] ?? ''),
                    'booking_title' => trim($_POST['booking_title'] ?? ''),
                    'booking_text' => trim($_POST['booking_text'] ?? ''),
                    'about_title' => trim($_POST['about_title'] ?? ''),
                    'about_text' => trim($_POST['about_text'] ?? ''),
                    'contact_phone' => trim($_POST['contact_phone'] ?? ''),
                    'contact_email' => trim($_POST['contact_email'] ?? ''),
                    'contact_address' => trim($_POST['contact_address'] ?? ''),
                    'google_maps_embed_url' => trim($_POST['google_maps_embed_url'] ?? ''),
                    'map_section_title' => trim($_POST['map_section_title'] ?? ''),
                    'map_section_text' => trim($_POST['map_section_text'] ?? ''),
                    'footer_note' => trim($_POST['footer_note'] ?? ''),
                    'social_instagram' => trim($_POST['social_instagram'] ?? ''),
                    'social_facebook' => trim($_POST['social_facebook'] ?? ''),
                    'social_whatsapp' => trim($_POST['social_whatsapp'] ?? ''),
                    'seo_title' => trim($_POST['seo_title'] ?? ''),
                    'seo_description' => trim($_POST['seo_description'] ?? ''),
                    'seo_keywords' => trim($_POST['seo_keywords'] ?? ''),
                    'og_title' => trim($_POST['og_title'] ?? ''),
                    'og_description' => trim($_POST['og_description'] ?? ''),
                    'og_image' => trim($_POST['og_image'] ?? ''),
                    'og_type' => trim($_POST['og_type'] ?? 'website'),
                    'twitter_card' => trim($_POST['twitter_card'] ?? 'summary_large_image'),
                ]);
                flash('success', 'Site ayarlari guncellendi.');
                break;

            case 'save_service':
                $database->saveService($_POST);
                flash('success', 'Hizmet kaydedildi.');
                break;

            case 'delete_service':
                $database->deleteService((int) ($_POST['id'] ?? 0));
                flash('success', 'Hizmet silindi.');
                break;

            case 'save_therapist':
                $database->saveTherapist($_POST);
                flash('success', 'Terapist kaydedildi.');
                break;

            case 'delete_therapist':
                $database->deleteTherapist((int) ($_POST['id'] ?? 0));
                flash('success', 'Terapist silindi.');
                break;

            case 'save_slide':
                $database->saveSlide($_POST);
                flash('success', 'Slider ogesi kaydedildi.');
                break;

            case 'delete_slide':
                $database->deleteSlide((int) ($_POST['id'] ?? 0));
                flash('success', 'Slider ogesi silindi.');
                break;

            case 'save_gallery_item':
                $database->saveGalleryItem($_POST);
                flash('success', 'Salon gorseli kaydedildi.');
                break;

            case 'delete_gallery_item':
                $database->deleteGalleryItem((int) ($_POST['id'] ?? 0));
                flash('success', 'Salon gorseli silindi.');
                break;

            case 'save_hours':
                $database->saveBusinessHours($_POST['hours'] ?? []);
                flash('success', 'Calisma saatleri guncellendi.');
                break;

            case 'save_notifications':
                $database->saveSettings([
                    'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
                    'smtp_host' => trim($_POST['smtp_host'] ?? ''),
                    'smtp_port' => trim($_POST['smtp_port'] ?? '587'),
                    'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
                    'smtp_username' => trim($_POST['smtp_username'] ?? ''),
                    'smtp_password' => trim($_POST['smtp_password'] ?? ''),
                    'smtp_from_email' => trim($_POST['smtp_from_email'] ?? ''),
                    'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
                    'smtp_subject_created' => trim($_POST['smtp_subject_created'] ?? ''),
                    'smtp_subject_updated' => trim($_POST['smtp_subject_updated'] ?? ''),
                    'smtp_template_created' => trim($_POST['smtp_template_created'] ?? ''),
                    'smtp_template_updated' => trim($_POST['smtp_template_updated'] ?? ''),
                    'smtp_test_subject' => trim($_POST['smtp_test_subject'] ?? ''),
                    'smtp_test_template' => trim($_POST['smtp_test_template'] ?? ''),
                    'sms_enabled' => isset($_POST['sms_enabled']) ? '1' : '0',
                    'sms_api_url' => trim($_POST['sms_api_url'] ?? ''),
                    'sms_method' => trim($_POST['sms_method'] ?? 'POST'),
                    'sms_headers' => trim($_POST['sms_headers'] ?? ''),
                    'sms_body_template' => trim($_POST['sms_body_template'] ?? ''),
                    'sms_query_template' => trim($_POST['sms_query_template'] ?? ''),
                    'sms_template_created' => trim($_POST['sms_template_created'] ?? ''),
                    'sms_template_updated' => trim($_POST['sms_template_updated'] ?? ''),
                ]);
                flash('success', 'SMTP ve SMS ayarlari kaydedildi.');
                break;

            case 'send_notification_test':
                $results = Notifier::sendTest($_POST);
                $messages = array_map(static fn(array $result): string => $result['success'] ? 'Test basariyla gonderildi.' : 'Test gonderimi basarisiz oldu.', $results);
                flash('success', implode(' ', $messages));
                break;
        }
    } catch (Throwable $exception) {
        flash('error', 'Islem sirasinda hata olustu: ' . $exception->getMessage());
    }

    $redirectTarget = 'admin.php';
    if ($page !== 'dashboard') {
        $redirectTarget .= '?page=' . urlencode($page);
    }
    redirect($redirectTarget);
}

$settings = admin_settings();
$stats = $database->appointmentStats();
$services = $database->services(false);
$therapists = $database->therapists(false);
$slides = $database->slides(false);
$galleryItems = $database->galleryItems(false);
$businessHours = $database->businessHours();
$notificationLogs = $database->notificationLogs();
$error = flash('error');
$success = flash('success');
$pageTitle = admin_page_title($page);

require __DIR__ . '/app/Views/admin/header.php';

if ($error): ?><div class="alert alert-danger"><p><?= e($error) ?></p></div><?php endif;
if ($success): ?><div class="alert alert-success"><p><?= e($success) ?></p></div><?php endif;
if ($page === 'dashboard'): ?>
    <section id="overview" class="stack-section">
        <div class="section-head compact">
            <span class="eyebrow">Genel Bakis</span>
            <h2>Panel ozeti</h2>
            <p>Icerik, tema, hizmet, ekip ve bildirim ayarlari bu sayfali duzende ayrildi. Randevular icin sol menuden ayri yonetim ekranina gecebilirsin.</p>
        </div>
        <div class="stats-grid">
            <article class="stat-card"><span>Toplam</span><strong><?= e((string) ($stats['total'] ?? 0)) ?></strong></article>
            <article class="stat-card"><span>Bekleyen</span><strong><?= e((string) ($stats['pending_count'] ?? 0)) ?></strong></article>
            <article class="stat-card"><span>Onaylanan</span><strong><?= e((string) ($stats['approved_count'] ?? 0)) ?></strong></article>
            <article class="stat-card"><span>Tamamlanan</span><strong><?= e((string) ($stats['completed_count'] ?? 0)) ?></strong></article>
        </div>
    </section>
<?php elseif ($page === 'content'): ?>
    <section class="card admin-panel">
        <div class="section-head compact">
            <span class="eyebrow">Icerik ve Marka</span>
            <h2>Anasayfa, marka, tema ve SEO yonetimi</h2>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_settings">
            <div class="form-grid triple">
                <label>Site adi<input type="text" name="site_name" value="<?= e(setting($settings, 'site_name')) ?>"></label>
                <label>Site URL<input type="text" name="site_url" value="<?= e(setting($settings, 'site_url', base_url())) ?>"></label>
                <label>Para birimi<input type="text" name="currency" value="<?= e(setting($settings, 'currency', 'TRY')) ?>"></label>
                <label>Text logo<input type="text" name="text_logo" value="<?= e(setting($settings, 'text_logo')) ?>"></label>
                <label>Saat dilimi<input type="text" name="timezone" value="<?= e(setting($settings, 'timezone', 'Europe/Istanbul')) ?>"></label>
                <label>Logo URL<input type="text" name="site_logo" value="<?= e(setting($settings, 'site_logo')) ?>"></label>
                <label>Logo tipi
                    <select name="logo_type">
                        <option value="text" <?= setting($settings, 'logo_type') === 'text' ? 'selected' : '' ?>>Text logo</option>
                        <option value="image" <?= setting($settings, 'logo_type') === 'image' ? 'selected' : '' ?>>Gorsel logo</option>
                        <option value="minimal" <?= setting($settings, 'logo_type') === 'minimal' ? 'selected' : '' ?>>Sadece site adi</option>
                    </select>
                </label>
                <label>Tema
                    <select name="theme_mode">
                        <?php foreach (theme_options() as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= setting($settings, 'theme_mode') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Renk semasi
                    <select name="color_scheme">
                        <?php foreach (scheme_options() as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= setting($settings, 'color_scheme') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>

            <div class="form-grid triple">
                <label>Ust etiket<input type="text" name="site_tagline" value="<?= e(setting($settings, 'site_tagline')) ?>"></label>
                <label>Hero badge<input type="text" name="hero_badge" value="<?= e(setting($settings, 'hero_badge')) ?>"></label>
                <label>Hero baslik<input type="text" name="hero_title" value="<?= e(setting($settings, 'hero_title')) ?>"></label>
                <label class="span-3">Hero metin<textarea name="hero_text"><?= e(setting($settings, 'hero_text')) ?></textarea></label>
                <label>Hero metric etiket<input type="text" name="hero_metric_label" value="<?= e(setting($settings, 'hero_metric_label')) ?>"></label>
                <label>Hero metric deger<input type="text" name="hero_metric_value" value="<?= e(setting($settings, 'hero_metric_value')) ?>"></label>
                <label>Ozellik 1<input type="text" name="feature_1" value="<?= e(setting($settings, 'feature_1')) ?>"></label>
                <label>Ozellik 2<input type="text" name="feature_2" value="<?= e(setting($settings, 'feature_2')) ?>"></label>
                <label>Ozellik 3<input type="text" name="feature_3" value="<?= e(setting($settings, 'feature_3')) ?>"></label>
            </div>

            <div class="form-grid triple">
                <label>Hizmetler baslik<input type="text" name="services_title" value="<?= e(setting($settings, 'services_title')) ?>"></label>
                <label class="span-2">Hizmetler metin<textarea name="services_text"><?= e(setting($settings, 'services_text')) ?></textarea></label>
                <label>Terapistler baslik<input type="text" name="therapists_title" value="<?= e(setting($settings, 'therapists_title')) ?>"></label>
                <label class="span-2">Terapistler metin<textarea name="therapists_text"><?= e(setting($settings, 'therapists_text')) ?></textarea></label>
                <label>Randevu baslik<input type="text" name="booking_title" value="<?= e(setting($settings, 'booking_title')) ?>"></label>
                <label class="span-2">Randevu metin<textarea name="booking_text"><?= e(setting($settings, 'booking_text')) ?></textarea></label>
                <label>Hakkimizda baslik<input type="text" name="about_title" value="<?= e(setting($settings, 'about_title')) ?>"></label>
                <label class="span-2">Hakkimizda metin<textarea name="about_text"><?= e(setting($settings, 'about_text')) ?></textarea></label>
            </div>

            <div class="form-grid triple">
                <label>Telefon<input type="text" name="contact_phone" value="<?= e(setting($settings, 'contact_phone')) ?>"></label>
                <label>E-posta<input type="text" name="contact_email" value="<?= e(setting($settings, 'contact_email')) ?>"></label>
                <label>Adres<input type="text" name="contact_address" value="<?= e(setting($settings, 'contact_address')) ?>"></label>
                <label>Harita bolum baslik<input type="text" name="map_section_title" value="<?= e(setting($settings, 'map_section_title')) ?>"></label>
                <label class="span-2">Harita bolum metin<textarea name="map_section_text"><?= e(setting($settings, 'map_section_text')) ?></textarea></label>
                <label class="span-3">Google Maps Embed URL
                    <input type="text" name="google_maps_embed_url" value="<?= e(setting($settings, 'google_maps_embed_url')) ?>" placeholder="https://www.google.com/maps/embed?...">
                </label>
                <label>Instagram<input type="text" name="social_instagram" value="<?= e(setting($settings, 'social_instagram')) ?>"></label>
                <label>Facebook<input type="text" name="social_facebook" value="<?= e(setting($settings, 'social_facebook')) ?>"></label>
                <label>WhatsApp<input type="text" name="social_whatsapp" value="<?= e(setting($settings, 'social_whatsapp')) ?>"></label>
                <label class="span-3">Footer notu<textarea name="footer_note"><?= e(setting($settings, 'footer_note')) ?></textarea></label>
            </div>

            <div class="form-grid triple">
                <label>SEO baslik<input type="text" name="seo_title" value="<?= e(setting($settings, 'seo_title')) ?>"></label>
                <label class="span-2">SEO aciklama<textarea name="seo_description"><?= e(setting($settings, 'seo_description')) ?></textarea></label>
                <label class="span-3">SEO anahtar kelimeler<textarea name="seo_keywords"><?= e(setting($settings, 'seo_keywords')) ?></textarea></label>
                <label>OG baslik<input type="text" name="og_title" value="<?= e(setting($settings, 'og_title')) ?>"></label>
                <label class="span-2">OG aciklama<textarea name="og_description"><?= e(setting($settings, 'og_description')) ?></textarea></label>
                <label>OG gorsel URL<input type="text" name="og_image" value="<?= e(setting($settings, 'og_image')) ?>"></label>
                <label>OG type<input type="text" name="og_type" value="<?= e(setting($settings, 'og_type')) ?>"></label>
                <label>Twitter card<input type="text" name="twitter_card" value="<?= e(setting($settings, 'twitter_card')) ?>"></label>
            </div>

            <button class="btn btn-primary" type="submit">Ayarlari Kaydet</button>
        </form>
    </section>
<?php elseif ($page === 'services'): ?>
    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Hizmetler</span><h2>Hizmet ekle, duzenle, sil</h2></div>
        <div class="subsection-title">Yeni hizmet</div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_service">
            <div class="form-grid triple">
                <label>Baslik<input type="text" name="name" required></label>
                <label>Slug<input type="text" name="slug"></label>
                <label>Sure (dk)<input type="number" name="duration" value="60"></label>
                <label>Fiyat<input type="number" step="0.01" name="price" value="0"></label>
                <label>Gorsel URL<input type="text" name="image_url"></label>
                <label>Sira<input type="number" name="sort_order" value="0"></label>
                <label class="span-3">Aciklama<textarea name="description"></textarea></label>
                <label class="toggle-inline"><input type="checkbox" name="is_active" checked> Aktif</label>
            </div>
            <button class="btn btn-primary" type="submit">Hizmet Ekle</button>
        </form>
        <div class="subsection-title">Mevcut hizmetler</div>
        <div class="list-grid">
            <?php foreach ($services as $service): ?>
                <form method="post" class="card entity-card">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="save_service">
                    <input type="hidden" name="id" value="<?= e((string) $service['id']) ?>">
                    <div class="form-grid triple compact-grid">
                        <label>Baslik<input type="text" name="name" value="<?= e($service['name']) ?>"></label>
                        <label>Slug<input type="text" name="slug" value="<?= e($service['slug']) ?>"></label>
                        <label>Sure<input type="number" name="duration" value="<?= e((string) $service['duration']) ?>"></label>
                        <label>Fiyat<input type="number" step="0.01" name="price" value="<?= e((string) $service['price']) ?>"></label>
                        <label>Gorsel<input type="text" name="image_url" value="<?= e($service['image_url'] ?? '') ?>"></label>
                        <label>Sira<input type="number" name="sort_order" value="<?= e((string) $service['sort_order']) ?>"></label>
                        <label class="span-3">Aciklama<textarea name="description"><?= e($service['description']) ?></textarea></label>
                        <label class="toggle-inline"><input type="checkbox" name="is_active" <?= (int) $service['is_active'] === 1 ? 'checked' : '' ?>> Aktif</label>
                    </div>
                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Guncelle</button>
                        <button class="btn btn-danger" type="submit" name="admin_action" value="delete_service" onclick="return confirm('Hizmet silinsin mi?')">Sil</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($page === 'therapists'): ?>
    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Terapistler</span><h2>Terapist ekle, sil, duzenle</h2></div>
        <div class="subsection-title">Yeni terapist</div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_therapist">
            <div class="form-grid triple">
                <label>Ad Soyad<input type="text" name="name" required></label>
                <label>Unvan<input type="text" name="title" required></label>
                <label>Foto URL<input type="text" name="photo_url"></label>
                <label class="span-3">Biyografi<textarea name="bio"></textarea></label>
                <label class="toggle-inline"><input type="checkbox" name="is_active" checked> Aktif</label>
            </div>
            <button class="btn btn-primary" type="submit">Terapist Ekle</button>
        </form>
        <div class="subsection-title">Mevcut terapistler</div>
        <div class="list-grid">
            <?php foreach ($therapists as $therapist): ?>
                <form method="post" class="card entity-card">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="save_therapist">
                    <input type="hidden" name="id" value="<?= e((string) $therapist['id']) ?>">
                    <div class="form-grid triple compact-grid">
                        <label>Ad Soyad<input type="text" name="name" value="<?= e($therapist['name']) ?>"></label>
                        <label>Unvan<input type="text" name="title" value="<?= e($therapist['title']) ?>"></label>
                        <label>Foto URL<input type="text" name="photo_url" value="<?= e($therapist['photo_url'] ?? '') ?>"></label>
                        <label class="span-3">Biyografi<textarea name="bio"><?= e($therapist['bio']) ?></textarea></label>
                        <label class="toggle-inline"><input type="checkbox" name="is_active" <?= (int) $therapist['is_active'] === 1 ? 'checked' : '' ?>> Aktif</label>
                    </div>
                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Guncelle</button>
                        <button class="btn btn-danger" type="submit" name="admin_action" value="delete_therapist" onclick="return confirm('Terapist silinsin mi?')">Sil</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?php if ($page === 'slides'): ?>
    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Slider</span><h2>Anasayfa slider yonetimi</h2></div>
        <div class="subsection-title">Yeni slider ogesi</div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_slide">
            <div class="form-grid triple">
                <label>Baslik<input type="text" name="title" required></label>
                <label>CTA metni<input type="text" name="cta_text"></label>
                <label>CTA link<input type="text" name="cta_link" value="#booking"></label>
                <label>Gorsel URL<input type="text" name="image_url"></label>
                <label>Sira<input type="number" name="sort_order" value="0"></label>
                <label class="toggle-inline"><input type="checkbox" name="is_active" checked> Aktif</label>
                <label class="span-3">Alt metin<textarea name="subtitle"></textarea></label>
            </div>
            <button class="btn btn-primary" type="submit">Slider Ogesi Ekle</button>
        </form>
        <div class="subsection-title">Mevcut slider ogeleri</div>
        <div class="list-grid">
            <?php foreach ($slides as $slide): ?>
                <form method="post" class="card entity-card">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="save_slide">
                    <input type="hidden" name="id" value="<?= e((string) $slide['id']) ?>">
                    <div class="form-grid triple compact-grid">
                        <label>Baslik<input type="text" name="title" value="<?= e($slide['title']) ?>"></label>
                        <label>CTA metni<input type="text" name="cta_text" value="<?= e($slide['cta_text'] ?? '') ?>"></label>
                        <label>CTA link<input type="text" name="cta_link" value="<?= e($slide['cta_link'] ?? '') ?>"></label>
                        <label>Gorsel URL<input type="text" name="image_url" value="<?= e($slide['image_url'] ?? '') ?>"></label>
                        <label>Sira<input type="number" name="sort_order" value="<?= e((string) $slide['sort_order']) ?>"></label>
                        <label class="toggle-inline"><input type="checkbox" name="is_active" <?= (int) $slide['is_active'] === 1 ? 'checked' : '' ?>> Aktif</label>
                        <label class="span-3">Alt metin<textarea name="subtitle"><?= e($slide['subtitle']) ?></textarea></label>
                    </div>
                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Guncelle</button>
                        <button class="btn btn-danger" type="submit" name="admin_action" value="delete_slide" onclick="return confirm('Slider ogesi silinsin mi?')">Sil</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($page === 'salon'): ?>
    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Salonumuz</span><h2>Salon gorselleri ve basliklari</h2></div>
        <div class="subsection-title">Yeni salon bolumu</div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_gallery_item">
            <div class="form-grid triple">
                <label>Baslik<input type="text" name="title" required placeholder="Spa Odamiz"></label>
                <label>Gorsel URL<input type="text" name="image_url" required></label>
                <label>Sira<input type="number" name="sort_order" value="0"></label>
                <label class="span-3">Aciklama<textarea name="description" placeholder="Bu alani kisa bir metinle tanitabilirsiniz."></textarea></label>
                <label class="toggle-inline"><input type="checkbox" name="is_active" checked> Aktif</label>
            </div>
            <button class="btn btn-primary" type="submit">Salon Bolumu Ekle</button>
        </form>
        <div class="subsection-title">Mevcut salon bolumleri</div>
        <div class="list-grid">
            <?php foreach ($galleryItems as $item): ?>
                <form method="post" class="card entity-card">
                    <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="admin_action" value="save_gallery_item">
                    <input type="hidden" name="id" value="<?= e((string) $item['id']) ?>">
                    <div class="form-grid triple compact-grid">
                        <label>Baslik<input type="text" name="title" value="<?= e($item['title']) ?>"></label>
                        <label>Gorsel URL<input type="text" name="image_url" value="<?= e($item['image_url'] ?? '') ?>"></label>
                        <label>Sira<input type="number" name="sort_order" value="<?= e((string) $item['sort_order']) ?>"></label>
                        <label class="span-3">Aciklama<textarea name="description"><?= e($item['description'] ?? '') ?></textarea></label>
                        <label class="toggle-inline"><input type="checkbox" name="is_active" <?= (int) $item['is_active'] === 1 ? 'checked' : '' ?>> Aktif</label>
                    </div>
                    <div class="button-row">
                        <button class="btn btn-primary" type="submit">Guncelle</button>
                        <button class="btn btn-danger" type="submit" name="admin_action" value="delete_gallery_item" onclick="return confirm('Salon bolumu silinsin mi?')">Sil</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
<?php elseif ($page === 'hours'): ?>
    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Calisma Saatleri</span><h2>Gun bazli randevu kurallari</h2><p>Saat secimi, kullanici gunu sectikten sonra burada tanimlanan mesaiye gore otomatik kisitlanir.</p></div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_hours">
            <div class="hours-grid">
                <?php foreach (weekday_options() as $weekday => $label): $hour = $businessHours[$weekday] ?? []; ?>
                    <div class="card hour-card">
                        <h3><?= e($label) ?></h3>
                        <label class="toggle-inline"><input type="checkbox" name="hours[<?= $weekday ?>][is_closed]" <?= isset($hour['is_closed']) && (int) $hour['is_closed'] === 1 ? 'checked' : '' ?>> Kapali</label>
                        <label>Baslangic<input type="time" name="hours[<?= $weekday ?>][start_time]" value="<?= e(substr((string) ($hour['start_time'] ?? '10:00:00'), 0, 5)) ?>"></label>
                        <label>Bitis<input type="time" name="hours[<?= $weekday ?>][end_time]" value="<?= e(substr((string) ($hour['end_time'] ?? '22:00:00'), 0, 5)) ?>"></label>
                        <label>Slot araligi (dk)<input type="number" min="15" step="15" name="hours[<?= $weekday ?>][slot_interval]" value="<?= e((string) ($hour['slot_interval'] ?? 30)) ?>"></label>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="btn btn-primary" type="submit">Saatleri Kaydet</button>
        </form>
    </section>
<?php elseif ($page === 'notifications'): ?>
    <section class="card admin-panel">
        <div class="section-head compact">
            <span class="eyebrow">SMTP ve SMS</span>
            <h2>Randevu bildirim sistemi</h2>
            <p>Yeni randevu olustugunda ve durum degistiginde e-posta ile SMS bildirimleri gonderilebilir. Mesajlarda `{customer_name}`, `{appointment_date}`, `{appointment_time}`, `{status_label}` gibi tokenlar kullanilabilir.</p>
        </div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="admin_action" value="save_notifications">
            <div class="subsection-title">SMTP ayarlari</div>
            <div class="form-grid triple">
                <label class="toggle-inline"><input type="checkbox" name="smtp_enabled" <?= setting($settings, 'smtp_enabled') === '1' ? 'checked' : '' ?>> SMTP aktif</label>
                <label>Host<input type="text" name="smtp_host" value="<?= e(setting($settings, 'smtp_host')) ?>"></label>
                <label>Port<input type="number" name="smtp_port" value="<?= e(setting($settings, 'smtp_port', '587')) ?>"></label>
                <label>Guvenlik
                    <select name="smtp_encryption">
                        <?php foreach (['none' => 'Yok', 'tls' => 'TLS', 'ssl' => 'SSL'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= setting($settings, 'smtp_encryption', 'tls') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Kullanici adi<input type="text" name="smtp_username" value="<?= e(setting($settings, 'smtp_username')) ?>"></label>
                <label>Sifre<input type="password" name="smtp_password" value="<?= e(setting($settings, 'smtp_password')) ?>"></label>
                <label>Gonderen e-posta<input type="text" name="smtp_from_email" value="<?= e(setting($settings, 'smtp_from_email', setting($settings, 'contact_email'))) ?>"></label>
                <label>Gonderen ad<input type="text" name="smtp_from_name" value="<?= e(setting($settings, 'smtp_from_name', setting($settings, 'site_name'))) ?>"></label>
                <label>Olusma konusu<input type="text" name="smtp_subject_created" value="<?= e(setting($settings, 'smtp_subject_created')) ?>"></label>
                <label>Guncelleme konusu<input type="text" name="smtp_subject_updated" value="<?= e(setting($settings, 'smtp_subject_updated')) ?>"></label>
                <label class="span-3">Olusma e-posta sablonu<textarea name="smtp_template_created"><?= e(setting($settings, 'smtp_template_created')) ?></textarea></label>
                <label class="span-3">Guncelleme e-posta sablonu<textarea name="smtp_template_updated"><?= e(setting($settings, 'smtp_template_updated')) ?></textarea></label>
                <label>Test konu<input type="text" name="smtp_test_subject" value="<?= e(setting($settings, 'smtp_test_subject')) ?>"></label>
                <label class="span-2">Test e-posta sablonu<textarea name="smtp_test_template"><?= e(setting($settings, 'smtp_test_template')) ?></textarea></label>
            </div>
            <div class="subsection-title">SMS ayarlari</div>
            <div class="form-grid triple">
                <label class="toggle-inline"><input type="checkbox" name="sms_enabled" <?= setting($settings, 'sms_enabled') === '1' ? 'checked' : '' ?>> SMS aktif</label>
                <label>API URL<input type="text" name="sms_api_url" value="<?= e(setting($settings, 'sms_api_url')) ?>"></label>
                <label>Method
                    <select name="sms_method">
                        <option value="POST" <?= setting($settings, 'sms_method', 'POST') === 'POST' ? 'selected' : '' ?>>POST</option>
                        <option value="GET" <?= setting($settings, 'sms_method', 'POST') === 'GET' ? 'selected' : '' ?>>GET</option>
                    </select>
                </label>
                <label class="span-3">Headerlar<textarea name="sms_headers"><?= e(setting($settings, 'sms_headers')) ?></textarea></label>
                <label class="span-3">POST body sablonu<textarea name="sms_body_template"><?= e(setting($settings, 'sms_body_template')) ?></textarea></label>
                <label class="span-3">GET query sablonu<textarea name="sms_query_template"><?= e(setting($settings, 'sms_query_template')) ?></textarea></label>
                <label class="span-3">Olusma SMS sablonu<textarea name="sms_template_created"><?= e(setting($settings, 'sms_template_created')) ?></textarea></label>
                <label class="span-3">Guncelleme SMS sablonu<textarea name="sms_template_updated"><?= e(setting($settings, 'sms_template_updated')) ?></textarea></label>
            </div>
            <button class="btn btn-primary" type="submit">Bildirim Ayarlarini Kaydet</button>
        </form>
    </section>

    <section class="card admin-panel">
        <div class="section-head compact"><span class="eyebrow">Test</span><h2>SMTP ve SMS test gonderimi</h2></div>
        <div class="form-grid two-panels">
            <form method="post" class="card entity-card">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="admin_action" value="send_notification_test">
                <input type="hidden" name="test_channel" value="email">
                <div class="form-grid">
                    <label>Test e-posta<input type="email" name="test_email" required></label>
                    <label>Mesaj<input type="text" name="message" required></label>
                </div>
                <button class="btn btn-primary" type="submit">SMTP Test Gonder</button>
            </form>
            <form method="post" class="card entity-card">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="admin_action" value="send_notification_test">
                <input type="hidden" name="test_channel" value="sms">
                <div class="form-grid">
                    <label>Test telefon<input type="text" name="test_phone" required></label>
                    <label>Mesaj<input type="text" name="message" required></label>
                </div>
                <button class="btn btn-primary" type="submit">SMS Test Gonder</button>
            </form>
        </div>
        <div class="subsection-title">Son bildirim loglari</div>
        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                <tr><th>Kanal</th><th>Olay</th><th>Alici</th><th>Durum</th><th>Tarih</th></tr>
                </thead>
                <tbody>
                <?php foreach ($notificationLogs as $log): ?>
                    <tr>
                        <td><?= e($log['channel']) ?></td>
                        <td><?= e($log['event_key']) ?></td>
                        <td><?= e($log['recipient']) ?></td>
                        <td><?= e($log['status']) ?></td>
                        <td><?= e($log['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif;

require __DIR__ . '/app/Views/admin/footer.php';
