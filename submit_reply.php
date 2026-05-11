<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user', 'support', 'admin'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_id = mysqli_real_escape_string($conn, $_POST['display_id'] ?? '');
    $message    = mysqli_real_escape_string($conn, trim($_POST['message'] ?? ''));
    $user_email = $_SESSION['email'];

    if (!$display_id || !$message) {
        echo json_encode(['success' => false, 'error' => 'missing_fields']);
        exit();
    }

    $role = $_SESSION['role'];

    // Users can only reply to their own tickets
    if ($role === 'user') {
        $check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$display_id' AND user_email = '$user_email' LIMIT 1");
        if (mysqli_num_rows($check) === 0) {
            echo json_encode(['success' => false, 'error' => 'not_found']);
            exit();
        }
    } else {
        $check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$display_id' LIMIT 1");
        if (mysqli_num_rows($check) === 0) {
            echo json_encode(['success' => false, 'error' => 'not_found']);
            exit();
        }
    }

    $sql = "INSERT INTO ticket_replies (display_id, sender_email, sender_role, message) VALUES ('$display_id', '$user_email', '$role', '$message')";
    if (mysqli_query($conn, $sql)) {
        if ($role === 'support' || $role === 'admin') {
            $support_email = mysqli_real_escape_string($conn, $user_email);
            mysqli_query($conn, "UPDATE tickets SET status = 'On-Going', assigned_support_email = '$support_email' WHERE display_id = '$display_id' AND status IN ('Open', 'Under Review')");
            mysqli_query($conn, "UPDATE tickets SET assigned_support_email = '$support_email' WHERE display_id = '$display_id' AND assigned_support_email IS NULL");
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'db_error']);
    }
}
mysqli_close($conn);
?>
