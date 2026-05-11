<?php
session_start();
$error = "";
$success = "";
if (isset($_GET['error'])) {
    if ($_GET['error'] == "wrongpassword") {
        $error = "The password or bank digits are incorrect.";
    } elseif ($_GET['error'] == "notfound") {
        $error = "No account exists with that email.";
    } elseif ($_GET['error'] == "emptyfields") {
        $error = "Please fill in all fields.";
    } elseif ($_GET['error'] == "deactivated") {
        $error = "This account has been disabled. Please contact support.";
    } elseif ($_GET['error'] == "unverified") {
        $error = "Your account is pending admin verification. Please wait for approval.";
    } elseif ($_GET['error'] == "unauthorized") {
        $error = "Access denied. Please use the staff login portal.";
    }
}
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Your password has been reset successfully. You can now log in.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | KlovrBank</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
    /*                  Log In   STARTS                      */
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

.form-footer {
    text-align: left;
    margin-bottom: 20px;
}

.forgot-link, .signup-link {
    display: block;
    color: #333;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 10px;
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

.remember-me {
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
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

        .form-footer   { animation: fadeUp 0.5s ease 0.7s both; }
        .submit-button { animation: fadeUp 0.5s ease 0.8s both; }
        .signup-link   { animation: fadeIn 0.5s ease 0.9s both; }

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
                <h1>LOG IN</h1>

                <?php if ($error !== ""): ?>
                    <p style="color: #ff4d4d; background: rgba(255,0,0,0.1); padding: 10px; border-radius: 5px; text-align: center; font-size: 14px; margin-bottom: 15px;">
                        <?php echo $error; ?>
                    </p>
                <?php endif; ?>
                <?php if ($success !== ""): ?>
                    <p style="color: #068700; background: rgba(0,135,0,0.08); padding: 10px; border-radius: 5px; text-align: center; font-size: 14px; margin-bottom: 15px;">
                        <?php echo $success; ?>
                    </p>
                <?php endif; ?>

                <form action="login_process.php" method="POST">
                    <div class="input-box">
                        <label for="Email_Address"><i class="bx bx-user"></i> Email Address</label>
                        <input type="email" name="email" id="Email_Address" placeholder="example@domain.com" required>
                    </div>

                    <div class="input-box">
                        <label for="account_number_4">Last (4) Digits of Bank Account Number</label>
                        <input type="text" name="bank_digits" id="account_number_4" maxlength="4" inputmode="numeric" placeholder="XXXX" required>
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
                            <i class="bx bx-user bx-remove-padding" style="color:#fafafa;"></i> Log In 
                        </button>
                    </div>
                    <a href="signup.php" class="signup-link" style="display:inline;">Don't have an account? <span style="color:#068700; font-weight:bold;">Sign up here</span></a>
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

    <!-- FOOTER -->
    <div class="footer" style="background:rgba(255,255,255,0.97); padding:1.2rem 2rem; display:flex; justify-content:center; align-items:center; box-shadow:0 -2px 12px rgba(0,0,0,0.1); border-top:2px solid #e2e8f0;">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
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