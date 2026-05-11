<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");
$display_id = mysqli_real_escape_string($conn, $_POST['display_id'] ?? '');
$user_email = mysqli_real_escape_string($conn, $_SESSION['email']);

if (!$display_id) {
    echo json_encode(['success' => false, 'error' => 'missing_fields']);
    exit();
}

// Verify ticket belongs to this user and is not already resolved
$check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$display_id' AND user_email = '$user_email' AND status != 'Resolved' LIMIT 1");
if (mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'error' => 'not_found']);
    exit();
}

mysqli_query($conn, "UPDATE tickets SET status = 'Resolved', is_escalated = 0 WHERE display_id = '$display_id' AND user_email = '$user_email'");

// Send thank-you auto-message as the assigned support agent
$ticket = mysqli_fetch_assoc(mysqli_query($conn, "SELECT assigned_support_email FROM tickets WHERE display_id = '$display_id' LIMIT 1"));
$support_email = mysqli_real_escape_string($conn, $ticket['assigned_support_email'] ?? 'support');
$thank_you = mysqli_real_escape_string($conn, 'Thank you for choosing KlovrBank!');
mysqli_query($conn, "INSERT INTO ticket_replies (display_id, sender_email, sender_role, message) VALUES ('$display_id', '$support_email', 'support', '$thank_you')");

echo json_encode(['success' => true]);
mysqli_close($conn);
?>
