<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['admin','support'])) {
    echo json_encode(['count' => 0, 'by_sender' => []]); exit();
}

$conn  = new mysqli("localhost", "root", "", "help_desk_db");
$me    = $_SESSION['email'];
$role  = $_SESSION['role'];

if ($role === 'admin') {
    // Return unread count per support sender
    $r = $conn->query("SELECT sender_email, COUNT(*) as c FROM internal_messages
        WHERE receiver_email = '{$conn->real_escape_string($me)}' AND is_read = 0
        GROUP BY sender_email");
    $by_sender = [];
    $total = 0;
    while ($row = $r->fetch_assoc()) {
        $by_sender[$row['sender_email']] = (int)$row['c'];
        $total += (int)$row['c'];
    }
    echo json_encode(['count' => $total, 'by_sender' => $by_sender]);
} else {
    // Support: total unread from admin
    $r = $conn->query("SELECT COUNT(*) as c FROM internal_messages
        WHERE receiver_email = '{$conn->real_escape_string($me)}' AND sender_role = 'admin' AND is_read = 0");
    echo json_encode(['count' => (int)$r->fetch_assoc()['c'], 'by_sender' => []]);
}
$conn->close();
?>
