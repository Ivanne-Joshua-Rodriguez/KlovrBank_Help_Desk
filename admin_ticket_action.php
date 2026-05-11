<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) { echo json_encode(['success' => false, 'error' => 'db']); exit(); }

$action     = $_POST['action'] ?? '';
$display_id = $conn->real_escape_string($_POST['display_id'] ?? '');

switch ($action) {

    case 'delete':
        $check = $conn->query("SELECT status FROM tickets WHERE display_id = '$display_id'");
        $ticket = $check ? $check->fetch_assoc() : null;
        if (!$ticket || $ticket['status'] !== 'Resolved') {
            echo json_encode(['success' => false, 'error' => 'not_resolved']); break;
        }
        $conn->query("DELETE FROM ticket_replies WHERE display_id = '$display_id'");
        $conn->query("DELETE FROM tickets WHERE display_id = '$display_id'");
        echo json_encode(['success' => $conn->affected_rows >= 0]);
        break;

    case 'close':
        $r = $conn->query("UPDATE tickets SET status = 'Resolved' WHERE display_id = '$display_id'");
        echo json_encode(['success' => $r !== false]);
        break;

    case 'reassign':
        $email = $conn->real_escape_string($_POST['support_email'] ?? '');
        $r = $conn->query("UPDATE tickets SET assigned_support_email = '$email' WHERE display_id = '$display_id'");
        echo json_encode(['success' => $r !== false]);
        break;

    case 'escalate':
        $r = $conn->query("UPDATE tickets SET is_escalated = 1 WHERE display_id = '$display_id'");
        echo json_encode(['success' => $r !== false]);
        break;

    case 'deescalate':
        $r = $conn->query("UPDATE tickets SET is_escalated = 0 WHERE display_id = '$display_id'");
        echo json_encode(['success' => $r !== false]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'unknown_action']);
}

$conn->close();
?>
