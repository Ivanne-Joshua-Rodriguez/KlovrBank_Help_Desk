<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false]); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) { echo json_encode(['success' => false, 'error' => 'missing_fields']); exit(); }

    $conn = new mysqli("localhost", "root", "", "help_desk_db");

    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $check->close(); $conn->close();
        echo json_encode(['success' => false, 'error' => 'email_exists']); exit();
    }
    $check->close();

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $bank_account = '0000000000000000';
    $bank_account_4 = '0000';

    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, bank_account, bank_account_4, role, is_active, is_verified) VALUES (?, ?, ?, ?, 'support', 1, 1)");
    $stmt->bind_param("ssss", $email, $hash, $bank_account, $bank_account_4);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => $ok]);
} else {
    echo json_encode(['success' => false]);
}
