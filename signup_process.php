<?php
session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) { echo json_encode(['success' => false, 'error' => 'db']); exit(); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email        = trim($_POST['email'] ?? '');
    $full_account = preg_replace('/\D/', '', $_POST['bank_digits'] ?? '');
    $raw_password = $_POST['password'] ?? '';

    if (strlen($full_account) !== 16) {
        echo json_encode(['success' => false, 'error' => 'invalid_account']); exit();
    }

    if (strlen($raw_password) < 8) {
        echo json_encode(['success' => false, 'error' => 'weak_password']); exit();
    }

    $check_stmt = $conn->prepare("SELECT email, bank_account FROM users WHERE email = ? OR bank_account = ? LIMIT 1");
    $check_stmt->bind_param("ss", $email, $full_account);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $check_stmt->close();
        echo json_encode(['success' => false, 'error' => $row['email'] === $email ? 'email_exists' : 'account_exists']);
        exit();
    }
    $check_stmt->close();

    $password_hash  = password_hash($raw_password, PASSWORD_DEFAULT);
    $bank_account_4 = substr($full_account, -4);

    $stmt = $conn->prepare("INSERT INTO users (email, password_hash, bank_account, bank_account_4, role, is_active, is_verified) VALUES (?, ?, ?, ?, 'user', 1, 0)");
    $stmt->bind_param("ssss", $email, $password_hash, $full_account, $bank_account_4);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'db_error']);
    }
    $stmt->close();
}
$conn->close();