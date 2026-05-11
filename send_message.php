<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['admin','support'])) {
    echo json_encode(['success' => false]); exit();
}

$conn          = new mysqli("localhost", "root", "", "help_desk_db");
$msg           = trim($_POST['message'] ?? '');
$receiver_email = trim($_POST['receiver_email'] ?? '');
$sender_role   = $_SESSION['role'];
$receiver_role = $sender_role === 'admin' ? 'support' : 'admin';

if (!$msg) { echo json_encode(['success' => false]); exit(); }

// If support is sending, look up the admin email from the DB
if ($sender_role === 'support') {
    $r = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
    $row = $r ? $r->fetch_assoc() : null;
    if (!$row) { echo json_encode(['success' => false, 'error' => 'No admin found']); exit(); }
    $receiver_email = $row['email'];
}

if (!$receiver_email) { echo json_encode(['success' => false]); exit(); }

$stmt = $conn->prepare("INSERT INTO internal_messages (sender_email, sender_role, receiver_role, receiver_email, message) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $_SESSION['email'], $sender_role, $receiver_role, $receiver_email, $msg);
$stmt->execute();
echo json_encode(['success' => true]);
$stmt->close();
$conn->close();
?>
