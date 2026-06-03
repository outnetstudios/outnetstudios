<?php

namespace App\Services;

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $senderName;
    private $socket;
    private string $newline = "\r\n";

    public function __construct(string $host, int $port, string $username, string $password, string $senderName = 'Website')
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->senderName = $senderName;
    }

    public function send(string $fromEmail, string $toEmail, string $subject, string $htmlBody, string $textBody = null): bool
    {
        $textBody = $textBody ?? strip_tags($htmlBody);
        $this->connect();
        $this->sendCommand("EHLO {$this->host}", [250]);
        $this->sendCommand('STARTTLS', [220]);

        if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new \RuntimeException('No se pudo habilitar TLS en el socket SMTP.');
        }

        $this->sendCommand("EHLO {$this->host}", [250]);
        $this->sendCommand('AUTH LOGIN', [334]);
        $this->sendCommand(base64_encode($this->username), [334]);
        $this->sendCommand(base64_encode($this->password), [235]);
        $this->sendCommand("MAIL FROM:<{$fromEmail}>", [250]);
        $this->sendCommand("RCPT TO:<{$toEmail}>", [250, 251]);
        $this->sendCommand('DATA', [354]);

        $boundary = '==boundary_' . md5(uniqid((string)rand(), true));
        $headers = [];
        $headers[] = "From: {$this->senderName} <{$fromEmail}>";
        $headers[] = "To: <{$toEmail}>";
        $headers[] = "Subject: {$subject}";
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";

        $message = implode($this->newline, $headers) . $this->newline . $this->newline;
        $message .= "--{$boundary}{$this->newline}";
        $message .= "Content-Type: text/plain; charset=UTF-8{$this->newline}";
        $message .= "Content-Transfer-Encoding: 7bit{$this->newline}{$this->newline}";
        $message .= $textBody . $this->newline . $this->newline;
        $message .= "--{$boundary}{$this->newline}";
        $message .= "Content-Type: text/html; charset=UTF-8{$this->newline}";
        $message .= "Content-Transfer-Encoding: 7bit{$this->newline}{$this->newline}";
        $message .= $htmlBody . $this->newline . $this->newline;
        $message .= "--{$boundary}--{$this->newline}.{$this->newline}";

        $this->sendRaw($message);
        $this->sendCommand('', [250]);
        $this->sendCommand('QUIT', [221]);
        fclose($this->socket);

        return true;
    }

    private function connect(): void
    {
        $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, 30);
        if (!is_resource($this->socket)) {
            throw new \RuntimeException("No se pudo conectar al servidor SMTP: {$errstr} ({$errno})");
        }

        stream_set_timeout($this->socket, 30);
        $this->getResponse([220]);
    }

    private function sendCommand(string $command, array $expectedCodes): string
    {
        if ($command !== '') {
            fwrite($this->socket, $command . $this->newline);
        }

        return $this->getResponse($expectedCodes);
    }

    private function sendRaw(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function getResponse(array $expectedCodes): string
    {
        $response = ''; 
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new \RuntimeException("Respuesta SMTP inesperada: {$response}");
        }

        return $response;
    }
}
