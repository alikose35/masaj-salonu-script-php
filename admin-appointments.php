<?php

declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

if (!app_installed()) {
    redirect('install.php');
}

admin_require_auth();

$database = db();
$settings = admin_settings();
$statusFilter = trim($_GET['status'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_action'])) {
    if (!verify_csrf($_POST['_csrf'] ?? null)) {
        flash('error', 'Guvenlik dogrulamasi basarisiz.');
        redirect('admin-appointments.php');
    }

    try {
        switch ($_POST['admin_action']) {
            case 'update_appointment':
                $appointmentId = $database->updateAppointment($_POST);
                try {
                    Notifier::notifyAppointmentUpdated($appointmentId);
                    flash('success', 'Randevu guncellendi ve bildirim tetiklendi.');
                } catch (Throwable $notificationException) {
                    flash('success', 'Randevu guncellendi, ancak bildirim gonderiminde gecici bir sorun olustu.');
                }
                break;

            case 'quick_approve':
                $_POST['status'] = 'approved';
                $appointmentId = $database->updateAppointment($_POST);
                try {
                    Notifier::notifyAppointmentUpdated($appointmentId);
                    flash('success', 'Randevu hizlica onaylandi.');
                } catch (Throwable $notificationException) {
                    flash('success', 'Randevu onaylandi, ancak bildirim gonderiminde gecici bir sorun olustu.');
                }
                break;

            case 'quick_cancel':
                $_POST['status'] = 'cancelled';
                $appointmentId = $database->updateAppointment($_POST);
                try {
                    Notifier::notifyAppointmentUpdated($appointmentId);
                    flash('success', 'Randevu iptal edildi.');
                } catch (Throwable $notificationException) {
                    flash('success', 'Randevu iptal edildi, ancak bildirim gonderiminde gecici bir sorun olustu.');
                }
                break;

            case 'resend_notifications':
                Notifier::notifyAppointmentUpdated((int) ($_POST['id'] ?? 0));
                flash('success', 'Bildirimler yeniden gonderildi.');
                break;
        }
    } catch (Throwable $exception) {
        flash('error', 'Randevu islemi sirasinda hata olustu: ' . $exception->getMessage());
    }

    $target = 'admin-appointments.php';
    if ($statusFilter !== '') {
        $target .= '?status=' . urlencode($statusFilter);
    }
    redirect($target);
}

$appointments = $database->appointments($statusFilter !== '' ? $statusFilter : null);
$stats = $database->appointmentStats();
$error = flash('error');
$success = flash('success');
$pageKey = 'appointments';
$pageTitle = 'Randevu Yonetimi';
$statusMap = [
    'pending' => 'Bekliyor',
    'approved' => 'Onaylandi',
    'completed' => 'Tamamlandi',
    'cancelled' => 'Iptal',
];
$statusClassMap = [
    'pending' => 'status-pending',
    'approved' => 'status-approved',
    'completed' => 'status-completed',
    'cancelled' => 'status-cancelled',
];
$filterCounts = [
    '' => (int) ($stats['total'] ?? 0),
    'pending' => (int) ($stats['pending_count'] ?? 0),
    'approved' => (int) ($stats['approved_count'] ?? 0),
    'completed' => (int) ($stats['completed_count'] ?? 0),
    'cancelled' => max(0, (int) ($stats['total'] ?? 0) - (int) ($stats['pending_count'] ?? 0) - (int) ($stats['approved_count'] ?? 0) - (int) ($stats['completed_count'] ?? 0)),
];

require __DIR__ . '/app/Views/admin/header.php';
if ($error): ?><div class="alert alert-danger"><p><?= e($error) ?></p></div><?php endif;
if ($success): ?><div class="alert alert-success"><p><?= e($success) ?></p></div><?php endif;
?>
<section class="stack-section">
    <div class="section-head compact">
        <span class="eyebrow">Randevular</span>
        <h2>Ayri operasyon ekrani</h2>
        <p>Bu sayfa sadece randevu akisini yonetmek icin ayrildi. Durum degisikligi, yonetici notu ve yeniden bildirim gonderimi tek ekranda toplanir.</p>
    </div>
    <div class="stats-grid">
        <article class="stat-card"><span>Toplam</span><strong><?= e((string) ($stats['total'] ?? 0)) ?></strong></article>
        <article class="stat-card"><span>Bekleyen</span><strong><?= e((string) ($stats['pending_count'] ?? 0)) ?></strong></article>
        <article class="stat-card"><span>Onaylanan</span><strong><?= e((string) ($stats['approved_count'] ?? 0)) ?></strong></article>
        <article class="stat-card"><span>Tamamlanan</span><strong><?= e((string) ($stats['completed_count'] ?? 0)) ?></strong></article>
    </div>
</section>

<section class="card admin-panel">
    <div class="section-head compact"><span class="eyebrow">Filtre</span><h2>Duruma gore listele</h2></div>
    <div class="status-tabs">
        <?php foreach (['' => 'Tum durumlar', 'pending' => 'Bekliyor', 'approved' => 'Onaylandi', 'completed' => 'Tamamlandi', 'cancelled' => 'Iptal'] as $key => $label): ?>
            <a class="status-tab <?= $statusFilter === $key ? 'is-active' : '' ?>" href="admin-appointments.php<?= $key !== '' ? '?status=' . urlencode($key) : '' ?>">
                <?= e($label) ?> (<?= e((string) ($filterCounts[$key] ?? 0)) ?>)
            </a>
        <?php endforeach; ?>
    </div>
    <form method="get" class="filter-row">
        <select name="status">
            <option value="">Tum durumlar</option>
            <?php foreach ($statusMap as $value => $label): ?>
                <option value="<?= e($value) ?>" <?= $statusFilter === $value ? 'selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-primary" type="submit">Filtrele</button>
    </form>
</section>

<section class="card admin-panel">
    <div class="section-head compact"><span class="eyebrow">Liste</span><h2>Randevu detaylari</h2></div>
    <div class="appointment-list">
        <?php foreach ($appointments as $appointment): ?>
            <?php $status = (string) ($appointment['status'] ?? 'pending'); ?>
            <form method="post" class="card appointment-card compact-appointment">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) $appointment['id']) ?>">
                <div class="appointment-head">
                    <span class="status-pill <?= e($statusClassMap[$status] ?? 'status-pending') ?>"><?= e($statusMap[$status] ?? 'Bekliyor') ?></span>
                    <span class="appointment-id">#<?= e((string) $appointment['id']) ?></span>
                </div>
                <div class="appointment-meta">
                    <div>
                        <span class="eyebrow">Misafir</span>
                        <h3><?= e($appointment['customer_name']) ?></h3>
                        <p><?= e($appointment['customer_phone']) ?><?php if ($appointment['customer_email']): ?> / <?= e($appointment['customer_email']) ?><?php endif; ?></p>
                    </div>
                    <div>
                        <span class="eyebrow">Detay</span>
                        <p><?= e($appointment['service_name'] ?? '-') ?> / <?= e($appointment['therapist_name'] ?? '-') ?></p>
                        <p><?= e($appointment['appointment_date']) ?> - <?= e(substr((string) $appointment['appointment_time'], 0, 5)) ?></p>
                    </div>
                    <div class="quick-status">
                        <label>Durum
                            <select name="status">
                                <?php foreach ($statusMap as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    </div>
                </div>
                <div class="button-row appointment-actions">
                    <?php if ($status !== 'approved' && $status !== 'cancelled'): ?>
                        <button class="btn btn-primary" type="submit" name="admin_action" value="quick_approve">Hemen Onayla</button>
                    <?php endif; ?>
                    <?php if ($status !== 'cancelled'): ?>
                        <button class="btn btn-danger" type="submit" name="admin_action" value="quick_cancel" onclick="return confirm('Bu randevu iptal edilsin mi?')">Iptal Et</button>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="submit" name="admin_action" value="update_appointment">Kaydet ve Bildirim Gonder</button>
                    <button class="btn btn-secondary" type="submit" name="admin_action" value="resend_notifications">Yalnizca Bildirim Tekrarla</button>
                </div>
                <details class="appointment-details">
                    <summary>Detaylari Ac</summary>
                    <div class="form-grid compact-grid">
                        <label>Yonetici notu<textarea name="admin_notes"><?= e($appointment['admin_notes'] ?? '') ?></textarea></label>
                        <label>Musteri notu<textarea readonly><?= e($appointment['notes'] ?? '') ?></textarea></label>
                    </div>
                </details>
            </form>
        <?php endforeach; ?>
    </div>
</section>
<?php require __DIR__ . '/app/Views/admin/footer.php'; ?>
