<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['admin','support'])) {
    echo json_encode([]); exit();
}

$conn  = new mysqli("localhost", "root", "", "help_desk_db");
$me    = $_SESSION['email'];
$role  = $_SESSION['role'];

if ($role === 'admin') {
    // Admin fetches conversation with a specific support agent
    $other = trim($_GET['with'] ?? '');
    if (!$other) { echo json_encode([]); exit(); }

    // Mark messages from that support agent to admin as read
    $conn->query("UPDATE internal_messages SET is_read = 1
        WHERE sender_email = '{$conn->real_escape_string($other)}'
          AND receiver_email = '{$conn->real_escape_string($me)}'
          AND is_read = 0");

    $stmt = $conn->prepare("SELECT sender_email, sender_role, message, created_at
        FROM internal_messages
        WHERE (sender_email = ? AND receiver_email = ?)
           OR (sender_email = ? AND receiver_email = ?)
        ORDER BY created_at ASC LIMIT 200");
    $stmt->bind_param("ssss", $me, $other, $other, $me);
} else {
    // Support: fetch conversation with admin (look up admin email)
    $r = $conn->query("SELECT email FROM users WHERE role = 'admin' LIMIT 1");
    $admin_row = $r ? $r->fetch_assoc() : null;
    if (!$admin_row) { echo json_encode([]); exit(); }
    $admin_email = $admin_row['email'];

    $stmt = $conn->prepare("SELECT sender_email, sender_role, message, created_at
        FROM internal_messages
        WHERE (sender_email = ? AND receiver_email = ?)
           OR (sender_email = ? AND receiver_email = ?)
        ORDER BY created_at ASC LIMIT 200");
    $stmt->bind_param("ssss", $me, $admin_email, $admin_email, $me);

    // Mark admin messages to this support as read
    $conn->query("UPDATE internal_messages SET is_read = 1
        WHERE receiver_email = '{$conn->real_escape_string($me)}'
          AND sender_role = 'admin'
          AND is_read = 0");
}

$stmt->execute();
$result = $stmt->get_result();
$messages = [];
while ($row = $result->fetch_assoc()) $messages[] = $row;
echo json_encode($messages);
$stmt->close();
$conn->close();
?>
