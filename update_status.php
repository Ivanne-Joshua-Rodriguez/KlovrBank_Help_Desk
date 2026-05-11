<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();
$conn = mysqli_connect("localhost", "root", "", "help_desk_db");

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'support') {
    header("Location: login.php?error=unauthorized");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tid    = mysqli_real_escape_string($conn, $_POST['ticket_id']);
    $status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $agent  = mysqli_real_escape_string($conn, $_SESSION['email']);

    // Block changes if ticket is already Resolved
    $check = mysqli_query($conn, "SELECT status FROM tickets WHERE ticket_id = '$tid' LIMIT 1");
    $current = mysqli_fetch_assoc($check);
    if (!$current || $current['status'] === 'Resolved') {
        header("Location: support_dashboard.php");
        exit();
    }

    if ($status === 'Open') {
        $sql = "UPDATE tickets SET status = '$status', assigned_support_email = NULL WHERE ticket_id = '$tid'";
    } elseif ($status === 'Resolved') {
        // Clear escalation flag when resolving
        $sql = "UPDATE tickets SET status = '$status', assigned_support_email = '$agent', is_escalated = 0 WHERE ticket_id = '$tid'";
    } else {
        $sql = "UPDATE tickets SET status = '$status', assigned_support_email = '$agent' WHERE ticket_id = '$tid'";
    }
    mysqli_query($conn, $sql);
    header("Location: support_dashboard.php");
    exit();
}
?>