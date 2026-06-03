<?php
require_once __DIR__ . '/../Database.php';

class ContactRepository
{
    private $connection;

    public function __construct()
    {
        $this->connection = Database::getConnection();
    }

    public function save(array $data): bool
    {
        $query = 'INSERT INTO contacto (nombre_apellido, email, telefono, plan_interes, nombre_empresa, sector, descripcion) VALUES (:nombre_apellido, :email, :telefono, :plan_interes, :nombre_empresa, :sector, :descripcion)';
        $stmt = $this->connection->prepare($query);

        return $stmt->execute([
            ':nombre_apellido' => $data['nombre_apellido'],
            ':email' => $data['email'],
            ':telefono' => $data['telefono'],
            ':plan_interes' => $data['plan_interes'],
            ':nombre_empresa' => $data['nombre_empresa'],
            ':sector' => $data['sector'],
            ':descripcion' => $data['descripcion'],
        ]);
    }

    public function getAll()
    {
        return $this->connection->query('SELECT * FROM contacto ORDER BY fecha_envio DESC');
    }
}
