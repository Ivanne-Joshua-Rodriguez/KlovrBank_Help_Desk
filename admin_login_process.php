<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "help_desk_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $user_password = $_POST['password'];

    if (empty($email) || empty($user_password)) {
        header("Location: admin_login.php?error=emptyfields");
        exit();
    }

    $stmt = $conn->prepare("SELECT id, password_hash, role, is_active, status FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (!in_array($user['role'], ['admin', 'support'])) {
            header("Location: admin_login.php?error=unauthorized");
            exit();
        }

        if ($user['is_active'] == 0 || $user['status'] === 'Deactivated') {
            header("Location: admin_login.php?error=deactivated");
            exit();
        }

        if (password_verify($user_password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: support_dashboard.php");
            }
            exit();
        } else {
            header("Location: admin_login.php?error=wrongpassword");
            exit();
        }
    } else {
        header("Location: admin_login.php?error=notfound");
        exit();
    }
}
$conn->close();
?>
