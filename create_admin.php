<?php
$dbPath = __DIR__ . '/database.sqlite';
$pdo = new PDO("sqlite:$dbPath");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Пересоздаем таблицу
$pdo->exec("DROP TABLE IF EXISTS users");
$pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL
    )
");

// Создаем админа с правильным паролем
$password = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES ('admin', ?)");
$stmt->execute([$password]);

echo "Пользователь создан!\n";
echo "Логин: admin\n";
echo "Пароль: admin123\n";
echo "Хеш пароля: " . $password . "\n";

// Проверяем
$stmt = $pdo->query("SELECT * FROM users");
$user = $stmt->fetch(PDO::FETCH_ASSOC);
echo "\nПроверка:\n";
echo "ID: " . $user['id'] . "\n";
echo "Username: " . $user['username'] . "\n";
echo "Password verify: " . (password_verify('admin123', $user['password']) ? "OK" : "FAIL") . "\n";
?>
