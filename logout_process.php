<?php
session_start();
$role = $_SESSION['role'] ?? '';
session_unset();
session_destroy();
if ($role === 'admin' || $role === 'support') {
    header("Location: admin_login.php");
} else {
    header("Location: login.php");
}
exit();
?>