<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) { echo json_encode(['success' => false, 'error' => 'db']); exit(); }

$action  = $_POST['action']  ?? '';
$user_id = (int)($_POST['user_id'] ?? 0);

if (!$user_id || !in_array($action, ['verify', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'invalid']); exit();
}

if ($action === 'verify') {
    $stmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ? AND role = 'user'");
} else {
    // reject = delete the unverified account
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user' AND is_verified = 0");
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
echo json_encode(['success' => $stmt->affected_rows > 0]);
$stmt->close();
$conn->close();
