<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

if ($field === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email address.']); exit();
    }
    // Check not taken by another account
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND email != ?");
    $chk->bind_param("ss", $value, $_SESSION['email']);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        echo json_encode(['success' => false, 'error' => 'That email is already in use.']); exit();
    }
    $chk->close();
    // Also update tickets so they still belong to this user
    $upd = $conn->prepare("UPDATE users SET email = ? WHERE email = ? AND role = 'user'");
    $upd->bind_param("ss", $value, $_SESSION['email']);
    $upd->execute();
    if ($value !== $_SESSION['email']) {
        $conn->query("UPDATE tickets SET user_email = '{$conn->real_escape_string($value)}' WHERE user_email = '{$conn->real_escape_string($_SESSION['email'])}'");
        $_SESSION['email'] = $value;
    }
    $upd->close();
    echo json_encode(['success' => true]);

} elseif ($field === 'phone') {
    if ($value !== '' && (!ctype_digit($value) || strlen($value) !== 11)) {
        echo json_encode(['success' => false, 'error' => 'Phone must be exactly 11 digits.']); exit();
    }
    $val = $value === '' ? null : $value;
    $upd = $conn->prepare("UPDATE users SET phone = ? WHERE email = ? AND role = 'user'");
    $upd->bind_param("ss", $val, $_SESSION['email']);
    $upd->execute();
    echo json_encode(['success' => true]);
    $upd->close();

} elseif ($field === 'display_name') {
    if (strlen($value) > 60) {
        echo json_encode(['success' => false, 'error' => 'Display name too long (max 60 chars).']); exit();
    }
    $val = $value === '' ? null : $value;
    $upd = $conn->prepare("UPDATE users SET display_name = ? WHERE email = ? AND role = 'user'");
    $upd->bind_param("ss", $val, $_SESSION['email']);
    $upd->execute();
    echo json_encode(['success' => true]);
    $upd->close();

} elseif (in_array($field, ['house_no', 'street', 'subdivision', 'municipality', 'region', 'country'])) {
    $col = $conn->real_escape_string($field);
    $val = $value === '' ? null : $value;
    $upd = $conn->prepare("UPDATE users SET `$col` = ? WHERE email = ? AND role = 'user'");
    $upd->bind_param("ss", $val, $_SESSION['email']);
    $upd->execute();
    echo json_encode(['success' => true]);
    $upd->close();

} elseif ($field === 'postal_code') {
    if ($value !== '' && (!ctype_digit($value) || strlen($value) !== 4)) {
        echo json_encode(['success' => false, 'error' => 'Postal code must be exactly 4 digits.']); exit();
    }
    $val = $value === '' ? null : $value;
    $upd = $conn->prepare("UPDATE users SET postal_code = ? WHERE email = ? AND role = 'user'");
    $upd->bind_param("ss", $val, $_SESSION['email']);
    $upd->execute();
    echo json_encode(['success' => true]);
    $upd->close();

} else {
    echo json_encode(['success' => false, 'error' => 'Invalid field.']);
}

$conn->close();
