<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false]); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['support_id'])) {
    $conn = new mysqli("localhost", "root", "", "help_desk_db");

    $email_stmt = $conn->prepare("SELECT email FROM users WHERE id = ? AND role = 'support'");
    $email_stmt->bind_param("i", $_POST['support_id']);
    $email_stmt->execute();
    $email_result = $email_stmt->get_result()->fetch_assoc();
    $email_stmt->close();

    if ($email_result) {
        $revert = $conn->prepare("UPDATE tickets SET status = 'Open', assigned_support_email = NULL WHERE assigned_support_email = ? AND status IN ('Under Review', 'On-Going')");
        $revert->bind_param("s", $email_result['email']);
        $revert->execute();
        $revert->close();
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'support'");
    $stmt->bind_param("i", $_POST['support_id']);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false]);
}
