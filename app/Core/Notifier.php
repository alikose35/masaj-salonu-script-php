<?php

declare(strict_types=1);

class Notifier
{
    public static function notifyAppointmentCreated(int $appointmentId): void
    {
        $appointment = db()->appointmentById($appointmentId);
        if (!$appointment) {
            return;
        }

        self::sendForEvent('appointment_created', $appointment);
    }

    public static function notifyAppointmentUpdated(int $appointmentId): void
    {
        $appointment = db()->appointmentById($appointmentId);
        if (!$appointment) {
            return;
        }

        self::sendForEvent('appointment_updated', $appointment);
    }

    public static function sendTest(array $payload): array
    {
        $settings = array_merge(db()->defaultSettings(), app_settings());
        $results = [];
        $message = trim($payload['message'] ?? '');

        if ($message === '') {
            throw new RuntimeException('Test mesaji bos olamaz.');
        }

        if (($payload['test_channel'] ?? '') === 'email') {
            $to = trim($payload['test_email'] ?? '');
            if ($to === '') {
                throw new RuntimeException('Test e-posta adresi gerekli.');
            }

            $subject = replace_notification_tokens(setting($settings, 'smtp_test_subject', 'SMTP test bildirimi'), [
                'message' => $message,
            ]);
            $body = replace_notification_tokens(setting($settings, 'smtp_test_template', "Merhaba,\n\n{message}\n"), [
                'message' => $message,
            ]);

            $results[] = self::sendEmail($settings, $to, $subject, $body, 'smtp_test');
        } elseif (($payload['test_channel'] ?? '') === 'sms') {
            $phone = trim($payload['test_phone'] ?? '');
            if ($phone === '') {
                throw new RuntimeException('Test telefon numarasi gerekli.');
            }

            $results[] = self::sendSms($settings, $phone, $message, 'sms_test');
        } else {
            throw new RuntimeException('Gecersiz test kanali.');
        }

        return $results;
    }

    private static function sendForEvent(string $event, array $appointment): void
    {
        $settings = array_merge(db()->defaultSettings(), app_settings());
        $tokens = appointment_tokens($appointment, $settings);

        if (setting($settings, 'smtp_enabled') === '1' && $appointment['customer_email']) {
            $subjectKey = $event === 'appointment_created' ? 'smtp_subject_created' : 'smtp_subject_updated';
            $bodyKey = $event === 'appointment_created' ? 'smtp_template_created' : 'smtp_template_updated';
            $subject = replace_notification_tokens(setting($settings, $subjectKey), $tokens);
            $body = replace_notification_tokens(setting($settings, $bodyKey), $tokens);
            self::sendEmail($settings, (string) $appointment['customer_email'], $subject, $body, $event);
        }

        if (setting($settings, 'sms_enabled') === '1' && $appointment['customer_phone']) {
            $messageKey = $event === 'appointment_created' ? 'sms_template_created' : 'sms_template_updated';
            $message = replace_notification_tokens(setting($settings, $messageKey), $tokens);
            self::sendSms($settings, (string) $appointment['customer_phone'], $message, $event);
        }
    }

    private static function sendEmail(array $settings, string $to, string $subject, string $body, string $event): array
    {
        $client = new SimpleSmtpClient(
            setting($settings, 'smtp_host'),
            (int) setting($settings, 'smtp_port', '587'),
            setting($settings, 'smtp_encryption', 'tls'),
            setting($settings, 'smtp_username'),
            setting($settings, 'smtp_password')
        );

        $fromEmail = setting($settings, 'smtp_from_email', setting($settings, 'contact_email'));
        $fromName = setting($settings, 'smtp_from_name', setting($settings, 'site_name'));

        $result = $client->send($fromEmail, $fromName, $to, $subject, nl2br($body), $body);
        db()->logNotification('email', $event, $to, $subject, $body, $result['success'] ? 'success' : 'failed', $result['response']);

        return $result;
    }

    private static function sendSms(array $settings, string $phone, string $message, string $event): array
    {
        $endpoint = setting($settings, 'sms_api_url');
        if ($endpoint === '') {
            throw new RuntimeException('SMS API URL tanimlanmamis.');
        }

        $method = strtoupper(setting($settings, 'sms_method', 'POST'));
        $headersRaw = trim(setting($settings, 'sms_headers'));
        $payloadTemplate = setting($settings, 'sms_body_template', '{"phone":"{phone}","message":"{message}"}');
        $replacements = [
            'phone' => $phone,
            'message' => $message,
        ];

        $headers = [];
        if ($headersRaw !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $headersRaw) as $line) {
                $line = trim(replace_notification_tokens($line, $replacements));
                if ($line !== '') {
                    $headers[] = $line;
                }
            }
        }

        $body = replace_notification_tokens($payloadTemplate, $replacements);
        $context = [
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
                'timeout' => 15,
            ],
        ];

        if ($method !== 'GET') {
            $context['http']['content'] = $body;
        } else {
            $queryGlue = str_contains($endpoint, '?') ? '&' : '?';
            $endpoint .= $queryGlue . ltrim(replace_notification_tokens(setting($settings, 'sms_query_template', 'phone={phone}&message={message}'), $replacements), '?');
        }

        $response = @file_get_contents($endpoint, false, stream_context_create($context));
        $success = $response !== false;
        $responseText = $response === false ? 'SMS istegi basarisiz.' : $response;
        db()->logNotification('sms', $event, $phone, 'SMS', $message, $success ? 'success' : 'failed', $responseText);

        return [
            'success' => $success,
            'response' => $responseText,
        ];
    }
}

class SimpleSmtpClient
{
    private string $host;
    private int $port;
    private string $encryption;
    private string $username;
    private string $password;

    public function __construct(string $host, int $port, string $encryption, string $username, string $password)
    {
        $this->host = $host;
        $this->port = $port;
        $this->encryption = strtolower($encryption);
        $this->username = $username;
        $this->password = $password;
    }

    public function send(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody, string $textBody): array
    {
        if ($this->host === '' || $fromEmail === '' || $toEmail === '') {
            throw new RuntimeException('SMTP ayarlari eksik.');
        }

        $transport = $this->encryption === 'ssl' ? 'ssl://' . $this->host : $this->host;
        $socket = @stream_socket_client($transport . ':' . $this->port, $errno, $errstr, 20);
        if (!$socket) {
            throw new RuntimeException('SMTP baglantisi kurulamadi: ' . $errstr);
        }

        stream_set_timeout($socket, 20);

        $this->expect($socket, [220]);
        $this->command($socket, 'EHLO localhost', [250]);

        if ($this->encryption === 'tls') {
            $this->command($socket, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('TLS oturumu baslatilamadi.');
            }
            $this->command($socket, 'EHLO localhost', [250]);
        }

        if ($this->username !== '') {
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($this->username), [334]);
            $this->command($socket, base64_encode($this->password), [235]);
        }

        $this->command($socket, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251]);
        $this->command($socket, 'DATA', [354]);

        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = [
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'To: <' . $toEmail . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $textBody . "\r\n";
        $message .= '--' . $boundary . "\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $htmlBody . "\r\n";
        $message .= '--' . $boundary . "--\r\n.\r\n";

        fwrite($socket, $message);
        $response = $this->expect($socket, [250]);
        $this->command($socket, 'QUIT', [221]);
        fclose($socket);

        return [
            'success' => true,
            'response' => $response,
        ];
    }

    private function command($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCodes);
    }

    private function expect($socket, array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('SMTP hata cevabi: ' . trim($response));
        }

        return trim($response);
    }
}
