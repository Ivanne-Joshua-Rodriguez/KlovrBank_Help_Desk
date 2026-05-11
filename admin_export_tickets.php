<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=unauthorized"); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) die("Connection failed");

$where  = [];
$status = $_GET['status'] ?? '';
$cat    = $_GET['category'] ?? '';
$from   = $_GET['from'] ?? '';
$to     = $_GET['to'] ?? '';

if ($status)           $where[] = "status = '" . $conn->real_escape_string($status) . "'";
if ($cat)              $where[] = "category = '" . $conn->real_escape_string($cat) . "'";
if ($from)             $where[] = "DATE(created_at) >= '" . $conn->real_escape_string($from) . "'";
if ($to)               $where[] = "DATE(created_at) <= '" . $conn->real_escape_string($to) . "'";

$sql = "SELECT display_id, user_email, subject, category, status, is_escalated, assigned_support_email, created_at FROM tickets";
if ($where) $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY created_at DESC";

$result = $conn->query($sql);

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="tickets_export_' . date('Ymd_His') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Ticket ID', 'User Email', 'Subject', 'Category', 'Status', 'Escalated', 'Assigned Support', 'Date Submitted']);

while ($row = $result->fetch_assoc()) {
    fputcsv($out, [
        '#' . $row['display_id'],
        $row['user_email'],
        $row['subject'],
        ucfirst($row['category']),
        $row['status'],
        $row['is_escalated'] ? 'Yes' : 'No',
        $row['assigned_support_email'] ?? 'Unassigned',
        $row['created_at'],
    ]);
}

fclose($out);
$conn->close();
?>
