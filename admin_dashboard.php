<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    header("Location: admin_login.php?error=unauthorized");
    exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Auto-update For Renewal status
$conn->query("UPDATE users SET status = 'For Renewal' WHERE role = 'user' AND DATEDIFF(NOW(), created_at) >= 1825 AND status = 'Active'");
$users_result = $conn->query("SELECT id, email, bank_account, is_active, is_verified, status, created_at FROM users WHERE role = 'user' ORDER BY id ASC");
$users = $users_result ? $users_result->fetch_all(MYSQLI_ASSOC) : [];

$support_result = $conn->query("SELECT id, email, is_active, profile_picture, profile_picture_offset FROM users WHERE role = 'support' ORDER BY id ASC");
$support_accounts = $support_result ? $support_result->fetch_all(MYSQLI_ASSOC) : [];

$tickets_result = $conn->query("SELECT ticket_id, display_id, user_email, subject, category, status, is_escalated, assigned_support_email, content, image_path, created_at FROM tickets ORDER BY created_at DESC");
$all_tickets = $tickets_result ? $tickets_result->fetch_all(MYSQLI_ASSOC) : [];

$unverified_result = $conn->query("SELECT id, email, bank_account, created_at FROM users WHERE role = 'user' AND is_verified = 0 ORDER BY created_at DESC");
$unverified_users = $unverified_result ? $unverified_result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | KlovrBank</title>
    <link rel="stylesheet" href="style.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; background-color: #F1F3E0; position: relative; }
        .bg-video {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
            
        }

        .heading {
            background: rgba(255,255,255,0.97);
            padding: 1rem 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .welcome { display: flex; align-items: center; gap: 1rem; }
        .welcome video { height: 48px; width: auto; }
        .welcome-text h1 { color: #2d3748; font-size: 1.4rem; }
        .welcome-text span { color: #10b981; font-size: 0.85rem; font-weight: 500; }
        .header-actions { display: flex; align-items: center; gap: 1rem; }
        .notif-btn {
            position: relative;
            background: #f0fdf4;
            border: none;
            border-radius: 50%;
            width: 42px; height: 42px;
            font-size: 1.3rem;
            color: #10b981;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .notif-btn:hover { background: #d1fae5; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 9px; height: 9px;
            background: #f56565; border-radius: 50%; border: 2px solid white;
        }

        .admin-container {
            display: flex;
            width: 90%;
            max-width: 1200px;
            height: calc(100vh - 130px);
            min-height: 520px;
            margin: 30px auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }

        /* ── SIDEBAR ── */
        .admin-sidebar {
            width: 220px;
            flex-shrink: 0;
            background: #1a2e22;
            padding: 1.5rem 0;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }
        .sidebar-title {
            color: #10b981;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 0 1.25rem 0.75rem;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 0.5rem;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.75rem 1.25rem;
            color: #a0aec0;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: background 0.2s, color 0.2s;
            cursor: pointer;
        }
        .sidebar-menu li a i { font-size: 1.1rem; }
        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            background: rgba(16,185,129,0.12);
            color: #10b981;
            border-left: 3px solid #10b981;
        }

        /* ── MAIN CONTENT ── */
        .admin-content {
            flex: 1;
            background: #e8f5e9;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        .section-view {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
        /* ── HERO BANNER (minimized) ── */
        .hero-banner.admin-hero {
            height: 110px !important;
            position: relative;
            background: url('Images/KLVER1.jpg') center 30% / cover no-repeat !important;
            -webkit-mask-image: linear-gradient(to bottom, black 0%, black 60%, transparent 100%);
            mask-image: linear-gradient(to bottom, black 0%, black 60%, transparent 100%);
        }
        .hero-banner.admin-hero .hero-img { display: none; }
        .admin-hero .hero-text {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .admin-hero .hero-text h1 {
            font-size: 2.2rem;
            font-weight: 800;
            letter-spacing: 4px;
            color: #ffffff;
            text-shadow: 0 0 12px rgba(16,185,129,0.8), 0 2px 8px rgba(0,0,0,0.6);
            text-transform: uppercase;
        }

        .footer {
            background: rgba(255,255,255,0.97) !important;
            padding: 1.2rem 2rem !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.1) !important;
            border-top: 2px solid #e2e8f0 !important;
        }
        .footer-left {
            display: flex; align-items: center; gap: 0.5rem;
            color: #4a5568; font-size: 0.9rem;
        }
        .footer-left i { color: #10b981; font-size: 1.1rem; }
        .logout-btn {
            background: #f56565; color: white;
            padding: 0.5rem 1.5rem; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            display: flex; align-items: center; gap: 0.4rem;
            transition: all 0.3s;
        }
        .logout-btn:hover {
            background: #e53e3e; transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245,101,101,0.4);
        }

        .content-title {
            font-size: 1rem;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }
        .tool-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem 1.25rem;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.6rem;
            background: #fafafa;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .tool-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .tool-card .tool-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
        }
        .tool-card h3 { font-size: 0.95rem; font-weight: 700; color: #2d3748; }
        .tool-card p  { font-size: 0.8rem; color: #718096; line-height: 1.4; }
        .tool-card .tool-btn {
            margin-top: 0.5rem;
            padding: 0.5rem 1.1rem;
            border: none; border-radius: 7px;
            font-size: 0.82rem; font-weight: 600;
            cursor: pointer; transition: opacity 0.2s;
        }
        .tool-card .tool-btn:hover { opacity: 0.85; }

        /* ── TOOLS GRID SCROLL ── */
        .tools-grid-wrap {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
            padding-right: 6px;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.25rem;
            padding-bottom: 0.5rem;
        }

        /* ── PANELS ── */
        .panel { display: none; }
        .panel.active { display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; }
        .panel.active .panel-body { flex: 1; min-height: 0; }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            align-self: flex-start;
            background: #f0fdf4;
            color: #10b981;
            border: 1.5px solid #10b981;
            border-radius: 8px;
            padding: 0.45rem 1rem;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 1.25rem;
            transition: background 0.2s, color 0.2s;
        }
        .back-btn:hover { background: #10b981; color: white; }

        .panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: #2d3748;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
        }

        .panel-body {
            background: #fafafa;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.75rem;
        }
        .panel-body.scrollable-table {
            padding: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .panel-body.scrollable-table .table-scroll {
            flex: 1;
            overflow-y: scroll;
            min-height: 0;
            padding: 0 1.75rem 1.75rem;
        }

        .panel-body label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 0.35rem;
        }

        .panel-body input, .panel-body select, .panel-body textarea {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1.5px solid #cbd5e0;
            border-radius: 7px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            background: white;
            color: #2d3748;
        }

        .panel-body textarea { resize: vertical; min-height: 90px; }

        .panel-submit {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .panel-submit:hover { opacity: 0.85; }
        .btn-red    { background: #e53e3e; color: white; }
        .btn-green  { background: #10b981; color: white; }
        .btn-orange { background: #dd6b20; color: white; }
        .btn-purple { background: #805ad5; color: white; }

        /* ── USER TABLE ── */
        .user-table { width: 100%; border-collapse: collapse; font-size: 0.88rem; }
        .user-table th { background: #f7fafc; color: #4a5568; font-weight: 600; padding: 0.65rem 0.85rem; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .user-table td { padding: 0.65rem 0.85rem; border-bottom: 1px solid #e2e8f0; color: #2d3748; vertical-align: middle; }
        .user-table tr:hover td { background: #f7fafc; }
        .delete-row-btn { background: #e53e3e; color: white; border: none; border-radius: 6px; padding: 0.35rem 0.85rem; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .delete-row-btn:hover { opacity: 0.8; }
        .badge-active   { background: #d1fae5; color: #065f46; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-inactive { background: #fee2e2; color: #991b1b; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }
        .badge-renewal  { background: #fef3c7; color: #92400e; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; }

        /* ── CONFIRM MODAL ── */
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 999; align-items: center; justify-content: center; }
        .modal-overlay.open { display: flex; }
        @keyframes modalPopIn {
            from { opacity: 0; transform: scale(0.85) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .modal-overlay.open .modal-box { animation: modalPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .modal-box { background: white; border-radius: 14px; padding: 2rem; width: 380px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); text-align: center; }
        .modal-box .modal-icon { font-size: 2.5rem; color: #e53e3e; margin-bottom: 0.75rem; }
        .modal-box h3 { font-size: 1.1rem; color: #2d3748; margin-bottom: 0.5rem; }
        .modal-box p  { font-size: 0.88rem; color: #718096; margin-bottom: 1.5rem; }
        .modal-actions { display: flex; gap: 0.75rem; justify-content: center; }
        .modal-cancel { padding: 0.55rem 1.4rem; border: 1.5px solid #cbd5e0; border-radius: 8px; background: white; color: #4a5568; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .modal-cancel:hover { background: #f7fafc; }
        .modal-confirm { padding: 0.55rem 1.4rem; border: none; border-radius: 8px; background: #e53e3e; color: white; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        .modal-confirm:hover { opacity: 0.85; }

        #panel-faq.active .panel-body { overflow-y: auto; }
        .card-red   .tool-icon { background: #fff5f5; color: #e53e3e; }
        .card-red   .tool-btn  { background: #e53e3e; color: white; }
        .card-green .tool-icon { background: #f0fdf4; color: #10b981; }
        .card-green .tool-btn  { background: #10b981; color: white; }
        .card-orange .tool-icon { background: #fffaf0; color: #dd6b20; }
        .card-orange .tool-btn  { background: #dd6b20; color: white; }
        .card-purple .tool-icon { background: #faf5ff; color: #805ad5; }
        .card-purple .tool-btn  { background: #805ad5; color: white; }
        .card-blue .tool-icon { background: #ebf8ff; color: #3182ce; }
        .card-blue .tool-btn  { background: #3182ce; color: white; }
        .card-teal .tool-icon { background: #e6fffa; color: #319795; }
        .card-teal .tool-btn  { background: #319795; color: white; }
        .card-yellow .tool-icon { background: #fffff0; color: #d69e2e; }
        .card-yellow .tool-btn  { background: #d69e2e; color: white; }
        .card-pink .tool-icon { background: #fff5f7; color: #d53f8c; }
        .card-pink .tool-btn  { background: #d53f8c; color: white; }

        /* ── ENTER ANIMATIONS ── */
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInLeft {
            from { opacity: 0; transform: translateX(-28px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(28px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .heading          { animation: fadeInDown 0.5s ease both; }
        .admin-hero       { animation: fadeInDown 0.5s 0.1s ease both; }
        .admin-sidebar    { animation: fadeInLeft  0.55s 0.25s ease both; }
        .admin-content    { animation: fadeInRight 0.55s 0.25s ease both; }
        .tool-card:nth-child(1) { animation: fadeInUp 0.45s 0.35s ease both; }
        .tool-card:nth-child(2) { animation: fadeInUp 0.45s 0.42s ease both; }
        .tool-card:nth-child(3) { animation: fadeInUp 0.45s 0.49s ease both; }
        .tool-card:nth-child(4) { animation: fadeInUp 0.45s 0.56s ease both; }
        .tool-card:nth-child(5) { animation: fadeInUp 0.45s 0.63s ease both; }
        .tool-card:nth-child(6) { animation: fadeInUp 0.45s 0.70s ease both; }
        .tool-card:nth-child(7) { animation: fadeInUp 0.45s 0.77s ease both; }
        .tool-card:nth-child(8) { animation: fadeInUp 0.45s 0.84s ease both; }
        .footer           { animation: fadeInUp 0.5s 0.9s ease both; }
        .tools-grid.animate .tool-card:nth-child(1) { animation: fadeInUp 0.45s 0.05s ease both; }
        .tools-grid.animate .tool-card:nth-child(2) { animation: fadeInUp 0.45s 0.10s ease both; }
        .tools-grid.animate .tool-card:nth-child(3) { animation: fadeInUp 0.45s 0.15s ease both; }
        .tools-grid.animate .tool-card:nth-child(4) { animation: fadeInUp 0.45s 0.20s ease both; }
        .tools-grid.animate .tool-card:nth-child(5) { animation: fadeInUp 0.45s 0.25s ease both; }
        .tools-grid.animate .tool-card:nth-child(6) { animation: fadeInUp 0.45s 0.30s ease both; }
        .tools-grid.animate .tool-card:nth-child(7) { animation: fadeInUp 0.45s 0.35s ease both; }
        .tools-grid.animate .tool-card:nth-child(8) { animation: fadeInUp 0.45s 0.40s ease both; }
        .tkt-table { width:100%; border-collapse:collapse; font-size:0.85rem; }
        .tkt-table th { background:#f7fafc; color:#4a5568; font-weight:600; padding:0.6rem 0.75rem; text-align:left; border-bottom:2px solid #e2e8f0; position:sticky; top:0; z-index:1; }
        .tkt-table td { padding:0.6rem 0.75rem; border-bottom:1px solid #e2e8f0; color:#2d3748; vertical-align:middle; }
        .tkt-table tr:hover td { background:#f0fdf4; }
        .badge-open        { background:#fef3c7; color:#92400e; padding:0.18rem 0.55rem; border-radius:20px; font-size:0.75rem; font-weight:600; white-space:nowrap; }
        .badge-underreview { background:#e0e7ff; color:#3730a3; padding:0.18rem 0.55rem; border-radius:20px; font-size:0.75rem; font-weight:600; white-space:nowrap; }
        .badge-ongoing     { background:#dbeafe; color:#1e40af; padding:0.18rem 0.55rem; border-radius:20px; font-size:0.75rem; font-weight:600; white-space:nowrap; }
        .badge-resolved    { background:#d1fae5; color:#065f46; padding:0.18rem 0.55rem; border-radius:20px; font-size:0.75rem; font-weight:600; white-space:nowrap; }
        .tkt-action-btn { border:none; border-radius:6px; padding:0.3rem 0.75rem; font-size:0.78rem; font-weight:600; cursor:pointer; transition:opacity 0.2s; }
        .tkt-action-btn:hover { opacity:0.8; }
        .btn-tbl-red { background:#e53e3e; color:white; }
        .btn-tbl-green  { background:#10b981; color:white; }
        .btn-tbl-purple { background:#805ad5; color:white; }
        .btn-tbl-blue   { background:#3182ce; color:white; }
        .badge-escalated { background:#fde8ff; color:#6b21a8; padding:0.18rem 0.55rem; border-radius:20px; font-size:0.75rem; font-weight:600; }
        .toast { position:fixed; bottom:1.5rem; right:1.5rem; background:#2d3748; color:white; padding:0.75rem 1.25rem; border-radius:10px; font-size:0.88rem; font-weight:600; z-index:9999; opacity:0; transform:translateY(12px); transition:opacity 0.3s,transform 0.3s; pointer-events:none; }
        .toast.show { opacity:1; transform:translateY(0); }
        /* ── IMAGE LIGHTBOX ── */
        .lightbox-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:9998; align-items:center; justify-content:center; }
        .lightbox-overlay.open { display:flex; }
        .lightbox-img { max-width:90vw; max-height:85vh; border-radius:8px; box-shadow:0 8px 40px rgba(0,0,0,0.6); transform-origin:center center; transition:transform 0.15s ease; user-select:none; display:block; }
        .lightbox-close { position:fixed; top:1.25rem; right:1.5rem; background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.4); color:white; border-radius:50%; width:40px; height:40px; font-size:1.4rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:9999; }
        .lightbox-close:hover { background:rgba(255,255,255,0.3); }
        .lightbox-zoom { position:fixed; bottom:1.5rem; left:50%; transform:translateX(-50%); display:flex; gap:0.5rem; z-index:9999; }
        .lightbox-zoom button { background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.4); color:white; border-radius:8px; padding:0.4rem 1rem; font-size:1.1rem; cursor:pointer; }
        .lightbox-zoom button:hover { background:rgba(255,255,255,0.3); }
    </style>
</head>
<body>

    <video class="bg-video" autoplay loop muted playsinline>
        <source src="Videos/KLVER1VID.mp4" type="video/mp4">
    </video>
    <script>document.querySelector('.bg-video').playbackRate = 0.4;</script>

    <!-- HEADER -->
    <div class="heading">
        <div class="welcome">
            <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
            <div class="welcome-text">
                <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['email']); ?> 🍀</h1>
                <span>KlovrBank Admin Panel</span>
            </div>
        </div>
        <div class="header-actions">
            <button onclick="confirmLogout()" class="logout-btn">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </div>
    </div>

        <div class="admin-container">
            <aside class="admin-sidebar">
                <h2 class="sidebar-title">All Settings</h2>
                <ul class="sidebar-menu">
                    <li class="active" id="tab-tools"><a onclick="showSection('tools')"><i class='bx bx-cog'></i> Tools</a></li>
                    <li id="tab-tickets"><a onclick="showSection('tickets')"><i class='bx bx-purchase-tag'></i> Tickets</a></li>
                    <li id="tab-stats"><a onclick="showSection('stats')"><i class='bx bx-bar-chart-alt-2'></i> Statistics</a></li>
                </ul>
            </aside>

            <main class="admin-content">

                <!-- ══ TOOLS SECTION ══ -->
                <div id="section-tools" class="section-view">
                    <p class="content-title">Management Tools</p>
                    <div class="tools-grid-wrap">
                    <div class="tools-grid">

                        <div class="tool-card card-red">
                            <div class="tool-icon"><i class='bx bx-user-x'></i></div>
                            <h3>User Account Deletion</h3>
                            <p>Permanently remove a user account and all associated data from the system.</p>
                            <button class="tool-btn" onclick="showPanel('panel-delete-user')">Delete User</button>
                        </div>

                        <div class="tool-card card-green">
                            <div class="tool-icon"><i class='bx bx-user-plus'></i></div>
                            <h3>Support Account Creation</h3>
                            <p>Create a new support staff account and assign access credentials.</p>
                            <button class="tool-btn" onclick="showPanel('panel-create-support')">Create Support</button>
                        </div>

                        <div class="tool-card card-orange">
                            <div class="tool-icon"><i class='bx bx-user-minus'></i></div>
                            <h3>Support Account Deletion</h3>
                            <p>Remove a support staff account and revoke their system access.</p>
                            <button class="tool-btn" onclick="showPanel('panel-delete-support')">Delete Support</button>
                        </div>

                        <div class="tool-card card-blue">
                            <div class="tool-icon" id="verify-users-icon" style="position:relative;">
                                <i class='bx bx-user-check'></i>
                                <?php if (!empty($unverified_users)): ?>
                                <span style="position:absolute;top:-4px;right:-4px;background:#e53e3e;color:white;border-radius:50%;width:18px;height:18px;font-size:0.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;"><?php echo count($unverified_users); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3>Verify User Accounts</h3>
                            <p>Approve or reject newly registered user accounts awaiting verification.</p>
                            <button class="tool-btn" onclick="showPanel('panel-verify-users')">Verify Users</button>
                        </div>

                        <div class="tool-card card-teal">
                            <div class="tool-icon" id="msgToolIcon" style="position:relative;"><i class='bx bx-chat'></i><span id="msgBadge" style="display:none;position:absolute;top:-4px;right:-4px;background:#e53e3e;color:white;border-radius:50%;width:18px;height:18px;font-size:0.68rem;font-weight:700;align-items:center;justify-content:center;"></span></div>
                            <h3>Message Support Team</h3>
                            <p>Send and receive internal messages with the support staff.</p>
                            <button class="tool-btn" onclick="showPanel('panel-messaging')">Open Chat</button>
                        </div>

                        <div class="tool-card card-blue">
                            <div class="tool-icon"><i class='bx bx-group'></i></div>
                            <h3>Support Profiles</h3>
                            <p>View profile pages and performance data for all support agents.</p>
                            <button class="tool-btn" onclick="showPanel('panel-support-profiles')">View Profiles</button>
                        </div>

                        <div class="tool-card card-yellow">
                            <div class="tool-icon"><i class='bx bx-toggle-right'></i></div>
                            <h3>Manage User Status</h3>
                            <p>Set user accounts to Active, Inactive, or Deactivated.</p>
                            <button class="tool-btn" onclick="showPanel('panel-user-status')">Manage Status</button>
                        </div>

                    </div>
                    </div><!-- end tools-grid-wrap -->

                    <!-- Panel: Delete User -->
                    <div id="panel-delete-user" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-delete-user')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Delete User Account</p>
                        <div class="panel-body scrollable-table">
                            <?php if (empty($users)): ?>
                                <p style="color:#718096; font-size:0.9rem; padding:1.75rem;">No user accounts found.</p>
                            <?php else: ?>
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-users" placeholder="Search by email or bank account..." oninput="filterTable('search-users','user-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="user-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Email</th>
                                            <th>Bank Account</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="user-tbody">
                                        <?php foreach ($users as $i => $u): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><?php $ba = htmlspecialchars($u['bank_account']); echo strlen($ba) === 16 ? implode('-', str_split($ba, 4)) : $ba; ?></td>
                                            <td>
                                                <?php if (!$u['is_active'] || !$u['is_verified']): ?>
                                                    <span class="badge-inactive">Inactive</span>
                                                <?php elseif ($u['status'] === 'For Renewal'): ?>
                                                    <span class="badge-renewal">For Renewal</span>
                                                <?php else: ?>
                                                    <span class="badge-active">Active</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="delete-row-btn"
                                                    onclick="openDeleteModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')"
                                                ><i class='bx bx-trash'></i> Delete</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Panel: Create Support -->
                    <div id="panel-create-support" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-create-support')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Create Support Account</p>
                        <div class="panel-body">
                            <div id="create-support-msg" style="display:none;margin-bottom:1rem;"></div>
                            <div>
                                <label for="support_email">Email Address</label>
                                <input type="email" id="support_email" placeholder="support@klovrbank.com">
                                <label for="support_password">Password</label>
                                <div style="position:relative;margin-bottom:1rem;">
                                    <input type="password" id="support_password" placeholder="Enter password" style="width:100%;padding:0.6rem 2.2rem 0.6rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.9rem;background:white;color:#2d3748;margin-bottom:0;">
                                    <button type="button" onclick="togglePw('support_password','spEye')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#718096;font-size:1.05rem;padding:0;line-height:1;"><i class='bx bx-hide' id="spEye"></i></button>
                                </div>
                                <button class="panel-submit btn-green" onclick="doCreateSupport()"><i class='bx bx-user-plus'></i> Create Account</button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Delete Support -->
                    <div id="panel-delete-support" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-delete-support')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Delete Support Account</p>
                        <div class="panel-body scrollable-table">
                            <?php if (empty($support_accounts)): ?>
                                <p style="color:#718096; font-size:0.9rem; padding:1.75rem;">No support accounts found.</p>
                            <?php else: ?>
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-support" placeholder="Search by email..." oninput="filterTable('search-support','support-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="user-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Email</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="support-tbody">
                                        <?php foreach ($support_accounts as $i => $s): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                                            <td>
                                                <?php if ($s['is_active']): ?>
                                                    <span class="badge-active">Active</span>
                                                <?php else: ?>
                                                    <span class="badge-inactive">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="delete-row-btn"
                                                    onclick="openDeleteSupportModal(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['email'], ENT_QUOTES); ?>')"
                                                ><i class='bx bx-trash'></i> Delete</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Panel: Verify Users -->
                    <div id="panel-verify-users" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-verify-users')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Verify User Accounts</p>
                        <div class="panel-body scrollable-table">
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-verify" placeholder="Search by email..." oninput="filterTable('search-verify','verify-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="user-table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Email</th>
                                            <th>Bank Account</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="verify-tbody">
                                        <?php foreach ($unverified_users as $i => $u): ?>
                                        <tr id="verify-row-<?php echo $u['id']; ?>">
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td><?php $ba = htmlspecialchars($u['bank_account']); echo strlen($ba) === 16 ? implode('-', str_split($ba, 4)) : $ba; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                            <td style="display:flex;gap:0.5rem;">
                                                <button class="tkt-action-btn btn-tbl-green" onclick="doVerify(<?php echo $u['id']; ?>, 'verify', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')"><i class='bx bx-check'></i> Approve</button>
                                                <button class="tkt-action-btn btn-tbl-red" onclick="doVerify(<?php echo $u['id']; ?>, 'reject', '<?php echo htmlspecialchars($u['email'], ENT_QUOTES); ?>')"><i class='bx bx-x'></i> Reject</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: User Status -->
                    <div id="panel-user-status" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-user-status')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Manage User Status</p>
                        <div class="panel-body scrollable-table">
                            <?php if (empty($users)): ?>
                                <p style="color:#718096;font-size:0.9rem;padding:1.75rem;">No user accounts found.</p>
                            <?php else: ?>
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-status" placeholder="Search by email..." oninput="filterTable('search-status','status-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="user-table">
                                    <thead><tr><th>#</th><th>Email</th><th>Current Status</th><th>Change To</th></tr></thead>
                                    <tbody id="status-tbody">
                                        <?php foreach ($users as $i => $u): ?>
                                        <tr id="status-row-<?php echo $u['id']; ?>">
                                            <td><?php echo $i + 1; ?></td>
                                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                                            <td id="status-badge-<?php echo $u['id']; ?>">
                                                <?php
                                                    if (!$u['is_active'] || !$u['is_verified']) echo '<span class="badge-inactive">Inactive</span>';
                                                    elseif ($u['status'] === 'For Renewal') echo '<span class="badge-renewal">For Renewal</span>';
                                                    elseif ($u['status'] === 'Deactivated') echo '<span class="badge-inactive">Deactivated</span>';
                                                    else echo '<span class="badge-active">Active</span>';
                                                ?>
                                            </td>
                                            <td style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                                                <button class="tkt-action-btn btn-tbl-green" onclick="doStatusChange(<?php echo $u['id']; ?>,'Active','<?php echo htmlspecialchars($u['email'],ENT_QUOTES); ?>')">Active</button>
                                                <button class="tkt-action-btn" style="background:#718096;color:white;" onclick="doStatusChange(<?php echo $u['id']; ?>,'Inactive','<?php echo htmlspecialchars($u['email'],ENT_QUOTES); ?>')">Inactive</button>
                                                <button class="tkt-action-btn btn-tbl-red" onclick="doStatusChange(<?php echo $u['id']; ?>,'Deactivated','<?php echo htmlspecialchars($u['email'],ENT_QUOTES); ?>')">Deactivated</button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Panel: Messaging -->
                    <div id="panel-messaging" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-messaging')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Message Support Team</p>
                        <div class="panel-body" style="padding:0;display:flex;flex:1;min-height:0;overflow:hidden;">
                            <!-- Contacts sidebar -->
                            <div id="msgContacts" style="width:220px;flex-shrink:0;border-right:1.5px solid #e2e8f0;display:flex;flex-direction:column;overflow-y:auto;">
                                <div style="padding:0.65rem 1rem;border-bottom:1.5px solid #e2e8f0;">
                                    <button onclick="openBroadcastModal()" style="width:100%;background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;border-radius:7px;padding:0.5rem 0.75rem;font-size:0.82rem;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:0.4rem;"><i class='bx bx-broadcast'></i> Broadcast to All</button>
                                </div>
                                <?php if (empty($support_accounts)): ?>
                                    <div style="padding:1rem;font-size:0.85rem;color:#a0aec0;text-align:center;">No support agents.</div>
                                <?php else: ?>
                                <?php foreach ($support_accounts as $s): ?>
                                <div class="msg-contact" data-email="<?php echo htmlspecialchars($s['email'],ENT_QUOTES); ?>"
                                     onclick="openChat('<?php echo htmlspecialchars($s['email'],ENT_QUOTES); ?>')"
                                     style="padding:0.85rem 1rem;cursor:pointer;display:flex;align-items:center;gap:0.65rem;border-bottom:1px solid #f0f0f0;transition:background 0.15s;">
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;flex-shrink:0;">
                                        <i class='bx bx-user'></i>
                                    </div>
                                    <div style="min-width:0;flex:1;">
                                        <div style="font-size:0.82rem;font-weight:600;color:#2d3748;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($s['email']); ?></div>
                                        <div style="font-size:0.72rem;color:#a0aec0;">Support Agent</div>
                                    </div>
                                    <span class="msg-unread-badge" id="badge-<?php echo htmlspecialchars($s['email'],ENT_QUOTES); ?>" style="display:none;background:#f56565;color:white;border-radius:50%;width:18px;height:18px;font-size:0.68rem;font-weight:700;align-items:center;justify-content:center;flex-shrink:0;"></span>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <!-- Chat area -->
                            <div style="flex:1;display:flex;flex-direction:column;min-width:0;">
                                <div id="msgChatPlaceholder" style="flex:1;display:flex;align-items:center;justify-content:center;color:#a0aec0;font-size:0.9rem;flex-direction:column;gap:0.5rem;">
                                    <i class='bx bx-chat' style="font-size:2.5rem;"></i>
                                    <span>Select a support agent to start chatting</span>
                                </div>
                                <div id="msgChatArea" style="flex:1;display:none;flex-direction:column;min-height:0;">
                                    <div id="msgChatHeader" style="padding:0.75rem 1rem;border-bottom:1.5px solid #e2e8f0;font-size:0.88rem;font-weight:600;color:#2d3748;display:flex;align-items:center;gap:0.5rem;">
                                        <i class='bx bx-user-circle' style="color:#10b981;font-size:1.2rem;"></i>
                                        <span id="msgChatHeaderName"></span>
                                    </div>
                                    <div id="msgThread" style="flex:1;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:0.5rem;"></div>
                                    <div style="padding:0.75rem 1rem;border-top:1.5px solid #e2e8f0;display:flex;gap:0.5rem;">
                                        <textarea id="msgInput" placeholder="Type a message..." style="flex:1;padding:0.6rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;resize:none;min-height:52px;font-family:inherit;"></textarea>
                                        <button onclick="sendMsg()" style="background:#10b981;color:white;border:none;border-radius:7px;padding:0.6rem 1rem;font-weight:600;cursor:pointer;align-self:flex-end;"><i class='bx bx-send'></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Support Profiles -->
                    <div id="panel-support-profiles" class="panel">
                        <button class="back-btn" onclick="hidePanel('panel-support-profiles')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Support Agent Profiles</p>
                        <div class="panel-body scrollable-table">
                            <?php if (empty($support_accounts)): ?>
                                <p style="color:#718096;font-size:0.9rem;padding:1.75rem;">No support accounts found.</p>
                            <?php else: ?>
                            <div class="table-scroll">
                                <table class="user-table">
                                    <thead><tr><th>#</th><th>Avatar</th><th>Email</th><th>Status</th><th>Action</th></tr></thead>
                                    <tbody>
                                        <?php foreach ($support_accounts as $i => $s): ?>
                                        <tr>
                                            <td><?php echo $i + 1; ?></td>
                                            <td>
                                                <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);display:flex;align-items:center;justify-content:center;color:white;font-size:1rem;overflow:hidden;">
                                                    <?php if (!empty($s['profile_picture'])): ?>
                                                        <img src="<?php echo htmlspecialchars($s['profile_picture']); ?>" style="width:100%;height:100%;object-fit:cover;object-position:<?php echo htmlspecialchars($s['profile_picture_offset'] ?? '50% 50%'); ?>;">
                                                    <?php else: ?>
                                                        <i class='bx bx-user'></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                                            <td><?php echo $s['is_active'] ? '<span class="badge-active">Active</span>' : '<span class="badge-inactive">Inactive</span>'; ?></td>
                                            <td><a href="admin_view_support_profile.php?id=<?php echo $s['id']; ?>" class="tkt-action-btn btn-tbl-blue" style="text-decoration:none;"><i class='bx bx-user-circle'></i> View Profile</a></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
                <div id="section-tickets" class="section-view" style="display:none;">
                    <p class="content-title">Ticket Management</p>
                    <div class="tools-grid-wrap">
                    <div class="tools-grid" id="tickets-grid">
                        <div class="tool-card card-blue">
                            <div class="tool-icon"><i class='bx bx-list-ul'></i></div>
                            <h3>View All Tickets</h3>
                            <p>Browse and search all submitted support tickets across the system.</p>
                            <button class="tool-btn" onclick="showTicketPanel('panel-view-tickets')">View Tickets</button>
                        </div>
                        <div class="tool-card card-orange">
                            <div class="tool-icon"><i class='bx bx-transfer'></i></div>
                            <h3>Reassign Ticket</h3>
                            <p>Transfer an open ticket to a different support agent.</p>
                            <button class="tool-btn" onclick="showTicketPanel('panel-reassign')">Reassign Ticket</button>
                        </div>
                        <div class="tool-card card-red">
                            <div class="tool-icon"><i class='bx bx-trash'></i></div>
                            <h3>Delete Ticket</h3>
                            <p>Permanently remove a ticket and its conversation history.</p>
                            <button class="tool-btn" onclick="showTicketPanel('panel-delete-ticket')">Delete Ticket</button>
                        </div>
                        <div class="tool-card card-teal">
                            <div class="tool-icon"><i class='bx bx-export'></i></div>
                            <h3>Export Tickets</h3>
                            <p>Download ticket data as a CSV report.</p>
                            <button class="tool-btn" onclick="showTicketPanel('panel-export')">Export Tickets</button>
                        </div>
                    </div>
                    </div><!-- end tickets tools-grid-wrap -->

                    <!-- Panel: View All Tickets -->
                    <div id="panel-view-tickets" class="panel">
                        <button class="back-btn" onclick="hideTicketPanel('panel-view-tickets')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">All Tickets</p>
                        <div class="panel-body scrollable-table">
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-view-tickets" placeholder="Search by ID, email, subject..." oninput="filterTable('search-view-tickets','view-tickets-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="tkt-table">
                                    <thead><tr><th>#</th><th>User</th><th>Subject</th><th>Category</th><th>Status</th><th>Assigned To</th><th style="cursor:pointer;user-select:none;" onclick="sortViewTickets()">Date <span id="viewDateSortIcon">&#8597;</span></th></tr></thead>
                                    <tbody id="view-tickets-tbody">
                                    <?php foreach ($all_tickets as $t): ?>
                                    <tr data-created="<?php echo $t['created_at']; ?>" style="cursor:pointer;" onclick="openAdminTicketDetail(<?php echo htmlspecialchars(json_encode($t),ENT_QUOTES); ?>)">
                                        <td>#<?php echo htmlspecialchars($t['display_id']); ?></td>
                                        <td><?php echo htmlspecialchars($t['user_email']); ?></td>
                                        <td><?php echo htmlspecialchars($t['subject']); ?></td>
                                        <td><?php echo ucfirst($t['category']); ?></td>
                                        <td><?php
                                            $sc = $t['status'] === 'On-Going' ? 'ongoing' : ($t['status'] === 'Under Review' ? 'underreview' : strtolower($t['status']));
                                            echo '<span class="badge-'.$sc.'">'.$t['status'].'</span>';
                                        ?></td>
                                        <td><?php echo $t['assigned_support_email'] ? htmlspecialchars($t['assigned_support_email']) : '<span style="color:#a0aec0;font-size:0.78rem;">Unassigned</span>'; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Delete Ticket -->
                    <div id="panel-delete-ticket" class="panel">
                        <button class="back-btn" onclick="hideTicketPanel('panel-delete-ticket')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Delete Ticket</p>
                        <div class="panel-body scrollable-table">
                            <div style="padding:0.75rem 1.75rem 0.5rem;">
                                <input type="text" id="search-del-tickets" placeholder="Search tickets..." oninput="filterTable('search-del-tickets','del-tickets-tbody')" style="width:100%;padding:0.5rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;outline:none;">
                            </div>
                            <div class="table-scroll">
                                <table class="tkt-table">
                                    <thead><tr><th>#</th><th>User</th><th>Subject</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                                    <tbody id="del-tickets-tbody">
                                    <?php foreach ($all_tickets as $t): if ($t['status'] !== 'Resolved') continue; ?>
                                    <tr id="del-row-<?php echo htmlspecialchars($t['display_id']); ?>">
                                        <td>#<?php echo htmlspecialchars($t['display_id']); ?></td>
                                        <td><?php echo htmlspecialchars($t['user_email']); ?></td>
                                        <td><?php echo htmlspecialchars($t['subject']); ?></td>
                                        <td><span class="badge-resolved">Resolved</span></td>
                                        <td><?php echo date('M d, Y', strtotime($t['created_at'])); ?></td>
                                        <td><button class="tkt-action-btn btn-tbl-red" onclick="openTktConfirm('delete','<?php echo htmlspecialchars($t['display_id'],ENT_QUOTES); ?>','<?php echo htmlspecialchars($t['subject'],ENT_QUOTES); ?>')"><i class='bx bx-trash'></i> Delete</button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: Reassign Ticket -->
                    <div id="panel-reassign" class="panel">
                        <button class="back-btn" onclick="hideTicketPanel('panel-reassign')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Reassign Ticket</p>
                        <div class="panel-body">
                            <label>Select Ticket</label>
                            <select id="reassign-ticket-id">
                                <option value="">-- Select a ticket --</option>
                                <?php foreach ($all_tickets as $t): ?>
                                <option value="<?php echo htmlspecialchars($t['display_id']); ?>">
                                    #<?php echo htmlspecialchars($t['display_id']); ?> — <?php echo htmlspecialchars($t['subject']); ?> (<?php echo $t['status']; ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <label>Assign To</label>
                            <select id="reassign-support-email">
                                <option value="">-- Select a support agent --</option>
                                <?php foreach ($support_accounts as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['email']); ?>"><?php echo htmlspecialchars($s['email']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="panel-submit btn-orange" onclick="doReassign()"><i class='bx bx-transfer'></i> Reassign Ticket</button>
                        </div>
                    </div>

                    <!-- Panel: Export Tickets -->
                    <div id="panel-export" class="panel">
                        <button class="back-btn" onclick="hideTicketPanel('panel-export')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Export Tickets</p>
                        <div class="panel-body">
                            <p style="font-size:0.85rem;color:#718096;margin-bottom:1.25rem;">Filter the tickets you want to export, then click Download CSV.</p>
                            <form action="admin_export_tickets.php" method="GET" target="_blank" style="display:flex;flex-wrap:wrap;gap:1rem;align-items:flex-end;">
                                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                                    <label>Status</label>
                                    <select name="status" style="padding:0.55rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;background:white;margin-bottom:0;">
                                        <option value="">All Statuses</option>
                                        <option value="Open">Open</option>
                                        <option value="On-Going">On-Going</option>
                                        <option value="Resolved">Resolved</option>
                                    </select>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                                    <label>Category</label>
                                    <select name="category" style="padding:0.55rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;background:white;margin-bottom:0;">
                                        <option value="">All Categories</option>
                                        <option value="technical">Technical</option>
                                        <option value="account">Account</option>
                                        <option value="billing">Billing</option>
                                        <option value="general">General</option>
                                    </select>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                                    <label>From Date</label>
                                    <input type="date" name="from" style="padding:0.55rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;background:white;margin-bottom:0;">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                                    <label>To Date</label>
                                    <input type="date" name="to" style="padding:0.55rem 0.85rem;border:1.5px solid #cbd5e0;border-radius:7px;font-size:0.88rem;background:white;margin-bottom:0;">
                                </div>
                                <div style="display:flex;flex-direction:column;gap:0.3rem;">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="panel-submit btn-green"><i class='bx bx-download'></i> Download CSV</button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- ══ STATISTICS SECTION ══ -->
                <?php
                    $total        = count($all_tickets);
                    $open         = count(array_filter($all_tickets, fn($t) => $t['status'] === 'Open'));
                    $under_review = count(array_filter($all_tickets, fn($t) => $t['status'] === 'Under Review'));
                    $ongoing      = count(array_filter($all_tickets, fn($t) => $t['status'] === 'On-Going'));
                    $resolved     = count(array_filter($all_tickets, fn($t) => $t['status'] === 'Resolved'));
                    $escalated    = 0;

                    $by_category = [];
                    foreach ($all_tickets as $t) {
                        $cat = ucfirst($t['category']);
                        $by_category[$cat] = ($by_category[$cat] ?? 0) + 1;
                    }
                    arsort($by_category);

                    // Support performance: resolved tickets per agent
                    $perf = [];
                    foreach ($all_tickets as $t) {
                        $agent = $t['assigned_support_email'] ?: 'Unassigned';
                        if (!isset($perf[$agent])) $perf[$agent] = ['total'=>0,'resolved'=>0,'ongoing'=>0,'under_review'=>0,'open'=>0];
                        $perf[$agent]['total']++;
                        if ($t['status']==='Resolved')     $perf[$agent]['resolved']++;
                        if ($t['status']==='On-Going')     $perf[$agent]['ongoing']++;
                        if ($t['status']==='Under Review') $perf[$agent]['under_review']++;
                        if ($t['status']==='Open')         $perf[$agent]['open']++;
                    }

                    // User activity: tickets per user
                    $user_activity = [];
                    foreach ($all_tickets as $t) {
                        $ue = $t['user_email'];
                        if (!isset($user_activity[$ue])) $user_activity[$ue] = ['total'=>0,'open'=>0,'under_review'=>0,'ongoing'=>0,'resolved'=>0];
                        $user_activity[$ue]['total']++;
                        if ($t['status']==='Open')         $user_activity[$ue]['open']++;
                        if ($t['status']==='Under Review') $user_activity[$ue]['under_review']++;
                        if ($t['status']==='On-Going')     $user_activity[$ue]['ongoing']++;
                        if ($t['status']==='Resolved')     $user_activity[$ue]['resolved']++;
                    }
                    arsort($user_activity);

                    $total_users  = count($users);
                    $active_users = count(array_filter($users, fn($u) => $u['is_active']));
                ?>
                <div id="section-stats" class="section-view" style="display:none;">
                    <p class="content-title">Statistics</p>
                    <div class="tools-grid-wrap">
                    <div class="tools-grid" id="stats-grid">

                        <div class="tool-card card-blue">
                            <div class="tool-icon"><i class='bx bx-line-chart'></i></div>
                            <h3>Ticket Overview</h3>
                            <p>View total, open, resolved, and escalated ticket counts.</p>
                            <button class="tool-btn" onclick="showStatsPanel('panel-stat-overview')">View</button>
                        </div>

                        <div class="tool-card card-green">
                            <div class="tool-icon"><i class='bx bx-user-check'></i></div>
                            <h3>Support Performance</h3>
                            <p>Track resolution rates per support agent.</p>
                            <button class="tool-btn" onclick="showStatsPanel('panel-stat-support')">View</button>
                        </div>

                        <div class="tool-card card-orange">
                            <div class="tool-icon"><i class='bx bx-group'></i></div>
                            <h3>User Activity</h3>
                            <p>Monitor ticket submissions and activity per user.</p>
                            <button class="tool-btn" onclick="showStatsPanel('panel-stat-users')">View</button>
                        </div>

                    </div>
                    </div><!-- end stats tools-grid-wrap -->

                    <!-- Panel: Ticket Overview -->
                    <div id="panel-stat-overview" class="panel">
                        <button class="back-btn" onclick="hideStatsPanel('panel-stat-overview')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Ticket Overview</p>
                        <div class="panel-body" style="overflow-y:auto;">
                            <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:1rem;margin-bottom:1.5rem;">
                                <div style="background:#ebf8ff;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#2b6cb0;"><?php echo $total; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Total Tickets</div>
                                </div>
                                <div style="background:#fef3c7;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#92400e;"><?php echo $open; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Open</div>
                                </div>
                                <div style="background:#e0e7ff;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#3730a3;"><?php echo $under_review; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Under Review</div>
                                </div>
                                <div style="background:#dbeafe;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#1e40af;"><?php echo $ongoing; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">On-Going</div>
                                </div>
                                <div style="background:#d1fae5;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#065f46;"><?php echo $resolved; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Resolved</div>
                                </div>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr;gap:1rem;margin-bottom:1.5rem;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#065f46;"><?php echo $total ? round($resolved/$total*100) : 0; ?>%</div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Resolution Rate</div>
                                </div>
                            </div>
                            <p style="font-size:0.8rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:0.65rem;">Tickets by Category</p>
                            <?php foreach ($by_category as $cat => $count): $pct = $total ? round($count/$total*100) : 0; ?>
                            <div style="margin-bottom:0.6rem;">
                                <div style="display:flex;justify-content:space-between;font-size:0.82rem;color:#4a5568;margin-bottom:0.2rem;">
                                    <span><?php echo $cat; ?></span><span><?php echo $count; ?> (<?php echo $pct; ?>%)</span>
                                </div>
                                <div style="background:#e2e8f0;border-radius:20px;height:8px;">
                                    <div style="background:#10b981;height:8px;border-radius:20px;width:<?php echo $pct; ?>%;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Panel: Support Performance -->
                    <div id="panel-stat-support" class="panel">
                        <button class="back-btn" onclick="hideStatsPanel('panel-stat-support')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">Support Performance</p>
                        <div class="panel-body scrollable-table">
                            <div class="table-scroll">
                                <table class="tkt-table">
                                    <thead><tr><th>Agent</th><th>Assigned</th><th>Open</th><th>Under Review</th><th>On-Going</th><th>Resolved</th><th>Resolution Rate</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($perf as $agent => $d): $rate = $d['total'] ? round($d['resolved']/$d['total']*100) : 0; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($agent); ?></td>
                                        <td><?php echo $d['total']; ?></td>
                                        <td><span class="badge-open"><?php echo $d['open']; ?></span></td>
                                        <td><span class="badge-underreview"><?php echo $d['under_review']; ?></span></td>
                                        <td><span class="badge-ongoing"><?php echo $d['ongoing']; ?></span></td>
                                        <td><span class="badge-resolved"><?php echo $d['resolved']; ?></span></td>
                                        <td>
                                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                                <div style="flex:1;background:#e2e8f0;border-radius:20px;height:7px;">
                                                    <div style="background:#10b981;height:7px;border-radius:20px;width:<?php echo $rate; ?>%;"></div>
                                                </div>
                                                <span style="font-size:0.78rem;font-weight:700;color:#065f46;white-space:nowrap;"><?php echo $rate; ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Panel: User Activity -->
                    <div id="panel-stat-users" class="panel">
                        <button class="back-btn" onclick="hideStatsPanel('panel-stat-users')"><i class='bx bx-arrow-back'></i> Back</button>
                        <p class="panel-title">User Activity</p>
                        <div class="panel-body" style="overflow-y:auto;">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1.5rem;">
                                <div style="background:#ebf8ff;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#2b6cb0;"><?php echo $total_users; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Total Users</div>
                                </div>
                                <div style="background:#d1fae5;border-radius:10px;padding:1rem;text-align:center;">
                                    <div style="font-size:1.8rem;font-weight:800;color:#065f46;"><?php echo $active_users; ?></div>
                                    <div style="font-size:0.78rem;color:#4a5568;margin-top:0.2rem;">Active Users</div>
                                </div>
                            </div>
                            <p style="font-size:0.8rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:0.65rem;">Tickets per User</p>
                            <table class="tkt-table">
                                <thead><tr><th>User Email</th><th>Total</th><th>Open</th><th>Under Review</th><th>On-Going</th><th>Resolved</th></tr></thead>
                                <tbody>
                                <?php foreach ($user_activity as $email => $d): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($email); ?></td>
                                    <td><strong><?php echo $d['total']; ?></strong></td>
                                    <td><span class="badge-open"><?php echo $d['open']; ?></span></td>
                                    <td><span class="badge-underreview"><?php echo $d['under_review']; ?></span></td>
                                    <td><span class="badge-ongoing"><?php echo $d['ongoing']; ?></span></td>
                                    <td><span class="badge-resolved"><?php echo $d['resolved']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </main>
        </div>
    
    <!-- LOGOUT CONFIRM MODAL -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-box">
            <div class="modal-icon"><i class='bx bx-log-out'></i></div>
            <h3>Log Out?</h3>
            <p>Are you sure you want to log out?</p>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="document.getElementById('logoutModal').classList.remove('open')">Cancel</button>
                <a href="logout_process.php" class="modal-confirm" style="text-decoration:none;"><i class='bx bx-log-out'></i> Yes, Log Out</a>
            </div>
        </div>
    </div>

    <!-- DELETE USER MODAL -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal-box">
            <div class="modal-icon"><i class='bx bx-error-circle'></i></div>
            <h3>Delete User Account?</h3>
            <p id="modal-msg">This action is permanent and cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeDeleteModal()">Cancel</button>
                <form id="delete-form" method="POST" action="admin_delete_user.php" style="margin:0;">
                    <input type="hidden" name="user_id" id="modal-user-id">
                    <button type="submit" class="modal-confirm"><i class='bx bx-trash'></i> Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <!-- TICKET ACTION CONFIRM MODAL -->
    <div class="modal-overlay" id="tktConfirmModal">
        <div class="modal-box">
            <div class="modal-icon"><i class='bx bx-error-circle'></i></div>
            <h3 id="tkt-modal-title">Confirm Action?</h3>
            <p id="tkt-modal-msg"></p>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeTktConfirm()">Cancel</button>
                <button class="modal-confirm" id="tkt-modal-confirm">Confirm</button>
            </div>
        </div>
    </div>

    <!-- BROADCAST MODAL -->
    <div class="modal-overlay" id="broadcastModal">
        <div class="modal-box" style="width:440px;text-align:left;">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1rem;">
                <i class='bx bx-broadcast' style="font-size:1.4rem;color:#10b981;"></i>
                <h3 style="font-size:1rem;color:#2d3748;">Broadcast to All Support Agents</h3>
                <button onclick="closeBroadcastModal()" style="margin-left:auto;background:none;border:none;font-size:1.3rem;cursor:pointer;color:#718096;"><i class='bx bx-x'></i></button>
            </div>
            <div id="broadcastResult" style="display:none;"></div>
            <textarea id="broadcastMsg" placeholder="Type a message to send to all support agents..." style="width:100%;padding:0.65rem 0.9rem;border:1.5px solid #cbd5e0;border-radius:8px;font-size:0.9rem;resize:none;min-height:90px;font-family:inherit;margin-bottom:1rem;"></textarea>
            <div style="display:flex;gap:0.75rem;justify-content:flex-end;">
                <button class="modal-cancel" onclick="closeBroadcastModal()">Cancel</button>
                <button onclick="sendBroadcast()" style="padding:0.55rem 1.4rem;border:none;border-radius:8px;background:#10b981;color:white;font-weight:600;cursor:pointer;"><i class='bx bx-send'></i> Send to All</button>
            </div>
        </div>
    </div>

    <div class="toast" id="toast"></div>

    <!-- ADMIN TICKET DETAIL MODAL -->
    <div class="modal-overlay" id="adminTktModal" style="z-index:1000;">
        <div style="background:white;border-radius:14px;width:560px;max-width:92vw;max-height:88vh;display:flex;flex-direction:column;box-shadow:0 12px 40px rgba(0,0,0,0.25);overflow:hidden;">
            <div style="background:linear-gradient(135deg,#10b981,#059669);color:white;padding:1rem 1.4rem;display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
                <span id="atModalId" style="font-weight:700;font-size:1rem;"></span>
                <button onclick="document.getElementById('adminTktModal').classList.remove('open')" style="background:none;border:none;color:white;font-size:1.4rem;cursor:pointer;line-height:1;"><i class='bx bx-x'></i></button>
            </div>
            <div style="padding:1.25rem 1.5rem;overflow-y:auto;flex:1;min-height:0;">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem 1.5rem;margin-bottom:1rem;font-size:0.88rem;color:#718096;">
                    <div><strong style="color:#4a5568;">Subject</strong><br><span id="atModalSubject"></span></div>
                    <div><strong style="color:#4a5568;">Category</strong><br><span id="atModalCategory"></span></div>
                    <div><strong style="color:#4a5568;">Status</strong><br><span id="atModalStatus"></span></div>
                    <div><strong style="color:#4a5568;">Date</strong><br><span id="atModalDate"></span></div>
                    <div><strong style="color:#4a5568;">User</strong><br><span id="atModalUser"></span></div>
                    <div><strong style="color:#4a5568;">Assigned To</strong><br><span id="atModalAssigned"></span></div>
                </div>
                <div style="background:#f7fafc;border-radius:8px;padding:1rem 1.2rem;font-size:0.92rem;color:#2d3748;line-height:1.8;white-space:pre-wrap;max-height:180px;overflow-y:auto;margin-bottom:0.75rem;" id="atModalContent"></div>
                <div id="atModalImgWrap" style="display:none;margin-bottom:0.75rem;">
                    <p style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:0.4rem;">Attached Image</p>
                    <img id="atModalImg" src="" style="max-width:100%;max-height:180px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:zoom-in;object-fit:contain;" onclick="openAdminLightbox(this.src)">
                </div>
            </div>
        </div>
    </div>

    <!-- IMAGE LIGHTBOX -->
    <div class="lightbox-overlay" id="adminLightbox" onclick="closeAdminLightbox()">
        <button class="lightbox-close" onclick="closeAdminLightbox()"><i class='bx bx-x'></i></button>
        <img class="lightbox-img" id="adminLightboxImg" src="" alt="" onclick="event.stopPropagation()">
        <div class="lightbox-zoom">
            <button onclick="event.stopPropagation();zoomAdmin(-0.25)"><i class='bx bx-zoom-out'></i></button>
            <button onclick="event.stopPropagation();zoomAdmin(0)"><i class='bx bx-reset'></i></button>
            <button onclick="event.stopPropagation();zoomAdmin(0.25)"><i class='bx bx-zoom-in'></i></button>
        </div>
    </div>

    <!-- DELETE SUPPORT MODAL -->
    <div class="modal-overlay" id="deleteSupportModal">
        <div class="modal-box">
            <div class="modal-icon"><i class='bx bx-error-circle'></i></div>
            <h3>Delete Support Account?</h3>
            <p id="modal-support-msg">This action is permanent and cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-cancel" onclick="closeDeleteSupportModal()">Cancel</button>
                <form id="delete-support-form" method="POST" action="admin_delete_support.php" style="margin:0;">
                    <input type="hidden" name="support_id" id="modal-support-id">
                    <button type="submit" class="modal-confirm"><i class='bx bx-trash'></i> Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        let _viewDateSortAsc = false;
        function sortViewTickets() {
            _viewDateSortAsc = !_viewDateSortAsc;
            document.getElementById('viewDateSortIcon').textContent = _viewDateSortAsc ? '\u2191' : '\u2193';
            const tbody = document.getElementById('view-tickets-tbody');
            const rows  = Array.from(tbody.querySelectorAll('tr'));
            rows.sort((a, b) => {
                const da = new Date(a.dataset.created), db = new Date(b.dataset.created);
                return _viewDateSortAsc ? da - db : db - da;
            });
            rows.forEach(r => tbody.appendChild(r));
        }

        function filterTable(inputId, tbodyId) {
            const q = document.getElementById(inputId).value.toLowerCase();
            document.querySelectorAll('#' + tbodyId + ' tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        }

        function openDeleteModal(id, email) {
            document.getElementById('modal-user-id').value = id;
            document.getElementById('modal-msg').textContent = 'You are about to permanently delete: ' + email;
            document.getElementById('deleteModal').classList.add('open');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('open');
        }
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
        document.getElementById('delete-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const userId = document.getElementById('modal-user-id').value;
            const fd = new FormData();
            fd.append('user_id', userId);
            fetch('admin_delete_user.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    closeDeleteModal();
                    if (res.success) {
                        document.querySelectorAll('#user-tbody tr').forEach(row => {
                            if (row.querySelector('button[onclick*="' + userId + '"]')) row.remove();
                        });
                        showToast('User deleted successfully.');
                    } else {
                        showToast('Error: delete failed.');
                    }
                });
        });

        function openDeleteSupportModal(id, email) {
            document.getElementById('modal-support-id').value = id;
            document.getElementById('modal-support-msg').textContent = 'You are about to permanently delete: ' + email;
            document.getElementById('deleteSupportModal').classList.add('open');
        }
        function closeDeleteSupportModal() {
            document.getElementById('deleteSupportModal').classList.remove('open');
        }
        document.getElementById('deleteSupportModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteSupportModal();
        });
        document.getElementById('delete-support-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const supportId = document.getElementById('modal-support-id').value;
            const fd = new FormData();
            fd.append('support_id', supportId);
            fetch('admin_delete_support.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    closeDeleteSupportModal();
                    if (res.success) {
                        document.querySelectorAll('#support-tbody tr').forEach(row => {
                            if (row.querySelector('button[onclick*="' + supportId + '"]')) row.remove();
                        });
                        showToast('Support account deleted successfully.');
                    } else {
                        showToast('Error: delete failed.');
                    }
                });
        });

        function showSection(name) {
            document.querySelectorAll('.section-view').forEach(s => s.style.display = 'none');
            document.getElementById('section-' + name).style.display = 'flex';
            document.querySelectorAll('.sidebar-menu li').forEach(li => li.classList.remove('active'));
            document.getElementById('tab-' + name).classList.add('active');
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            // Reset all grids and titles in the newly shown section
            const sec = document.getElementById('section-' + name);
            sec.querySelectorAll('.tools-grid-wrap').forEach(w => w.style.display = '');
            sec.querySelectorAll('.content-title').forEach(t => t.style.display = '');
            sec.querySelectorAll('.tools-grid').forEach(g => {
                g.classList.remove('animate');
                void g.offsetWidth;
                g.classList.add('animate');
            });
        }

        function showPanel(id) {
            const wrap = document.querySelector('#section-tools .tools-grid-wrap');
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
            wrap.style.display = 'none';
            document.querySelector('#section-tools .content-title').style.display = 'none';
            const panel = document.getElementById(id);
            panel.classList.add('active');
            if (id === 'panel-messaging') loadMessages();
            if (id === 'panel-verify-users') startVerifyPolling();
        }

        function confirmLogout() {
            document.getElementById('logoutModal').classList.add('open');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });

        function hidePanel(id) {
            document.getElementById(id).classList.remove('active');
            const wrap = document.querySelector('#section-tools .tools-grid-wrap');
            wrap.style.display = '';
            document.querySelector('#section-tools .content-title').style.display = '';
            if (id === 'panel-messaging') {
                clearInterval(_msgPollInterval);
                _currentChatEmail = null;
                document.getElementById('msgChatPlaceholder').style.display = 'flex';
                document.getElementById('msgChatArea').style.display = 'none';
                document.querySelectorAll('.msg-contact').forEach(c => c.style.background = '');
            }
            if (id === 'panel-verify-users') stopVerifyPolling();
        }

        function showTicketPanel(id) {
            const wrap = document.querySelector('#section-tickets .tools-grid-wrap');
            wrap.style.display = 'none';
            document.querySelector('#section-tickets .content-title').style.display = 'none';
            document.querySelectorAll('#section-tickets .panel').forEach(p => p.classList.remove('active'));
            document.getElementById(id).classList.add('active');
        }
        function hideTicketPanel(id) {
            document.getElementById(id).classList.remove('active');
            const wrap = document.querySelector('#section-tickets .tools-grid-wrap');
            wrap.style.display = '';
            document.querySelector('#section-tickets .content-title').style.display = '';
            const grid = document.getElementById('tickets-grid');
            grid.classList.remove('animate');
            void grid.offsetWidth;
            grid.classList.add('animate');
        }

        /* ── TICKET CONFIRM MODAL ── */
        let _tktAction = '', _tktId = '';
        function openTktConfirm(action, displayId, subject) {
            _tktAction = action; _tktId = displayId;
            const labels = { delete: 'Delete', close: 'Close', reassign: 'Reassign' };
            const colors = { delete: '#e53e3e', close: '#10b981', reassign: '#dd6b20' };
            document.getElementById('tkt-modal-title').textContent = labels[action] + ' Ticket?';
            document.getElementById('tkt-modal-msg').textContent = 'Ticket #' + displayId + ': ' + subject;
            document.getElementById('tkt-modal-confirm').style.background = colors[action] || '#e53e3e';
            document.getElementById('tkt-modal-confirm').textContent = 'Yes, ' + labels[action];
            document.getElementById('tktConfirmModal').classList.add('open');
        }
        function closeTktConfirm() {
            document.getElementById('tktConfirmModal').classList.remove('open');
        }
        document.getElementById('tktConfirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeTktConfirm();
        });
        document.getElementById('tkt-modal-confirm').addEventListener('click', function() {
            const fd = new FormData();
            fd.append('action', _tktAction);
            fd.append('display_id', _tktId);
            fetch('admin_ticket_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    closeTktConfirm();
                    if (res.success) {
                        if (_tktAction === 'delete') {
                            const row = document.getElementById('del-row-' + _tktId);
                            if (row) row.remove();
                        }
                        showToast('Done! Ticket #' + _tktId + ' ' + _tktAction + 'd.');
                    } else {
                        showToast(res.error === 'not_resolved' ? 'Only resolved tickets can be deleted.' : 'Error: action failed.');
                    }
                });
        });
        function showToast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        /* ── ADMIN LIVE TICKET POLLING ── */
        function pollAdminTickets() {
            fetch('get_tickets.php')
                .then(r => r.json())
                .then(tickets => {
                    const viewTbody  = document.getElementById('view-tickets-tbody');
                    const delTbody   = document.getElementById('del-tickets-tbody');
                    const sel        = document.getElementById('reassign-ticket-id');

                    const existingDisplayIds = new Set(
                        Array.from(viewTbody.querySelectorAll('tr'))
                            .map(r => { const td = r.querySelector('td'); return td ? td.textContent.replace('#','').trim() : ''; })
                            .filter(Boolean)
                    );

                    tickets.forEach(t => {
                        const statusCls = t.status === 'On-Going' ? 'ongoing' : (t.status === 'Under Review' ? 'underreview' : t.status.toLowerCase());

                        if (existingDisplayIds.has(t.display_id)) {
                            // Update status + assigned in view table
                            const viewRow = Array.from(viewTbody.querySelectorAll('tr')).find(r => {
                                const td = r.querySelector('td'); return td && td.textContent.trim() === '#' + t.display_id;
                            });
                            if (viewRow) {
                                const cells = viewRow.querySelectorAll('td');
                                if (cells[4]) cells[4].innerHTML = `<span class="badge-${statusCls}">${t.status}</span>`;
                                if (cells[5]) cells[5].innerHTML = t.assigned_support_email ? t.assigned_support_email : '<span style="color:#a0aec0;font-size:0.78rem;">Unassigned</span>';
                                viewRow.dataset.created = t.created_at;
                                viewRow.onclick = () => openAdminTicketDetail(t);
                            }
                            // Update reassign option label
                            if (sel) {
                                const opt = sel.querySelector(`option[value="${t.display_id}"]`);
                                if (opt) opt.textContent = `#${t.display_id} — ${t.subject} (${t.status})`;
                            }
                            return;
                        }

                        // New ticket — add to all three tables and the reassign dropdown
                        const dateStr = new Date(t.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});

                        const viewTr = document.createElement('tr');
                        viewTr.dataset.created = t.created_at;
                        viewTr.style.cursor = 'pointer';
                        viewTr.innerHTML = `<td>#${t.display_id}</td><td>${t.user_email}</td><td>${t.subject}</td><td>${t.category.charAt(0).toUpperCase()+t.category.slice(1)}</td><td><span class="badge-${statusCls}">${t.status}</span></td><td>${t.assigned_support_email ? t.assigned_support_email : '<span style="color:#a0aec0;font-size:0.78rem;">Unassigned</span>'}</td><td>${dateStr}</td>`;
                        viewTr.onclick = () => openAdminTicketDetail(t);
                        viewTbody.insertBefore(viewTr, viewTbody.firstChild);

                        if (t.status !== 'Resolved') return;
                        const delTr = document.createElement('tr');
                        delTr.id = 'del-row-' + t.display_id;
                        delTr.innerHTML = `<td>#${t.display_id}</td><td>${t.user_email}</td><td>${t.subject}</td><td><span class="badge-resolved">Resolved</span></td><td>${dateStr}</td><td><button class="tkt-action-btn btn-tbl-red" onclick="openTktConfirm('delete','${t.display_id}','${t.subject.replace(/'/g,"\\'")}')"><i class='bx bx-trash'></i> Delete</button></td>`;
                        delTbody.insertBefore(delTr, delTbody.firstChild);

                        if (sel && !sel.querySelector(`option[value="${t.display_id}"]`)) {
                            const opt = document.createElement('option');
                            opt.value = t.display_id;
                            opt.textContent = `#${t.display_id} — ${t.subject} (${t.status})`;
                            sel.appendChild(opt);
                        }
                    });
                });
        }
        setInterval(pollAdminTickets, 7000);

        function doReassign() {
            const displayId = document.getElementById('reassign-ticket-id').value;
            const email     = document.getElementById('reassign-support-email').value;
            if (!displayId || displayId === '' || !email || email === '') { showToast('Please select both a ticket and a support agent.'); return; }
            const fd = new FormData();
            fd.append('action', 'reassign');
            fd.append('display_id', displayId);
            fd.append('support_email', email);
            fetch('admin_ticket_action.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showToast('Ticket #' + displayId + ' reassigned to ' + email);
                        document.getElementById('reassign-ticket-id').selectedIndex = 0;
                        document.getElementById('reassign-support-email').selectedIndex = 0;
                        // Update assigned column in view-tickets table live
                        const viewRow = Array.from(document.querySelectorAll('#view-tickets-tbody tr')).find(r => r.querySelector('td') && r.querySelector('td').textContent.trim() === '#' + displayId);
                        if (viewRow) {
                            const cells = viewRow.querySelectorAll('td');
                            if (cells[5]) cells[5].textContent = email;
                        }
                    } else {
                        showToast('Error: reassign failed.');
                    }
                });
        }

        function showStatsPanel(id) {
            document.querySelector('#section-stats .tools-grid-wrap').style.display = 'none';
            document.querySelector('#section-stats .content-title').style.display = 'none';
            document.querySelectorAll('#section-stats .panel').forEach(p => p.classList.remove('active'));
            document.getElementById(id).classList.add('active');
        }
        function hideStatsPanel(id) {
            document.getElementById(id).classList.remove('active');
            document.querySelector('#section-stats .tools-grid-wrap').style.display = '';
            document.querySelector('#section-stats .content-title').style.display = '';
            const grid = document.getElementById('stats-grid');
            grid.classList.remove('animate');
            void grid.offsetWidth;
            grid.classList.add('animate');
        }
        function togglePw(inputId, iconId) {
            const inp = document.getElementById(inputId);
            const ico = document.getElementById(iconId);
            if (inp.type === 'password') { inp.type = 'text'; ico.className = 'bx bx-show'; }
            else { inp.type = 'password'; ico.className = 'bx bx-hide'; }
        }

        function doCreateSupport() {
            const email = document.getElementById('support_email').value.trim();
            const password = document.getElementById('support_password').value;
            const msgEl = document.getElementById('create-support-msg');
            if (!email || !password) { msgEl.style.cssText='display:block;background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;font-size:0.88rem;'; msgEl.innerHTML='<i class="bx bx-error-circle"></i> Please fill in all fields.'; return; }
            const fd = new FormData();
            fd.append('email', email);
            fd.append('password', password);
            fetch('admin_create_support.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    msgEl.style.display = 'block';
                    if (res.success) {
                        msgEl.style.cssText='display:block;background:#d1fae5;color:#065f46;padding:10px;border-radius:7px;font-size:0.88rem;';
                        msgEl.innerHTML='<i class="bx bx-check-circle"></i> Support account created successfully.';
                        document.getElementById('support_email').value = '';
                        document.getElementById('support_password').value = '';
                    } else {
                        msgEl.style.cssText='display:block;background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;font-size:0.88rem;';
                        msgEl.innerHTML='<i class="bx bx-error-circle"></i> ' + (res.error === 'email_exists' ? 'That email is already registered.' : 'An error occurred.');
                    }
                });
        }

        /* ── MESSAGING ── */
        let _currentChatEmail = null;
        let _msgPollInterval  = null;

        function openChat(email) {
            _currentChatEmail = email;
            // Highlight active contact
            document.querySelectorAll('.msg-contact').forEach(c => {
                c.style.background = c.dataset.email === email ? '#f0fdf4' : '';
            });
            document.getElementById('msgChatHeaderName').textContent = email;
            document.getElementById('msgChatPlaceholder').style.display = 'none';
            document.getElementById('msgChatArea').style.display = 'flex';
            // Clear unread badge for this contact
            const badge = document.getElementById('badge-' + email);
            if (badge) badge.style.display = 'none';
            loadMessages();
            clearInterval(_msgPollInterval);
            _msgPollInterval = setInterval(loadMessages, 3000);
        }

        function loadMessages() {
            if (!_currentChatEmail) return;
            fetch('get_messages.php?with=' + encodeURIComponent(_currentChatEmail))
                .then(r => r.json())
                .then(msgs => {
                    const thread = document.getElementById('msgThread');
                    const wasAtBottom = thread.scrollHeight - thread.scrollTop <= thread.clientHeight + 40;
                    if (!msgs.length) {
                        thread.innerHTML = '<div style="text-align:center;color:#a0aec0;font-size:0.85rem;padding:1rem;">No messages yet. Say hello!</div>';
                        return;
                    }
                    thread.innerHTML = msgs.map(m => {
                        const isMe = m.sender_role === 'admin';
                        return `<div style="max-width:72%;padding:0.55rem 0.85rem;border-radius:14px;font-size:0.87rem;line-height:1.5;align-self:${isMe?'flex-end':'flex-start'};background:${isMe?'linear-gradient(135deg,#10b981,#059669)':'#f0f4f8'};color:${isMe?'white':'#2d3748'};border-bottom-${isMe?'right':'left'}-radius:4px;word-break:break-word;">
                            <div style="font-size:0.68rem;opacity:0.7;margin-bottom:0.2rem;">${m.created_at}</div>
                            ${m.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                        </div>`;
                    }).join('');
                    if (wasAtBottom) thread.scrollTop = thread.scrollHeight;
                    document.getElementById('msgBadge').style.display = 'none';
                });
        }

        function sendMsg() {
            const input = document.getElementById('msgInput');
            const msg = input.value.trim();
            if (!msg || !_currentChatEmail) return;
            const fd = new FormData();
            fd.append('message', msg);
            fd.append('receiver_email', _currentChatEmail);
            fetch('send_message.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { if (res.success) { input.value = ''; loadMessages(); } });
        }
        document.getElementById('msgInput').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
        });

        // Poll unread messages badge every 5s
        function pollUnreadBadge() {
            fetch('get_unread_messages.php').then(r=>r.json()).then(d=>{
                const b = document.getElementById('msgBadge');
                if (d.count > 0) {
                    b.textContent = d.count; b.style.display = 'flex';
                } else {
                    b.style.display = 'none';
                }
                Object.entries(d.by_sender).forEach(([email, count]) => {
                    const badge = document.getElementById('badge-' + email);
                    if (badge) { badge.textContent = count; badge.style.display = count > 0 ? 'flex' : 'none'; }
                });
            });
        }
        pollUnreadBadge();
        setInterval(pollUnreadBadge, 5000);

        /* ── ADMIN TICKET DETAIL ── */
        function openAdminTicketDetail(t) {
            const sc = t.status === 'On-Going' ? 'ongoing' : (t.status === 'Under Review' ? 'underreview' : t.status.toLowerCase());
            document.getElementById('atModalId').textContent      = '#' + t.display_id;
            document.getElementById('atModalSubject').textContent  = t.subject;
            document.getElementById('atModalCategory').textContent = t.category.charAt(0).toUpperCase() + t.category.slice(1);
            document.getElementById('atModalStatus').innerHTML     = '<span class="badge-' + sc + '">' + t.status + '</span>';
            document.getElementById('atModalDate').textContent     = new Date(t.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
            document.getElementById('atModalUser').textContent     = t.user_email;
            document.getElementById('atModalAssigned').textContent = t.assigned_support_email || 'Unassigned';
            document.getElementById('atModalContent').textContent  = t.content || '';
            const imgWrap = document.getElementById('atModalImgWrap');
            if (t.image_path) {
                document.getElementById('atModalImg').src = t.image_path;
                imgWrap.style.display = 'block';
            } else { imgWrap.style.display = 'none'; }
            document.getElementById('adminTktModal').classList.add('open');
        }
        document.getElementById('adminTktModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });

        /* ── LIGHTBOX ── */
        let _adminLbScale = 1;
        function openAdminLightbox(src) {
            _adminLbScale = 1;
            const img = document.getElementById('adminLightboxImg');
            img.src = src; img.style.transform = 'scale(1)';
            document.getElementById('adminLightbox').classList.add('open');
        }
        function closeAdminLightbox() { document.getElementById('adminLightbox').classList.remove('open'); }
        function zoomAdmin(delta) {
            if (delta === 0) { _adminLbScale = 1; }
            else { _adminLbScale = Math.min(4, Math.max(0.25, _adminLbScale + delta)); }
            document.getElementById('adminLightboxImg').style.transform = 'scale(' + _adminLbScale + ')';
        }
        document.getElementById('adminLightbox').addEventListener('wheel', function(e) {
            e.preventDefault(); zoomAdmin(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeAdminLightbox(); });

        /* ── USER STATUS ── */
        function doStatusChange(userId, newStatus, email) {
            const fd = new FormData();
            fd.append('user_id', userId);
            fd.append('new_status', newStatus);
            fetch('admin_update_user_status.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const cell = document.getElementById('status-badge-' + userId);
                        const badges = { Active: '<span class="badge-active">Active</span>', Inactive: '<span class="badge-inactive">Inactive</span>', Deactivated: '<span class="badge-inactive">Deactivated</span>' };
                        if (cell) cell.innerHTML = badges[newStatus] || '';
                        showToast(email + ' set to ' + newStatus + '.');
                    } else { showToast('Error: status update failed.'); }
                });
        }

        /* ── VERIFY USERS POLLING ── */
        let _verifyPollInterval = null;

        function refreshVerifyUsers() {
            fetch('get_unverified_users.php').then(r=>r.json()).then(users=>{
                const tbody = document.getElementById('verify-tbody');
                if (!tbody) return;
                const liveIds = new Set(users.map(u => String(u.id)));
                // Remove rows for users no longer pending
                Array.from(tbody.querySelectorAll('tr')).forEach(row => {
                    const id = row.id.replace('verify-row-','');
                    if (id && !liveIds.has(id)) row.remove();
                });
                // Add rows for newly registered users
                const existingIds = new Set(Array.from(tbody.querySelectorAll('tr')).map(r=>r.id.replace('verify-row-','')).filter(Boolean));
                users.forEach(u => {
                    if (existingIds.has(String(u.id))) return;
                    const ba = u.bank_account && u.bank_account.length === 16 ? u.bank_account.match(/.{4}/g).join('-') : (u.bank_account || '');
                    const date = new Date(u.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
                    const tr = document.createElement('tr');
                    tr.id = 'verify-row-' + u.id;
                    tr.innerHTML = `<td>${tbody.rows.length+1}</td><td>${u.email}</td><td>${ba}</td><td>${date}</td><td style="display:flex;gap:0.5rem;"><button class="tkt-action-btn btn-tbl-green" onclick="doVerify(${u.id},'verify','${u.email.replace(/'/g,"\\'")}')"><i class='bx bx-check'></i> Approve</button><button class="tkt-action-btn btn-tbl-red" onclick="doVerify(${u.id},'reject','${u.email.replace(/'/g,"\\'")}')"><i class='bx bx-x'></i> Reject</button></td>`;
                    tbody.appendChild(tr);
                });
                // Update badge on the Verify Users tool card
                const iconWrap = document.getElementById('verify-users-icon');
                if (iconWrap) {
                    let badge = iconWrap.querySelector('span');
                    if (users.length > 0) {
                        if (!badge) { badge = document.createElement('span'); badge.style.cssText='position:absolute;top:-4px;right:-4px;background:#e53e3e;color:white;border-radius:50%;width:18px;height:18px;font-size:0.68rem;font-weight:700;display:flex;align-items:center;justify-content:center;'; iconWrap.appendChild(badge); }
                        badge.textContent = users.length;
                    } else if (badge) { badge.remove(); }
                }
            });
        }

        function startVerifyPolling() {
            refreshVerifyUsers(); // immediate fetch on panel open
            _verifyPollInterval = setInterval(refreshVerifyUsers, 5000);
        }
        function stopVerifyPolling() { clearInterval(_verifyPollInterval); _verifyPollInterval = null; }

        // Poll badge count even when panel is closed, and run immediately on load
        refreshVerifyUsers();
        setInterval(refreshVerifyUsers, 10000);

        /* ── VERIFY USERS ── */
        function doVerify(userId, action, email) {
            const label = action === 'verify' ? 'Approve' : 'Reject';
            if (!confirm(label + ' account: ' + email + '?')) return;
            const fd = new FormData();
            fd.append('action', action);
            fd.append('user_id', userId);
            fetch('admin_verify_user.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const row = document.getElementById('verify-row-' + userId);
                        if (row) row.remove();
                        showToast(email + ' ' + (action === 'verify' ? 'approved.' : 'rejected and removed.'));
                    } else {
                        showToast('Error: action failed.');
                    }
                });
        }

        /* ── BROADCAST MODAL ── */
        function openBroadcastModal() {
            document.getElementById('broadcastModal').classList.add('open');
            document.getElementById('broadcastMsg').value = '';
            document.getElementById('broadcastResult').style.display = 'none';
        }
        function closeBroadcastModal() {
            document.getElementById('broadcastModal').classList.remove('open');
        }
        document.getElementById('broadcastModal').addEventListener('click', function(e) {
            if (e.target === this) closeBroadcastModal();
        });
        function sendBroadcast() {
            const msg = document.getElementById('broadcastMsg').value.trim();
            const res = document.getElementById('broadcastResult');
            if (!msg) { res.style.cssText='display:block;background:#fee2e2;color:#991b1b;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;margin-bottom:0.75rem;'; res.innerHTML='<i class="bx bx-error-circle"></i> Please enter a message.'; return; }
            const agents = <?php echo json_encode(array_column($support_accounts, 'email')); ?>;
            if (!agents.length) { res.style.cssText='display:block;background:#fee2e2;color:#991b1b;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;margin-bottom:0.75rem;'; res.innerHTML='<i class="bx bx-error-circle"></i> No support agents found.'; return; }
            Promise.all(agents.map(email => {
                const fd = new FormData();
                fd.append('message', msg);
                fd.append('receiver_email', email);
                return fetch('send_message.php', { method: 'POST', body: fd }).then(r => r.json());
            })).then(() => {
                res.style.cssText='display:block;background:#d1fae5;color:#065f46;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;margin-bottom:0.75rem;';
                res.innerHTML='<i class="bx bx-check-circle"></i> Broadcast sent to ' + agents.length + ' agent(s).';
                document.getElementById('broadcastMsg').value = '';
                showToast('Broadcast sent to all support agents.');
            });
        }
    </script>

    <div class="footer">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
    </div>
</body>
</html>