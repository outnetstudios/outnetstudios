<?php

namespace App\Services;

class MailService
{
    private ?SmtpMailer $mailer = null;
    private array $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/env.php';
        $this->config = $config['mail'] ?? [];

        if (!empty($this->config['smtp_host'])) {
            $this->mailer = new SmtpMailer(
                $this->config['smtp_host'], 
                (int)$this->config['smtp_port'], 
                $this->config['smtp_user'], 
                $this->config['smtp_pass'],
                $this->config['from_name'] ?? 'Outnet Studios'
            );
        }
    }

    public function send(string $toEmail, string $subject, string $htmlBody, string $textBody = null): bool
    {
        $fromEmail = $this->config['from_email'];
        if (empty($fromEmail)) {
            throw new \RuntimeException('La dirección FROM no está configurada.');
        }

        if (!empty($this->config['mailjet_api_key']) && !empty($this->config['mailjet_secret_key'])) {
            return $this->sendViaMailjet($fromEmail, $toEmail, $subject, $htmlBody, $textBody);
        }

        if ($this->mailer) {
            return $this->mailer->send($fromEmail, $toEmail, $subject, $htmlBody, $textBody);
        }

        throw new \RuntimeException('No hay una configuración de transporte de correo válida.');
    }

    private function sendViaMailjet(string $fromEmail, string $toEmail, string $subject, string $htmlBody, string $textBody = null): bool
    {
        $textBody = $textBody ?? strip_tags($htmlBody);

        $payload = [
            'Messages' => [[
                'From' => [
                    'Email' => $fromEmail,
                    'Name' => $this->config['from_name'] ?? 'Outnet Studios',
                ],
                'To' => [[
                    'Email' => $toEmail,
                ]],
                'Subject' => $subject,
                'TextPart' => $textBody,
                'HTMLPart' => $htmlBody,
            ]],
        ];

        $authHeader = 'Authorization: Basic ' . base64_encode($this->config['mailjet_api_key'] . ':' . $this->config['mailjet_secret_key']);
        $headers = [
            'Content-Type: application/json',
            $authHeader,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init('https://api.mailjet.com/v3.1/send');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_POSTFIELDS => json_encode($payload),
            ]);

            $response = curl_exec($ch);
            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException('Error Mailjet cURL: ' . $error);
            }

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        } elseif (ini_get('allow_url_fopen')) {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => json_encode($payload),
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);

            $response = @file_get_contents('https://api.mailjet.com/v3.1/send', false, $context);
            if ($response === false) {
                $error = error_get_last()['message'] ?? 'Error desconocido al enviar Mailjet';
                throw new \RuntimeException('Error Mailjet HTTP: ' . $error);
            }

            $statusCode = null;
            if (isset($http_response_header) && preg_match('/HTTP\/\S+\s(\d+)/', $http_response_header[0], $matches)) {
                $statusCode = (int)$matches[1];
            }
        } else {
            if ($this->mailer) {
                return $this->mailer->send($fromEmail, $toEmail, $subject, $htmlBody, $textBody);
            }

            throw new \RuntimeException('No hay método HTTP disponible para enviar correo.');
        }

        if ($statusCode !== 200) {
            throw new \RuntimeException('Mailjet API HTTP ' . $statusCode . ': ' . $response);
        }

        $decoded = json_decode($response, true);
        if (!isset($decoded['Messages'][0]['Status']) || $decoded['Messages'][0]['Status'] !== 'success') {
            throw new \RuntimeException('Mailjet API error: ' . $response);
        }

        return true;
    }

    public function sendContactNotification(array $contactData): bool
    {
        $adminEmail = $this->config['admin_email'] ?? null;
        if (empty($adminEmail)) {
            throw new \RuntimeException('El correo de notificación administrativa no está configurado.');
        }

        $subject = 'Nuevo contacto desde Outnet Studios';
        $htmlBody = '<h1>Nuevo prospecto</h1>' .
            '<p><strong>Nombre:</strong> ' . htmlspecialchars($contactData['nombre_apellido'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Email:</strong> ' . htmlspecialchars($contactData['email'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Teléfono:</strong> ' . htmlspecialchars($contactData['telefono'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Plan de Interés:</strong> ' . htmlspecialchars($contactData['plan_interes'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Empresa:</strong> ' . htmlspecialchars($contactData['nombre_empresa'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Sector:</strong> ' . htmlspecialchars($contactData['sector'], ENT_QUOTES, 'UTF-8') . '</p>' .
            '<p><strong>Descripción:</strong> ' . nl2br(htmlspecialchars($contactData['descripcion'], ENT_QUOTES, 'UTF-8')) . '</p>';

        $textBody = "Nuevo prospecto\n" .
            "Nombre: {$contactData['nombre_apellido']}\n" .
            "Email: {$contactData['email']}\n" .
            "Teléfono: {$contactData['telefono']}\n" .
            "Plan de Interés: {$contactData['plan_interes']}\n" .
            "Empresa: {$contactData['nombre_empresa']}\n" .
            "Sector: {$contactData['sector']}\n" .
            "Descripción: {$contactData['descripcion']}\n";

        return $this->send($adminEmail, $subject, $htmlBody, $textBody);
    }

    public function sendPasswordReset(string $email, string $resetLink): bool
    {
        $subject = 'Restablece tu contraseña';
        $htmlBody = '<h1>Restablecer contraseña</h1>' .
            '<p>Hemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace:</p>' .
            '<p><a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">Restablecer contraseña</a></p>' .
            '<p>Si no solicitaste este cambio, puedes ignorar este correo.</p>';

        $textBody = "Restablecer contraseña\n" .
            "Entra al siguiente enlace: {$resetLink}\n" .
            "Si no solicitaste esto, ignora este mensaje.";

        return $this->send($email, $subject, $htmlBody, $textBody);
    }
}
