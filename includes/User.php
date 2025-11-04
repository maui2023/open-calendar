<?php
require_once 'Database.php';

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getUsers($filters = [])
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'u.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['role'])) {
            $conditions[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $sql = "SELECT u.*, c.name AS country_name 
                FROM users u 
                LEFT JOIN countries c ON u.country_id = c.id
                {$where}
                ORDER BY u.created_at DESC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getUserById($id)
    {
        return $this->db->fetch(
            "SELECT u.*, c.name AS country_name 
             FROM users u 
             LEFT JOIN countries c ON u.country_id = c.id
             WHERE u.id = :id",
            ['id' => $id]
        );
    }

    public function createUser($data)
    {
        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'status' => $data['status'] ?? 'pending',
            'role' => $data['role'] ?? 'user',
            'country_id' => $data['country_id'] ?? null,
            'approved_at' => $data['approved_at'] ?? null,
            'approved_by' => $data['approved_by'] ?? null,
        ];

        return $this->db->insert('users', $payload);
    }

    public function approveUser($id, $approvedBy)
    {
        return $this->db->update(
            'users',
            [
                'status' => 'active',
                'approved_at' => date('Y-m-d H:i:s'),
                'approved_by' => $approvedBy
            ],
            'id = :id',
            ['id' => $id]
        );
    }

    public function disableUser($id, $approvedBy)
    {
        return $this->db->update(
            'users',
            [
                'status' => 'disabled',
                'approved_by' => $approvedBy
            ],
            'id = :id',
            ['id' => $id]
        );
    }

    public function emailExists($email, $excludeId = null)
    {
        $existing = $this->db->fetch(
            "SELECT id FROM users WHERE email = :email" . ($excludeId ? " AND id != :exclude_id" : ""),
            array_filter([
                'email' => $email,
                'exclude_id' => $excludeId
            ], fn($value) => $value !== null)
        );

        return (bool) $existing;
    }

    public function getUserByEmail($email)
    {
        return $this->db->fetch(
            "SELECT * FROM users WHERE email = :email LIMIT 1",
            ['email' => $email]
        );
    }

    public function updateUser($id, $data)
    {
        return $this->db->update('users', $data, 'id = :id', ['id' => $id]);
    }
}
