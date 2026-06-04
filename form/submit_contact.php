<?php
require_once __DIR__ . '/../src/Repositories/ContactRepository.php';
require_once __DIR__ . '/../src/Services/SmtpMailer.php';
require_once __DIR__ . '/../src/Services/MailService.php';
require_once __DIR__ . '/../includes/plan_helpers.php';

use App\Services\MailService;

header('Content-Type: application/json; charset=UTF-8');

$response = [
    'success' => false,
    'message' => 'No se pudo procesar tu solicitud en este momento.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contactRepository = new ContactRepository();
    $mailService = new MailService();

    $data = [
        'nombre_apellido' => trim($_POST['nombre_apellido'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'telefono' => trim($_POST['telefono'] ?? ''),
        'plan_interes' => normalizePlanInteresValue(trim($_POST['plan_interes'] ?? '')),
        'nombre_empresa' => trim($_POST['nombre_empresa'] ?? ''),
        'sector' => trim($_POST['sector'] ?? ''),
        'descripcion' => trim($_POST['descripcion'] ?? ''),
    ];

    try {
        if ($contactRepository->save($data)) {
            $mailService->sendContactNotification($data);
            $response = [
                'success' => true,
                'message' => 'Tus datos fueron enviados correctamente. Gracias por contactarnos.',
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No se pudo guardar la información. Por favor intenta de nuevo.',
            ];
        }
    } catch (PDOException $e) {
        error_log('Contact save failed: ' . $e->getMessage());
    } catch (Exception $e) {
        error_log('Contact notification failed: ' . $e->getMessage());
    }
}

echo json_encode($response);
