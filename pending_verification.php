<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Pending | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #F1F3E0; }
        .card {
            background: white;
            border-radius: 16px;
            padding: 3rem 2.5rem;
            max-width: 460px;
            width: 90%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            text-align: center;
        }
        .icon-wrap {
            width: 72px; height: 72px;
            background: #fef3c7;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2.2rem;
            color: #d69e2e;
        }
        h1 { font-size: 1.4rem; color: #2d3748; margin-bottom: 0.75rem; }
        p  { font-size: 0.92rem; color: #718096; line-height: 1.7; margin-bottom: 1.75rem; }
        .steps {
            background: #f7fafc;
            border-radius: 10px;
            padding: 1.1rem 1.25rem;
            text-align: left;
            margin-bottom: 1.75rem;
        }
        .steps p { margin-bottom: 0; color: #4a5568; font-size: 0.85rem; }
        .step { display: flex; align-items: flex-start; gap: 0.65rem; margin-bottom: 0.6rem; }
        .step:last-child { margin-bottom: 0; }
        .step-num {
            background: #10b981; color: white;
            border-radius: 50%; width: 20px; height: 20px;
            font-size: 0.72rem; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px;
        }
        .step span { font-size: 0.85rem; color: #4a5568; line-height: 1.5; }
        a.btn-back {
            display: inline-flex; align-items: center; gap: 0.4rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; text-decoration: none;
            padding: 0.65rem 1.75rem; border-radius: 8px;
            font-weight: 600; font-size: 0.9rem;
            transition: opacity 0.2s;
        }
        a.btn-back:hover { opacity: 0.88; }
        .footer-note { margin-top: 1.25rem; font-size: 0.78rem; color: #a0aec0; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class='bx bx-time-five'></i></div>
        <h1>Account Pending Verification</h1>
        <p>Your account has been created successfully. An admin needs to verify your account before you can log in.</p>
        <div class="steps">
            <div class="step"><div class="step-num">1</div><span>Your registration details have been saved.</span></div>
            <div class="step"><div class="step-num">2</div><span>An admin will review and approve your account.</span></div>
            <div class="step"><div class="step-num">3</div><span>Once verified, you can log in normally.</span></div>
        </div>
        <a href="login.php" class="btn-back"><i class='bx bx-log-in'></i> Back to Login</a>
        <p class="footer-note">© 2026 KlovrBank — Digital Banking Help Desk</p>
    </div>
</body>
</html>
