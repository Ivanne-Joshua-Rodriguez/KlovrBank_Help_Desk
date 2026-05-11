<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['admin', 'support'])) {
    echo json_encode([]); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
$role = $_SESSION['role'];

if ($role === 'admin') {
    $r = $conn->query("SELECT ticket_id, display_id, user_email, subject, category, status, is_escalated, assigned_support_email, content, image_path, created_at FROM tickets ORDER BY created_at DESC");
} else {
    $email = $conn->real_escape_string($_SESSION['email']);
    $r = $conn->query("SELECT ticket_id, display_id, user_email, subject, category, status, is_escalated, assigned_support_email, content, image_path, created_at FROM tickets WHERE status = 'Open' OR assigned_support_email = '$email' ORDER BY is_escalated DESC, created_at DESC");
}

$tickets = [];
while ($row = $r->fetch_assoc()) $tickets[] = $row;
echo json_encode($tickets);
$conn->close();
?>
