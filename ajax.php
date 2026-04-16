<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!app_installed()) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Sistem kurulumu tamamlanmamis.']);
    exit;
}

$action = $_POST['action'] ?? 'book_appointment';
$database = db();

try {
    if ($action === 'available_slots') {
        $date = trim($_POST['appointment_date'] ?? '');

        if (!is_valid_date($date) || $date < date('Y-m-d')) {
            throw new RuntimeException('Gecerli bir tarih seciniz.');
        }

        $slots = $database->availableSlots($date);

        echo json_encode([
            'ok' => true,
            'slots' => $slots,
            'message' => $slots ? 'Uygun saatler listelendi.' : 'Secilen gunde uygun saat bulunmuyor.',
        ]);
        exit;
    }

    if ($action === 'book_appointment') {
        if (!verify_csrf($_POST['_csrf'] ?? null)) {
            throw new RuntimeException('Guvenlik dogrulamasi basarisiz.');
        }

        $data = [
            'customer_name' => trim($_POST['customer_name'] ?? ''),
            'customer_phone' => trim($_POST['customer_phone'] ?? ''),
            'customer_email' => trim($_POST['customer_email'] ?? ''),
            'service_id' => (int) ($_POST['service_id'] ?? 0),
            'therapist_id' => (int) ($_POST['therapist_id'] ?? 0),
            'appointment_date' => trim($_POST['appointment_date'] ?? ''),
            'appointment_time' => trim($_POST['appointment_time'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        foreach (['customer_name', 'customer_phone', 'appointment_date', 'appointment_time'] as $field) {
            if ($data[$field] === '') {
                throw new RuntimeException('Zorunlu alanlardan biri eksik.');
            }
        }

        if ($data['service_id'] < 1 || $data['therapist_id'] < 1) {
            throw new RuntimeException('Hizmet ve terapist secimi zorunludur.');
        }

        if ($data['customer_email'] !== '' && !filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-posta adresi gecersiz.');
        }

        if (!is_valid_date($data['appointment_date']) || $data['appointment_date'] < date('Y-m-d')) {
            throw new RuntimeException('Gecmis tarihli randevu olusturulamaz.');
        }

        if (!preg_match('/^\d{2}:\d{2}$/', $data['appointment_time'])) {
            throw new RuntimeException('Gecerli bir saat seciniz.');
        }

        $availableSlots = $database->availableSlots($data['appointment_date']);
        if (!in_array($data['appointment_time'], $availableSlots, true)) {
            throw new RuntimeException('Secilen saat dilimi bu gun icin musait degil.');
        }

        $appointmentId = $database->createAppointment($data);
        $message = 'Randevu talebiniz alindi. Yonetici onayi sonrasinda sizinle iletisime gecilecektir.';

        try {
            Notifier::notifyAppointmentCreated($appointmentId);
        } catch (Throwable $notificationException) {
            $message .= ' Bildirim gonderiminde gecici bir sorun olustu, ekibimiz kaydi panelden gorebilir.';
        }

        echo json_encode([
            'ok' => true,
            'message' => $message,
        ]);
        exit;
    }

    throw new RuntimeException('Gecersiz istek.');
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => $exception->getMessage(),
    ]);
}
