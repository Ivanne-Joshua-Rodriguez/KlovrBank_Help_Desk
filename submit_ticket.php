<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "help_desk_db");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['email'])) {
    $user_email = $_SESSION['email'];
    $subject    = mysqli_real_escape_string($conn, $_POST['subject']);
    $category   = mysqli_real_escape_string($conn, $_POST['category']);
    $content    = mysqli_real_escape_string($conn, $_POST['message']);

    // Handle optional image upload
    $image_path = null;
    if (!empty($_FILES['ticket_image']['name'])) {
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        $ftype   = mime_content_type($_FILES['ticket_image']['tmp_name']);
        if (in_array($ftype, $allowed) && $_FILES['ticket_image']['size'] <= 10 * 1024 * 1024) {
            $ext      = pathinfo($_FILES['ticket_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_', true) . '.' . $ext;
            $dest     = __DIR__ . '/uploads/' . $filename;
            if (@move_uploaded_file($_FILES['ticket_image']['tmp_name'], $dest)) {
                $image_path = 'uploads/' . $filename;
            }
        }
    }

    // Generate unique display ID
    $year = date('y');
    $final_id = "";
    do {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $rand  = '';
        for ($i = 0; $i < 4; $i++) $rand .= $chars[rand(0, strlen($chars) - 1)];
        $final_id = "TK" . $year . $rand;
        $check = mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE display_id = '$final_id'");
    } while (mysqli_num_rows($check) > 0);

    $img_escaped = $image_path ? mysqli_real_escape_string($conn, $image_path) : null;
    $sql = $img_escaped
        ? "INSERT INTO tickets (display_id, user_email, subject, category, content, status, image_path) VALUES ('$final_id', '$user_email', '$subject', '$category', '$content', 'Open', '$img_escaped')"
        : "INSERT INTO tickets (display_id, user_email, subject, category, content, status) VALUES ('$final_id', '$user_email', '$subject', '$category', '$content', 'Open')";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'ticket_id' => $final_id, 'subject' => $subject, 'category' => $category, 'date' => date('M d, Y')]);
    } else {
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
}
?>
