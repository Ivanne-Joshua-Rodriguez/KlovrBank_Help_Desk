<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");
$email = $_SESSION['email'];

$result = mysqli_query($conn, "SELECT display_id, subject, status FROM tickets WHERE user_email = '$email' ORDER BY created_at DESC");

$tickets = [];
while ($row = mysqli_fetch_assoc($result)) {
    $tickets[] = $row;
}

echo json_encode($tickets);
mysqli_close($conn);
?>
