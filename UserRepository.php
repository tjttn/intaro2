<?php
namespace App\Repository;

use App\Model\User;
use PDO;

class UserRepository
{
    private PDO $connection;
    
    public function __construct()
    {
        $this->connection = new PDO("sqlite:" . __DIR__ . "/../../database.sqlite");
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->initTable();
    }
    
    private function initTable(): void
    {
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL
            )
        ");
        
        $stmt = $this->connection->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->connection->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)");
            $stmt->execute([$password]);
        }
    }
    
    public function findByUsername(string $username): ?User
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $user = new User();
            $user->setId($data['id']);
            $user->setUsername($data['username']);
            $user->setPassword($data['password']);
            return $user;
        }
        return null;
    }
}
