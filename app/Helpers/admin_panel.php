<?php

declare(strict_types=1);

function admin_settings(): array
{
    return array_merge(db()->defaultSettings(), app_settings());
}

function admin_require_auth(): void
{
    if (!Auth::check()) {
        redirect('admin.php');
    }
}

function admin_nav_items(): array
{
    return [
        ['key' => 'dashboard', 'label' => 'Genel Bakis', 'href' => 'admin.php'],
        ['key' => 'content', 'label' => 'Icerik ve Marka', 'href' => 'admin.php?page=content'],
        ['key' => 'services', 'label' => 'Hizmetler', 'href' => 'admin.php?page=services'],
        ['key' => 'therapists', 'label' => 'Terapistler', 'href' => 'admin.php?page=therapists'],
        ['key' => 'slides', 'label' => 'Slider', 'href' => 'admin.php?page=slides'],
        ['key' => 'salon', 'label' => 'Salonumuz', 'href' => 'admin.php?page=salon'],
        ['key' => 'hours', 'label' => 'Calisma Saatleri', 'href' => 'admin.php?page=hours'],
        ['key' => 'notifications', 'label' => 'SMTP ve SMS', 'href' => 'admin.php?page=notifications'],
        ['key' => 'appointments', 'label' => 'Randevu Yonetimi', 'href' => 'admin-appointments.php'],
    ];
}

function admin_page_key(): string
{
    $page = trim($_GET['page'] ?? 'dashboard');
    $allowed = ['dashboard', 'content', 'services', 'therapists', 'slides', 'salon', 'hours', 'notifications'];

    return in_array($page, $allowed, true) ? $page : 'dashboard';
}

function admin_page_title(string $page): string
{
    return match ($page) {
        'content' => 'Icerik ve Marka',
        'services' => 'Hizmetler',
        'therapists' => 'Terapistler',
        'slides' => 'Slider',
        'salon' => 'Salonumuz',
        'hours' => 'Calisma Saatleri',
        'notifications' => 'SMTP ve SMS',
        default => 'Genel Bakis',
    };
}
