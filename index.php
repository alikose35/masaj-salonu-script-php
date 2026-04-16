<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (!app_installed()) {
    redirect('install.php');
}

$database = db();
$settings = array_merge($database->defaultSettings(), app_settings());
$services = $database->services();
$therapists = $database->therapists();
$slides = $database->slides();
$galleryItems = $database->galleryItems();
$hours = $database->businessHours();
$themeMode = setting($settings, 'theme_mode', 'serene');
$scheme = setting($settings, 'color_scheme', 'sand');
$mapsEmbedUrl = setting($settings, 'google_maps_embed_url');
$socials = [
    'Instagram' => setting($settings, 'social_instagram'),
    'Facebook' => setting($settings, 'social_facebook'),
    'WhatsApp' => setting($settings, 'social_whatsapp'),
];
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(setting($settings, 'seo_title', setting($settings, 'site_name'))) ?></title>
    <meta name="description" content="<?= e(setting($settings, 'seo_description')) ?>">
    <meta name="keywords" content="<?= e(setting($settings, 'seo_keywords')) ?>">
    <meta property="og:title" content="<?= e(setting($settings, 'og_title', setting($settings, 'seo_title'))) ?>">
    <meta property="og:description" content="<?= e(setting($settings, 'og_description', setting($settings, 'seo_description'))) ?>">
    <meta property="og:type" content="<?= e(setting($settings, 'og_type', 'website')) ?>">
    <meta property="og:image" content="<?= e(setting($settings, 'og_image', setting($settings, 'site_logo'))) ?>">
    <meta name="twitter:card" content="<?= e(setting($settings, 'twitter_card', 'summary_large_image')) ?>">
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body class="theme-<?= e($themeMode) ?> scheme-<?= e($scheme) ?>">
<header class="site-header sticky-header">
    <div class="container nav-row">
        <a class="brand" href="index.php"><?= render_logo($settings) ?></a>
        <nav class="top-nav">
            <a href="#services">Hizmetler</a>
            <a href="#therapists">Terapistler</a>
            <a href="#salonumuz">Salonumuz</a>
            <a href="#booking">Randevu</a>
            <a href="#contact">Iletisim</a>
            <a href="admin.php">Admin</a>
        </nav>
    </div>
</header>

<main>
    <section class="hero-section">
        <div class="container hero-stage">
            <div class="hero-copy">
                <span class="eyebrow"><?= e(setting($settings, 'site_tagline')) ?></span>
                <span class="hero-badge"><?= e(setting($settings, 'hero_badge')) ?></span>
                <h1><?= e(setting($settings, 'hero_title')) ?></h1>
                <p><?= e(setting($settings, 'hero_text')) ?></p>
                <div class="button-row">
                    <a class="btn btn-primary" href="#booking">Hemen Randevu Al</a>
                    <a class="btn btn-secondary" href="#services">Hizmetleri Incele</a>
                </div>
                <div class="feature-pills">
                    <span><?= e(setting($settings, 'feature_1')) ?></span>
                    <span><?= e(setting($settings, 'feature_2')) ?></span>
                    <span><?= e(setting($settings, 'feature_3')) ?></span>
                </div>
            </div>
            <div class="hero-side">
                <div class="hero-card card">
                    <p class="metric-label"><?= e(setting($settings, 'hero_metric_label')) ?></p>
                    <div class="metric-value"><?= e(setting($settings, 'hero_metric_value')) ?></div>
                    <p>Mobil uyumlu vitrin, detayli admin paneli ve gun bazli musaitlik mantigi ile tasarlandi.</p>
                </div>
                <?php if ($slides): ?>
                    <div class="slider-shell card">
                        <div class="slider-track" id="heroSlider">
                            <?php foreach ($slides as $index => $slide): ?>
                                <article class="slide <?= $index === 0 ? 'is-active' : '' ?>" style="background-image:url('<?= e($slide['image_url'] ?? '') ?>')">
                                    <div class="slide-overlay">
                                        <span class="eyebrow">Slider <?= e((string) ($index + 1)) ?></span>
                                        <h3><?= e($slide['title']) ?></h3>
                                        <p><?= e($slide['subtitle']) ?></p>
                                        <?php if (($slide['cta_text'] ?? '') !== ''): ?>
                                            <a class="btn btn-primary" href="<?= e($slide['cta_link'] ?: '#booking') ?>"><?= e($slide['cta_text']) ?></a>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                        <div class="slider-dots" id="sliderDots">
                            <?php foreach ($slides as $index => $slide): ?>
                                <button type="button" class="<?= $index === 0 ? 'is-active' : '' ?>" data-slide-index="<?= e((string) $index) ?>"></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="services" class="section-block">
        <div class="container section-shell">
            <div class="section-head">
                <span class="eyebrow">Hizmetler</span>
                <h2><?= e(setting($settings, 'services_title')) ?></h2>
                <p><?= e(setting($settings, 'services_text')) ?></p>
            </div>
            <div class="card-grid service-grid">
                <?php foreach ($services as $service): ?>
                    <article class="service-card">
                        <?php if (($service['image_url'] ?? '') !== ''): ?>
                            <div class="service-media" style="background-image:url('<?= e($service['image_url']) ?>')"></div>
                        <?php endif; ?>
                        <span class="service-duration"><?= e((string) $service['duration']) ?> dk</span>
                        <h3><?= e($service['name']) ?></h3>
                        <p><?= e($service['description']) ?></p>
                        <strong><?= e(number_format((float) $service['price'], 2, ',', '.')) ?> <?= e(setting($settings, 'currency', 'TRY')) ?></strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="therapists" class="section-block alt-bg">
        <div class="container section-shell">
            <div class="section-head">
                <span class="eyebrow">Terapistler</span>
                <h2><?= e(setting($settings, 'therapists_title')) ?></h2>
                <p><?= e(setting($settings, 'therapists_text')) ?></p>
            </div>
            <div class="card-grid therapist-grid">
                <?php foreach ($therapists as $therapist): ?>
                    <article class="therapist-card">
                        <?php if (($therapist['photo_url'] ?? '') !== ''): ?>
                            <div class="therapist-photo" style="background-image:url('<?= e($therapist['photo_url']) ?>')"></div>
                        <?php endif; ?>
                        <h3><?= e($therapist['name']) ?></h3>
                        <span><?= e($therapist['title']) ?></span>
                        <p><?= e($therapist['bio']) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="salonumuz" class="section-block">
        <div class="container section-shell">
            <div class="section-head">
                <span class="eyebrow">Salonumuz</span>
                <h2>Salonumuzdan goruntuler</h2>
                <p>Spa odamiz, VIP odamiz ve salon alanlarimizi basliklar halinde kesfedin.</p>
            </div>
            <div class="card-grid salon-grid">
                <?php foreach ($galleryItems as $item): ?>
                    <article class="salon-card">
                        <?php if (($item['image_url'] ?? '') !== ''): ?>
                            <div class="salon-media" style="background-image:url('<?= e($item['image_url']) ?>')"></div>
                        <?php endif; ?>
                        <h3><?= e($item['title']) ?></h3>
                        <?php if (($item['description'] ?? '') !== ''): ?>
                            <p><?= e($item['description']) ?></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="booking" class="section-block">
        <div class="container section-shell booking-layout">
            <div class="section-head compact">
                <span class="eyebrow">Online Rezervasyon</span>
                <h2><?= e(setting($settings, 'booking_title')) ?></h2>
                <p><?= e(setting($settings, 'booking_text')) ?></p>
                <div class="hour-summary card">
                    <h3>Calisma programi</h3>
                    <ul class="simple-list">
                        <?php foreach (weekday_options() as $weekday => $label): $day = $hours[$weekday] ?? null; ?>
                            <li>
                                <strong><?= e($label) ?></strong>
                                <span>
                                    <?php if (!$day || (int) $day['is_closed'] === 1): ?>
                                        Kapali
                                    <?php else: ?>
                                        <?= e(substr((string) $day['start_time'], 0, 5)) ?> - <?= e(substr((string) $day['end_time'], 0, 5)) ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <form id="appointmentForm" class="booking-form card">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <div class="form-grid">
                    <label>Ad Soyad<input type="text" name="customer_name" required></label>
                    <label>Telefon<input type="text" name="customer_phone" required></label>
                    <label>E-posta<input type="email" name="customer_email"></label>
                    <label>Hizmet
                        <select name="service_id" required>
                            <option value="">Seciniz</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= e((string) $service['id']) ?>"><?= e($service['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Terapist
                        <select name="therapist_id" required>
                            <option value="">Seciniz</option>
                            <?php foreach ($therapists as $therapist): ?>
                                <option value="<?= e((string) $therapist['id']) ?>"><?= e($therapist['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Tarih<input id="appointmentDate" type="date" name="appointment_date" min="<?= e(date('Y-m-d')) ?>" required></label>
                    <label>Saat
                        <select id="appointmentTime" name="appointment_time" required disabled>
                            <option value="">Once tarih secin</option>
                        </select>
                    </label>
                    <label class="span-2">Not<textarea name="notes" placeholder="Ek taleplerinizi yazabilirsiniz"></textarea></label>
                </div>
                <div class="helper-text" id="slotFeedback">Gun secildiginde sadece uygun saatler listelenir.</div>
                <div class="button-row">
                    <button class="btn btn-primary" type="submit">Randevu Talebi Gonder</button>
                </div>
                <div id="formMessage" class="helper-text"></div>
            </form>
        </div>
    </section>

    <section id="contact" class="section-block alt-bg">
        <div class="container section-shell about-grid">
            <div class="section-head compact">
                <span class="eyebrow">Hakkinda</span>
                <h2><?= e(setting($settings, 'about_title')) ?></h2>
                <p><?= e(setting($settings, 'about_text')) ?></p>
            </div>
            <div class="about-card card">
                <h3>Iletisim</h3>
                <p><?= e(setting($settings, 'contact_address')) ?></p>
                <p><?= e(setting($settings, 'contact_phone')) ?></p>
                <p><?= e(setting($settings, 'contact_email')) ?></p>
                <div class="social-row">
                    <?php foreach ($socials as $name => $url): ?>
                        <?php if ($url !== ''): ?><a href="<?= e($url) ?>" target="_blank" rel="noreferrer"><?= e($name) ?></a><?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php if ($mapsEmbedUrl !== ''): ?>
                    <div class="map-frame contact-map">
                        <iframe src="<?= e($mapsEmbedUrl) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="location" class="section-block">
        <div class="container section-shell">
            <div class="section-head compact">
                <span class="eyebrow">Harita</span>
                <h2><?= e(setting($settings, 'map_section_title', 'Konum')) ?></h2>
                <p><?= e(setting($settings, 'map_section_text')) ?></p>
            </div>
            <div class="card map-card">
                <?php if ($mapsEmbedUrl !== ''): ?>
                    <div class="map-frame">
                        <iframe src="<?= e($mapsEmbedUrl) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <p class="helper-text">Google Maps alani admin panelinden ayarlanabilir.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="brand footer-brand"><?= render_logo($settings) ?></div>
            <p><?= e(setting($settings, 'footer_note')) ?></p>
        </div>
        <div>
            <p><?= e(setting($settings, 'contact_address')) ?></p>
            <p><?= e(setting($settings, 'contact_phone')) ?></p>
            <p><?= e(setting($settings, 'contact_email')) ?></p>
        </div>
    </div>
</footer>

<script>
window.appConfig = {
    ajaxUrl: 'ajax.php'
};
</script>
<script src="public/assets/js/app.js"></script>
</body>
</html>
