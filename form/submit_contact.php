<?php
require_once __DIR__ . '/../src/Repositories/ContactRepository.php';
require_once __DIR__ . '/../src/Services/SmtpMailer.php';
require_once __DIR__ . '/../src/Services/MailService.php';

use App\Services\MailService;

header('Content-Type: application/json; charset=UTF-8');

function normalizePlanInteres(string $value): string
{
    $normalized = strtolower(trim($value));

    $map = [
        'basic' => 'Plan Básico',
        'basico' => 'Plan Básico',
        'básico' => 'Plan Básico',
        'plan basico' => 'Plan Básico',
        'plan básico' => 'Plan Básico',
        'professional' => 'Plan Profesional',
        'profesional' => 'Plan Profesional',
        'plan profesional' => 'Plan Profesional',
        'premium' => 'Plan Premium',
        'plan premium' => 'Plan Premium',
    ];

    return $map[$normalized] ?? $value;
}

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
        'plan_interes' => normalizePlanInteres(trim($_POST['plan_interes'] ?? '')),
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
