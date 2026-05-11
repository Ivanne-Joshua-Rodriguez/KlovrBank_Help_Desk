<?php
session_start();

$host   = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false
         ? 'localhost' : 'localhost';
$dbuser = $host === 'localhost' ? 'root'         : 'root';
$dbpass = $host === 'localhost' ? ''             : '';
$dbname = $host === 'localhost' ? 'help_desk_db' : 'help_desk_db';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

$token            = trim($_POST['token'] ?? '');
$password         = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

if (!$token || !$password || !$confirm_password) {
    header("Location: forgot_password.php?error=emptyfields"); exit();
}

if (strlen($password) < 6) {
    header("Location: reset_password.php?token=" . urlencode($token) . "&error=short"); exit();
}

if ($password !== $confirm_password) {
    header("Location: reset_password.php?token=" . urlencode($token) . "&error=mismatch"); exit();
}

$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: forgot_password.php?error=expired"); exit();
}

$email = $row['email'];
$hash  = password_hash($password, PASSWORD_DEFAULT);

$upd = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ? AND role = 'user'");
$upd->bind_param("ss", $hash, $email);
$upd->execute();
$upd->close();

$mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
$mark->bind_param("s", $token);
$mark->execute();
$mark->close();

$conn->close();

header("Location: login.php?reset=success"); exit();
