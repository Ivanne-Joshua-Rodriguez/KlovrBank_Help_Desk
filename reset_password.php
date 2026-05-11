<?php
session_start();

$host   = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') === false
         ? 'localhost' : 'localhost';
$dbuser = $host === 'localhost' ? 'root'         : 'root';
$dbpass = $host === 'localhost' ? ''             : '';
$dbname = $host === 'localhost' ? 'help_desk_db' : 'help_desk_db';

$conn = new mysqli($host, $dbuser, $dbpass, $dbname);

$token = trim($_GET['token'] ?? '');
$error = '';

if (!$token) { header("Location: forgot_password.php?error=invalid"); exit(); }

$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$row) { header("Location: forgot_password.php?error=expired"); exit(); }

if (isset($_GET['error'])) {
    $errors = ['mismatch' => 'Passwords do not match.', 'short' => 'Password must be at least 6 characters.'];
    $error = $errors[$_GET['error']] ?? 'An error occurred.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; background: #F1F3E0; }
        .bg-video { position: fixed; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
        .navbar-wrapper { background: #F1F3E0; box-shadow: 0 2px 12px rgba(0,0,0,0.08); position: relative; z-index: 10; }
        .navbar { width: 100%; height: 80px; display: flex; justify-content: space-between; align-items: center; padding: 0 5%; }
        @font-face { font-family: 'KugileDemo'; src: url('Fonts/Kugile_Demo.ttf') format('truetype'); }
        .nav-brand { display: flex; align-items: center; gap: 0.4rem; }
        .nav-brand video { height: 52px; width: auto; mix-blend-mode: multiply; }
        .nav-brand h1 { color: #2d5a27; font-size: 36px; font-weight: bold; font-family: 'KugileDemo', Arial, sans-serif; line-height: 1; margin-top: 10px; }
        .page-center { flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .card { background: rgba(255,255,255,0.97); border-radius: 16px; padding: 2.5rem 2rem; width: 100%; max-width: 460px; box-shadow: 0 8px 32px rgba(0,0,0,0.15); animation: fadeUp 0.5s ease both; }
        .card h1 { color: #068700; font-size: 1.8rem; font-family: 'KugileDemo', Arial, sans-serif; text-align: center; margin-bottom: 0.4rem; }
        .card .subtitle { text-align: center; font-size: 13px; color: #718096; margin-bottom: 1.5rem; }
        .input-box { margin-bottom: 1.1rem; }
        .input-box label { display: block; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 5px; }
        .input-box input { width: 100%; padding: 11px 12px; padding-right: 40px; border: 1.5px solid #ccc; border-radius: 8px; font-size: 0.95rem; transition: border-color 0.2s; background: #fafafa; }
        .input-box input:focus { outline: none; border-color: #10b981; background: white; }
        .input-wrap { position: relative; }
        .toggle-pw { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #666; font-size: 1.1rem; padding: 0; line-height: 1; }
        .btn { width: 100%; background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 12px; border: none; border-radius: 25px; font-weight: bold; font-size: 16px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px; margin-bottom: 1rem; transition: opacity 0.2s, transform 0.2s; }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }
        .footer { background: rgba(255,255,255,0.97); padding: 1.1rem 2rem; display: flex; justify-content: center; align-items: center; box-shadow: 0 -2px 12px rgba(0,0,0,0.1); border-top: 2px solid #e2e8f0; color: #4a5568; font-size: 0.88rem; gap: 0.4rem; }
        .footer i { color: #10b981; }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>
    <video class="bg-video" autoplay loop muted playsinline>
        <source src="Videos/KLVER1VID.mp4" type="video/mp4">
    </video>
    <script>document.querySelector('.bg-video').playbackRate = 0.4;</script>

    <div class="navbar-wrapper">
        <nav class="navbar">
            <div class="nav-brand">
                <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
                <h1>KlovrBank</h1>
            </div>
        </nav>
    </div>

    <div class="page-center">
        <div class="card">
            <h1>RESET PASSWORD</h1>
            <p class="subtitle">Enter your new password below.</p>

            <?php if ($error): ?>
                <p style="color:#ff4d4d;background:rgba(255,0,0,0.08);padding:10px;border-radius:8px;text-align:center;font-size:14px;margin-bottom:1rem;">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            <?php endif; ?>

            <form action="reset_password_process.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="input-box">
                    <label><i class="bx bx-lock"></i> New Password</label>
                    <div class="input-wrap">
                        <input type="password" name="password" id="pw1" placeholder="Enter new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw1','eye1')"><i class='bx bx-hide' id="eye1"></i></button>
                    </div>
                </div>

                <div class="input-box">
                    <label><i class="bx bx-lock-alt"></i> Confirm Password</label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" id="pw2" placeholder="Confirm new password" required>
                        <button type="button" class="toggle-pw" onclick="togglePw('pw2','eye2')"><i class='bx bx-hide' id="eye2"></i></button>
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="bx bx-check-circle" style="color:#fafafa;"></i> Reset Password
                </button>
            </form>
        </div>
    </div>

    <div class="footer">
        <i class='bx bxs-leaf'></i>
        <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
    </div>
    <script>
        function togglePw(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bx bx-show'; }
            else { inp.type = 'password'; ico.className = 'bx bx-hide'; }
        }
    </script>
</body>
</html>
