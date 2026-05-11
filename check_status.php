<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");
if (!$conn) { die(json_encode(['error' => 'db_error'])); }

if (!isset($_GET['ticket_id'])) { echo json_encode(['error' => 'not_found']); exit(); }

$ticket_id = mysqli_real_escape_string($conn, $_GET['ticket_id']);

// Determine the email to match against
if (isset($_SESSION['logged_in']) && $_SESSION['role'] === 'user') {
    $user_email = $_SESSION['email'];
} elseif (!empty($_GET['email'])) {
    $user_email = mysqli_real_escape_string($conn, trim($_GET['email']));
} else {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM tickets WHERE display_id = '$ticket_id' AND user_email = '$user_email' LIMIT 1");

if (mysqli_num_rows($result) > 0) {
    $ticket = mysqli_fetch_assoc($result);
    if ($ticket['status'] === 'Open') $ticket['assigned_support_email'] = null;
    echo json_encode($ticket);
} else {
    echo json_encode(['error' => 'not_found']);
}
mysqli_close($conn);
?>
