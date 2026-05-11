<?php
session_start();
$error = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'email_exists') {
        $error = "This email is already registered.";
    } elseif ($_GET['error'] == 'account_exists') {
        $error = "This bank account number is already linked to another account.";
    } else {
        $error = "An error occurred. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | KlovrBank</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
*{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}


body{
    font-family: Arial, sans-serif;
    background-color: #F1F3E0;
}

.navbar-wrapper {
    overflow: hidden;
    position: relative;
}

.navbar {
    width: 100%;
    height: 100px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 5%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, #F1F3E0 40%) !important;
}

.icon {
    display: flex;
    align-items: center;
    margin-left: 0;
}

.icon img {
    height: 60px;
    width: auto;
    margin-right: 5px;
}

.icon video {
    height: 60px;
    width: auto;
    margin-right: 5px;
    mix-blend-mode: multiply;
}

@font-face {
    font-family: 'KugileDemo';
    src: url('fonts/Kugile_Demo.ttf') format('truetype');
}

.logo{
    color: #2d5a27;
    font-size: 42px;
    font-weight: bold;
    font-family: 'KugileDemo', Arial, sans-serif;
    line-height: 1;
    vertical-align: middle;
    margin-top: 15px;
}

.menu ul{
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    justify-content: flex-end;
}

.menu ul li a{
    text-decoration: none;
    color: white;
    background-color: #068700;
    padding: 8px 20px;
    border-radius: 20px;
    margin-left: 10px;
    font-size: 14px;
    transition: 0.4s;
    display: inline-block;
}

.menu ul li a:hover{
    background-color: #2d5a27;
}

.content {
    width: 100%;
    display: flex;
    position: relative;
    min-height: 600px;
}

.left-side, .right-side {
    flex-basis: 50%;
    flex-grow: 0;
    flex-shrink: 0;
    min-height: 500px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    position: relative;
    overflow: hidden;
}

.left-side {
    position: relative;
    overflow: hidden;
    display: flex;
    height: 100vh;
    background-color:#28a745;
    padding-left: 2%;
}

.left-side-content {
    position: relative;
    z-index: 2;
    text-align: left;
    color: #ffffff;
    padding: 1.5rem 2rem;
    background: rgba(0, 0, 0, 0.25);
    border-radius: 16px;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    max-width: 480px;
    margin-left: 5%;
    margin-right: auto;
}

.left-side-content h1 {
    font-size: 46px;
    font-weight: 900;
    letter-spacing: -1px;
    margin-bottom: 8px;
    text-shadow: 0 2px 12px rgba(0,0,0,0.4);
    white-space: nowrap;
}

.background-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    opacity: 0.6;
    background-image: url('Images/KLVER2.jpg');
    background-size: cover;
    background-position: center;
    -webkit-mask-image: linear-gradient(to right, black 0%, black 30%, rgba(0,0,0,0.5) 70%, transparent 100%) !important;
    mask-image: linear-gradient(to right, black 0%, black 30%, rgba(0,0,0,0.5) 70%, transparent 100%) !important;
}

.big-icon {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.6rem;
}

.big-icon img {
    width: 52px;
    height: auto;
    filter: drop-shadow(0 2px 8px rgba(0,0,0,0.4));
}

.left-side h2 {
    font-size: 15px;
    font-weight: 400;
    line-height: 1.7;
    color: rgba(255,255,255,0.75);
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid rgba(255,255,255,0.2);
    letter-spacing: 0.4px;
    font-style: italic;
}

.right-side {
    background-color: #28a745;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.right-side-KLVER1 {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

.right-side-KLVER1 video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.login-container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 100;
}

.login-box {
    width: 450px;
    background: #F1F3E0;
    padding: 40px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.login-box h1 {
    color: #068700;
    font-size: 36px;
    margin-bottom: 20px;
    font-family: 'KugileDemo', Arial, sans-serif;
}

.input-box {
    text-align: left;
    margin-bottom: 20px;
}

.input-box label {
    font-size: 14px;
    font-weight: bold;
    color: #333;
}

.input-box input {
    width: 100%;
    padding: 12px;
    border: 1.5px solid #ccc;
    border-radius: 5px;
    margin-top: 5px;
}

.login-redirect {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
    color: #666666;
}

.login-redirect a {
    color: #068700;
    text-decoration: none;
    font-weight: bold;
}

.login-redirect a:hover {
    text-decoration: underline;
}

.btn {
    width: 100%;
    background-color: #4CAF50;
    color: white;
    padding: 12px;
    border: none;
    border-radius: 25px;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 15px;
}

.btn:hover {
    background-color: #388E3C;
}

.footer {
    width: 100%;
    height: 100px;
    text-align: center;
    padding: 50px;
    font-size: 12px;
    background-color: #F1F3E0;
    color: #61876E;
    justify-content: center !important;
}

@keyframes slideInLeft {
    from { opacity: 0; transform: translateX(-60px); }
    to   { opacity: 1; transform: translateX(0); }
}

.left-side-content {
    animation: slideInLeft 0.7s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
}

@keyframes fadeUp {
    from { opacity: 0; transform: translateY(30px); }
    to   { opacity: 1; transform: translateY(0); }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.login-box {
    animation: fadeUp 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) 0.1s both;
}

.login-box h1 { animation: fadeUp 0.5s ease 0.3s both; }

.input-box:nth-child(1) { animation: fadeUp 0.5s ease 0.4s both; }
.input-box:nth-child(2) { animation: fadeUp 0.5s ease 0.5s both; }
.input-box:nth-child(3) { animation: fadeUp 0.5s ease 0.6s both; }

.submit-button { animation: fadeUp 0.5s ease 0.7s both; }
.login-redirect { animation: fadeIn 0.5s ease 0.8s both; }

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-100%); }
    to   { opacity: 1; transform: translateY(0); }
}

.navbar {
    animation: slideDown 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) both;
}

@media (max-width: 768px) {
    .left-side { display: none; }
    .content { min-height: 100vh; }
    .right-side { flex-basis: 100%; width: 100%; min-height: 100vh; }
    .login-container {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        width: 92%;
        max-width: 440px;
    }
    .login-box { width: 100%; padding: 2rem 1.5rem; }
    .navbar { height: 70px; padding: 0 1rem; }
    .logo { font-size: 28px; }
    .icon video { height: 42px; }
    .menu ul li a { padding: 6px 14px; font-size: 13px; }
    .footer { padding: 1rem; }
}

@media (max-width: 400px) {
    .login-box { padding: 1.75rem 1.1rem; }
    .login-box h1 { font-size: 28px; }
}
    </style>
</head>
<body>
    <div class="main">
    <div class="navbar-wrapper">
        <div class="navbar" style="background: #F1F3E0;">
            <div class="icon">
                <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
                <h1 class="logo">KlovrBank</h1>
            </div>
            <div class="menu">
                <ul>
                    <li><a href="guest_tickets.php">Tickets</a></li>
                </ul>
            </div>
        </div>
    </div>
    </div>

    <div class="content">
        <div class="left-side">
            <div class="background-overlay"></div>
            <div class="left-side-content">
                <div class="big-icon">
                    <img src="Images/LOGO.png">
                    <h1>KlovrBank</h1>
                </div>
                <h2>Plant your wealth, harvest your future.</h2>
            </div>
        </div>

        <div class="login-container">
            <div class="login-box">
                <h1>SIGN UP</h1>

                <?php if ($error !== ""): ?>
                    <p style="color: #ff4d4d; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px; text-align: center; font-size: 14px; margin-bottom: 15px;">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>

                <form id="signupForm">
                    <div class="input-box">
                        <label for="Email_Address"><i class="bx bx-user"></i> Email Address</label>
                        <input type="email" name="email" id="Email_Address" placeholder="example@domain.com" required>
                    </div>
                    <div class="input-box">
                        <label for="account_number">Full Bank Account Number</label>
                        <input type="text" id="account_number" maxlength="19" required inputmode="numeric" placeholder="XXXX-XXXX-XXXX-XXXX" required>
                        <input type="hidden" name="bank_digits" id="account_number_raw">
                    </div>
                    <div class="input-box">
                        <label for="Password"><i class="bx bx-lock"></i> Password</label>
                        <div style="position:relative;margin-top:5px;">
                            <input type="password" name="password" id="Password" placeholder="Enter your password" required style="width:100%;padding:12px;padding-right:40px;border:1.5px solid #ccc;border-radius:5px;">
                            <button type="button" onclick="togglePw('Password','pwEye')" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#666;font-size:1.1rem;padding:0;line-height:1;"><i class='bx bx-hide' id="pwEye"></i></button>
                        </div>
                    </div>

                    <div class="submit-button">
                        <button type="submit" class="btn">
                            <i class="bx bx-user-plus bx-remove-padding" style="color:#fafafa;"></i> Sign Up
                        </button>
                    </div>
                    <div class="login-redirect">
                        Already have an account? <a href="login.php">Log in here</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="right-side">
            <div class="right-side-KLVER1">
                <video autoplay muted loop playsinline>
                    <source src="Videos/KLVER1VID.mp4" type="video/mp4">
                </video>
            </div>
        </div>
    </div>

    <!-- PENDING VERIFICATION POPUP -->
    <div id="pendingOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;align-items:center;justify-content:center;">
        <div style="background:white;border-radius:16px;padding:2.5rem 2rem;max-width:420px;width:90%;box-shadow:0 12px 40px rgba(0,0,0,0.25);text-align:center;">
            <div style="width:68px;height:68px;background:#fef3c7;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2rem;color:#d69e2e;"><i class='bx bx-time-five'></i></div>
            <h2 style="font-size:1.2rem;color:#2d3748;margin-bottom:0.6rem;">Account Pending Verification</h2>
            <p style="font-size:0.88rem;color:#718096;line-height:1.7;margin-bottom:1.5rem;">Your account has been created! An admin needs to verify it before you can log in. You'll be able to sign in once approved.</p>
            <a href="login.php" style="display:inline-flex;align-items:center;gap:0.4rem;background:linear-gradient(135deg,#10b981,#059669);color:white;text-decoration:none;padding:0.65rem 1.75rem;border-radius:8px;font-weight:600;font-size:0.9rem;"><i class='bx bx-log-in'></i> Go to Login</a>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer" style="background:rgba(255,255,255,0.97); padding:1.2rem 2rem; display:flex; justify-content:center; align-items:center; box-shadow:0 -2px 12px rgba(0,0,0,0.1); border-top:2px solid #e2e8f0;">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
    </div>
<script>
    const input = document.getElementById('account_number');
    const raw = document.getElementById('account_number_raw');

    input.addEventListener('input', function () {
        let digits = this.value.replace(/\D/g, '').slice(0, 16);
        this.value = digits.match(/.{1,4}/g)?.join('-') ?? digits;
        raw.value = digits;
    });

    document.getElementById('signupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const digits = input.value.replace(/\D/g, '');
        if (digits.length !== 16) {
            showSignupError('Please enter the full 16-digit bank account number.');
            return;
        }
        const password = document.getElementById('Password').value;
        if (password.length < 8) {
            showSignupError('Password must be at least 8 characters long.');
            return;
        }
        raw.value = digits;
        const fd = new FormData(this);
        fetch('signup_process.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const overlay = document.getElementById('pendingOverlay');
                    overlay.style.display = 'flex';
                } else {
                    const errMap = {
                        email_exists:    'This email is already registered.',
                        account_exists:  'This bank account number is already linked to another account.',
                        invalid_account: 'Please enter the full 16-digit bank account number.',
                        weak_password:   'Password must be at least 8 characters long.',
                    };
                    showSignupError(errMap[res.error] || 'An error occurred. Please try again.');
                }
            });
    });

    function showSignupError(msg) {
        let errEl = document.getElementById('signupError');
        if (!errEl) {
            errEl = document.createElement('p');
            errEl.id = 'signupError';
            errEl.style.cssText = 'color:#ff4d4d;background:rgba(255,0,0,0.1);padding:10px;border-radius:5px;text-align:center;font-size:14px;margin-bottom:15px;';
            document.getElementById('signupForm').prepend(errEl);
        }
        errEl.textContent = msg;
    }

    function togglePw(inputId, iconId) {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bx bx-show'; }
        else { inp.type = 'password'; ico.className = 'bx bx-hide'; }
    }
</script>
</body>
</html>