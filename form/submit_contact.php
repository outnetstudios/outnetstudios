<?php
require_once __DIR__ . '/../src/Repositories/ContactRepository.php';
require_once __DIR__ . '/../src/Services/SmtpMailer.php';
require_once __DIR__ . '/../src/Services/MailService.php';

use App\Services\MailService;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contactRepository = new ContactRepository();
    $mailService = new MailService();

    $data = [
        'nombre_apellido' => trim($_POST['nombre_apellido'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'plan_interes' => trim($_POST['plan_interes'] ?? ''),
        'nombre_empresa' => trim($_POST['nombre_empresa'] ?? ''),
        'sector' => trim($_POST['sector'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
    ];

    try {
        if ($contactRepository->save($data)) {
            $mailService->sendContactNotification($data);
            echo "Datos enviados correctamente y notificación enviada.";
        } else {
            echo "No se pudo guardar la información. Por favor intenta de nuevo.";
        }
    } catch (PDOException $e) {
        error_log('Contact save failed: ' . $e->getMessage());
        echo "No se pudo procesar tu solicitud en este momento.";
    } catch (Exception $e) {
        error_log('Contact notification failed: ' . $e->getMessage());
        echo "No se pudo procesar tu solicitud en este momento.";
    }
}
?>
