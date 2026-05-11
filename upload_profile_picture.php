<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'], ['user','support'])) {
    echo json_encode(['success' => false, 'error' => 'unauthorized']); exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");

// Handle offset-only update
if (isset($_POST['offset_only'])) {
    $offset = trim($_POST['offset'] ?? '50% 50%');
    $stmt = $conn->prepare("UPDATE users SET profile_picture_offset = ? WHERE email = ?");
    $stmt->bind_param("ss", $offset, $_SESSION['email']);
    $stmt->execute();
    echo json_encode(['success' => true]);
    $stmt->close(); $conn->close(); exit();
}

// Handle image upload
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']); exit();
}

$file    = $_FILES['avatar'];
$maxSize = 5 * 1024 * 1024; // 5MB
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'File too large (max 5MB).']); exit();
}

$allowed = ['image/jpeg','image/png','image/gif','image/webp'];
$finfo   = finfo_open(FILEINFO_MIME_TYPE);
$mime    = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type.']); exit();
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . md5($_SESSION['email'] . time()) . '.' . $ext;
$dest     = __DIR__ . '/uploads/avatars/' . $filename;
$webPath  = 'uploads/avatars/' . $filename;

// Delete old avatar if exists
$old = $conn->prepare("SELECT profile_picture FROM users WHERE email = ?");
$old->bind_param("s", $_SESSION['email']);
$old->execute();
$oldRow = $old->get_result()->fetch_assoc();
$old->close();
if (!empty($oldRow['profile_picture'])) {
    $oldFile = __DIR__ . '/' . $oldRow['profile_picture'];
    if (file_exists($oldFile)) unlink($oldFile);
}

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => false, 'error' => 'Failed to save file.']); exit();
}

$offset = trim($_POST['offset'] ?? '50% 50%');
$stmt = $conn->prepare("UPDATE users SET profile_picture = ?, profile_picture_offset = ? WHERE email = ?");
$stmt->bind_param("sss", $webPath, $offset, $_SESSION['email']);
$stmt->execute();
$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'path' => $webPath]);
?>
