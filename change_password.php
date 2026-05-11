<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';

if (!$old || !$new) {
    echo json_encode(['success' => false, 'error' => 'All fields are required.']); exit();
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters.']); exit();
}

$stmt = $conn->prepare("SELECT password_hash FROM users WHERE email = ? AND role = 'user' LIMIT 1");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($old, $row['password_hash'])) {
    echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']); exit();
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$upd  = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ? AND role = 'user'");
$upd->bind_param("ss", $hash, $_SESSION['email']);
$upd->execute();
echo json_encode(['success' => true]);
$upd->close();
$conn->close();
