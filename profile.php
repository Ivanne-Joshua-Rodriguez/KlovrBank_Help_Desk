<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php?error=unauthorized");
    exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT id, email, bank_account, role, is_active, is_verified, status, phone, display_name, created_at, house_no, street, subdivision, municipality, region, country, postal_code, profile_picture, profile_picture_offset FROM users WHERE email = ? AND role = 'user' LIMIT 1");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$ticket_counts = [];
foreach (['total' => '', 'open' => "AND status='Open'", 'ongoing' => "AND status='On-Going'", 'resolved' => "AND status='Resolved'"] as $key => $cond) {
    $r = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE user_email='{$conn->real_escape_string($user['email'])}' $cond");
    $ticket_counts[$key] = $r->fetch_assoc()['c'];
}

$tickets_result = $conn->query("SELECT display_id, subject, category, status, content, created_at FROM tickets WHERE user_email='{$conn->real_escape_string($user['email'])}' ORDER BY created_at DESC");
$user_tickets = $tickets_result ? $tickets_result->fetch_all(MYSQLI_ASSOC) : [];

$conn->close();

$ba = $user['bank_account'];
$masked_ba = strlen($ba) === 16
    ? '****-****-****-' . substr($ba, -4)
    : $ba;
$formatted_ba = strlen($ba) === 16
    ? implode('-', str_split($ba, 4))
    : $ba;
$member_since = date('F d, Y', strtotime($user['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body { min-height: 100vh; display: flex; flex-direction: column; }

        .bg-video {
            position: fixed; inset: 0;
            width: 100%; height: 100%;
            object-fit: cover; z-index: -1;
        }

        /* ── HEADER ── */
        .heading {
            background: #F1F3E0;
            padding: 1rem 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            display: flex; align-items: center; justify-content: space-between;
            position: relative; z-index: 100;
        }
        .welcome { display: flex; align-items: center; gap: 1rem; }
        .welcome video { height: 52px; width: auto; mix-blend-mode: multiply; }
        .welcome-text h1 { color: #2d3748; font-size: 1.4rem; }
        .welcome-text span { color: #10b981; font-size: 0.85rem; font-weight: 500; }
        .header-actions { display: flex; align-items: center; gap: 1rem; }

        .back-header-btn {
            background: #e8ead6; color: #2d5a27;
            border: none; border-radius: 8px;
            padding: 0.5rem 1.2rem;
            font-weight: 600; font-size: 0.9rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            transition: background 0.2s;
            text-decoration: none;
        }
        .back-header-btn:hover { background: #d6d9c0; }

        .logout-btn {
            background: #f56565; color: white;
            padding: 0.5rem 1.5rem; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            display: flex; align-items: center; gap: 0.4rem;
            transition: all 0.3s;
        }
        .logout-btn:hover { background: #e53e3e; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(245,101,101,0.4); }

        /* ── PAGE ── */
        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
            width: 100%;
        }

        /* ── PROFILE HERO ── */
        .profile-hero {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 16px;
            padding: 2rem 2.5rem;
            display: flex;
            align-items: center;
            gap: 2rem;
            box-shadow: 0 4px 20px rgba(16,185,129,0.3);
            animation: fadeInDown 0.5s ease both;
        }
        .avatar {
            width: 90px; height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 3px solid rgba(255,255,255,0.5);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: white;
            flex-shrink: 0;
            overflow: hidden;
            cursor: pointer;
            position: relative;
        }
        .avatar img { width:100%; height:100%; object-fit:cover; }
        .avatar-edit-hint {
            position:absolute; inset:0; background:rgba(0,0,0,0.45);
            display:flex; align-items:center; justify-content:center;
            opacity:0; transition:opacity 0.2s; border-radius:50%;
            color:white; font-size:1.4rem;
        }
        .avatar:hover .avatar-edit-hint { opacity:1; }

        /* ── AVATAR CROP MODAL ── */
        .crop-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:2000; align-items:center; justify-content:center; }
        .crop-overlay.open { display:flex; }
        .crop-modal { background:white; border-radius:16px; width:380px; max-width:92vw; overflow:hidden; box-shadow:0 12px 40px rgba(0,0,0,0.3); }
        .crop-modal-header { background:linear-gradient(135deg,#10b981,#059669); color:white; padding:1rem 1.4rem; font-weight:700; font-size:1rem; display:flex; justify-content:space-between; align-items:center; }
        .crop-modal-body { padding:1.25rem; }
        .crop-viewport {
            width:100%; height:260px;
            border-radius:10px; overflow:hidden;
            border:2px solid #e2e8f0;
            position:relative; cursor:grab; background:#f0f0f0;
            user-select:none;
        }
        .crop-viewport:active { cursor:grabbing; }
        .crop-viewport img { position:absolute; width:100%; height:100%; object-fit:cover; pointer-events:none; }
        .crop-circle-guide {
            position:absolute; inset:0;
            background: radial-gradient(circle 110px at center, transparent 110px, rgba(0,0,0,0.45) 110px);
            pointer-events:none;
        }
        .crop-modal-footer { padding:0 1.25rem 1.25rem; display:flex; gap:0.75rem; justify-content:flex-end; }
        .crop-cancel { padding:0.55rem 1.2rem; border:1.5px solid #cbd5e0; border-radius:8px; background:white; color:#4a5568; font-weight:600; cursor:pointer; }
        .crop-save { padding:0.55rem 1.4rem; border:none; border-radius:8px; background:linear-gradient(135deg,#10b981,#059669); color:white; font-weight:600; cursor:pointer; }
        .hero-info h2 { color: white; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .hero-info p  { color: rgba(255,255,255,0.8); font-size: 0.9rem; }
        .hero-badges  { display: flex; gap: 0.6rem; margin-top: 0.6rem; flex-wrap: wrap; }
        .hero-badge {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.4);
            color: white; border-radius: 20px;
            padding: 0.2rem 0.75rem; font-size: 0.78rem; font-weight: 600;
            display: flex; align-items: center; gap: 0.3rem;
        }

        /* ── STATS ROW ── */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            animation: fadeInUp 0.5s 0.1s ease both;
        }
        .stat-card {
            background: rgba(255,255,255,0.97);
            border-radius: 12px;
            padding: 1.2rem 1rem;
            text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card i { font-size: 1.8rem; padding: 0.5rem; border-radius: 8px; margin-bottom: 0.4rem; display: inline-block; }
        .stat-card.total i   { color: #10b981; background: #f0fdf4; }
        .stat-card.open i    { color: #f59e0b; background: #fffbeb; }
        .stat-card.ongoing i { color: #3b82f6; background: #eff6ff; }
        .stat-card.resolved i{ color: #8b5cf6; background: #f5f3ff; }
        .stat-card .val   { font-size: 1.8rem; font-weight: 700; color: #2d3748; line-height: 1; }
        .stat-card .label { font-size: 0.78rem; color: #718096; margin-top: 0.2rem; }

        /* ── DETAIL CARD ── */
        .detail-card {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            animation: fadeInUp 0.5s 0.2s ease both;
        }
        .detail-card-header {
            background: #f7fafc;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .detail-card-header h3 { font-size: 0.95rem; font-weight: 700; color: #2d3748; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-card-header i  { color: #10b981; font-size: 1.1rem; }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .detail-field {
            padding: 1.1rem 1.5rem;
            border-bottom: 1px solid #f0f4f8;
            display: flex; flex-direction: column; gap: 0.25rem;
        }
        .detail-field:nth-child(odd) { border-right: 1px solid #f0f4f8; }
        .detail-field.full { grid-column: 1 / -1; border-right: none; }
        .detail-field label {
            font-size: 0.72rem; font-weight: 700; color: #a0aec0;
            letter-spacing: 0.5px; text-transform: uppercase;
        }
        .detail-field .val {
            font-size: 0.95rem; color: #2d3748; font-weight: 500;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .detail-field .val i { color: #10b981; font-size: 1rem; }

        .badge-active    { background: #d1fae5; color: #065f46; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-inactive  { background: #fee2e2; color: #991b1b; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-renewal   { background: #fef3c7; color: #92400e; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-user      { background: #dbeafe; color: #1e40af; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }

        /* bank account toggle */
        .ba-toggle {
            background: none; border: none; cursor: pointer;
            color: #10b981; font-size: 1rem; padding: 0;
            display: flex; align-items: center;
            transition: color 0.2s;
        }
        .ba-toggle:hover { color: #059669; }

        /* ── FOOTER ── */
        .footer {
            background: rgba(255,255,255,0.97);
            padding: 1.2rem 2rem;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
            border-top: 2px solid #e2e8f0;
            animation: fadeInUp 0.5s 0.3s ease both;
        }
        .footer-left { display: flex; align-items: center; gap: 0.5rem; color: #4a5568; font-size: 0.9rem; }
        .footer-left i { color: #10b981; font-size: 1.1rem; }

        /* ── LOGOUT MODAL ── */
        .logout-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1001; align-items: center; justify-content: center; }
        .logout-modal-overlay.open { display: flex; }
        @keyframes modalPopIn {
            from { opacity: 0; transform: scale(0.85) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .logout-modal-overlay.open .logout-modal-box { animation: modalPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
        .logout-modal-box { background: white; border-radius: 14px; padding: 2rem; width: 340px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); text-align: center; }
        .logout-modal-box .modal-icon { font-size: 2.5rem; color: #f56565; margin-bottom: 0.75rem; }
        .logout-modal-box h3 { font-size: 1.1rem; color: #2d3748; margin-bottom: 0.5rem; }
        .logout-modal-box p  { font-size: 0.88rem; color: #718096; margin-bottom: 1.5rem; }
        .logout-modal-actions { display: flex; gap: 0.75rem; justify-content: center; }
        .logout-modal-cancel  { padding: 0.55rem 1.4rem; border: 1.5px solid #cbd5e0; border-radius: 8px; background: white; color: #4a5568; font-weight: 600; cursor: pointer; }
        .logout-modal-cancel:hover { background: #f7fafc; }
        .logout-modal-confirm { padding: 0.55rem 1.4rem; border: none; border-radius: 8px; background: #f56565; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        .logout-modal-confirm:hover { opacity: 0.85; }

        /* ── TICKETS SECTION ── */
        .tickets-card {
            background: rgba(255,255,255,0.97);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            animation: fadeInUp 0.5s 0.25s ease both;
        }
        .tickets-card-header {
            background: #f7fafc;
            padding: 1rem 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;
        }
        .tickets-card-header h3 { font-size: 0.95rem; font-weight: 700; color: #2d3748; text-transform: uppercase; letter-spacing: 0.5px; display: flex; align-items: center; gap: 0.5rem; }
        .tickets-card-header h3 i { color: #10b981; font-size: 1.1rem; }
        .filter-btns { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .filter-btn {
            padding: 0.3rem 0.85rem; border-radius: 20px;
            font-size: 0.78rem; font-weight: 700;
            border: 1.5px solid transparent; cursor: pointer;
            transition: all 0.2s;
            background: #edf2f7; color: #4a5568;
        }
        .filter-btn:hover { background: #e2e8f0; }
        .filter-btn.active { background: #10b981; color: white; border-color: #10b981; }

        .edit-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .tickets-list {
            padding: 1rem 1.5rem;
            display: flex; flex-direction: column; gap: 0.65rem;
            max-height: 420px; overflow-y: auto;
        }
        .ticket-block {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.9rem 1.1rem;
            cursor: pointer;
            transition: box-shadow 0.2s, border-color 0.2s, transform 0.15s;
            background: #fafafa;
            display: flex; align-items: center; justify-content: space-between; gap: 1rem;
        }
        .ticket-block:hover { box-shadow: 0 4px 14px rgba(0,0,0,0.09); border-color: #10b981; transform: translateY(-1px); }
        .ticket-block-left { display: flex; flex-direction: column; gap: 0.25rem; min-width: 0; }
        .ticket-block-id { font-size: 0.75rem; font-weight: 700; color: #10b981; }
        .ticket-block-subject { font-size: 0.93rem; font-weight: 600; color: #2d3748; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 400px; }
        .ticket-block-meta { font-size: 0.75rem; color: #a0aec0; }
        .ticket-block-right { flex-shrink: 0; }
        .status-pill { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 700; white-space: nowrap; }
        .pill-open     { background: #fef3c7; color: #92400e; }
        .pill-on-going { background: #dbeafe; color: #1e40af; }
        .pill-resolved { background: #d1fae5; color: #065f46; }
        .tickets-empty { padding: 2rem; text-align: center; color: #a0aec0; font-size: 0.9rem; }

        /* ── TICKET MODAL ── */
        .tkt-modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.5); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .tkt-modal-overlay.open { display: flex; }
        .tkt-modal {
            background: white; border-radius: 14px;
            width: 520px; max-width: 90vw;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .tkt-modal-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; padding: 1rem 1.4rem;
            display: flex; justify-content: space-between; align-items: center;
        }
        .tkt-modal-header span { font-weight: 700; font-size: 1rem; }
        .tkt-modal-close { background: none; border: none; color: white; font-size: 1.4rem; cursor: pointer; line-height: 1; }
        .tkt-modal-reply-btn {
            background: rgba(255,255,255,0.15); color: white;
            border: 1.5px solid rgba(255,255,255,0.5); border-radius: 7px;
            padding: 0.3rem 0.85rem; font-size: 0.82rem; font-weight: 600;
            cursor: pointer; transition: background 0.2s;
            display: flex; align-items: center; gap: 0.35rem;
        }
        .tkt-modal-reply-btn:hover { background: rgba(255,255,255,0.3); }
        .tkt-modal-body { padding: 1.5rem; }
        .tkt-modal-meta {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 0.6rem 1.5rem; margin-bottom: 1.25rem;
            font-size: 0.88rem; color: #718096;
        }
        .tkt-modal-meta strong { color: #4a5568; }
        .tkt-modal-content {
            background: #f7fafc; border-radius: 8px;
            padding: 1rem 1.2rem; font-size: 0.92rem;
            color: #2d3748; line-height: 1.8;
            white-space: pre-wrap; max-height: 160px; overflow-y: auto;
        }
        .tkt-conversation { margin-top: 1.25rem; border-top: 1.5px solid #e2e8f0; padding-top: 1rem; }
        .tkt-conversation h4 {
            font-size: 0.75rem; font-weight: 700; color: #a0aec0;
            letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 0.65rem;
        }
        .tkt-reply-thread { display: flex; flex-direction: column; gap: 0.6rem; max-height: 160px; overflow-y: auto; margin-bottom: 0.75rem; }
        .tkt-bubble {
            max-width: 78%; padding: 0.6rem 0.85rem;
            border-radius: 12px; font-size: 0.87rem; line-height: 1.6;
        }
        .tkt-bubble.user {
            align-self: flex-end;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border-bottom-right-radius: 4px;
        }
        .tkt-bubble.support {
            align-self: flex-start;
            background: #f0f4f8; color: #2d3748;
            border-bottom-left-radius: 4px;
        }
        .tkt-bubble .bubble-meta { font-size: 0.71rem; margin-bottom: 0.2rem; opacity: 0.75; }
        .tkt-reply-empty { font-size: 0.83rem; color: #a0aec0; text-align: center; padding: 0.4rem 0; }
        .tkt-reply-section { display: none; }
        .tkt-reply-section textarea {
            width: 100%; padding: 0.65rem 0.9rem;
            border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 0.9rem; resize: none; min-height: 75px;
            font-family: inherit; background: #fafafa;
            transition: border-color 0.2s;
        }
        .tkt-reply-section textarea:focus { outline: none; border-color: #10b981; background: white; }
        .tkt-reply-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 0.6rem; }
        .tkt-reply-from { font-size: 0.78rem; color: #a0aec0; }
        .tkt-send-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none; padding: 0.55rem 1.4rem;
            border-radius: 8px; font-weight: 600; font-size: 0.88rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            transition: all 0.2s;
        }
        .tkt-send-btn:hover { opacity: 0.88; transform: translateY(-1px); }
        .tkt-send-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        .tkt-reply-sent { font-size: 0.82rem; color: #059669; font-weight: 600; display: none; align-items: center; gap: 0.3rem; }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp   { from { opacity: 0; transform: translateY(28px);  } to { opacity: 1; transform: translateY(0); } }

        @media (max-width: 640px) {
            .heading { padding: 0.75rem 1rem; }
            .welcome video { height: 38px; }
            .welcome-text h1 { font-size: 1.1rem; }
            .header-actions { gap: 0.5rem; }
            .back-header-btn { padding: 0.45rem 0.75rem; font-size: 0.8rem; }
            .back-header-btn .btn-text { display: none; }
            .logout-btn { padding: 0.45rem 0.85rem; font-size: 0.82rem; }

            .page-wrapper { padding: 1rem; gap: 1rem; }

            .profile-hero { flex-direction: column; align-items: flex-start; padding: 1.25rem; gap: 1rem; }

            .stats-row { grid-template-columns: repeat(2, 1fr); }

            .detail-grid { grid-template-columns: 1fr; }
            .detail-field:nth-child(odd) { border-right: none; }
            .detail-field.full { grid-column: 1; }

            .edit-grid { grid-template-columns: 1fr; }

            .tkt-modal-meta { grid-template-columns: 1fr; }

            .ticket-block-subject { max-width: 200px; }

            .filter-btns { gap: 0.35rem; }
            .filter-btn { padding: 0.25rem 0.65rem; font-size: 0.72rem; }
        }
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
                <h1>KlovrBank</h1>
                <span>My Profile</span>
            </div>
        </div>
        <div class="header-actions">
            <a href="user_dashboard.php" class="back-header-btn">
                <i class='bx bx-arrow-back'></i> <span class="btn-text">Back to Dashboard</span>
            </a>
            <button onclick="confirmLogout()" class="logout-btn">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </div>
    </div>

    <!-- PAGE -->
    <div class="page-wrapper">

        <!-- HERO -->
        <div class="profile-hero">
            <div class="avatar" onclick="document.getElementById('avatarFileInput').click()" title="Change profile picture">
                <?php if (!empty($user['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" style="object-position:<?php echo htmlspecialchars($user['profile_picture_offset'] ?? '50% 50%'); ?>">
                <?php else: ?>
                    <i class='bx bx-user'></i>
                <?php endif; ?>
                <div class="avatar-edit-hint"><i class='bx bx-camera'></i></div>
            </div>
            <input type="file" id="avatarFileInput" accept="image/*" style="display:none" onchange="openCropModal(this)">
            <div class="hero-info">
                <h2><?php echo htmlspecialchars($user['email']); ?></h2>
                <p>Member since <?php echo $member_since; ?></p>
                <div class="hero-badges">
                    <span class="hero-badge"><i class='bx bx-shield-check'></i> Verified Account</span>
                    <span class="hero-badge"><i class='bx bx-user'></i> <?php echo ucfirst($user['role']); ?></span>
                    <?php if ($user['is_active']): ?>
                        <span class="hero-badge"><i class='bx bx-check-circle'></i> Active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TICKET STATS -->
        <div class="stats-row">
            <div class="stat-card total">
                <i class='bx bx-list-ul'></i>
                <div class="val"><?php echo $ticket_counts['total']; ?></div>
                <div class="label">Total Tickets</div>
            </div>
            <div class="stat-card open">
                <i class='bx bx-error-circle'></i>
                <div class="val"><?php echo $ticket_counts['open']; ?></div>
                <div class="label">Open</div>
            </div>
            <div class="stat-card ongoing">
                <i class='bx bx-time-five'></i>
                <div class="val"><?php echo $ticket_counts['ongoing']; ?></div>
                <div class="label">On-Going</div>
            </div>
            <div class="stat-card resolved">
                <i class='bx bx-check-circle'></i>
                <div class="val"><?php echo $ticket_counts['resolved']; ?></div>
                <div class="label">Resolved</div>
            </div>
        </div>

        <!-- ACCOUNT DETAILS -->
        <div class="detail-card">
            <div class="detail-card-header">
                <i class='bx bx-id-card'></i>
                <h3>Account Details</h3>
            </div>
            <div class="detail-grid">
                <div class="detail-field full">
                    <label>Email Address</label>
                    <div class="val"><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="detail-field">
                    <label>Bank Account Number</label>
                    <div class="val">
                        <i class='bx bx-credit-card'></i>
                        <span id="baDisplay"><?php echo htmlspecialchars($masked_ba); ?></span>
                        <button class="ba-toggle" onclick="toggleBA()" title="Show/Hide">
                            <i class='bx bx-show' id="baIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="detail-field">
                    <label>Account Status</label>
                    <div class="val">
                        <?php if (!$user['is_active'] || !$user['is_verified']): ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php elseif (($user['status'] ?? 'Active') === 'For Renewal'): ?>
                            <span class="badge-renewal"><i class='bx bx-time'></i> For Renewal</span>
                        <?php else: ?>
                            <span class="badge-active"><i class='bx bx-check'></i> Active</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-field">
                    <label>Role</label>
                    <div class="val"><i class='bx bx-user-circle'></i> <span class="badge-user"><?php echo ucfirst($user['role']); ?></span></div>
                </div>
                <div class="detail-field">
                    <label>Phone Number</label>
                    <div class="val"><i class='bx bx-phone'></i> <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : '<span style="color:#a0aec0;font-size:0.85rem;">Not set</span>'; ?></div>
                </div>
                <div class="detail-field">
                    <label>Account ID</label>
                    <div class="val"><i class='bx bx-hash'></i> <?php echo $user['id']; ?></div>
                </div>
                <div class="detail-field">
                    <label>Member Since</label>
                    <div class="val"><i class='bx bx-calendar'></i> <?php echo $member_since; ?></div>
                </div>
                <?php
                $addr_parts = array_filter([
                    $user['house_no'], $user['street'], $user['subdivision'],
                    $user['municipality'], $user['region'], $user['country'],
                    $user['postal_code'] ? $user['postal_code'] : null
                ]);
                $full_address = implode(', ', $addr_parts);
                ?>
                <div class="detail-field full">
                    <label>Home Address</label>
                    <div class="val"><i class='bx bx-map'></i> <?php echo $full_address ? htmlspecialchars($full_address) : '<span style="color:#a0aec0;font-size:0.85rem;">Not set</span>'; ?></div>
                </div>
            </div>
        </div>

        <!-- EDIT PROFILE -->
        <div class="detail-card" style="animation: fadeInUp 0.5s 0.21s ease both;">
            <div class="detail-card-header">
                <i class='bx bx-edit'></i>
                <h3>Edit Profile</h3>
            </div>
            <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:1rem;">
                <div id="profileEditMsg" style="display:none;"></div>
                <div class="edit-grid">
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Display Name</label>
                        <input type="text" id="editDisplayName" maxlength="60" placeholder="Enter display name" value="<?php echo htmlspecialchars($user['display_name'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        <span style="font-size:0.72rem;color:#a0aec0;">Shown instead of email prefix</span>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Email Address</label>
                        <input type="email" id="editEmail" placeholder="New email address" value="<?php echo htmlspecialchars($user['email']); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                    </div>
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Phone Number</label>
                        <input type="text" id="editPhone" maxlength="11" inputmode="numeric" placeholder="11-digit number" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        <span style="font-size:0.72rem;color:#a0aec0;">Exactly 11 digits</span>
                    </div>
                </div>

                <div style="border-top:1.5px solid #e2e8f0;padding-top:1rem;">
                    <p style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:0.75rem;"><i class='bx bx-map' style="font-size:0.9rem;"></i> Home Address</p>
                    <div class="edit-grid">
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">House No.</label>
                            <input type="text" id="editHouseNo" maxlength="20" placeholder="e.g. 12B" value="<?php echo htmlspecialchars($user['house_no'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Street</label>
                            <input type="text" id="editStreet" maxlength="100" placeholder="e.g. Rizal St." value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Subdivision / Barangay</label>
                            <input type="text" id="editSubdivision" maxlength="100" placeholder="e.g. Brgy. San Jose" value="<?php echo htmlspecialchars($user['subdivision'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Municipality / City</label>
                            <input type="text" id="editMunicipality" maxlength="100" placeholder="e.g. Quezon City" value="<?php echo htmlspecialchars($user['municipality'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Region</label>
                            <input type="text" id="editRegion" maxlength="100" placeholder="e.g. NCR" value="<?php echo htmlspecialchars($user['region'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Country</label>
                            <input type="text" id="editCountry" maxlength="100" placeholder="e.g. Philippines" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                        </div>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Postal Code</label>
                            <input type="text" id="editPostalCode" maxlength="4" inputmode="numeric" placeholder="4-digit code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" style="padding:0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                            <span style="font-size:0.72rem;color:#a0aec0;">Exactly 4 digits</span>
                        </div>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button onclick="saveProfile()" style="background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;padding:0.6rem 1.75rem;border-radius:8px;font-weight:600;font-size:0.9rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;"><i class='bx bx-save'></i> Save Changes</button>
                </div>
            </div>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="detail-card" style="animation: fadeInUp 0.5s 0.22s ease both;">
            <div class="detail-card-header">
                <i class='bx bx-lock-alt'></i>
                <h3>Change Password</h3>
            </div>
            <div style="padding:1.25rem 1.5rem;display:flex;flex-direction:column;gap:0.85rem;">
                <div id="pwChangeMsg" style="display:none;"></div>
                <div class="edit-grid">
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Current Password</label>
                        <div style="position:relative;">
                            <input type="password" id="oldPw" placeholder="Enter current password" style="width:100%;padding:0.65rem 2.2rem 0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                            <button type="button" onclick="togglePw('oldPw','oldPwEye')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#a0aec0;font-size:1rem;padding:0;"><i class='bx bx-hide' id="oldPwEye"></i></button>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">New Password</label>
                        <div style="position:relative;">
                            <input type="password" id="newPw" placeholder="Enter new password" style="width:100%;padding:0.65rem 2.2rem 0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                            <button type="button" onclick="togglePw('newPw','newPwEye')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#a0aec0;font-size:1rem;padding:0;"><i class='bx bx-hide' id="newPwEye"></i></button>
                        </div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:0.3rem;">
                        <label style="font-size:0.72rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;">Confirm New Password</label>
                        <div style="position:relative;">
                            <input type="password" id="confirmPw" placeholder="Confirm new password" style="width:100%;padding:0.65rem 2.2rem 0.65rem 0.85rem;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem;background:#fafafa;">
                            <button type="button" onclick="togglePw('confirmPw','confirmPwEye')" style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#a0aec0;font-size:1rem;padding:0;"><i class='bx bx-hide' id="confirmPwEye"></i></button>
                        </div>
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;">
                    <button onclick="changePassword()" style="background:linear-gradient(135deg,#10b981,#059669);color:white;border:none;padding:0.6rem 1.75rem;border-radius:8px;font-weight:600;font-size:0.9rem;cursor:pointer;display:flex;align-items:center;gap:0.4rem;"><i class='bx bx-lock-open-alt'></i> Update Password</button>
                </div>
            </div>
        </div>

        <!-- VIEW ALL TICKETS -->
        <div class="tickets-card">
            <div class="tickets-card-header">
                <h3><i class='bx bx-list-ul'></i> View All Tickets</h3>
                <div class="filter-btns">
                    <button class="filter-btn active" onclick="filterTickets('all', this)">All</button>
                    <button class="filter-btn" onclick="filterTickets('Open', this)">Open</button>
                    <button class="filter-btn" onclick="filterTickets('On-Going', this)">On-Going</button>
                    <button class="filter-btn" onclick="filterTickets('Resolved', this)">Resolved</button>
                </div>
            </div>
            <div class="tickets-list" id="ticketsList">
                <?php if (empty($user_tickets)): ?>
                    <div class="tickets-empty"><i class='bx bx-inbox' style="font-size:2rem;display:block;margin-bottom:0.5rem;"></i>No tickets submitted yet.</div>
                <?php else: ?>
                    <?php foreach ($user_tickets as $t):
                        $pill = 'pill-' . strtolower(str_replace(' ', '-', $t['status']));
                    ?>
                    <div class="ticket-block"
                         data-status="<?php echo htmlspecialchars($t['status']); ?>"
                         data-id="<?php echo htmlspecialchars($t['display_id'], ENT_QUOTES); ?>"
                         data-subject="<?php echo htmlspecialchars($t['subject'], ENT_QUOTES); ?>"
                         data-category="<?php echo htmlspecialchars(ucfirst($t['category']), ENT_QUOTES); ?>"
                         data-date="<?php echo date('M d, Y', strtotime($t['created_at'])); ?>"
                         data-content="<?php echo htmlspecialchars($t['content'], ENT_QUOTES); ?>">
                        <div class="ticket-block-left">
                            <span class="ticket-block-id">#<?php echo htmlspecialchars($t['display_id']); ?></span>
                            <span class="ticket-block-subject"><?php echo htmlspecialchars($t['subject']); ?></span>
                            <span class="ticket-block-meta"><?php echo ucfirst($t['category']); ?> &bull; <?php echo date('M d, Y', strtotime($t['created_at'])); ?></span>
                        </div>
                        <div class="ticket-block-right">
                            <span class="status-pill <?php echo $pill; ?>"><?php echo $t['status']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- TICKET MODAL -->
    <div class="tkt-modal-overlay" id="tktModal">
        <div class="tkt-modal">
            <div class="tkt-modal-header">
                <span id="tktModalId"></span>
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <button class="tkt-modal-reply-btn" onclick="toggleTktReply()"><i class='bx bx-reply'></i> Reply</button>
                    <button class="tkt-modal-close" onclick="closeTktModal()"><i class='bx bx-x'></i></button>
                </div>
            </div>
            <div class="tkt-modal-body">
                <div class="tkt-modal-meta">
                    <div><strong>Subject</strong><br><span id="tktModalSubject"></span></div>
                    <div><strong>Category</strong><br><span id="tktModalCategory"></span></div>
                    <div><strong>Status</strong><br><span id="tktModalStatus"></span></div>
                    <div><strong>Date Submitted</strong><br><span id="tktModalDate"></span></div>
                </div>
                <div class="tkt-modal-content" id="tktModalContent"></div>
                <div class="tkt-conversation">
                    <h4><i class='bx bx-conversation'></i> Conversation</h4>
                    <div class="tkt-reply-thread" id="tktReplyThread"></div>
                    <div class="tkt-reply-section" id="tktReplySection">
                        <textarea id="tktReplyMsg" placeholder="Write your reply to support..."></textarea>
                        <div class="tkt-reply-footer">
                            <span class="tkt-reply-from" id="tktReplyFrom"></span>
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <span class="tkt-reply-sent" id="tktReplySent"><i class='bx bx-check-circle'></i> Sent!</span>
                                <button class="tkt-send-btn" id="tktSendBtn" onclick="sendTktReply()"><i class='bx bx-send'></i> Send Reply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- AVATAR CROP MODAL -->
    <div class="crop-overlay" id="cropOverlay">
        <div class="crop-modal">
            <div class="crop-modal-header">
                <span><i class='bx bx-crop'></i> Position Your Photo</span>
                <button onclick="closeCropModal()" style="background:none;border:none;color:white;font-size:1.3rem;cursor:pointer;"><i class='bx bx-x'></i></button>
            </div>
            <div class="crop-modal-body">
                <p style="font-size:0.82rem;color:#718096;margin-bottom:0.75rem;">Drag the image to choose what shows in your profile circle.</p>
                <div class="crop-viewport" id="cropViewport">
                    <img id="cropImg" src="" draggable="false">
                    <div class="crop-circle-guide"></div>
                </div>
            </div>
            <div class="crop-modal-footer">
                <button class="crop-cancel" onclick="closeCropModal()">Cancel</button>
                <button class="crop-save" onclick="saveCrop()"><i class='bx bx-save'></i> Save Photo</button>
            </div>
        </div>
    </div>

    <!-- LOGOUT MODAL -->
    <div class="logout-modal-overlay" id="logoutModal">
        <div class="logout-modal-box">
            <div class="modal-icon"><i class='bx bx-log-out'></i></div>
            <h3>Log Out?</h3>
            <p>Are you sure you want to log out?</p>
            <div class="logout-modal-actions">
                <button class="logout-modal-cancel" onclick="document.getElementById('logoutModal').classList.remove('open')">Cancel</button>
                <a href="logout_process.php" class="logout-modal-confirm"><i class='bx bx-log-out'></i> Yes, Log Out</a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
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

        // Digits-only inputs
        document.getElementById('editPhone').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
        document.getElementById('editPostalCode').addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 4);
        });

        async function saveProfile() {
            const phone      = document.getElementById('editPhone').value.trim();
            const postalCode = document.getElementById('editPostalCode').value.trim();

            if (phone && phone.length !== 11) {
                showProfileMsg('error', 'Phone number must be exactly 11 digits.'); return;
            }
            if (postalCode && postalCode.length !== 4) {
                showProfileMsg('error', 'Postal code must be exactly 4 digits.'); return;
            }

            const updates = [
                { field: 'display_name',  value: document.getElementById('editDisplayName').value.trim() },
                { field: 'email',         value: document.getElementById('editEmail').value.trim() },
                { field: 'phone',         value: phone },
                { field: 'house_no',      value: document.getElementById('editHouseNo').value.trim() },
                { field: 'street',        value: document.getElementById('editStreet').value.trim() },
                { field: 'subdivision',   value: document.getElementById('editSubdivision').value.trim() },
                { field: 'municipality',  value: document.getElementById('editMunicipality').value.trim() },
                { field: 'region',        value: document.getElementById('editRegion').value.trim() },
                { field: 'country',       value: document.getElementById('editCountry').value.trim() },
                { field: 'postal_code',   value: postalCode },
            ];

            for (const u of updates) {
                const fd = new FormData();
                fd.append('field', u.field);
                fd.append('value', u.value);
                const res = await fetch('update_profile.php', { method: 'POST', body: fd }).then(r => r.json());
                if (!res.success) { showProfileMsg('error', res.error || 'Update failed.'); return; }
            }
            showProfileMsg('success', 'Profile updated successfully.');
        }

        function showProfileMsg(type, text) {
            const el = document.getElementById('profileEditMsg');
            el.style.cssText = type === 'success'
                ? 'display:block;background:#d1fae5;color:#065f46;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;'
                : 'display:block;background:#fee2e2;color:#991b1b;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;';
            el.innerHTML = (type === 'success' ? '<i class="bx bx-check-circle"></i> ' : '<i class="bx bx-error-circle"></i> ') + text;
            setTimeout(() => el.style.display = 'none', 4000);
        }

        function changePassword() {
            const old = document.getElementById('oldPw').value;
            const nw  = document.getElementById('newPw').value;
            const cnf = document.getElementById('confirmPw').value;
            const msg = document.getElementById('pwChangeMsg');
            if (!old || !nw || !cnf) { showPwMsg('error', 'Please fill in all fields.'); return; }
            if (nw !== cnf) { showPwMsg('error', 'New passwords do not match.'); return; }
            if (nw.length < 6) { showPwMsg('error', 'New password must be at least 6 characters.'); return; }
            const fd = new FormData();
            fd.append('old_password', old);
            fd.append('new_password', nw);
            fetch('change_password.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        showPwMsg('success', 'Password updated successfully.');
                        document.getElementById('oldPw').value = '';
                        document.getElementById('newPw').value = '';
                        document.getElementById('confirmPw').value = '';
                    } else {
                        showPwMsg('error', res.error || 'An error occurred.');
                    }
                });
        }

        function showPwMsg(type, text) {
            const el = document.getElementById('pwChangeMsg');
            el.style.cssText = type === 'success'
                ? 'display:block;background:#d1fae5;color:#065f46;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;'
                : 'display:block;background:#fee2e2;color:#991b1b;padding:0.6rem 0.9rem;border-radius:7px;font-size:0.85rem;';
            el.innerHTML = (type === 'success' ? '<i class="bx bx-check-circle"></i> ' : '<i class="bx bx-error-circle"></i> ') + text;
            setTimeout(() => el.style.display = 'none', 4000);
        }

        function confirmLogout() {
            document.getElementById('logoutModal').classList.add('open');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });

        const fullBA   = '<?php echo htmlspecialchars($formatted_ba); ?>';
        const maskedBA = '<?php echo htmlspecialchars($masked_ba); ?>';
        let baVisible  = false;

        function toggleBA() {
            baVisible = !baVisible;
            document.getElementById('baDisplay').textContent = baVisible ? fullBA : maskedBA;
            document.getElementById('baIcon').className = baVisible ? 'bx bx-hide' : 'bx bx-show';
        }

        function filterTickets(status, btn) {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            document.querySelectorAll('.ticket-block').forEach(block => {
                block.style.display = (status === 'all' || block.dataset.status === status) ? '' : 'none';
            });
        }

        let _currentTktId = null;

        document.getElementById('ticketsList').addEventListener('click', function(e) {
            const block = e.target.closest('.ticket-block');
            if (!block) return;
            _currentTktId = block.dataset.id;
            document.getElementById('tktModalId').textContent      = '#' + block.dataset.id;
            document.getElementById('tktModalSubject').textContent  = block.dataset.subject;
            document.getElementById('tktModalCategory').textContent = block.dataset.category;
            document.getElementById('tktModalStatus').textContent   = block.dataset.status;
            document.getElementById('tktModalDate').textContent     = block.dataset.date;
            document.getElementById('tktModalContent').textContent  = block.dataset.content;
            document.getElementById('tktReplyFrom').textContent     = 'From: ' + '<?php echo htmlspecialchars($_SESSION["email"]); ?>';
            document.getElementById('tktReplyMsg').value = '';
            document.getElementById('tktReplySent').style.display = 'none';
            document.getElementById('tktReplySection').style.display = 'none';
            loadTktReplies(_currentTktId);
            document.getElementById('tktModal').classList.add('open');
        });

        function loadTktReplies(displayId) {
            fetch(`get_replies.php?display_id=${displayId}`)
                .then(r => r.json())
                .then(replies => {
                    const thread = document.getElementById('tktReplyThread');
                    if (!replies.length) {
                        thread.innerHTML = '<div class="tkt-reply-empty">No replies yet.</div>';
                        return;
                    }
                    thread.innerHTML = replies.map(r =>
                        `<div class="tkt-bubble ${r.sender_role}">
                            <div class="bubble-meta">${r.sender_email} &bull; ${r.created_at}</div>
                            ${r.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                        </div>`
                    ).join('');
                    thread.scrollTop = thread.scrollHeight;
                });
        }

        function toggleTktReply() {
            const s = document.getElementById('tktReplySection');
            s.style.display = s.style.display === 'none' ? 'block' : 'none';
            if (s.style.display === 'block') setTimeout(() => document.getElementById('tktReplyMsg').focus(), 50);
        }

        function sendTktReply() {
            const msg = document.getElementById('tktReplyMsg').value.trim();
            if (!msg) { document.getElementById('tktReplyMsg').focus(); return; }
            const btn = document.getElementById('tktSendBtn');
            btn.disabled = true;
            const fd = new FormData();
            fd.append('display_id', _currentTktId);
            fd.append('message', msg);
            fetch('submit_reply.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('tktReplyMsg').value = '';
                        const sent = document.getElementById('tktReplySent');
                        sent.style.display = 'flex';
                        setTimeout(() => sent.style.display = 'none', 3000);
                        loadTktReplies(_currentTktId);
                    }
                })
                .finally(() => btn.disabled = false);
        }

        function closeTktModal() {
            document.getElementById('tktModal').classList.remove('open');
        }
        document.getElementById('tktModal').addEventListener('click', function(e) {
            if (e.target === this) closeTktModal();
        });
        /* ── AVATAR UPLOAD & CROP ── */
        let _cropFile = null, _cropOffsetX = 50, _cropOffsetY = 50;
        let _dragStart = null, _imgNatW = 0, _imgNatH = 0;

        function openCropModal(input) {
            if (!input.files[0]) return;
            _cropFile = input.files[0];
            if (_cropFile.size > 5 * 1024 * 1024) { alert('Image must be under 5MB.'); return; }
            const url = URL.createObjectURL(_cropFile);
            const img = document.getElementById('cropImg');
            img.onload = () => { _imgNatW = img.naturalWidth; _imgNatH = img.naturalHeight; };
            img.src = url;
            _cropOffsetX = 50; _cropOffsetY = 50;
            img.style.objectPosition = '50% 50%';
            document.getElementById('cropOverlay').classList.add('open');
            input.value = '';
        }

        function closeCropModal() {
            document.getElementById('cropOverlay').classList.remove('open');
            _cropFile = null;
        }

        // Drag to reposition
        const vp = document.getElementById('cropViewport');
        vp.addEventListener('mousedown', e => {
            _dragStart = { x: e.clientX, y: e.clientY, ox: _cropOffsetX, oy: _cropOffsetY };
        });
        window.addEventListener('mousemove', e => {
            if (!_dragStart) return;
            const vpRect = vp.getBoundingClientRect();
            const dx = (e.clientX - _dragStart.x) / vpRect.width  * 100;
            const dy = (e.clientY - _dragStart.y) / vpRect.height * 100;
            _cropOffsetX = Math.min(100, Math.max(0, _dragStart.ox - dx));
            _cropOffsetY = Math.min(100, Math.max(0, _dragStart.oy - dy));
            document.getElementById('cropImg').style.objectPosition = _cropOffsetX + '% ' + _cropOffsetY + '%';
        });
        window.addEventListener('mouseup', () => { _dragStart = null; });

        // Touch support
        vp.addEventListener('touchstart', e => {
            const t = e.touches[0];
            _dragStart = { x: t.clientX, y: t.clientY, ox: _cropOffsetX, oy: _cropOffsetY };
        }, { passive: true });
        window.addEventListener('touchmove', e => {
            if (!_dragStart) return;
            const t = e.touches[0];
            const vpRect = vp.getBoundingClientRect();
            const dx = (t.clientX - _dragStart.x) / vpRect.width  * 100;
            const dy = (t.clientY - _dragStart.y) / vpRect.height * 100;
            _cropOffsetX = Math.min(100, Math.max(0, _dragStart.ox - dx));
            _cropOffsetY = Math.min(100, Math.max(0, _dragStart.oy - dy));
            document.getElementById('cropImg').style.objectPosition = _cropOffsetX + '% ' + _cropOffsetY + '%';
        }, { passive: true });
        window.addEventListener('touchend', () => { _dragStart = null; });

        function saveCrop() {
            if (!_cropFile) return;
            const offset = _cropOffsetX.toFixed(1) + '% ' + _cropOffsetY.toFixed(1) + '%';
            const fd = new FormData();
            fd.append('avatar', _cropFile);
            fd.append('offset', offset);
            fetch('upload_profile_picture.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Update avatar on page
                        const avatarDiv = document.querySelector('.avatar');
                        let img = avatarDiv.querySelector('img');
                        if (!img) {
                            avatarDiv.innerHTML = '<img src="" style="width:100%;height:100%;object-fit:cover;"><div class="avatar-edit-hint"><i class="bx bx-camera"></i></div>';
                            img = avatarDiv.querySelector('img');
                        }
                        img.src = res.path + '?t=' + Date.now();
                        img.style.objectPosition = offset;
                        closeCropModal();
                    } else {
                        alert(res.error || 'Upload failed.');
                    }
                });
        }
    </script>
</body>

<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2025/03/01/02/20250301022520-TR4PWHYI.js" defer></script>

</html>

