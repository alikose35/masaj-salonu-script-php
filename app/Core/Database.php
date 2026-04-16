<?php

declare(strict_types=1);

class Database
{
    private array $config;
    private ?PDO $pdo = null;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = $this->config['host'] ?? 'db';
        $port = (int) ($this->config['port'] ?? 3306);
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $this->pdo = new PDO(
            $dsn,
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return $this->pdo;
    }

    public function ensureSchema(): void
    {
        $pdo = $this->pdo();

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) NOT NULL UNIQUE,
                setting_value LONGTEXT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(160) NOT NULL UNIQUE,
                duration SMALLINT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                description TEXT NULL,
                image_url VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                sort_order INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS therapists (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                title VARCHAR(150) NOT NULL,
                bio TEXT NULL,
                photo_url VARCHAR(255) NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS slides (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                subtitle TEXT NULL,
                image_url VARCHAR(255) NULL,
                cta_text VARCHAR(120) NULL,
                cta_link VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS salon_gallery (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(180) NOT NULL,
                description TEXT NULL,
                image_url VARCHAR(255) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS business_hours (
                id INT AUTO_INCREMENT PRIMARY KEY,
                weekday TINYINT NOT NULL UNIQUE,
                is_closed TINYINT(1) NOT NULL DEFAULT 0,
                start_time TIME NULL,
                end_time TIME NULL,
                slot_interval SMALLINT NOT NULL DEFAULT 30
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS notification_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                channel ENUM('email', 'sms') NOT NULL,
                event_key VARCHAR(60) NOT NULL,
                recipient VARCHAR(190) NOT NULL,
                subject VARCHAR(255) NULL,
                payload LONGTEXT NULL,
                status VARCHAR(30) NOT NULL,
                response_text LONGTEXT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS appointments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                customer_name VARCHAR(150) NOT NULL,
                customer_phone VARCHAR(50) NOT NULL,
                customer_email VARCHAR(150) NULL,
                service_id INT NOT NULL,
                therapist_id INT NOT NULL,
                appointment_date DATE NOT NULL,
                appointment_time TIME NOT NULL,
                notes TEXT NULL,
                admin_notes TEXT NULL,
                status ENUM('pending', 'approved', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NULL,
                CONSTRAINT fk_appointments_service FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,
                CONSTRAINT fk_appointments_therapist FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->ensureColumn('services', 'image_url', 'VARCHAR(255) NULL AFTER description');
        $this->ensureColumn('therapists', 'photo_url', 'VARCHAR(255) NULL AFTER bio');
        $this->ensureColumn('appointments', 'admin_notes', 'TEXT NULL AFTER notes');
        $this->ensureColumn('appointments', 'updated_at', 'DATETIME NULL AFTER created_at');
        $this->ensureIndex('appointments', 'idx_appointments_slot_status', ['appointment_date', 'appointment_time', 'status']);
        $this->seedDefaults();
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $statement = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name'
        );
        $statement->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        if ((int) $statement->fetchColumn() === 0) {
            $this->pdo()->exec(sprintf('ALTER TABLE %s ADD COLUMN %s %s', $table, $column, $definition));
        }
    }

    private function ensureIndex(string $table, string $indexName, array $columns): void
    {
        $statement = $this->pdo()->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name AND INDEX_NAME = :index_name'
        );
        $statement->execute([
            'table_name' => $table,
            'index_name' => $indexName,
        ]);

        if ((int) $statement->fetchColumn() > 0) {
            return;
        }

        $escapedColumns = array_map(static fn(string $column): string => sprintf('`%s`', $column), $columns);
        try {
            $this->pdo()->exec(sprintf(
                'CREATE INDEX `%s` ON `%s` (%s)',
                $indexName,
                $table,
                implode(', ', $escapedColumns)
            ));
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1061 || str_contains(strtolower($exception->getMessage()), 'duplicate key')) {
                return;
            }

            throw $exception;
        }
    }

    private function seedDefaults(): void
    {
        if ((int) $this->pdo()->query('SELECT COUNT(*) FROM business_hours')->fetchColumn() === 0) {
            $statement = $this->pdo()->prepare(
                'INSERT INTO business_hours (weekday, is_closed, start_time, end_time, slot_interval)
                VALUES (:weekday, :is_closed, :start_time, :end_time, :slot_interval)'
            );

            foreach (weekday_options() as $weekday => $label) {
                $statement->execute([
                    'weekday' => $weekday,
                    'is_closed' => 0,
                    'start_time' => $weekday === 6 ? '13:30:00' : '10:00:00',
                    'end_time' => $weekday === 6 ? '17:30:00' : '22:00:00',
                    'slot_interval' => 30,
                ]);
            }
        }

        if ((int) $this->pdo()->query('SELECT COUNT(*) FROM slides')->fetchColumn() === 0) {
            $this->pdo()->exec(
                "INSERT INTO slides (title, subtitle, image_url, cta_text, cta_link, sort_order, is_active) VALUES
                ('Derin gevseme ritueli', 'Aromaterapi ve premium dokunuslarla gunun stresini salon kapisinda birakin.', 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?auto=format&fit=crop&w=1200&q=80', 'Randevu Al', '#booking', 1, 1),
                ('Uzman terapist ekibi', 'Boutique deneyim, modern rezervasyon akisi ve misafir odakli karsilama.', 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=1200&q=80', 'Terapistleri Gor', '#therapists', 2, 1)"
            );
        }

        if ((int) $this->pdo()->query('SELECT COUNT(*) FROM salon_gallery')->fetchColumn() === 0) {
            $this->pdo()->exec(
                "INSERT INTO salon_gallery (title, description, image_url, sort_order, is_active) VALUES
                ('Spa Odamiz', 'Aromaterapi kokulari ve sakinlestirici isiklarla hazirlanan premium oda.', 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?auto=format&fit=crop&w=1200&q=80', 1, 1),
                ('VIP Odamiz', 'Cift kisilik ozel rituel seanslari icin ayrilmis konforlu alan.', 'https://images.unsplash.com/photo-1519824145371-296894a0daa9?auto=format&fit=crop&w=1200&q=80', 2, 1)"
            );
        }

        if ((int) $this->pdo()->query('SELECT COUNT(*) FROM settings')->fetchColumn() === 0) {
            $this->saveSettings($this->defaultSettings());
        }
    }

    public function defaultSettings(): array
    {
        return [
            'site_name' => 'Serenity Massage Lounge',
            'site_url' => '',
            'text_logo' => 'Serenity',
            'logo_type' => 'text',
            'site_logo' => '',
            'theme_mode' => 'serene',
            'color_scheme' => 'sand',
            'currency' => 'TRY',
            'timezone' => 'Europe/Istanbul',
            'site_tagline' => 'Urban wellness studio',
            'hero_badge' => 'Premium Massage Experience',
            'hero_title' => 'Profesyonel dokunuslarla zihninizi ve bedeninizi yenileyin.',
            'hero_text' => 'Uyeliksiz hizli rezervasyon, uzman terapistler ve butik spa deneyimini ayni akista sunun.',
            'hero_metric_label' => 'Misafir deneyimi',
            'hero_metric_value' => 'Uyeliksiz hizli rezervasyon',
            'feature_1' => 'Dinamik saat secimi ve gun bazli mesai kurali',
            'feature_2' => 'Slider, SEO ve marka yonetimi tek panelde',
            'feature_3' => 'Mobil uyumlu modern vitrin ve admin arayuzu',
            'services_title' => 'Hizmetler',
            'services_text' => 'Masaj, spa ve butik wellness hizmetlerinizi dikkat cekici sekilde sunun.',
            'therapists_title' => 'Uzman ekip',
            'therapists_text' => 'Terapist profillerini foto, unvan ve uzmanlik alanlariyla yonetin.',
            'booking_title' => 'Uyeliksiz rezervasyon',
            'booking_text' => 'Once gun secilir, sonra yalnizca musait saatler listelenir.',
            'about_title' => 'Neden bu sistem?',
            'about_text' => 'Salonunuzu markaya uygun sekilde yonetmek, SEO performansini guclendirmek ve rezervasyon hatalarini azaltmak icin tasarlandi.',
            'contact_phone' => '+90 555 000 00 00',
            'contact_email' => 'info@example.com',
            'contact_address' => 'Merkez Mah. Huzur Sok. No:12 Istanbul',
            'google_maps_embed_url' => '',
            'map_section_title' => 'Konum',
            'map_section_text' => 'Salon konumumuzu harita uzerinden kolayca goruntuleyebilirsiniz.',
            'footer_note' => 'Butik wellness deneyimi, modern rezervasyon altyapisi ile bulusuyor.',
            'seo_title' => 'Masaj Salonu ve Spa Randevu Sistemi',
            'seo_description' => 'Masaj salonlari icin profesyonel, mobil uyumlu ve yonetilebilir rezervasyon scripti.',
            'seo_keywords' => 'masaj salonu, spa rezervasyon, wellness, online randevu',
            'og_title' => 'Masaj Salonu ve Spa Randevu Sistemi',
            'og_description' => 'Markaniza uygun modern vitrin ve detayli yonetim paneli.',
            'og_image' => '',
            'og_type' => 'website',
            'twitter_card' => 'summary_large_image',
            'social_instagram' => '',
            'social_facebook' => '',
            'social_whatsapp' => '',
            'smtp_enabled' => '0',
            'smtp_host' => '',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => '',
            'smtp_password' => '',
            'smtp_from_email' => 'info@example.com',
            'smtp_from_name' => 'Serenity Massage Lounge',
            'smtp_subject_created' => 'Randevunuz alindi - {site_name}',
            'smtp_subject_updated' => 'Randevu durumunuz guncellendi - {status_label}',
            'smtp_template_created' => "Merhaba {customer_name},\n\n{appointment_date} tarihinde saat {appointment_time} icin randevu talebiniz alindi.\nHizmet: {service_name}\nTerapist: {therapist_name}\nDurum: {status_label}\n\n{site_name}",
            'smtp_template_updated' => "Merhaba {customer_name},\n\nRandevunuz guncellendi.\nTarih: {appointment_date}\nSaat: {appointment_time}\nHizmet: {service_name}\nDurum: {status_label}\nYonetici notu: {admin_notes}\n\n{site_name}",
            'smtp_test_subject' => 'SMTP test bildirimi',
            'smtp_test_template' => "Merhaba,\n\n{message}\n\n{site_name}",
            'sms_enabled' => '0',
            'sms_api_url' => '',
            'sms_method' => 'POST',
            'sms_headers' => "Content-Type: application/json",
            'sms_body_template' => '{"phone":"{phone}","message":"{message}"}',
            'sms_query_template' => 'phone={phone}&message={message}',
            'sms_template_created' => '{site_name}: {appointment_date} {appointment_time} icin randevu talebiniz alindi.',
            'sms_template_updated' => '{site_name}: randevunuz {status_label} durumuna guncellendi. {appointment_date} {appointment_time}',
        ];
    }

    public function settings(): array
    {
        $statement = $this->pdo()->query('SELECT setting_key, setting_value FROM settings');
        $settings = [];

        foreach ($statement->fetchAll() as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        return $settings;
    }

    public function saveSettings(array $settings): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO settings (setting_key, setting_value)
            VALUES (:setting_key, :setting_value)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($settings as $key => $value) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => (string) $value,
            ]);
        }
    }

    public function services(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM services';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return $this->pdo()->query($sql)->fetchAll();
    }

    public function saveService(array $payload): void
    {
        $data = [
            'name' => trim($payload['name'] ?? ''),
            'slug' => slugify($payload['slug'] ?? ($payload['name'] ?? '')),
            'duration' => max(15, (int) ($payload['duration'] ?? 60)),
            'price' => (float) ($payload['price'] ?? 0),
            'description' => trim($payload['description'] ?? ''),
            'image_url' => trim($payload['image_url'] ?? ''),
            'is_active' => isset($payload['is_active']) ? 1 : 0,
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
        ];

        if ((int) ($payload['id'] ?? 0) > 0) {
            $data['id'] = (int) $payload['id'];
            $statement = $this->pdo()->prepare(
                'UPDATE services SET
                name = :name, slug = :slug, duration = :duration, price = :price, description = :description,
                image_url = :image_url, is_active = :is_active, sort_order = :sort_order
                WHERE id = :id'
            );
            $statement->execute($data);
            return;
        }

        $statement = $this->pdo()->prepare(
            'INSERT INTO services (name, slug, duration, price, description, image_url, is_active, sort_order)
            VALUES (:name, :slug, :duration, :price, :description, :image_url, :is_active, :sort_order)'
        );
        $statement->execute($data);
    }

    public function deleteService(int $id): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM services WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function therapists(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM therapists';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY id ASC';

        return $this->pdo()->query($sql)->fetchAll();
    }

    public function saveTherapist(array $payload): void
    {
        $data = [
            'name' => trim($payload['name'] ?? ''),
            'title' => trim($payload['title'] ?? ''),
            'bio' => trim($payload['bio'] ?? ''),
            'photo_url' => trim($payload['photo_url'] ?? ''),
            'is_active' => isset($payload['is_active']) ? 1 : 0,
        ];

        if ((int) ($payload['id'] ?? 0) > 0) {
            $data['id'] = (int) $payload['id'];
            $statement = $this->pdo()->prepare(
                'UPDATE therapists SET name = :name, title = :title, bio = :bio, photo_url = :photo_url, is_active = :is_active WHERE id = :id'
            );
            $statement->execute($data);
            return;
        }

        $statement = $this->pdo()->prepare(
            'INSERT INTO therapists (name, title, bio, photo_url, is_active)
            VALUES (:name, :title, :bio, :photo_url, :is_active)'
        );
        $statement->execute($data);
    }

    public function deleteTherapist(int $id): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM therapists WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function slides(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM slides';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return $this->pdo()->query($sql)->fetchAll();
    }

    public function saveSlide(array $payload): void
    {
        $data = [
            'title' => trim($payload['title'] ?? ''),
            'subtitle' => trim($payload['subtitle'] ?? ''),
            'image_url' => trim($payload['image_url'] ?? ''),
            'cta_text' => trim($payload['cta_text'] ?? ''),
            'cta_link' => trim($payload['cta_link'] ?? ''),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => isset($payload['is_active']) ? 1 : 0,
        ];

        if ((int) ($payload['id'] ?? 0) > 0) {
            $data['id'] = (int) $payload['id'];
            $statement = $this->pdo()->prepare(
                'UPDATE slides SET
                title = :title, subtitle = :subtitle, image_url = :image_url, cta_text = :cta_text,
                cta_link = :cta_link, sort_order = :sort_order, is_active = :is_active
                WHERE id = :id'
            );
            $statement->execute($data);
            return;
        }

        $statement = $this->pdo()->prepare(
            'INSERT INTO slides (title, subtitle, image_url, cta_text, cta_link, sort_order, is_active)
            VALUES (:title, :subtitle, :image_url, :cta_text, :cta_link, :sort_order, :is_active)'
        );
        $statement->execute($data);
    }

    public function deleteSlide(int $id): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM slides WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function galleryItems(bool $activeOnly = true): array
    {
        $sql = 'SELECT * FROM salon_gallery';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';

        return $this->pdo()->query($sql)->fetchAll();
    }

    public function saveGalleryItem(array $payload): void
    {
        $data = [
            'title' => trim($payload['title'] ?? ''),
            'description' => trim($payload['description'] ?? ''),
            'image_url' => trim($payload['image_url'] ?? ''),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_active' => isset($payload['is_active']) ? 1 : 0,
        ];

        if ((int) ($payload['id'] ?? 0) > 0) {
            $data['id'] = (int) $payload['id'];
            $statement = $this->pdo()->prepare(
                'UPDATE salon_gallery SET
                title = :title, description = :description, image_url = :image_url, sort_order = :sort_order, is_active = :is_active
                WHERE id = :id'
            );
            $statement->execute($data);
            return;
        }

        $statement = $this->pdo()->prepare(
            'INSERT INTO salon_gallery (title, description, image_url, sort_order, is_active)
            VALUES (:title, :description, :image_url, :sort_order, :is_active)'
        );
        $statement->execute($data);
    }

    public function deleteGalleryItem(int $id): void
    {
        $statement = $this->pdo()->prepare('DELETE FROM salon_gallery WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function businessHours(): array
    {
        $hours = [];
        foreach ($this->pdo()->query('SELECT * FROM business_hours ORDER BY weekday ASC')->fetchAll() as $row) {
            $hours[(int) $row['weekday']] = $row;
        }

        return $hours;
    }

    public function saveBusinessHours(array $hours): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO business_hours (weekday, is_closed, start_time, end_time, slot_interval)
            VALUES (:weekday, :is_closed, :start_time, :end_time, :slot_interval)
            ON DUPLICATE KEY UPDATE
                is_closed = VALUES(is_closed),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                slot_interval = VALUES(slot_interval)'
        );

        foreach (weekday_options() as $weekday => $label) {
            $row = $hours[$weekday] ?? [];
            $isClosed = isset($row['is_closed']) ? 1 : 0;

            $statement->execute([
                'weekday' => $weekday,
                'is_closed' => $isClosed,
                'start_time' => $isClosed ? null : (($row['start_time'] ?? '10:00') . ':00'),
                'end_time' => $isClosed ? null : (($row['end_time'] ?? '22:00') . ':00'),
                'slot_interval' => max(15, (int) ($row['slot_interval'] ?? 30)),
            ]);
        }
    }

    public function availableSlots(string $date): array
    {
        $weekday = weekday_from_date($date);
        $hours = $this->businessHours()[$weekday] ?? null;

        if (!$hours || (int) $hours['is_closed'] === 1 || !$hours['start_time'] || !$hours['end_time']) {
            return [];
        }

        $slots = time_slots_between(
            substr((string) $hours['start_time'], 0, 5),
            substr((string) $hours['end_time'], 0, 5),
            (int) $hours['slot_interval']
        );

        $reserved = $this->appointmentSlots($date);
        $today = date('Y-m-d');
        $currentTime = date('H:i');

        return array_values(array_filter($slots, static function (string $slot) use ($reserved, $date, $today, $currentTime): bool {
            if ($date === $today && $slot <= $currentTime) {
                return false;
            }

            return !in_array($slot, $reserved, true);
        }));
    }

    public function appointmentSlots(string $date): array
    {
        $statement = $this->pdo()->prepare(
            'SELECT DATE_FORMAT(appointment_time, "%H:%i") AS appointment_time
            FROM appointments
            WHERE appointment_date = :appointment_date AND status IN ("pending", "approved")'
        );
        $statement->execute(['appointment_date' => $date]);

        return array_column($statement->fetchAll(), 'appointment_time');
    }

    public function createAppointment(array $payload): int
    {
        $date = trim((string) ($payload['appointment_date'] ?? ''));
        $time = trim((string) ($payload['appointment_time'] ?? ''));

        if (!is_valid_date($date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
            throw new RuntimeException('Randevu tarihi veya saati gecersiz.');
        }

        $pdo = $this->pdo();
        $pdo->beginTransaction();

        try {
            $lockStatement = $pdo->prepare(
                'SELECT id FROM appointments
                WHERE appointment_date = :appointment_date
                  AND appointment_time = :appointment_time
                  AND status IN ("pending", "approved")
                LIMIT 1
                FOR UPDATE'
            );
            $lockStatement->execute([
                'appointment_date' => $date,
                'appointment_time' => $time . ':00',
            ]);

            if ($lockStatement->fetch()) {
                throw new RuntimeException('Secilen saat dilimi bu gun icin musait degil.');
            }

            $statement = $pdo->prepare(
                'INSERT INTO appointments
                (customer_name, customer_phone, customer_email, service_id, therapist_id, appointment_date, appointment_time, notes, admin_notes, status, created_at, updated_at)
                VALUES
                (:customer_name, :customer_phone, :customer_email, :service_id, :therapist_id, :appointment_date, :appointment_time, :notes, "", "pending", NOW(), NOW())'
            );

            $statement->execute([
                'customer_name' => $payload['customer_name'],
                'customer_phone' => $payload['customer_phone'],
                'customer_email' => $payload['customer_email'],
                'service_id' => $payload['service_id'],
                'therapist_id' => $payload['therapist_id'],
                'appointment_date' => $date,
                'appointment_time' => $time . ':00',
                'notes' => $payload['notes'],
            ]);

            $appointmentId = (int) $pdo->lastInsertId();
            $pdo->commit();

            return $appointmentId;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function adminByEmail(string $email): ?array
    {
        $statement = $this->pdo()->prepare('SELECT * FROM admins WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);
        $admin = $statement->fetch();

        return $admin ?: null;
    }

    public function appointmentStats(): array
    {
        $statement = $this->pdo()->query(
            'SELECT
                COUNT(*) AS total,
                SUM(status = "pending") AS pending_count,
                SUM(status = "approved") AS approved_count,
                SUM(status = "completed") AS completed_count
            FROM appointments'
        );

        return $statement->fetch() ?: [];
    }

    public function appointments(?string $status = null): array
    {
        $sql = 'SELECT a.*, s.name AS service_name, t.name AS therapist_name
            FROM appointments a
            LEFT JOIN services s ON s.id = a.service_id
            LEFT JOIN therapists t ON t.id = a.therapist_id';
        $params = [];

        if ($status !== null && $status !== '') {
            $sql .= ' WHERE a.status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC';
        $statement = $this->pdo()->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll();
    }

    public function appointmentById(int $id): ?array
    {
        $statement = $this->pdo()->prepare(
            'SELECT a.*, s.name AS service_name, t.name AS therapist_name
            FROM appointments a
            LEFT JOIN services s ON s.id = a.service_id
            LEFT JOIN therapists t ON t.id = a.therapist_id
            WHERE a.id = :id
            LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $appointment = $statement->fetch();

        return $appointment ?: null;
    }

    public function updateAppointment(array $payload): int
    {
        $allowed = ['pending', 'approved', 'completed', 'cancelled'];
        $status = $payload['status'] ?? 'pending';

        if (!in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        $statement = $this->pdo()->prepare(
            'UPDATE appointments
            SET status = :status, admin_notes = :admin_notes, updated_at = NOW()
            WHERE id = :id'
        );
        $statement->execute([
            'status' => $status,
            'admin_notes' => trim($payload['admin_notes'] ?? ''),
            'id' => (int) $payload['id'],
        ]);

        return (int) $payload['id'];
    }

    public function logNotification(string $channel, string $event, string $recipient, string $subject, string $payload, string $status, string $response): void
    {
        $statement = $this->pdo()->prepare(
            'INSERT INTO notification_logs (channel, event_key, recipient, subject, payload, status, response_text, created_at)
            VALUES (:channel, :event_key, :recipient, :subject, :payload, :status, :response_text, NOW())'
        );
        $statement->execute([
            'channel' => $channel,
            'event_key' => $event,
            'recipient' => $recipient,
            'subject' => $subject,
            'payload' => $payload,
            'status' => $status,
            'response_text' => $response,
        ]);
    }

    public function notificationLogs(int $limit = 20): array
    {
        $statement = $this->pdo()->prepare(
            'SELECT * FROM notification_logs ORDER BY id DESC LIMIT :limit_value'
        );
        $statement->bindValue(':limit_value', $limit, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}
