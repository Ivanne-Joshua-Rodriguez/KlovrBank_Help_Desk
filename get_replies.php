<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");
$display_id = mysqli_real_escape_string($conn, $_GET['display_id'] ?? '');
if (!$display_id) { echo json_encode([]); exit(); }

if (!isset($_SESSION['logged_in'])) {
    // Guest: verify ownership via email param
    if (empty($_GET['email'])) { echo json_encode(['error' => 'unauthorized']); exit(); }
    $guest_email = mysqli_real_escape_string($conn, trim($_GET['email']));
    $check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$display_id' AND user_email = '$guest_email' LIMIT 1");
    if (mysqli_num_rows($check) === 0) { echo json_encode(['error' => 'forbidden']); exit(); }
} elseif (!in_array($_SESSION['role'], ['user', 'support', 'admin'])) {
    echo json_encode(['error' => 'unauthorized']); exit();
} elseif ($_SESSION['role'] === 'user') {
    $user_email = $_SESSION['email'];
    $check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$display_id' AND user_email = '$user_email' LIMIT 1");
    if (mysqli_num_rows($check) === 0) { echo json_encode(['error' => 'forbidden']); exit(); }
}

$result = mysqli_query($conn, "SELECT sender_email, sender_role, message, created_at FROM ticket_replies WHERE display_id = '$display_id' ORDER BY created_at ASC");
$replies = [];
while ($row = mysqli_fetch_assoc($result)) $replies[] = $row;
echo json_encode($replies);
mysqli_close($conn);
?>
