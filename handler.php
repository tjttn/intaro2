<?php

require 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

date_default_timezone_set("Europe/Moscow");
header("Content-Type: application/json");

// Подключение к БД
$mysqli = new mysqli("127.0.0.1", "root", "", "test");

if ($mysqli->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Ошибка подключения к БД: " . $mysqli->connect_error
    ]);
    exit;
}

$fio = htmlspecialchars($_POST['fio'] ?? '');
$email = htmlspecialchars($_POST['email'] ?? '');
$phone = htmlspecialchars($_POST['phone'] ?? '');
$comment = htmlspecialchars($_POST['comment'] ?? '');

if (empty($fio) || empty($email) || empty($phone) || empty($comment)) {
    echo json_encode([
        "status" => "error",
        "message" => "Все поля обязательны для заполнения"
    ]);
    exit;
}

$parts = explode(" ", $fio);
$surname = isset($parts[0]) ? $parts[0] : "";
$name = isset($parts[1]) ? $parts[1] : "";
$patronymic = isset($parts[2]) ? $parts[2] : "";

$email_escaped = $mysqli->real_escape_string($email);
$res = $mysqli->query("SELECT created_at FROM requests WHERE email='$email_escaped' ORDER BY id DESC LIMIT 1");

if ($row = $res->fetch_assoc()) {
    $last = strtotime($row['created_at']);
    $now = time();

    if ($now - $last < 3600) {
        $wait = 3600 - ($now - $last);
        $minutes = ceil($wait / 60);
        $seconds = $wait % 60;

        echo json_encode([
            "status" => "error",
            "message" => "Попробуйте снова через " . $minutes . " мин. " . $seconds . " сек."
        ]);
        exit;
    }
}

$fio_escaped = $mysqli->real_escape_string($fio);
$phone_escaped = $mysqli->real_escape_string($phone);
$comment_escaped = $mysqli->real_escape_string($comment);

$mysqli->query("
    INSERT INTO requests (fio, email, phone, comment, created_at)
    VALUES ('$fio_escaped', '$email_escaped', '$phone_escaped', '$comment_escaped', NOW())
");

$time = date("H:i:s d.m.Y", time() + 5400);

// Отправка через Symfony Mailer
$mail_sent = false;
$mail_error = "";

try {
    $dsn = 'smtp://mihazdan2004@gmail.com:jzztwsucslctonjw@smtp.gmail.com:587?encryption=tls&verify_peer=0';
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);
    
    $email_msg = (new Email())
        ->from('mihazdan2004@gmail.com')
        ->to('misha.zhdanov.2004@bk.ru')
        ->subject('🍑 Новая заявка с сайта')
        ->html("
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #FFF5E6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #FFB347 0%, #FF8C42 100%); color: white; padding: 30px; text-align: center; border-radius: 15px 15px 0 0; }
                    .content { background: white; padding: 30px; border-radius: 0 0 15px 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
                    .field { margin-bottom: 20px; padding: 15px; background: #FFF5E6; border-radius: 10px; border-left: 4px solid #FF8C42; }
                    .label { font-weight: bold; color: #FF8C42; display: block; margin-bottom: 8px; font-size: 14px; }
                    .value { color: #6B4E3D; font-size: 16px; }
                    .footer { text-align: center; margin-top: 20px; padding: 15px; color: #FFB347; font-size: 12px; background: #FFF5E6; border-radius: 10px; }
                    h1 { margin: 0; font-size: 24px; }
                    .emoji { font-size: 32px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <div class='emoji'>🍑</div>
                        <h1>Новая заявка с сайта</h1>
                    </div>
                    <div class='content'>
                        <div class='field'>
                            <div class='label'>👤 ФИО</div>
                            <div class='value'>$fio</div>
                        </div>
                        <div class='field'>
                            <div class='label'>📧 Email</div>
                            <div class='value'>$email</div>
                        </div>
                        <div class='field'>
                            <div class='label'>📞 Телефон</div>
                            <div class='value'>$phone</div>
                        </div>
                        <div class='field'>
                            <div class='label'>💬 Комментарий</div>
                            <div class='value'>$comment</div>
                        </div>
                        <div class='field'>
                            <div class='label'>⏰ Время отправки</div>
                            <div class='value'>$time</div>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>🍑 Это письмо сгенерировано автоматически. Пожалуйста, не отвечайте на него.</p>
                        <p style='margin-top: 10px;'>Спасибо за обращение! 🍑</p>
                    </div>
                </div>
            </body>
            </html>
        ")
        ->text("🍑 Новая заявка с сайта\n\nФИО: $fio\nEmail: $email\nТелефон: $phone\nКомментарий: $comment\nВремя отправки: $time\n\nСпасибо за обращение!");
    
    $mailer->send($email_msg);
    $mail_sent = true;
    
} catch (Exception $e) {
    $mail_sent = false;
    $mail_error = $e->getMessage();
}

echo json_encode([
    "status" => "ok",
    "name" => $name,
    "surname" => $surname,
    "patronymic" => $patronymic,
    "email" => $email,
    "phone" => $phone,
    "time" => $time,
    "mail_sent" => $mail_sent,
    "mail_error" => $mail_error
]);

$mysqli->close();
?>
