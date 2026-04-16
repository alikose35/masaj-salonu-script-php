<?php

declare(strict_types=1);

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf'];
}

function verify_csrf(?string $token): bool
{
    return isset($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], $token);
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
}

function old(string $key, string $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function remember_old(array $data): void
{
    $_SESSION['_old'] = $data;
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

function installation_checks(): array
{
    $required = [
        [
            'title' => 'PHP 8.1 or newer',
            'status' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'details' => 'Current version: ' . PHP_VERSION,
        ],
        [
            'title' => 'PDO MySQL extension',
            'status' => extension_loaded('pdo_mysql'),
            'details' => 'Required for MySQL connectivity.',
        ],
        [
            'title' => 'JSON extension',
            'status' => extension_loaded('json'),
            'details' => 'Required for AJAX payloads and settings data.',
        ],
        [
            'title' => 'OpenSSL extension',
            'status' => extension_loaded('openssl'),
            'details' => 'Required for secure token generation.',
        ],
        [
            'title' => 'Writable storage directory',
            'status' => is_dir(STORAGE_PATH) && is_writable(STORAGE_PATH),
            'details' => 'Configuration and lock files are stored here.',
        ],
    ];

    $recommended = [
        [
            'title' => 'mbstring extension',
            'status' => extension_loaded('mbstring'),
            'details' => 'Recommended for reliable multibyte text handling.',
        ],
        [
            'title' => 'fileinfo extension',
            'status' => extension_loaded('fileinfo'),
            'details' => 'Recommended for future media upload workflows.',
        ],
        [
            'title' => 'upload_max_filesize >= 5M',
            'status' => parse_size_to_bytes((string) ini_get('upload_max_filesize')) >= (5 * 1024 * 1024),
            'details' => 'Recommended for logo and media usage.',
        ],
    ];

    return [
        'required' => $required,
        'recommended' => $recommended,
    ];
}

function all_required_checks_passed(array $checks): bool
{
    foreach ($checks['required'] as $item) {
        if (!$item['status']) {
            return false;
        }
    }

    return true;
}

function base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $scriptName = rtrim(str_replace('\\', '/', $scriptName), '/');

    return $scheme . '://' . $host . ($scriptName === '/' ? '' : $scriptName);
}

function status_label(string $status): string
{
    return match ($status) {
        'approved' => 'Onaylandi',
        'completed' => 'Tamamlandi',
        'cancelled' => 'Iptal',
        default => 'Bekliyor',
    };
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'item-' . time();
}

function setting(array $settings, string $key, string $default = ''): string
{
    return (string) ($settings[$key] ?? $default);
}

function parse_json_setting(array $settings, string $key, array $fallback = []): array
{
    $raw = $settings[$key] ?? null;

    if (!is_string($raw) || $raw === '') {
        return $fallback;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : $fallback;
}

function theme_options(): array
{
    return [
        'serene' => 'Serene',
        'boutique' => 'Boutique',
    ];
}

function scheme_options(): array
{
    return [
        'sand' => 'Sand',
        'forest' => 'Forest',
        'clay' => 'Clay',
        'ocean' => 'Ocean',
        'sage' => 'Sage',
        'ruby' => 'Ruby',
        'night' => 'Night',
    ];
}

function weekday_options(): array
{
    return [
        1 => 'Pazartesi',
        2 => 'Sali',
        3 => 'Carsamba',
        4 => 'Persembe',
        5 => 'Cuma',
        6 => 'Cumartesi',
        7 => 'Pazar',
    ];
}

function weekday_from_date(string $date): int
{
    if (!is_valid_date($date)) {
        return 0;
    }

    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);

    return (int) $parsed->format('N');
}

function is_valid_date(string $date): bool
{
    $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
    if (!$parsed instanceof DateTimeImmutable) {
        return false;
    }

    $errors = DateTimeImmutable::getLastErrors();
    if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
        return false;
    }

    return $parsed->format('Y-m-d') === $date;
}

function parse_size_to_bytes(string $size): int
{
    $normalized = trim($size);
    if ($normalized === '') {
        return 0;
    }

    if (!preg_match('/^\s*(\d+(?:\.\d+)?)\s*([kmgtp]?)(?:b)?\s*$/i', $normalized, $matches)) {
        return 0;
    }

    $value = (float) $matches[1];
    $unit = strtolower($matches[2] ?? '');
    $power = match ($unit) {
        'k' => 1,
        'm' => 2,
        'g' => 3,
        't' => 4,
        'p' => 5,
        default => 0,
    };

    return (int) round($value * (1024 ** $power));
}

function render_logo(array $settings): string
{
    $logoType = setting($settings, 'logo_type', 'text');
    $siteName = setting($settings, 'site_name', 'Serenity Massage Lounge');
    $textLogo = setting($settings, 'text_logo', $siteName);
    $logoUrl = setting($settings, 'site_logo', '');

    if ($logoType === 'image' && $logoUrl !== '') {
        return '<img class="brand-image" src="' . e($logoUrl) . '" alt="' . e($siteName) . '">';
    }

    if ($logoType === 'text') {
        return '<span class="brand-text">' . e($textLogo) . '</span>';
    }

    return '<span class="brand-text minimal">' . e($siteName) . '</span>';
}

function time_slots_between(string $startTime, string $endTime, int $intervalMinutes): array
{
    $slots = [];
    $intervalMinutes = max(15, $intervalMinutes);
    $current = strtotime('1970-01-01 ' . $startTime . ':00');
    $end = strtotime('1970-01-01 ' . $endTime . ':00');

    while ($current < $end) {
        $slots[] = date('H:i', $current);
        $current = strtotime('+' . $intervalMinutes . ' minutes', $current);
    }

    return $slots;
}

function appointment_tokens(array $appointment, array $settings): array
{
    return [
        'site_name' => setting($settings, 'site_name'),
        'customer_name' => (string) ($appointment['customer_name'] ?? ''),
        'customer_phone' => (string) ($appointment['customer_phone'] ?? ''),
        'customer_email' => (string) ($appointment['customer_email'] ?? ''),
        'service_name' => (string) ($appointment['service_name'] ?? ''),
        'therapist_name' => (string) ($appointment['therapist_name'] ?? ''),
        'appointment_date' => (string) ($appointment['appointment_date'] ?? ''),
        'appointment_time' => substr((string) ($appointment['appointment_time'] ?? ''), 0, 5),
        'status' => (string) ($appointment['status'] ?? ''),
        'status_label' => status_label((string) ($appointment['status'] ?? 'pending')),
        'admin_notes' => (string) ($appointment['admin_notes'] ?? ''),
        'notes' => (string) ($appointment['notes'] ?? ''),
    ];
}

function replace_notification_tokens(string $template, array $tokens): string
{
    foreach ($tokens as $key => $value) {
        $template = str_replace('{' . $key . '}', (string) $value, $template);
    }

    return $template;
}
