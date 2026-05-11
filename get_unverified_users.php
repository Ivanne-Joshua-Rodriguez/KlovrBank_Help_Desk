<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    echo json_encode([]); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
$r = $conn->query("SELECT id, email, bank_account, created_at FROM users WHERE role = 'user' AND is_verified = 0 ORDER BY created_at DESC");
$users = [];
while ($row = $r->fetch_assoc()) $users[] = $row;
echo json_encode($users);
$conn->close();
?>
