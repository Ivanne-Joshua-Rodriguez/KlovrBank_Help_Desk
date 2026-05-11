<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'support') {
    echo json_encode(['success' => false]);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");
$display_id = mysqli_real_escape_string($conn, $_POST['display_id']);
$agent_email = mysqli_real_escape_string($conn, $_SESSION['email']);

// Only claim if status is Open (don't overwrite an already-claimed ticket)
$sql = "UPDATE tickets SET status = 'Under Review', assigned_support_email = '$agent_email'
        WHERE display_id = '$display_id' AND status = 'Open'";
mysqli_query($conn, $sql);

echo json_encode(['success' => true]);
mysqli_close($conn);
?>
