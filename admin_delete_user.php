<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false]); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['user_id'])) {
    $conn = new mysqli("localhost", "root", "", "help_desk_db");
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
    $stmt->bind_param("i", $_POST['user_id']);
    $stmt->execute();
    echo json_encode(['success' => $stmt->affected_rows > 0]);
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false]);
}
