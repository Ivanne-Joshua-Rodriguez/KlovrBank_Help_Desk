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
    // Collect the 'name' attributes from your HTML form
    $email = $_POST['email'];
    $bank_digits = $_POST['bank_digits']; 
    $user_password = $_POST['password'];

    // 4. PREPARE STATEMENT 
    $stmt = $conn->prepare("SELECT id, password_hash, role, is_active, is_verified, status, bank_account_4 FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // 5. CHECK IF ACCOUNT IS ACTIVE
        if ($user['is_active'] == 0 || $user['status'] === 'Deactivated') {
            header("Location: login.php?error=deactivated");
            exit();
        }

        // 5b. CHECK IF ACCOUNT IS VERIFIED
        if ($user['is_verified'] == 0) {
            header("Location: login.php?error=unverified");
            exit();
        }

        // Block non-user roles from the user login page
        if ($user['role'] !== 'user') {
            header("Location: login.php?error=unauthorized");
            exit();
        }

        // 6. VERIFY PASSWORD AND BANK DIGITS
        if (password_verify($user_password, $user['password_hash']) && $bank_digits === $user['bank_account_4']) {
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $user['role'];
            $_SESSION['bank_account_4'] = $user['bank_account_4'];
            $_SESSION['logged_in'] = true;

            header("Location: user_dashboard.php");
            exit();
            
        } else {
            header("Location: login.php?error=wrongpassword");
            exit();
        }
    } else {
        header("Location: login.php?error=notfound");
        exit();
    }
}
$conn->close();
?>