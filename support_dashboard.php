<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

// Security Guard: Only allow 'support' role
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'support') {
    header("Location: admin_login.php?error=unauthorized");
    exit();
}
?>

<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "help_desk_db");

$agent_email = mysqli_real_escape_string($conn, $_SESSION['email']);

// Fetch tickets visible to this agent: Open (unassigned) + their own Under Review/On-Going/Resolved
$sql = "SELECT * FROM tickets WHERE status = 'Open' OR assigned_support_email = '$agent_email' ORDER BY is_escalated DESC, created_at DESC";
$result = mysqli_query($conn, $sql);

// Fetch counts for the sidebar
$count_all = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE status = 'Open' OR assigned_support_email = '$agent_email'"));
$count_open = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE status = 'Open'"));
$count_under_review = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE status = 'Under Review' AND assigned_support_email = '$agent_email'"));
$count_ongoing = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE status = 'On-Going' AND assigned_support_email = '$agent_email'"));
$count_resolved = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE status = 'Resolved' AND assigned_support_email = '$agent_email'"));

$count_7days  = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND created_at >= NOW() - INTERVAL 7 DAY"));
$count_30days = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND created_at >= NOW() - INTERVAL 30 DAY"));

$count_technical = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND category = 'technical'"));
$count_account   = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND category = 'account'"));
$count_billing   = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND category = 'billing'"));
$count_general   = mysqli_num_rows(mysqli_query($conn, "SELECT ticket_id FROM tickets WHERE (status = 'Open' OR assigned_support_email = '$agent_email') AND category = 'general'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Dashboard | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .bg-video {
            position: fixed;
            inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        /* ── HEADER ── */
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

        .page-container {
            display: flex;
            flex: 1;
            gap: 1.5rem;
            padding: 1.5rem;
            width: 100%;
            margin: 0 auto;
            min-height: 0;
            max-width: 1600px;
            overflow: hidden;
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 280px;
            min-width: 200px;
            max-width: 400px;
            position: relative;
            transition: width 0.3s ease;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar.collapsed {
            width: 80px !important;
            min-width: 80px;
        }

        /* Start of New Things*/
        .sidebar.collapsed .sidebar-header {
            justify-content: center;
        }

        .sidebar.collapsed .nav-link {
            padding: 0.75rem;
            justify-content: center;
        }

        .sidebar.collapsed .resizer {
            display: none;
        }

        .collapsed-icons {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            padding: 0;
            max-height: 0;
            height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.3s ease 0s, opacity 0.2s ease 0s;
        }

        .sidebar.collapsed .collapsed-icons {
            max-height: 400px;
            height: auto;
            opacity: 1;
            padding: 0.75rem 0;
            transition: max-height 0.3s ease 0.3s, opacity 0.2s ease 0.3s;
        }

        .collapsed-icon-btn {
            width: 40px; height: 40px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 8px;
            color: #4a5568;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.2s;
        }

        .collapsed-icon-btn:hover { background: #f0fdf4; color: #10b981; }
        .collapsed-icon-btn.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .link-text {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .side-name, .section-title {
            white-space: nowrap;
            overflow: hidden;
        }

        /* End of New Things*/

        .sidebar.collapsed .link-text,
        .sidebar.collapsed .count,
        .sidebar.collapsed .side-name,
        .sidebar.collapsed .new-ticket-btn,
        .sidebar.collapsed .section-title {
            display: none;
        }

        .resizer {
            position: absolute;
            right: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            cursor: ew-resize;
            background: transparent;
            transition: background 0.1s;
        }

        .resizer:hover {
            background: #10b981;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            user-select: none;
        }

        .sidebar-header:hover { background: #f0fdf4; border-radius: 12px 12px 0 0; }

        #toggleBtn {
            font-size: 1.5rem;
            color: #10b981;
            cursor: pointer;
            transition: transform 0.2s;
        }

        #toggleBtn:hover {
            transform: scale(1.1);
        }

        .side-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .new-ticket-btn {
            margin-left: auto;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }

        .new-ticket-btn:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.5);
        }

        .sidebar-content {
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            transform: translateY(0);
            transition: transform 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-content-inner {
            padding: 1rem;
            transition: padding 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar.collapsed .sidebar-content {
            overflow: hidden;
            flex: 0;
            transform: translateY(-10px);
        }

        .sidebar.collapsed .sidebar-content-inner {
            padding-top: 0;
            padding-bottom: 0;
        }

        .list {
            list-style: none;
        }

        .section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #a0aec0;
            padding: 1rem 0.5rem 0.5rem;
            letter-spacing: 0.5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: #4a5568;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            margin-bottom: 0.25rem;
            white-space: nowrap; 
            overflow: hidden;
        }

        .nav-link:hover {
            background: #f0fdf4;
            color: #10b981;
        }

        .nav-link.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .nav-link i {
            font-size: 1.2rem;
        }

        .count {
            margin-left: auto;
            background: #edf2f7;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .nav-link.active .count {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .content-view {
            flex: 1;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #ticket-list-view {
            display: flex;
            flex-direction: column;
            flex: 1;
            min-height: 0;
        }

        .table-scroll {
            flex: 1;
            overflow-y: scroll;
            overflow-x: hidden;
            min-height: 0;
        }

        .table-scroll.empty {
            overflow-y: scroll;
            overflow-x: hidden;
        }

        .content-header {
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 1rem;
            margin-bottom: 2rem;
        }

        .content-header h2 {
            color: #2d3748;
            font-size: 1.8rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .ticket-table th,
        .ticket-table td {
            padding: 0.85rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .ticket-table th:nth-child(1), .ticket-table td:nth-child(1) { width: 100px; overflow: visible; white-space: nowrap; text-overflow: clip; }
        .ticket-table th:nth-child(2), .ticket-table td:nth-child(2) { width: 22%; }
        .ticket-table th:nth-child(3), .ticket-table td:nth-child(3) { width: auto; }
        .ticket-table th:nth-child(4), .ticket-table td:nth-child(4) { width: 110px; }
        .ticket-table th:nth-child(5), .ticket-table td:nth-child(5) { width: 130px; }
        .ticket-table th:nth-child(6), .ticket-table td:nth-child(6) { width: 140px; overflow: visible; white-space: nowrap; text-overflow: clip; padding-right: 1.5rem; }

        .ticket-table th {
            background: #f7fafc;
            color: #4a5568;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .unread-dot {
            display: inline-block;
            width: 8px; height: 8px;
            background: #10b981;
            border-radius: 50%;
            margin-left: 6px;
            vertical-align: middle;
        }

        .ticket-table tbody tr:hover {
            background: #f0fdf4;
        }

        .ticket-table tbody tr.escalated-row {
            background: #e8f5e9;
        }
        .ticket-table tbody tr.escalated-row:hover {
            background: #d4edda;
        }

        .status-badge {
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-open         { background: #fef3c7; color: #92400e; }
        .status-under-review { background: #e0e7ff; color: #3730a3; }
        .status-on-going     { background: #dbeafe; color: #1e40af; }
        .status-resolved     { background: #d1fae5; color: #065f46; }
        .escalate-icon { display: none; }

        /* Accept ticket inline banner */
        .accept-banner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #f0fdf4;
            border: 1.5px solid #10b981;
            border-radius: 10px;
            padding: 0.85rem 1.2rem;
        }
        .accept-banner p { font-size: 0.92rem; font-weight: 600; color: #065f46; margin: 0; }
        .btn-accept-ticket {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            padding: 0.55rem 1.4rem; border-radius: 8px;
            font-weight: 600; font-size: 0.88rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            white-space: nowrap; transition: opacity 0.2s;
        }
        .btn-accept-ticket:hover { opacity: 0.88; }

        @keyframes navFadeIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .sidebar.animating:not(.collapsed) .sidebar-content-inner .section-title,
        .sidebar.animating:not(.collapsed) .sidebar-content-inner .nav-link {
            animation: navFadeIn 0.25s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .sidebar:not(.collapsed) .sidebar-content-inner .section-title:nth-child(1) { animation-delay: 0.05s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(2) .nav-link   { animation-delay: 0.1s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(3) .nav-link   { animation-delay: 0.15s; }
        .sidebar:not(.collapsed) .sidebar-content-inner .section-title:nth-child(4) { animation-delay: 0.18s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(5) .nav-link   { animation-delay: 0.22s; }
        .sidebar:not(.collapsed) .sidebar-content-inner .section-title:nth-child(6) { animation-delay: 0.25s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(7) .nav-link   { animation-delay: 0.28s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(8) .nav-link   { animation-delay: 0.31s; }
        .sidebar:not(.collapsed) .sidebar-content-inner li:nth-child(9) .nav-link   { animation-delay: 0.34s; }

        .ticket-table tbody tr {
            cursor: pointer;
        }

        /* ── TICKET DETAIL VIEW ── */
        #ticket-detail-view {
            display: none;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            background: none;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 0.45rem 1rem;
            color: #4a5568;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            margin-bottom: 1.5rem;
        }

        .back-btn:hover {
            background: #f0fdf4;
            border-color: #10b981;
            color: #10b981;
        }

        .detail-subject {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem 2rem;
            font-size: 0.875rem;
            color: #718096;
            padding-bottom: 1.25rem;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .detail-meta strong { color: #4a5568; }

        .detail-body {
            font-size: 0.975rem;
            color: #2d3748;
            line-height: 1.8;
            white-space: pre-wrap;
            background: #f7fafc;
            border-radius: 10px;
            padding: 1.5rem;
        }

        .conversation-section { margin-top: 1.5rem; }
        .conversation-section h4 {
            font-size: 0.8rem; font-weight: 700; color: #a0aec0;
            letter-spacing: 0.5px; text-transform: uppercase;
            margin-bottom: 0.75rem;
        }
        .reply-thread { display: flex; flex-direction: column; gap: 0.65rem; margin-bottom: 1rem; }
        .reply-bubble {
            max-width: 75%; padding: 0.65rem 0.9rem;
            border-radius: 12px; font-size: 0.88rem; line-height: 1.6;
        }
        .reply-bubble.support {
            align-self: flex-end;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border-bottom-right-radius: 4px;
        }
        .reply-bubble.user {
            align-self: flex-start;
            background: #f0f4f8; color: #2d3748;
            border-bottom-left-radius: 4px;
        }
        .reply-bubble .bubble-meta {
            font-size: 0.72rem; margin-bottom: 0.25rem; opacity: 0.75;
        }
        .reply-empty { font-size: 0.85rem; color: #a0aec0; text-align: center; padding: 0.5rem 0; }
        .reply-compose { display: flex; gap: 0.6rem; align-items: flex-end; }
        .reply-compose textarea {
            flex: 1; padding: 0.65rem 0.9rem;
            border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 0.9rem; resize: none; min-height: 70px;
            font-family: inherit; background: #fafafa;
            transition: border-color 0.2s;
        }
        .reply-compose textarea:focus { outline: none; border-color: #10b981; background: white; }
        .btn-send-support {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            padding: 0.65rem 1.2rem; border-radius: 8px;
            font-weight: 600; font-size: 0.88rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            transition: all 0.2s; white-space: nowrap;
        }
        .btn-send-support:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-send-support:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

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
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .footer-left i { color: #10b981; font-size: 1.1rem; }

        .logout-btn {
            background: #f56565;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: #e53e3e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 101, 101, 0.4);
        }

        /* ── MESSAGING MODAL ── */
        .msg-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:999; align-items:center; justify-content:center; }
        .msg-modal-overlay.open { display:flex; }
        .msg-modal { background:white; border-radius:14px; width:480px; max-width:90vw; max-height:80vh; display:flex; flex-direction:column; box-shadow:0 8px 32px rgba(0,0,0,0.2); overflow:hidden; }
        .msg-modal-header { background:linear-gradient(135deg,#10b981,#059669); color:white; padding:1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
        .msg-modal-header span { font-weight:700; font-size:1rem; }
        .msg-modal-close { background:none; border:none; color:white; font-size:1.4rem; cursor:pointer; line-height:1; }
        .msg-thread { flex:1; overflow-y:auto; padding:1rem; display:flex; flex-direction:column; gap:0.5rem; min-height:0; }
        .msg-compose { padding:0.75rem 1rem; border-top:1.5px solid #e2e8f0; display:flex; gap:0.5rem; flex-shrink:0; }
        .msg-compose textarea { flex:1; padding:0.55rem 0.75rem; border:1.5px solid #cbd5e0; border-radius:7px; font-size:0.88rem; resize:none; min-height:52px; font-family:inherit; }
        .msg-compose textarea:focus { outline:none; border-color:#10b981; }
        .msg-send-btn { background:#10b981; color:white; border:none; border-radius:7px; padding:0.55rem 1rem; font-weight:600; cursor:pointer; align-self:flex-end; }

        @media (max-width: 768px) {
            .page-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-width: 100%;
            }
        }

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

        .heading        { animation: fadeInDown 0.5s ease both; }
        .sidebar        { animation: fadeInLeft 0.55s 0.2s ease both; }
        .content-view   { animation: fadeInRight 0.55s 0.3s ease both; }
        .footer         { animation: fadeInUp 0.5s 0.4s ease both; }

        /* ── IMAGE LIGHTBOX ── */
        .lightbox-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.88); z-index:2000; align-items:center; justify-content:center; }
        .lightbox-overlay.open { display:flex; }
        .lightbox-img { max-width:90vw; max-height:85vh; border-radius:8px; box-shadow:0 8px 40px rgba(0,0,0,0.6); transform-origin:center center; transition:transform 0.15s ease; user-select:none; display:block; }
        .lightbox-close { position:fixed; top:1.25rem; right:1.5rem; background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.4); color:white; border-radius:50%; width:40px; height:40px; font-size:1.4rem; cursor:pointer; display:flex; align-items:center; justify-content:center; z-index:2001; }
        .lightbox-close:hover { background:rgba(255,255,255,0.3); }
        .lightbox-zoom { position:fixed; bottom:1.5rem; left:50%; transform:translateX(-50%); display:flex; gap:0.5rem; z-index:2001; }
        .lightbox-zoom button { background:rgba(255,255,255,0.15); border:1.5px solid rgba(255,255,255,0.4); color:white; border-radius:8px; padding:0.4rem 1rem; font-size:1.1rem; cursor:pointer; }
        .lightbox-zoom button:hover { background:rgba(255,255,255,0.3); }

        /* ── LOGOUT MODAL ── */
        .logout-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 999; align-items: center; justify-content: center; }
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
        .logout-modal-cancel { padding: 0.55rem 1.4rem; border: 1.5px solid #cbd5e0; border-radius: 8px; background: white; color: #4a5568; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .logout-modal-cancel:hover { background: #f7fafc; }
        .logout-modal-confirm { padding: 0.55rem 1.4rem; border: none; border-radius: 8px; background: #f56565; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; transition: opacity 0.2s; }
        .logout-modal-confirm:hover { opacity: 0.85; }
    </style>
</head>
<body>
    <video class="bg-video" autoplay loop muted playsinline>
        <source src="Videos/KLVER1VID.mp4" type="video/mp4">
    </video>
    <script>document.querySelector('.bg-video').playbackRate = 0.4;</script>

    <div class="heading">
        <div class="welcome">
            <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
            <div class="welcome-text">
                <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['email']); ?> 🍀</h1>
                <span>Support, Delivering Success with a Touch of Luck</span>
            </div>
        </div>
        <div class="header-actions">
            <button onclick="openMsgModal()" style="position:relative;background:#f0fdf4;border:none;border-radius:50%;width:42px;height:42px;font-size:1.3rem;color:#10b981;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background 0.2s;" title="Message Admin">
                <i class='bx bx-chat'></i>
                <span id="supportMsgDot" style="display:none;position:absolute;top:6px;right:6px;width:9px;height:9px;background:#f56565;border-radius:50%;border:2px solid white;"></span>
            </button>
            <?php
            $pp_conn = new mysqli("localhost", "root", "", "help_desk_db");
            $pp_stmt = $pp_conn->prepare("SELECT profile_picture, profile_picture_offset FROM users WHERE email=? AND role='support' LIMIT 1");
            $pp_stmt->bind_param("s",$_SESSION['email']);
            $pp_stmt->execute();
            $pp_row = $pp_stmt->get_result()->fetch_assoc();
            $pp_stmt->close(); $pp_conn->close();
            ?>
            <a href="support_profile.php" style="background:#f0fdf4;border:none;border-radius:50%;width:42px;height:42px;font-size:1.4rem;color:#10b981;cursor:pointer;display:flex;align-items:center;justify-content:center;text-decoration:none;transition:background 0.2s;overflow:hidden;" title="My Profile">
                <?php if (!empty($pp_row['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($pp_row['profile_picture']); ?>" style="width:100%;height:100%;object-fit:cover;object-position:<?php echo htmlspecialchars($pp_row['profile_picture_offset'] ?? '50% 50%'); ?>;border-radius:50%;">
                <?php else: ?>
                    <i class='bx bx-user-circle'></i>
                <?php endif; ?>
            </a>
            <button onclick="confirmLogout()" class="logout-btn">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </div>
    </div>

    <div class="page-container">
        <aside class="sidebar" id="sidebar">
            <div id="resizer" class="resizer"></div>

            <div class="sidebar-header">
                <i class='bx bx-sidebar' id="toggleBtn"></i>
                <span class="side-name">Tickets</span>
            </div>

            <div class="collapsed-icons">
                <a href="#" class="collapsed-icon-btn active" data-filter="all" data-label="All Tickets" title="Tickets"><i class='bx bx-list-ul'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="status:Open" data-label="Open Tickets" title="Open Tickets"><i class='bx bx-error-circle'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="status:On-Going" data-label="On-Going Tickets" title="On-Going Tickets"><i class='bx bx-time-five'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="status:Resolved" data-label="Resolved Tickets" title="Resolved Tickets"><i class='bx bx-check-circle'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="days:7" data-label="Last 7 Days" title="Last 7 days"><i class='bx bx-calendar'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="days:30" data-label="Last 30 Days" title="Last 30 days"><i class='bx bx-calendar'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="category:technical" data-label="Technical Tickets" title="Technical"><i class='bx bx-wrench'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="category:account" data-label="Account Tickets" title="Account"><i class='bx bx-user'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="category:billing" data-label="Billing Tickets" title="Billing"><i class='bx bx-credit-card'></i></a>
                <a href="#" class="collapsed-icon-btn" data-filter="category:general" data-label="General Tickets" title="General"><i class='bx bx-chat'></i></a>
            </div>
            
            <div class="sidebar-content">
                <div class="sidebar-content-inner">
                <ul class="list"> 
                    <li class="section-title">ALL TICKETS</li>
                    <li><a href="#" class="nav-link active" data-filter="all" data-label="All Tickets">
                        <i class='bx bx-list-ul'></i> 
                        <span class="link-text">Tickets</span> 
                        <span class="count"><?php echo $count_all; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="status:Open" data-label="Open Tickets">
                        <i class='bx bx-error-circle'></i> 
                        <span class="link-text">Open Tickets</span> 
                        <span class="count"><?php echo $count_open; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="status:Under Review" data-label="Under Review">
                        <i class='bx bx-search-alt'></i> 
                        <span class="link-text">Under Review</span> 
                        <span class="count"><?php echo $count_under_review; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="status:On-Going" data-label="On-Going Tickets">
                        <i class='bx bx-time-five'></i> 
                        <span class="link-text">On-Going Tickets</span> 
                        <span class="count"><?php echo $count_ongoing; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="status:Resolved" data-label="Resolved Tickets">
                        <i class='bx bx-check-circle'></i> 
                        <span class="link-text">Resolved Tickets</span> 
                        <span class="count"><?php echo $count_resolved; ?></span>
                    </a></li>

                    <li class="section-title">MY VIEWS</li>
                    <li><a href="#" class="nav-link" data-filter="days:7" data-label="Last 7 Days">
                        <i class='bx bx-calendar'></i> 
                        <span class="link-text">Last 7 days</span> 
                        <span class="count"><?php echo $count_7days; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="days:30" data-label="Last 30 Days">
                        <i class='bx bx-calendar'></i> 
                        <span class="link-text">Last 30 days</span> 
                        <span class="count"><?php echo $count_30days; ?></span>
                    </a></li>

                    <li class="section-title">CATEGORY</li>
                    <li><a href="#" class="nav-link" data-filter="category:technical" data-label="Technical Tickets">
                        <i class='bx bx-wrench'></i> 
                        <span class="link-text">Technical</span> 
                        <span class="count"><?php echo $count_technical; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="category:account" data-label="Account Tickets">
                        <i class='bx bx-user'></i> 
                        <span class="link-text">Account</span> 
                        <span class="count"><?php echo $count_account; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="category:billing" data-label="Billing Tickets">
                        <i class='bx bx-credit-card'></i> 
                        <span class="link-text">Billing</span> 
                        <span class="count"><?php echo $count_billing; ?></span>
                    </a></li>
                    <li><a href="#" class="nav-link" data-filter="category:general" data-label="General Tickets">
                        <i class='bx bx-chat'></i> 
                        <span class="link-text">General</span> 
                        <span class="count"><?php echo $count_general; ?></span>
                    </a></li>
                </ul>
                </div>
            </div>
        </aside>

        <main class="content-view">
            <!-- LIST VIEW -->
            <div id="ticket-list-view">
                <div class="content-header">
                    <h2>All Tickets</h2>
                </div>

                <?php if (mysqli_num_rows($result) > 0): ?>
                <div class="table-scroll">
                    <table class="ticket-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User Email</th>
                                <th>Subject</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th style="cursor:pointer;user-select:none;" onclick="sortByDate()">Date <span id="dateSortIcon">↕</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr class="ticket-row<?php echo $row['is_escalated'] ? ' escalated-row' : ''; ?>"
                                data-id="<?php echo htmlspecialchars($row['display_id']); ?>"
                                data-email="<?php echo htmlspecialchars($row['user_email']); ?>"
                                data-subject="<?php echo htmlspecialchars($row['subject']); ?>"
                                data-category="<?php echo htmlspecialchars(strtolower($row['category'])); ?>"
                                data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                data-date="<?php echo date('M d, Y', strtotime($row['created_at'])); ?>"
                                data-created="<?php echo $row['created_at']; ?>"
                                data-content="<?php echo htmlspecialchars($row['content']); ?>"
                                data-escalated="<?php echo $row['is_escalated'] ? '1' : '0'; ?>"
                                data-assigned="<?php echo htmlspecialchars($row['assigned_support_email'] ?? ''); ?>"
                                data-image="<?php echo htmlspecialchars($row['image_path'] ?? ''); ?>">
                                <td>#<?php echo $row['display_id']; ?></td>
                                <td><?php echo htmlspecialchars($row['user_email']); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong><?php if ($row['is_escalated']): ?><i class='bx bxs-flag escalate-icon' title='Escalated'></i><?php endif; ?></td>
                                <td><?php echo ucfirst($row['category']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['-', '-'], $row['status'])); ?>">
                                        <?php echo $row['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-inbox'></i>
                        <h3>No tickets yet</h3>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DETAIL VIEW -->
            <div id="ticket-detail-view" style="display:none;flex-direction:column;flex:1;min-height:0;overflow:hidden;">
                <button class="back-btn" id="backBtn">
                    <i class='bx bx-arrow-back'></i> Back to All Tickets
                </button>
                <div style="flex:1;min-height:0;overflow-y:auto;">
                    <div class="detail-subject" id="detailSubject"></div>
                    <div class="detail-meta">
                        <span><strong>ID:</strong> <span id="detailId"></span></span>
                        <span><strong>From:</strong> <span id="detailEmail"></span></span>
                        <span><strong>Category:</strong> <span id="detailCategory"></span></span>
                        <span><strong>Status:</strong> <span id="detailStatus"></span></span>
                        <span><strong>Date:</strong> <span id="detailDate"></span></span>
                    </div>
                    <div class="detail-body" id="detailContent"></div>
                    <div id="detailImageWrap" style="display:none;margin-top:1rem;">
                        <p style="font-size:0.78rem;font-weight:700;color:#a0aec0;letter-spacing:0.5px;text-transform:uppercase;margin-bottom:0.4rem;">Attached Image</p>
                        <img id="detailImage" src="" alt="Ticket attachment" style="max-width:100%;max-height:260px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:zoom-in;" onclick="openLightbox(this.src)">
                    </div>
                    <div class="conversation-section">
                        <h4><i class='bx bx-conversation'></i> Conversation</h4>
                        <div class="reply-thread" id="replyThread"></div>
                    </div>
                </div>
                <div class="reply-compose" id="replyCompose" style="padding-top:1rem;border-top:1.5px solid #e2e8f0;margin-top:0.5rem;">
                    <textarea id="supportReplyMsg" placeholder="Write a message to the user..."></textarea>
                    <button class="btn-send-support" id="sendSupportReplyBtn" onclick="sendSupportReply()">
                        <i class='bx bx-send'></i> Send Message
                    </button>
                </div>
                <div id="acceptBanner" style="display:none;padding-top:1rem;border-top:1.5px solid #e2e8f0;margin-top:0.5rem;">
                    <div class="accept-banner">
                        <p><i class='bx bx-info-circle'></i> Do you accept this ticket?</p>
                        <button class="btn-accept-ticket" onclick="acceptTicket()"><i class='bx bx-check'></i> Accept</button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- IMAGE LIGHTBOX -->
    <div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class='bx bx-x'></i></button>
        <img class="lightbox-img" id="lightboxImg" src="" alt="" onclick="event.stopPropagation()">
        <div class="lightbox-zoom">
            <button onclick="event.stopPropagation();zoomLightbox(-0.25)"><i class='bx bx-zoom-out'></i></button>
            <button onclick="event.stopPropagation();zoomLightbox(0)"><i class='bx bx-reset'></i></button>
            <button onclick="event.stopPropagation();zoomLightbox(0.25)"><i class='bx bx-zoom-in'></i></button>
        </div>
    </div>

    <!-- LOGOUT CONFIRM MODAL -->
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

    <!-- MESSAGING MODAL -->
    <div class="msg-modal-overlay" id="msgModal">
        <div class="msg-modal">
            <div class="msg-modal-header">
                <span><i class='bx bx-chat'></i> Message Admin</span>
                <button class="msg-modal-close" onclick="closeMsgModal()"><i class='bx bx-x'></i></button>
            </div>
            <div class="msg-thread" id="supportMsgThread"></div>
            <div class="msg-compose">
                <textarea id="supportMsgInput" placeholder="Type a message to admin..."></textarea>
                <button class="msg-send-btn" onclick="sendSupportMsg()"><i class='bx bx-send'></i></button>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
    </div>

<script>
        let _dateSortAsc = false;
        function sortByDate() {
            _dateSortAsc = !_dateSortAsc;
            document.getElementById('dateSortIcon').textContent = _dateSortAsc ? '\u2191' : '\u2193';
            const tbody = document.querySelector('.ticket-table tbody');
            const rows  = Array.from(tbody.querySelectorAll('.ticket-row'));
            rows.sort((a, b) => {
                const aEsc = a.dataset.escalated === '1';
                const bEsc = b.dataset.escalated === '1';
                if (aEsc !== bEsc) return aEsc ? -1 : 1;
                const da = new Date(a.dataset.created), db = new Date(b.dataset.created);
                return _dateSortAsc ? da - db : db - da;
            });
            rows.forEach(r => tbody.appendChild(r));
        }

        function confirmLogout() {
            document.getElementById('logoutModal').classList.add('open');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });

        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('toggleBtn');
        const resizer = document.getElementById('resizer');

        // --- TOGGLE LOGIC ---
        document.querySelector('.sidebar-header').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            if (sidebar.classList.contains('collapsed')) {
                sidebar.style.removeProperty('width');
                sidebar.classList.remove('animating');
            } else {
                sidebar.classList.add('animating');
                setTimeout(() => sidebar.classList.remove('animating'), 600);
            }
        });

        // --- RESIZER LOGIC ---
        resizer.addEventListener('mousedown', (e) => {
            // Don't allow resizing if the sidebar is collapsed
            if (sidebar.classList.contains('collapsed')) return;

            document.addEventListener('mousemove', resize);
            document.addEventListener('mouseup', stopResize);
            
            // Disable transitions while dragging for a "snappy" feel
            sidebar.style.transition = 'none';
            
            // Prevent text selection while dragging
            document.body.style.cursor = 'ew-resize';
        });

        function resize(e) {
            // Calculate new width based on mouse position
            let newWidth = e.clientX - sidebar.getBoundingClientRect().left;
            
            // Constraints: Min 200px, Max 400px
            if (newWidth > 200 && newWidth < 400) {
                sidebar.style.width = newWidth + 'px';
            }
        }

        function stopResize() {
            document.removeEventListener('mousemove', resize);
            document.removeEventListener('mouseup', stopResize);
            
            // Re-enable smooth transitions for the toggle button
            sidebar.style.transition = 'width 0.3s ease';
            document.body.style.cursor = 'default';
        }

        // --- TICKET DETAIL VIEW ---
        const listView   = document.getElementById('ticket-list-view');
        const detailView = document.getElementById('ticket-detail-view');
        const backBtn    = document.getElementById('backBtn');

        let _currentTicketId = null;

        let _replyPollInterval = null;

        function openTicketDetail(row) {
            _currentTicketId = row.dataset.id;
            document.getElementById('detailId').textContent       = row.dataset.id;
            document.getElementById('detailSubject').textContent  = row.dataset.subject;
            document.getElementById('detailEmail').textContent    = row.dataset.email;
            document.getElementById('detailCategory').textContent = row.dataset.category;
            document.getElementById('detailDate').textContent     = row.dataset.date;
            document.getElementById('detailContent').textContent  = row.dataset.content;
            const imgWrap = document.getElementById('detailImageWrap');
            if (row.dataset.image) {
                document.getElementById('detailImage').src = row.dataset.image;
                imgWrap.style.display = 'block';
            } else { imgWrap.style.display = 'none'; }
            const statusEl = document.getElementById('detailStatus');
            statusEl.textContent = row.dataset.status;
            statusEl.className   = 'status-badge status-' + row.dataset.status.toLowerCase().replace(/ /g, '-');
            document.getElementById('supportReplyMsg').value = '';
            const isOpen     = row.dataset.status === 'Open';
            const isResolved = row.dataset.status === 'Resolved';
            document.getElementById('replyCompose').style.display = (isOpen || isResolved) ? 'none' : 'flex';
            document.getElementById('acceptBanner').style.display = isOpen ? 'block' : 'none';
            // Clear unread indicator
            const dot = row.querySelector('.unread-dot');
            if (dot) dot.remove();
            localStorage.setItem('read_' + row.dataset.id, Date.now());
            loadReplies(_currentTicketId);
            listView.style.display   = 'none';
            detailView.style.display = 'flex';
            clearInterval(_replyPollInterval);
            _replyPollInterval = setInterval(() => loadReplies(_currentTicketId), 4000);
        }

        document.querySelectorAll('.ticket-row').forEach(row => {
            row.addEventListener('click', () => openTicketDetail(row));
        });

        function acceptTicket() {
            if (!_currentTicketId) return;
            const fd = new FormData();
            fd.append('display_id', _currentTicketId);
            fetch('claim_ticket.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => {
                    const row = document.querySelector(`.ticket-row[data-id="${_currentTicketId}"]`);
                    if (row) {
                        row.dataset.status = 'Under Review';
                        const badge = row.querySelector('.status-badge');
                        if (badge) { badge.textContent = 'Under Review'; badge.className = 'status-badge status-under-review'; }
                        updateSidebarCounts();
                    }
                    const statusEl = document.getElementById('detailStatus');
                    statusEl.textContent = 'Under Review';
                    statusEl.className   = 'status-badge status-under-review';
                    document.getElementById('acceptBanner').style.display = 'none';
                    document.getElementById('replyCompose').style.display = 'flex';
                });
        }

        function loadReplies(displayId) {
            fetch(`get_replies.php?display_id=${displayId}`)
                .then(r => r.json())
                .then(replies => {
                    const thread = document.getElementById('replyThread');
                    if (!replies.length) {
                        thread.innerHTML = '<div class="reply-empty">No replies yet. Start the conversation.</div>';
                        return;
                    }
                    thread.innerHTML = replies.map(r =>
                        `<div class="reply-bubble ${r.sender_role === 'user' ? 'user' : 'support'}">
                            <div class="bubble-meta">${r.sender_email} &bull; ${r.created_at}</div>
                            ${escHtml(r.message)}
                        </div>`
                    ).join('');
                    thread.scrollTop = thread.scrollHeight;
                });
        }

        function sendSupportReply() {
            const msg = document.getElementById('supportReplyMsg').value.trim();
            if (!msg || !_currentTicketId) return;
            const btn = document.getElementById('sendSupportReplyBtn');
            btn.disabled = true;
            const fd = new FormData();
            fd.append('display_id', _currentTicketId);
            fd.append('message', msg);
            fetch('submit_reply.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('supportReplyMsg').value = '';
                        loadReplies(_currentTicketId);
                        const statusEl = document.getElementById('detailStatus');
                        if (statusEl.textContent === 'Open' || statusEl.textContent === 'Under Review') {
                            statusEl.textContent = 'On-Going';
                            statusEl.className = 'status-badge status-on-going';
                            const row = document.querySelector(`.ticket-row[data-id="${_currentTicketId}"]`);
                            if (row) {
                                row.dataset.status = 'On-Going';
                                const badge = row.querySelector('.status-badge');
                                if (badge) { badge.textContent = 'On-Going'; badge.className = 'status-badge status-on-going'; }
                                updateSidebarCounts();
                            }
                        }
                    }
                })
                .finally(() => btn.disabled = false);
        }

        function escHtml(str) {
            return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        }

        function updateSidebarCounts() {
            const rows = document.querySelectorAll('.ticket-row');
            let open = 0, underReview = 0, ongoing = 0, resolved = 0;
            rows.forEach(r => {
                const s = r.dataset.status;
                if (s === 'Open') open++;
                else if (s === 'Under Review') underReview++;
                else if (s === 'On-Going') ongoing++;
                else if (s === 'Resolved') resolved++;
            });
            const countEls = document.querySelectorAll('.nav-link .count');
            // order: all, open, under-review, on-going, resolved, last7, last30, technical, account, billing, general
            countEls[1].textContent = open;
            countEls[2].textContent = underReview;
            countEls[3].textContent = ongoing;
            countEls[4].textContent = resolved;
        }

        // Check unread indicators on page load
        (function checkUnread() {
            document.querySelectorAll('.ticket-row').forEach(row => {
                const id = row.dataset.id;
                const lastRead = parseInt(localStorage.getItem('read_' + id) || '0');
                fetch(`get_replies.php?display_id=${id}`)
                    .then(r => r.json())
                    .then(replies => {
                        const lastUserMsg = replies.filter(r => r.sender_role === 'user').pop();
                        if (!lastUserMsg) return;
                        const msgTime = new Date(lastUserMsg.created_at).getTime();
                        if (msgTime > lastRead) {
                            const subjectTd = row.querySelector('td:nth-child(3) strong');
                            if (subjectTd && !subjectTd.querySelector('.unread-dot')) {
                                const dot = document.createElement('span');
                                dot.className = 'unread-dot';
                                subjectTd.appendChild(dot);
                            }
                        }
                    });
            });
        })();

        backBtn.addEventListener('click', () => {
            clearInterval(_replyPollInterval);
            _replyPollInterval = null;
            detailView.style.display = 'none';
            listView.style.display   = 'flex';
            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) applyFilter(activeLink.dataset.filter, activeLink.dataset.label);
        });

        // --- SIDEBAR FILTERING ---
        function applyFilter(filter, label) {
            document.querySelector('.content-header h2').textContent = label;

            const rows = document.querySelectorAll('.ticket-row');
            const now  = new Date();
            let visibleCount = 0;

            rows.forEach(row => {
                let show = false;
                if (filter === 'all') {
                    show = true;
                } else if (filter.startsWith('status:')) {
                    show = row.dataset.status === filter.split(':')[1];
                } else if (filter.startsWith('category:')) {
                    show = row.dataset.category === filter.split(':')[1];
                } else if (filter.startsWith('days:')) {
                    const days    = parseInt(filter.split(':')[1]);
                    const created = new Date(row.dataset.created);
                    const diff    = (now - created) / (1000 * 60 * 60 * 24);
                    show = diff <= days;
                }
                row.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            let emptyEl = document.getElementById('filter-empty');
            if (!emptyEl) {
                emptyEl = document.createElement('div');
                emptyEl.id = 'filter-empty';
                emptyEl.className = 'empty-state';
                emptyEl.innerHTML = "<i class='bx bx-filter-alt'></i><h3>No tickets found</h3><p>No tickets match this filter.</p>";
                document.querySelector('.table-scroll').after(emptyEl);
            }
            emptyEl.style.display = visibleCount === 0 ? 'block' : 'none';
            document.querySelector('.table-scroll').classList.toggle('empty', visibleCount === 0);
        }

        document.querySelectorAll('.collapsed-icon-btn[data-filter]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.collapsed-icon-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                // Sync the matching nav-link
                const match = document.querySelector(`.nav-link[data-filter="${btn.dataset.filter}"]`);
                if (match) match.classList.add('active');
                applyFilter(btn.dataset.filter, btn.dataset.label);
            });
        });

        document.querySelectorAll('.nav-link[data-filter]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // Update active state
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                document.querySelectorAll('.collapsed-icon-btn').forEach(b => b.classList.remove('active'));
                link.classList.add('active');
                // Sync the matching collapsed icon
                const match = document.querySelector(`.collapsed-icon-btn[data-filter="${link.dataset.filter}"]`);
                if (match) match.classList.add('active');

                applyFilter(link.dataset.filter, link.dataset.label);
            });
        });
        /* ── LIGHTBOX ── */
        let _lbScale = 1;
        function openLightbox(src) {
            _lbScale = 1;
            const img = document.getElementById('lightboxImg');
            img.src = src;
            img.style.transform = 'scale(1)';
            document.getElementById('lightboxOverlay').classList.add('open');
        }
        function closeLightbox() { document.getElementById('lightboxOverlay').classList.remove('open'); }
        function zoomLightbox(delta) {
            if (delta === 0) { _lbScale = 1; }
            else { _lbScale = Math.min(4, Math.max(0.25, _lbScale + delta)); }
            document.getElementById('lightboxImg').style.transform = 'scale(' + _lbScale + ')';
        }
        document.getElementById('lightboxOverlay').addEventListener('wheel', function(e) {
            e.preventDefault(); zoomLightbox(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

        /* ── MESSAGING ── */
        let _supportMsgPollInterval = null;
        function openMsgModal() {
            document.getElementById('msgModal').classList.add('open');
            loadSupportMessages();
            _supportMsgPollInterval = setInterval(loadSupportMessages, 3000);
        }
        function closeMsgModal() {
            document.getElementById('msgModal').classList.remove('open');
            clearInterval(_supportMsgPollInterval);
            _supportMsgPollInterval = null;
        }
        document.getElementById('msgModal').addEventListener('click', e => { if (e.target === document.getElementById('msgModal')) closeMsgModal(); });
        function loadSupportMessages() {
            fetch('get_messages.php')
                .then(r => r.json())
                .then(msgs => {
                    const thread = document.getElementById('supportMsgThread');
                    if (!msgs.length) { thread.innerHTML = '<div style="text-align:center;color:#a0aec0;font-size:0.85rem;padding:1rem;">No messages yet. Start the conversation.</div>'; return; }
                    thread.innerHTML = msgs.map(m => {
                        const isMe = m.sender_role === 'support';
                        return `<div style="max-width:75%;padding:0.55rem 0.85rem;border-radius:12px;font-size:0.87rem;line-height:1.5;align-self:${isMe?'flex-end':'flex-start'};background:${isMe?'linear-gradient(135deg,#10b981,#059669)':'#f0f4f8'};color:${isMe?'white':'#2d3748'};border-bottom-${isMe?'right':'left'}-radius:4px;">
                            <div style="font-size:0.7rem;opacity:0.75;margin-bottom:0.2rem;">${m.created_at}</div>
                            ${m.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                        </div>`;
                    }).join('');
                    thread.scrollTop = thread.scrollHeight;
                    document.getElementById('supportMsgDot').style.display = 'none';
                });
        }
        function sendSupportMsg() {
            const input = document.getElementById('supportMsgInput');
            const msg = input.value.trim();
            if (!msg) return;
            const fd = new FormData();
            fd.append('message', msg);
            fd.append('receiver_email', 'admin'); // resolved server-side by role
            fetch('send_message.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => { if (res.success) { input.value = ''; loadSupportMessages(); } });
        }
        document.getElementById('supportMsgInput').addEventListener('keydown', e => {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendSupportMsg(); }
        });
        fetch('get_unread_messages.php').then(r=>r.json()).then(d=>{
            if (d.count > 0) document.getElementById('supportMsgDot').style.display = '';
        });
        setInterval(function() {
            fetch('get_unread_messages.php').then(r=>r.json()).then(d=>{
                document.getElementById('supportMsgDot').style.display = d.count > 0 ? '' : 'none';
            });
        }, 5000);

        /* ── LIVE TICKET POLLING ── */
        function pollTickets() {
            fetch('get_tickets.php')
                .then(r => r.json())
                .then(tickets => {
                    const tbody = document.querySelector('.ticket-table tbody');
                    if (!tbody) return;
                    const liveIds = new Set(tickets.map(t => t.display_id));
                    Array.from(tbody.querySelectorAll('.ticket-row')).forEach(row => {
                        if (!liveIds.has(row.dataset.id)) {
                            if (_currentTicketId === row.dataset.id) {
                                clearInterval(_replyPollInterval);
                                detailView.style.display = 'none';
                                listView.style.display = 'flex';
                            }
                            row.remove();
                        }
                    });
                    const existingIds = new Set(Array.from(tbody.querySelectorAll('.ticket-row')).map(r => r.dataset.id));
                    let added = false;
                    tickets.forEach(t => {
                        if (existingIds.has(t.display_id)) {
                            // Update status badge and dataset on existing row
                            const row = tbody.querySelector(`.ticket-row[data-id="${t.display_id}"]`);
                            if (row && row.dataset.status !== t.status) {
                                row.dataset.status = t.status;
                                row.dataset.assigned = t.assigned_support_email || '';
                                const badge = row.querySelector('.status-badge');
                                if (badge) {
                                    const cls = 'status-' + t.status.toLowerCase().replace(/ /g, '-');
                                    badge.textContent = t.status;
                                    badge.className = 'status-badge ' + cls;
                                }
                            }
                            return;
                        }
                        // New ticket — build and prepend row
                        const tr = document.createElement('tr');
                        const statusCls = 'status-' + t.status.toLowerCase().replace(/ /g, '-');
                        const escalatedCls = t.is_escalated == 1 ? ' escalated-row' : '';
                        tr.className = 'ticket-row' + escalatedCls;
                        tr.dataset.id       = t.display_id;
                        tr.dataset.email    = t.user_email;
                        tr.dataset.subject  = t.subject;
                        tr.dataset.category = (t.category || '').toLowerCase();
                        tr.dataset.status   = t.status;
                        tr.dataset.date     = new Date(t.created_at).toLocaleDateString('en-US',{month:'short',day:'numeric',year:'numeric'});
                        tr.dataset.created  = t.created_at;
                        tr.dataset.content  = t.content || '';
                        tr.dataset.escalated = t.is_escalated ? '1' : '0';
                        tr.dataset.assigned = t.assigned_support_email || '';
                        tr.dataset.image    = t.image_path || '';
                        tr.innerHTML = `<td>#${t.display_id}</td>
                            <td>${t.user_email}</td>
                            <td><strong>${t.subject}</strong>${t.is_escalated == 1 ? "<i class='bx bxs-flag escalate-icon' title='Escalated'></i>" : ''}</td>
                            <td>${t.category.charAt(0).toUpperCase()+t.category.slice(1)}</td>
                            <td><span class="status-badge ${statusCls}">${t.status}</span></td>
                            <td>${tr.dataset.date}</td>`;
                        tr.addEventListener('click', () => openTicketDetail(tr));
                        tbody.insertBefore(tr, tbody.firstChild);
                        added = true;
                    });
                    if (added) updateSidebarCounts();
                });
        }
        setInterval(pollTickets, 7000);
    </script>
</body>
</html>
