<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false]); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
$user_id   = (int)($_POST['user_id'] ?? 0);
$new_status = $_POST['new_status'] ?? '';

if (!$user_id || !in_array($new_status, ['Active', 'Inactive', 'Deactivated'])) {
    echo json_encode(['success' => false, 'error' => 'invalid']); exit();
}

// Map status to is_active flag
$is_active = $new_status === 'Active' ? 1 : 0;

$stmt = $conn->prepare("UPDATE users SET status = ?, is_active = ? WHERE id = ? AND role = 'user'");
$stmt->bind_param("sii", $new_status, $is_active, $user_id);
$stmt->execute();
echo json_encode(['success' => $stmt->affected_rows > 0]);
$stmt->close();
$conn->close();
