<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'user') {
    header("Location: login.php?error=unauthorized");
    exit();
}

// Fetch display_name and profile picture for greeting
$_display_name = null;
$_dn_conn = new mysqli("localhost", "root", "", "help_desk_db");
$_dn_stmt = $_dn_conn->prepare("SELECT display_name, profile_picture, profile_picture_offset FROM users WHERE email = ? AND role = 'user' LIMIT 1");
$_dn_stmt->bind_param("s", $_SESSION['email']);
$_dn_stmt->execute();
$_dn_row = $_dn_stmt->get_result()->fetch_assoc();
$_dn_stmt->close();
$_dn_conn->close();
$_display_name = !empty($_dn_row['display_name']) ? $_dn_row['display_name'] : explode('@', $_SESSION['email'])[0];
$_avatar = $_dn_row['profile_picture'] ?? '';
$_avatar_offset = $_dn_row['profile_picture_offset'] ?? '50% 50%';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .bg-video {
            position: fixed;
            inset: 0;
            width: 100%; height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            z-index: 0;
        }

        .stats-bar, .page-wrapper, .footer {
            position: relative;
            z-index: 1;
        }

        @font-face {
            font-family: 'KugileDemo';
            src: url('fonts/Kugile_Demo.ttf') format('truetype');
        }

        /* ── HEADER ── */
        .heading {
            background: #F1F3E0;
            padding: 1rem 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            z-index: 100;
        }

        .welcome { display: flex; align-items: center; gap: 1rem; }
        .welcome video { height: 52px; width: auto; mix-blend-mode: multiply; }
        .welcome-text h1 { color: #2d3748; font-size: 2rem; font-family: 'KugileDemo', Arial, sans-serif; line-height: 1; vertical-align: middle; margin-top: 10px; }
        .welcome-text span { color: #10b981; font-size: 0.85rem; font-weight: 500; }

        .welcome-message { color: #000000; font-size: 1rem; font-weight: 600; margin-bottom: 0.1rem; }

        .header-actions { display: flex; align-items: center; gap: 1rem; }

        .notif-btn {
            position: relative;
            background: #e8ead6;
            border: none;
            border-radius: 50%;
            width: 42px; height: 42px;
            font-size: 1.3rem;
            color: #2d5a27;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
        }
        .notif-btn:hover { background: #d6d9c0; }
        .notif-dot {
            position: absolute; top: 6px; right: 6px;
            width: 9px; height: 9px;
            background: #f56565; border-radius: 50%; border: 2px solid white;
        }

        /* ── STATS BAR ── */
        .stats-bar {
            display: flex;
            gap: 1rem;
            padding: 1.5rem 2rem 0;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .stat-pill {
            background: rgba(255,255,255,0.92);
            border-radius: 10px;
            padding: 0.9rem 1.4rem;
            display: flex; align-items: center; gap: 0.75rem;
            flex: 1;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        .stat-pill:hover { transform: translateY(-3px); }
        .stat-pill i { font-size: 1.8rem; padding: 0.5rem; border-radius: 8px; }
        .stat-pill.total i   { color: #10b981; background: #f0fdf4; }
        .stat-pill.open i    { color: #f59e0b; background: #fffbeb; }
        .stat-pill.progress i{ color: #3b82f6; background: #eff6ff; }
        .stat-pill.resolved i{ color: #8b5cf6; background: #f5f3ff; }
        .stat-pill .label { color: #718096; font-size: 0.8rem; }
        .stat-pill .value { color: #2d3748; font-size: 1.5rem; font-weight: 700; }

        /* ── PAGE WRAPPER ── */
        .page-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem 2rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        /* ── TOP ROW: FAQ + FORM ── */
        .top-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            align-items: stretch;
        }

        /* ── CARD ── */
        .card {
            background: rgba(255,255,255,0.97);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .card-header {
            display: flex; align-items: center; gap: 0.5rem;
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .card-header h2 {
            color: #2d3748; font-size: 1.15rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .card-header h2 i { color: #10b981; }

        /* ── FAQ ── */
        .faq-item {
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 0.65rem;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .faq-item.open { border-color: #10b981; }

        .faq-question {
            width: 100%; background: none; border: none;
            padding: 0.9rem 1rem;
            display: flex; justify-content: space-between; align-items: center;
            cursor: pointer;
            font-size: 0.9rem; font-weight: 600; color: #2d3748;
            text-align: left; gap: 0.75rem;
        }
        .faq-question:hover { background: #f0fdf4; }
        .faq-question i { color: #10b981; font-size: 1.2rem; flex-shrink: 0; transition: transform 0.3s; }
        .faq-item.open .faq-question i { transform: rotate(45deg); }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease;
        }
        .faq-answer-inner {
            padding: 0.75rem 1rem 1rem;
            color: #4a5568; font-size: 0.88rem; line-height: 1.7;
            border-top: 1px solid #e2e8f0;
        }

        /* ── TICKET FORM ── */
        .form-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            flex: 1;
        }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }

        .form-group label { color: #4a5568; font-weight: 600; font-size: 0.88rem; }

        .form-group input,
        .form-group textarea {
            padding: 0.7rem 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
            background: #fafafa;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none; border-color: #10b981; background: white;
        }
        .form-group textarea { resize: vertical; min-height: 110px; flex: 1; }

        /* ── CUSTOM SELECT ── */
        .custom-select-wrapper { position: relative; }
        .custom-select-trigger {
            padding: 0.7rem 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: #fafafa;
            cursor: pointer;
            display: flex; justify-content: space-between; align-items: center;
            color: #a0aec0;
            transition: border-color 0.2s, background 0.2s;
            user-select: none;
        }
        .custom-select-trigger.has-value { color: #2d3748; }
        .custom-select-trigger:hover,
        .custom-select-wrapper.open .custom-select-trigger {
            border-color: #10b981; background: white;
        }
        .custom-select-trigger .arrow {
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
            color: #10b981; font-size: 1.1rem;
        }
        .custom-select-wrapper.open .custom-select-trigger .arrow {
            transform: rotate(180deg);
        }
        .custom-options {
            position: absolute; top: calc(100% + 6px); left: 0; right: 0;
            background: white;
            border: 2px solid #10b981;
            border-radius: 10px;
            overflow: hidden;
            z-index: 100;
            box-shadow: 0 8px 24px rgba(16,185,129,0.15);
            opacity: 0;
            transform: translateY(-8px) scaleY(0.95);
            transform-origin: top;
            pointer-events: none;
            transition: opacity 0.22s ease, transform 0.22s cubic-bezier(.4,0,.2,1);
        }
        .custom-select-wrapper.open .custom-options {
            opacity: 1;
            transform: translateY(0) scaleY(1);
            pointer-events: all;
        }
        .custom-option {
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            color: #4a5568;
            cursor: pointer;
            display: flex; align-items: center; gap: 0.6rem;
            opacity: 0;
            transform: translateX(-10px);
            transition: background 0.15s, color 0.15s, opacity 0.2s ease, transform 0.2s ease;
        }
        .custom-select-wrapper.open .custom-option:nth-child(1) { opacity:1; transform:translateX(0); transition-delay: 0.04s; }
        .custom-select-wrapper.open .custom-option:nth-child(2) { opacity:1; transform:translateX(0); transition-delay: 0.09s; }
        .custom-select-wrapper.open .custom-option:nth-child(3) { opacity:1; transform:translateX(0); transition-delay: 0.14s; }
        .custom-select-wrapper.open .custom-option:nth-child(4) { opacity:1; transform:translateX(0); transition-delay: 0.19s; }
        .custom-option:hover { background: #f0fdf4; color: #10b981; }
        .custom-option.selected { background: #d1fae5; color: #065f46; font-weight: 600; }
        .custom-option i { color: #10b981; font-size: 1.1rem; }

        .form-footer {
            display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem;
        }

        .btn-cancel {
            background: #edf2f7; color: #4a5568; border: none;
            padding: 0.7rem 1.5rem; border-radius: 8px;
            font-weight: 600; cursor: pointer; transition: background 0.2s;
        }
        .btn-cancel:hover { background: #e2e8f0; }

        .btn-submit {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            padding: 0.7rem 2rem; border-radius: 8px;
            font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.5);
        }

        /* ── TICKET LOOKUP ── */
        .lookup-row {
            display: flex; gap: 0.75rem; align-items: center;
        }

        .lookup-row input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background: #fafafa;
            transition: border-color 0.2s;
        }
        .lookup-row input:focus { outline: none; border-color: #10b981; background: white; }

        .btn-lookup {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            padding: 0.75rem 1.5rem; border-radius: 8px;
            font-weight: 600; cursor: pointer;
            display: flex; align-items: center; gap: 0.5rem;
            transition: all 0.3s; white-space: nowrap;
        }
        .btn-lookup:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16,185,129,0.5);
        }

        .ticket-result {
            margin-top: 1.25rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            display: none;
        }

        .ticket-result.show { display: block; }

        .result-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 0.85rem 1.2rem;
            display: flex; justify-content: space-between; align-items: center;
        }

        .result-header span { font-weight: 700; font-size: 1rem; }

        .btn-view-full {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.5);
            border-radius: 7px;
            padding: 0.3rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-view-full:hover { background: rgba(255,255,255,0.35); }

        .btn-reply-header {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1.5px solid rgba(255,255,255,0.5);
            border-radius: 7px;
            padding: 0.3rem 0.85rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: flex; align-items: center; gap: 0.35rem;
        }
        .btn-reply-header:hover { background: rgba(255,255,255,0.35); }

        .reply-section {
            border-top: 1.5px solid #e2e8f0;
            padding: 1rem 1.5rem 1.2rem;
        }
        .reply-section textarea {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.92rem;
            resize: vertical;
            min-height: 80px;
            background: #fafafa;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        .reply-section textarea:focus { outline: none; border-color: #10b981; background: white; }
        .reply-section .reply-footer {
            display: flex; justify-content: space-between; align-items: center;
            margin-top: 0.6rem;
        }
        .reply-section .reply-from { font-size: 0.78rem; color: #a0aec0; }
        .btn-send-reply {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border: none;
            padding: 0.55rem 1.4rem; border-radius: 8px;
            font-weight: 600; font-size: 0.88rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            transition: all 0.2s;
        }
        .btn-send-reply:hover { opacity: 0.88; transform: translateY(-1px); }
        .reply-success {
            font-size: 0.82rem; color: #059669; font-weight: 600;
            display: none; align-items: center; gap: 0.3rem;
        }

        /* ── TICKET CONFIRM MODAL ── */
        .confirm-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .confirm-modal-overlay.open { display: flex; }
        .confirm-ticket {
            background: white;
            border-radius: 16px;
            width: 420px;
            max-width: 90vw;
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .confirm-ticket-header {
            background: linear-gradient(135deg, #10b981, #059669);
            padding: 1.5rem;
            text-align: center;
            color: white;
        }
        .confirm-ticket-header i { font-size: 2.5rem; margin-bottom: 0.4rem; display: block; }
        .confirm-ticket-header h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 0.25rem; }
        .confirm-ticket-header p { font-size: 0.85rem; opacity: 0.85; }
        .confirm-ticket-id {
            background: rgba(255,255,255,0.15);
            border: 1.5px dashed rgba(255,255,255,0.6);
            border-radius: 8px;
            padding: 0.4rem 1rem;
            font-size: 1.2rem;
            font-weight: 800;
            letter-spacing: 2px;
            display: inline-block;
            margin-top: 0.75rem;
        }
        .confirm-ticket-body {
            padding: 1.4rem 1.5rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1.25rem;
        }
        .confirm-field label {
            font-size: 0.72rem;
            font-weight: 700;
            color: #a0aec0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.2rem;
        }
        .confirm-field p { font-size: 0.9rem; color: #2d3748; font-weight: 500; }
        .confirm-field.full { grid-column: 1 / -1; }
        .confirm-ticket-footer {
            padding: 0 1.5rem 1.4rem;
            display: flex;
            justify-content: center;
        }
        .confirm-ticket-footer button {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 0.65rem 2rem;
            border-radius: 8px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .confirm-ticket-footer button:hover { opacity: 0.88; }
        .ticket-modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .ticket-modal-overlay.open { display: flex; }
        .ticket-modal {
            background: white;
            border-radius: 14px;
            width: 520px;
            max-width: 90vw;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .ticket-modal-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 1rem 1.4rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .ticket-modal-header span { font-weight: 700; font-size: 1rem; }
        .ticket-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 1.4rem;
            cursor: pointer;
            line-height: 1;
        }
        .ticket-modal-body { padding: 1.5rem; overflow-y: auto; flex: 1; min-height: 0; }
        .ticket-modal-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem 1.5rem;
            margin-bottom: 1.25rem;
            font-size: 0.88rem;
            color: #718096;
        }
        .ticket-modal-meta strong { color: #4a5568; }
        .ticket-modal-content {
            background: #f7fafc;
            border-radius: 8px;
            padding: 1rem 1.2rem;
            font-size: 0.92rem;
            color: #2d3748;
            line-height: 1.8;
            white-space: pre-wrap;
            max-height: 160px;
            overflow-y: auto;
        }

        .conversation-section { margin-top: 1.25rem; border-top: 1.5px solid #e2e8f0; padding-top: 1rem; }
        .conversation-section h4 {
            font-size: 0.75rem; font-weight: 700; color: #a0aec0;
            letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 0.65rem;
        }
        .reply-thread { display: flex; flex-direction: column; gap: 0.6rem; max-height: 160px; overflow-y: auto; margin-bottom: 0.75rem; }
        .reply-bubble {
            max-width: 78%; padding: 0.6rem 0.85rem;
            border-radius: 12px; font-size: 0.87rem; line-height: 1.6;
        }
        .reply-bubble.user {
            align-self: flex-end;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white; border-bottom-right-radius: 4px;
        }
        .reply-bubble.support {
            align-self: flex-start;
            background: #f0f4f8; color: #2d3748;
            border-bottom-left-radius: 4px;
        }
        .reply-bubble .bubble-meta { font-size: 0.71rem; margin-bottom: 0.2rem; opacity: 0.75; }
        .reply-empty { font-size: 0.83rem; color: #a0aec0; text-align: center; padding: 0.4rem 0; }

        .result-body {
            padding: 1.2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem 1.5rem;
        }

        .result-field label {
            color: #a0aec0; font-size: 0.75rem; font-weight: 700;
            letter-spacing: 0.5px; display: block; margin-bottom: 0.2rem;
        }

        .result-field p { color: #2d3748; font-size: 0.95rem; font-weight: 500; }
        .result-field.full { grid-column: 1 / -1; }

        .status-badge {
            padding: 0.3rem 0.75rem; border-radius: 20px;
            font-size: 0.78rem; font-weight: 700;
        }
        .status-open     { background: none; color: white; }
        .status-under-review { background: none; color: white; }
        .status-on-going { background: none; color: white; }
        .status-resolved { background: none; color: white; }

        .not-found {
            margin-top: 1rem;
            background: #fff5f5;
            border: 1.5px solid #fed7d7;
            border-radius: 8px;
            padding: 1rem 1.2rem;
            color: #c53030;
            font-size: 0.9rem;
            display: none;
            align-items: center;
            gap: 0.5rem;
        }
        .not-found.show { display: flex; }

        .footer {
            background: rgba(255,255,255,0.97);
            padding: 1.2rem 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.1);
            border-top: 2px solid #e2e8f0;
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
        .profile-btn {
            background: #e8ead6;
            border: none; border-radius: 50%;
            width: 42px; height: 42px;
            font-size: 1.4rem;
            color: #2d5a27;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            transition: background 0.2s, transform 0.2s;
        }
        .profile-btn:hover { background: #d6d9c0; transform: translateY(-2px); }

        @media (max-width: 900px) {
            .top-row { grid-template-columns: 1fr; }
            .stats-bar { flex-wrap: wrap; }
            .result-body { grid-template-columns: 1fr; }
        }

        @media (max-width: 640px) {
            .heading { padding: 0.75rem 1rem; gap: 0.5rem; }
            .welcome video { height: 38px; }
            .welcome-text h1 { font-size: 1.4rem; margin-top: 6px; }
            .welcome-message { display: none; }
            .header-actions { gap: 0.5rem; }
            .logout-btn { padding: 0.45rem 0.85rem; font-size: 0.82rem; }
            .logout-btn span { display: none; }

            /* Notification dropdown: anchor to viewport right edge so it never clips */
            .notif-wrapper { position: static; }
            .notif-dropdown {
                position: fixed;
                top: 70px;
                right: 1rem;
                left: 1rem;
                width: auto;
            }

            .stats-bar { padding: 1rem 1rem 0; gap: 0.6rem; }
            .stat-pill { padding: 0.7rem 0.85rem; gap: 0.5rem; }
            .stat-pill i { font-size: 1.4rem; padding: 0.35rem; }
            .stat-pill .value { font-size: 1.2rem; }
            .stat-pill .label { font-size: 0.72rem; }

            .page-wrapper { padding: 1rem; gap: 1rem; }
            .card { padding: 1rem; }

            .lookup-row { flex-direction: column; }
            .lookup-row input, .btn-lookup { width: 100%; }

            .confirm-ticket-body { grid-template-columns: 1fr; }
            .ticket-modal-meta { grid-template-columns: 1fr; }
            .result-body { grid-template-columns: 1fr; }

            /* Submit Ticket form footer: keep buttons on one row, no wrapping */
            .form-footer { flex-wrap: nowrap; gap: 0.5rem; }
            .btn-submit { padding: 0.7rem 1rem; white-space: nowrap; }
            .btn-submit .btn-label { display: none; }

            /* Result header: hide Reply button, shrink View Full to icon only */
            .result-header .btn-reply-header { display: none; }
            .btn-view-full .vf-label { display: none; }
            .btn-view-full { padding: 0.3rem 0.55rem; }
        }

        @media (max-width: 400px) {
            .stats-bar { grid-template-columns: 1fr 1fr; display: grid; }
            .stat-pill { flex-direction: column; text-align: center; gap: 0.25rem; }
        }

        
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

        .heading {
            animation: fadeInDown 0.5s ease both;
        }
        .stat-pill:nth-child(1) { animation: fadeInUp 0.5s 0.15s ease both; }
        .stat-pill:nth-child(2) { animation: fadeInUp 0.5s 0.25s ease both; }
        .stat-pill:nth-child(3) { animation: fadeInUp 0.5s 0.35s ease both; }
        .stat-pill:nth-child(4) { animation: fadeInUp 0.5s 0.45s ease both; }

        .top-row > .card:first-child  { animation: fadeInLeft  0.55s 0.3s ease both; }
        .top-row > .card:last-child   { animation: fadeInRight 0.55s 0.3s ease both; }

        .page-wrapper > .card:last-child { animation: fadeInUp 0.5s 0.5s ease both; }

        .footer { animation: fadeInUp 0.5s 0.55s ease both; }

        /* ── NOTIFICATION DROPDOWN ── */
        .notif-wrapper { position: relative; }
        .notif-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(0,0,0,0.15);
            border: 1.5px solid #e2e8f0;
            z-index: 999;
            overflow: hidden;
        }
        .notif-dropdown.open { display: block; }
        .notif-dropdown-header {
            padding: 0.85rem 1.1rem;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            font-weight: 700;
            font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .notif-list { max-height: 260px; overflow-y: scroll; }
        .notif-item {
            padding: 0.75rem 1.1rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background 0.15s;
        }
        .notif-item:last-child { border-bottom: none; }
        .notif-item:hover { background: #f0fdf4; }
        .notif-item .notif-id { font-weight: 700; color: #065f46; font-size: 0.88rem; }
        .notif-item .notif-subject { color: #4a5568; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .notif-item .notif-status { font-size: 0.75rem; font-weight: 600; margin-top: 0.2rem; display: inline-block; padding: 0.15rem 0.5rem; border-radius: 20px; }
        .notif-item .status-open         { background: #fef3c7; color: #92400e; }
        .notif-item .status-under-review  { background: #e0e7ff; color: #3730a3; }
        .notif-item .status-on-going      { background: #dbeafe; color: #1e40af; }
        .notif-item .status-resolved      { background: #d1fae5; color: #065f46; }
        .notif-empty { padding: 1.2rem; text-align: center; color: #a0aec0; font-size: 0.88rem; }

        /* ── LOGOUT MODAL ── */
        .logout-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1001; align-items: center; justify-content: center; }
        .logout-modal-overlay.open { display: flex; }
        @keyframes modalPopIn {
            from { opacity: 0; transform: scale(0.85) translateY(20px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }
        .logout-modal-overlay.open .logout-modal-box { animation: modalPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) both; }

        /* ── IMAGE LIGHTBOX ── */
        .lightbox-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.88); z-index: 2000;
            align-items: center; justify-content: center;
        }
        .lightbox-overlay.open { display: flex; }
        .lightbox-img {
            max-width: 90vw; max-height: 85vh;
            border-radius: 8px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6);
            transform-origin: center center;
            transition: transform 0.15s ease;
            user-select: none; display: block;
        }
        .lightbox-close {
            position: fixed; top: 1.25rem; right: 1.5rem;
            background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4);
            color: white; border-radius: 50%;
            width: 40px; height: 40px; font-size: 1.4rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s; z-index: 2001;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.3); }
        .lightbox-zoom {
            position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
            display: flex; gap: 0.5rem; z-index: 2001;
        }
        .lightbox-zoom button {
            background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4);
            color: white; border-radius: 8px; padding: 0.4rem 1rem;
            font-size: 1.1rem; cursor: pointer; transition: background 0.2s;
        }
        .lightbox-zoom button:hover { background: rgba(255,255,255,0.3); }

        /* ── IMAGE LIGHTBOX ── */
        .lightbox-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.88); z-index: 2000;
            align-items: center; justify-content: center;
            cursor: zoom-out;
        }
        .lightbox-overlay.open { display: flex; }
        .lightbox-inner {
            position: relative;
            display: flex; align-items: center; justify-content: center;
            width: 100%; height: 100%;
            overflow: hidden;
        }
        .lightbox-img {
            max-width: 90vw; max-height: 90vh;
            border-radius: 8px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.6);
            cursor: default;
            transform-origin: center center;
            transition: transform 0.15s ease;
            user-select: none;
        }
        .lightbox-close {
            position: fixed; top: 1.25rem; right: 1.5rem;
            background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4);
            color: white; border-radius: 50%;
            width: 40px; height: 40px; font-size: 1.4rem;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: background 0.2s; z-index: 2001;
        }
        .lightbox-close:hover { background: rgba(255,255,255,0.3); }
        .lightbox-zoom {
            position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
            display: flex; gap: 0.5rem; z-index: 2001;
        }
        .lightbox-zoom button {
            background: rgba(255,255,255,0.15); border: 1.5px solid rgba(255,255,255,0.4);
            color: white; border-radius: 8px; padding: 0.4rem 1rem;
            font-size: 1.1rem; cursor: pointer; transition: background 0.2s;
        }
        .lightbox-zoom button:hover { background: rgba(255,255,255,0.3); }
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

    <!-- HEADER -->
    <div class="heading">
        <div class="welcome">
            <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
            <div class="welcome-text">
                <h1>KlovrBank</h1>
            </div>
        </div>
        <div class="header-actions">
            <div class="notif-wrapper">
                <button class="notif-btn" onclick="toggleNotif()">
                    <i class='bx bx-bell'></i>
                    <span class="notif-dot" id="notifDot"></span>
                </button>
                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-dropdown-header"><i class='bx bx-bell'></i> Notifications</div>
                    <div class="notif-list" id="notifList"><div class="notif-empty">Loading...</div></div>
                </div>
            </div>
            <a href="profile.php" class="profile-btn" title="My Profile">
                <?php if (!empty($_avatar)): ?>
                    <img src="<?php echo htmlspecialchars($_avatar); ?>" style="width:100%;height:100%;object-fit:cover;object-position:<?php echo htmlspecialchars($_avatar_offset); ?>;border-radius:50%;">
                <?php else: ?>
                    <i class='bx bx-user-circle'></i>
                <?php endif; ?>
            </a>
            <div>
                <h1 class="welcome-message"> Hi <?php echo htmlspecialchars($_display_name); ?>!</h1>
            </div>
            <button onclick="confirmLogout()" class="logout-btn">
                <i class='bx bx-log-out'></i> Logout
            </button>
        </div>
    </div>

    <!-- PAGE WRAPPER -->
    <div class="page-wrapper">
        
        <!-- TOP ROW: FAQ (left) + TICKET FORM (right) -->
        <div class="top-row">

            <!-- FAQs -->
            <div class="card">
                <div class="card-header">
                    <h2><i class='bx bx-help-circle'></i> Frequently Asked Questions</h2>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How do I change my password safely?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        You can change your password through the <strong>user profile</strong>. Always choose a strong password that combines letters, numbers, and symbols for better security. Avoid reusing old passwords or using the same password across multiple services.
                    </div></div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Why is my card transaction being declined?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        Transactions may be declined due to insufficient funds, an expired card, or a security hold. Check your balance and card expiry. If the issue persists, submit a <strong>Billing</strong> ticket and our team will investigate within 2 hours for high-priority cases.
                    </div></div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        How do I change my card PIN?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        You can change your PIN at an ATM or through our mobile app. For security, you may berequired to verify your identity before setting a new PIN. Always choose a PIN that is secure and not easy to guess.
                    </div></div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        What should I do if my statement has an error?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        If you find an error, you should report it to us <strong>immediately</strong> for investigation. Provide details such as transaction date, amount, and description. The bank will review and correct valid discrepancies.
                    </div></div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        Why can’t I make international transactions?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        International transactions may be <strong>disabled by default</strong> or restricted due to account type or security settings. You may need to activate overseas usage in your app or contact your bank. Some banks also require additional verification.
                    </div></div>
                </div>

                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        What are bank fees and why do I have to pay them?
                        <i class='bx bx-plus'></i>
                    </button>
                    <div class="faq-answer"><div class="faq-answer-inner">
                        Bank fees are charged for certain services like account maintenance, transfers, or special transactions. They help cover operational costs and support banking services and systems. Not all accounts have the same fees depending on their type and features.
                    </div></div>
                </div>

            </div>

            <!-- SUBMIT TICKET FORM -->
            <div class="card" style="display:flex; flex-direction:column;">
                <div class="card-header">
                    <h2><i class='bx bx-send'></i> Submit a Ticket</h2>
                </div>
                <form id="submitForm" style="display:flex; flex-direction:column; flex:1;" onsubmit="submitTicket(event)" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group full">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="Brief description of your issue" required>
                        </div>
                        <div class="form-group full">
                            <label>Category</label>
                            <input type="hidden" id="category" name="category">
                            <div class="custom-select-wrapper" id="categoryWrapper">
                                <div class="custom-select-trigger" id="categoryTrigger" onclick="toggleCategoryDropdown()">
                                    <span id="categoryLabel">Select a category</span>
                                    <i class='bx bx-chevron-down arrow'></i>
                                </div>
                                <div class="custom-options">
                                    <div class="custom-option" onclick="selectCategory('Technical', 'Technical Issue', 'bx-wrench')"><i class='bx bx-wrench'></i> Technical Issue</div>
                                    <div class="custom-option" onclick="selectCategory('Account', 'Account Related', 'bx-user')"><i class='bx bx-user'></i> Account Related</div>
                                    <div class="custom-option" onclick="selectCategory('Billing', 'Billing', 'bx-credit-card')"><i class='bx bx-credit-card'></i> Billing</div>
                                    <div class="custom-option" onclick="selectCategory('General', 'General Inquiry', 'bx-chat')"><i class='bx bx-chat'></i> General Inquiry</div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group full" style="display:flex; flex-direction:column; flex:1;">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" placeholder="Describe your issue in detail..." required style="flex:1;"></textarea>
                        </div>
                        <input type="file" id="ticket_image" name="ticket_image" accept="image/*" style="display:none;">
                        <div id="imagePreview" style="display:none;position:relative;margin-top:0.25rem;align-items:flex-start;gap:0.5rem;">
                            <img id="previewImg" src="" style="max-height:100px;border-radius:8px;border:1.5px solid #e2e8f0;">
                            <button type="button" onclick="removeImage()" style="position:absolute;top:-8px;left:-8px;background:#e53e3e;color:white;border:none;border-radius:50%;width:22px;height:22px;font-size:0.85rem;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;"><i class='bx bx-x'></i></button>
                        </div>
                    </div>
                    <div class="form-footer">
                        <button type="button" class="btn-cancel" onclick="clearForm()">Clear</button>
                        <button type="button" class="btn-cancel" onclick="document.getElementById('ticket_image').click()" title="Attach image" style="padding:0.7rem 0.85rem;"><i class='bx bx-image-add' style="font-size:1.1rem;"></i></button>
                        <button type="submit" class="btn-submit">
                            <i class='bx bx-send'></i> <span class="btn-label">Submit Ticket</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- BOTTOM: TICKET LOOKUP -->
        <div class="card">
            <div class="card-header">
                <h2><i class='bx bx-search-alt'></i> Check Your Ticket</h2>
            </div>
            <div class="lookup-row">
                <input type="text" id="ticketIdInput" placeholder="Enter your Ticket ID (e.g. #1024)">
                <button class="btn-lookup" onclick="lookupTicket()">
                    <i class='bx bx-search'></i> Search
                </button>
            </div>

            <div class="not-found" id="notFound">
                <i class='bx bx-error-circle'></i>
                No ticket found with that ID. Please check and try again.
            </div>

            <div class="ticket-result" id="ticketResult">
                <div class="result-header">
                    <span id="resultId"></span>
                    <div style="display:flex; align-items:center; gap:0.75rem;">
                        <span id="resultStatus"></span>
                        <button class="btn-reply-header" onclick="openTicketModal(true)"><i class='bx bx-message'></i> Message</button>
                        <button class="btn-view-full" onclick="openTicketModal(false)"><i class='bx bx-expand-alt'></i> <span class="vf-label">View Full</span></button>
                    </div>
                </div>
                <div class="result-body">
                    <div class="result-field">
                        <label>SUBJECT</label>
                        <p id="resultSubject"></p>
                    </div>
                    <div class="result-field">
                        <label>CATEGORY</label>
                        <p id="resultCategory"></p>
                    </div>
                    <div class="result-field">
                        <label>DATE SUBMITTED</label>
                        <p id="resultDate"></p>
                    </div>
                    <div class="result-field" id="resultSupportWrap" style="display:none;">
                        <label>ASSIGNED SUPPORT</label>
                        <p id="resultSupport"></p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- TICKET CONFIRM MODAL -->
    <div class="confirm-modal-overlay" id="confirmModal">
        <div class="confirm-ticket">
            <div class="confirm-ticket-header">
                <i class='bx bx-check-circle'></i>
                <h3>Ticket Submitted!</h3>
                <p>Your ticket has been received. Keep your ID safe.</p>
                <div class="confirm-ticket-id" id="confirmTicketId" onclick="copyTicketId()" title="Click to copy" style="cursor:pointer;"></div>
                <div id="copyMsg" style="font-size:0.75rem;opacity:0.8;margin-top:0.4rem;display:none;">Copied!</div>
            </div>
            <div class="confirm-ticket-body">
                <div class="confirm-field full">
                    <label>Subject</label>
                    <p id="confirmSubject"></p>
                </div>
                <div class="confirm-field">
                    <label>Category</label>
                    <p id="confirmCategory"></p>
                </div>
                <div class="confirm-field">
                    <label>Date</label>
                    <p id="confirmDate"></p>
                </div>
                <div class="confirm-field">
                    <label>Status</label>
                    <p><span style="background:#fef3c7;color:#92400e;padding:0.2rem 0.6rem;border-radius:20px;font-size:0.8rem;font-weight:700;">Open</span></p>
                </div>
            </div>
            <div class="confirm-ticket-footer">
                <button onclick="closeConfirmModal()"><i class='bx bx-check'></i> Got it!</button>
            </div>
        </div>
    </div>

    <!-- TICKET FULL MODAL -->
    <div class="ticket-modal-overlay" id="ticketModal">
        <div class="ticket-modal">
            <div class="ticket-modal-header">
                <span id="modalId"></span>
                <div style="display:flex;align-items:center;gap:0.6rem;">
                    <button class="btn-reply-header" id="modalReplyToggleBtn" onclick="toggleReplySection()"><i class='bx bx-message'></i> Message</button>
                    <button class="ticket-modal-close" onclick="closeTicketModal()"><i class='bx bx-x'></i></button>
                </div>
            </div>
            <div class="ticket-modal-body">
                <div class="ticket-modal-meta">
                    <div><strong>Subject</strong><br><span id="modalSubject"></span></div>
                    <div><strong>Category</strong><br><span id="modalCategory"></span></div>
                    <div><strong>Status</strong><br><span id="modalStatus"></span></div>
                    <div><strong>Date Submitted</strong><br><span id="modalDate"></span></div>
                    <div id="modalSupportWrap" style="display:none;"><strong>Assigned Support</strong><br><span id="modalSupport"></span></div>
                </div>
                <div class="ticket-modal-content" id="modalContent"></div>
                <div id="modalImageWrap" style="display:none;margin-top:0.75rem;">
                    <img id="modalImage" src="" alt="Ticket attachment" style="max-width:100%;max-height:160px;border-radius:8px;border:1.5px solid #e2e8f0;cursor:zoom-in;object-fit:contain;" onclick="openLightbox(this.src)">
                </div>
                <div class="conversation-section">
                    <h4><i class='bx bx-conversation'></i> Conversation</h4>
                    <div class="reply-thread" id="modalReplyThread"></div>
                </div>
            </div>
            <div class="reply-section" id="replySection" style="display:none;">
                <textarea id="replyMessage" placeholder="Write your message to support..."></textarea>
                <div class="reply-footer">
                    <span class="reply-from" id="replyFromLabel"></span>
                    <div style="display:flex;align-items:center;gap:0.75rem;">
                        <span class="reply-success" id="replySuccess"><i class='bx bx-check-circle'></i> Sent!</span>
                        <button class="btn-send-reply" onclick="sendReply()"><i class='bx bx-send'></i> Send Message</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IMAGE LIGHTBOX -->
    <div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
        <button class="lightbox-close" onclick="closeLightbox()"><i class='bx bx-x'></i></button>
        <img class="lightbox-img" id="lightboxImg" src="" alt="Ticket attachment" onclick="event.stopPropagation()">
        <div class="lightbox-zoom">
            <button onclick="event.stopPropagation();zoomLightbox(-0.25)" title="Zoom out"><i class='bx bx-zoom-out'></i></button>
            <button onclick="event.stopPropagation();zoomLightbox(0)" title="Reset"><i class='bx bx-reset'></i></button>
            <button onclick="event.stopPropagation();zoomLightbox(0.25)" title="Zoom in"><i class='bx bx-zoom-in'></i></button>
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

    <!-- FOOTER -->
    <div class="footer">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
    </div>

    <script>
        function confirmLogout() {
            document.getElementById('logoutModal').classList.add('open');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.remove('open');
        });

        let _lastTicket = {};

        function lookupTicket() {
            const ticketId = document.getElementById('ticketIdInput').value.replace('#', '');
            const resultDiv = document.getElementById('ticketResult');
            const errorDiv = document.getElementById('notFound');

            if (!ticketId) {
                alert("Please enter a Ticket ID");
                return;
            }

            // Call the PHP file without reloading the page
            fetch(`check_status.php?ticket_id=${ticketId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error === 'unauthorized') {
                        errorDiv.querySelector ? null : null;
                        errorDiv.innerHTML = '<i class=\'bx bx-lock-alt\'></i> Please log in to check your ticket status.';
                        errorDiv.style.display = 'flex';
                        resultDiv.style.display = 'none';
                    } else if (data.error === 'not_found') {
                        errorDiv.style.display = 'block';
                        resultDiv.style.display = 'none';
                    } else {
                        errorDiv.style.display = 'none';
                        resultDiv.style.display = 'block';

                        // Fill the HTML with the data from the database
                        document.getElementById('resultId').innerText = '#' + data.display_id;
                        document.getElementById('resultStatus').innerText = data.status;
                        document.getElementById('resultSubject').innerText = data.subject;
                        document.getElementById('resultCategory').innerText = data.category.charAt(0).toUpperCase() + data.category.slice(1);
                        document.getElementById('resultDate').innerText = data.created_at;
                        _lastTicket = data;
                        const supportWrap = document.getElementById('resultSupportWrap');
                        document.getElementById('resultSupport').innerText = (data.assigned_support_email && data.status !== 'Open') ? data.assigned_support_email : 'None';
                        supportWrap.style.display = '';
                        // Optional: Add a class for status colors
                        document.getElementById('resultStatus').className = 'status-' + data.status.toLowerCase();
                    }
                })
                .catch(err => console.error("Error fetching ticket:", err));
        }

        document.getElementById('ticketIdInput').addEventListener('keydown', e => {
            if (e.key === 'Enter') lookupTicket();
        });

        // Show success alert if redirected after ticket submission
        const urlParams = new URLSearchParams(window.location.search);
        const newTicketId = urlParams.get('ticket_id');
        if (newTicketId) history.replaceState(null, '', 'user_dashboard.php');

        function submitTicket(e) {
            e.preventDefault();
            if (!validateForm()) return;
            fetch('submit_ticket.php', { method: 'POST', body: new FormData(e.target) })
                .then(r => r.json())
                .then(res => {
                    if (!res.success) { alert('Submit failed: ' + (res.error || 'unknown error')); return; }
                    document.getElementById('confirmTicketId').textContent = '#' + res.ticket_id;
                    document.getElementById('confirmSubject').textContent  = res.subject;
                    document.getElementById('confirmCategory').textContent = res.category.charAt(0).toUpperCase() + res.category.slice(1);
                    document.getElementById('confirmDate').textContent     = res.date;
                    document.getElementById('confirmModal').classList.add('open');
                    clearForm();
                });
        }

        function copyTicketId() {
            const id = document.getElementById('confirmTicketId').textContent;
            navigator.clipboard.writeText(id);
            const msg = document.getElementById('copyMsg');
            msg.style.display = 'block';
            setTimeout(() => msg.style.display = 'none', 2000);
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.remove('open');
        }
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) closeConfirmModal();
        });

        function validateForm() {
            const category = document.getElementById('category').value;
            if (!category) {
                const trigger = document.getElementById('categoryTrigger');
                trigger.style.borderColor = '#f56565';
                setTimeout(() => trigger.style.borderColor = '', 2000);
                return false;
            }
            return true;
        }

        function toggleCategoryDropdown() {
            document.getElementById('categoryWrapper').classList.toggle('open');
        }
        document.addEventListener('click', function(e) {
            const wrapper = document.getElementById('categoryWrapper');
            if (!wrapper.contains(e.target)) wrapper.classList.remove('open');
        });
        function selectCategory(value, label, icon) {
            document.getElementById('category').value = value;
            const trigger = document.getElementById('categoryTrigger');
            document.getElementById('categoryLabel').innerHTML = `<i class='bx ${icon}'></i> ${label}`;
            trigger.classList.add('has-value');
            document.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            document.getElementById('categoryWrapper').classList.remove('open');
        }

        document.getElementById('ticket_image').addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.getElementById('imagePreview');
            const img = document.getElementById('previewImg');
            if (file) {
                if (file.size > 10 * 1024 * 1024) {
                    alert('Image exceeds the 10MB limit. Please choose a smaller file.');
                    this.value = '';
                    preview.style.display = 'none';
                    img.src = '';
                    return;
                }
                img.src = URL.createObjectURL(file);
                preview.style.display = 'flex';
            } else {
                preview.style.display = 'none'; img.src = '';
            }
        });

        function removeImage() {
            const input = document.getElementById('ticket_image');
            input.value = '';
            document.getElementById('previewImg').src = '';
            document.getElementById('imagePreview').style.display = 'none';
        }

        function clearForm() {
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
            document.getElementById('category').value = '';
            document.getElementById('categoryLabel').innerHTML = 'Select a category';
            document.getElementById('categoryTrigger').classList.remove('has-value');
            document.querySelectorAll('.custom-option').forEach(o => o.classList.remove('selected'));
            removeImage();
        }

        let _modalReplyPollInterval = null;

        function openTicketModal(focusReply = false) {
            document.getElementById('modalId').textContent      = '#' + _lastTicket.display_id;
            document.getElementById('modalSubject').textContent  = _lastTicket.subject;
            document.getElementById('modalCategory').textContent = _lastTicket.category.charAt(0).toUpperCase() + _lastTicket.category.slice(1);
            document.getElementById('modalStatus').textContent   = _lastTicket.status;
            document.getElementById('modalDate').textContent     = _lastTicket.created_at;
            document.getElementById('modalContent').textContent  = _lastTicket.content;
            const imgWrap = document.getElementById('modalImageWrap');
            if (_lastTicket.image_path) {
                document.getElementById('modalImage').src = _lastTicket.image_path;
                imgWrap.style.display = 'block';
            } else { imgWrap.style.display = 'none'; }
            const modalSupportWrap = document.getElementById('modalSupportWrap');
            document.getElementById('modalSupport').textContent = (_lastTicket.assigned_support_email && _lastTicket.status !== 'Open') ? _lastTicket.assigned_support_email : 'None';
            modalSupportWrap.style.display = '';
            document.getElementById('replyFromLabel').textContent = 'From: ' + (_lastTicket.user_email || '');
            document.getElementById('replyMessage').value = '';
            document.getElementById('replySuccess').style.display = 'none';
            const isResolved = _lastTicket.status === 'Resolved';
            const replySection = document.getElementById('replySection');
            const toggleBtn = document.getElementById('modalReplyToggleBtn');
            if (isResolved) {
                replySection.style.display = 'none';
                toggleBtn.style.display = 'none';
            } else {
                toggleBtn.style.display = '';
                replySection.style.display = focusReply ? 'block' : 'none';
            }
            document.getElementById('ticketModal').classList.add('open');
            loadModalReplies(_lastTicket.display_id);
            clearInterval(_modalReplyPollInterval);
            _modalReplyPollInterval = setInterval(() => loadModalReplies(_lastTicket.display_id), 4000);
            if (focusReply && !isResolved) setTimeout(() => document.getElementById('replyMessage').focus(), 100);
        }

        function loadModalReplies(displayId) {
            fetch(`get_replies.php?display_id=${displayId}`)
                .then(r => r.json())
                .then(replies => {
                    const thread = document.getElementById('modalReplyThread');
                    if (!replies.length) {
                        thread.innerHTML = '<div class="reply-empty">No messages yet.</div>';
                        return;
                    }
                    const isResolved = _lastTicket.status === 'Resolved';
                    // Find the index of the last resolution prompt (case-insensitive)
                    let lastPromptIdx = -1;
                    for (let i = replies.length - 1; i >= 0; i--) {
                        if (replies[i].sender_role === 'support' && replies[i].message.trim().toLowerCase() === 'has the issue been resolved?') {
                            lastPromptIdx = i;
                            break;
                        }
                    }
                    // Buttons only show if the prompt is the very last message and ticket is not resolved
                    const showButtons = !isResolved && lastPromptIdx === replies.length - 1;
                    thread.innerHTML = replies.map((r, i) => {
                        const isResolutionPrompt = r.sender_role === 'support' && r.message.trim().toLowerCase() === 'has the issue been resolved?';
                        if (isResolutionPrompt) {
                            const buttons = (showButtons && i === lastPromptIdx) ? `<div class="resolution-actions" style="display:flex;gap:0.5rem;margin-top:0.5rem;">
                                <button onclick="answerResolution(true)" style="background:#10b981;color:white;border:none;border-radius:6px;padding:0.3rem 0.85rem;font-size:0.8rem;font-weight:600;cursor:pointer;"><i class='bx bx-check'></i> Yes</button>
                                <button onclick="answerResolution(false)" style="background:#e2e8f0;color:#4a5568;border:none;border-radius:6px;padding:0.3rem 0.85rem;font-size:0.8rem;font-weight:600;cursor:pointer;">No</button>
                            </div>` : '';
                            return `<div class="reply-bubble support" style="background:#f0fdf4;color:#065f46;border:1.5px solid #10b981;">
                                <div class="bubble-meta">${r.sender_email} &bull; ${r.created_at}</div>
                                <strong>Has the issue been resolved?</strong>
                                ${buttons}
                            </div>`;
                        }
                        return `<div class="reply-bubble ${r.sender_role}">
                            <div class="bubble-meta">${r.sender_email} &bull; ${r.created_at}</div>
                            ${r.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                        </div>`;
                    }).join('');
                    thread.scrollTop = thread.scrollHeight;
                });
        }
        function answerResolution(yes) {
            document.querySelectorAll('#modalReplyThread .resolution-actions').forEach(el => el.remove());
            if (!yes) return;
            const fd = new FormData();
            fd.append('display_id', _lastTicket.display_id);
            fetch('resolve_ticket.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        _lastTicket.status = 'Resolved';
                        document.getElementById('modalStatus').textContent = 'Resolved';
                        document.getElementById('replySection').style.display = 'none';
                        document.getElementById('modalReplyToggleBtn').style.display = 'none';
                        const statusEl = document.getElementById('resultStatus');
                        if (statusEl) { statusEl.textContent = 'Resolved'; statusEl.className = 'status-resolved'; }
                        loadModalReplies(_lastTicket.display_id);
                    }
                });
        }
        function toggleReplySection() {
            const s = document.getElementById('replySection');
            s.style.display = s.style.display === 'none' ? 'block' : 'none';
            if (s.style.display === 'block') setTimeout(() => document.getElementById('replyMessage').focus(), 50);
        }
        function sendReply() {
            const message = document.getElementById('replyMessage').value.trim();
            if (!message) { document.getElementById('replyMessage').focus(); return; }
            // If resolution buttons are visible, treat sending a message as "No"
            document.querySelectorAll('#modalReplyThread .resolution-actions').forEach(el => el.remove());
            const btn = document.querySelector('.btn-send-reply');
            btn.disabled = true;
            const fd = new FormData();
            fd.append('display_id', _lastTicket.display_id);
            fd.append('message', message);
            fetch('submit_reply.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('replyMessage').value = '';
                        const ok = document.getElementById('replySuccess');
                        ok.style.display = 'flex';
                        setTimeout(() => { ok.style.display = 'none'; }, 3000);
                        loadModalReplies(_lastTicket.display_id);
                    }
                })
                .finally(() => btn.disabled = false);
        }
        function closeTicketModal() {
            clearInterval(_modalReplyPollInterval);
            _modalReplyPollInterval = null;
            document.getElementById('ticketModal').classList.remove('open');
        }
        document.getElementById('ticketModal').addEventListener('click', function(e) {
            if (e.target === this) closeTicketModal();
        });

        function toggleNotif() {
            const dropdown = document.getElementById('notifDropdown');
            const isOpen = dropdown.classList.toggle('open');
            if (isOpen) loadNotifs();
        }

        document.addEventListener('click', function(e) {
            const wrapper = document.querySelector('.notif-wrapper');
            if (!wrapper.contains(e.target)) document.getElementById('notifDropdown').classList.remove('open');
        });

        function loadNotifs() {
            fetch('get_pending_tickets.php')
                .then(r => r.json())
                .then(tickets => {
                    const list = document.getElementById('notifList');
                    const dot  = document.getElementById('notifDot');
                    if (!tickets.length) {
                        list.innerHTML = '<div class="notif-empty">No pending tickets 🎉</div>';
                        dot.style.display = 'none';
                        return;
                    }
                    const hasPending = tickets.some(t => t.status !== 'Resolved');
                    dot.style.display = hasPending ? '' : 'none';
                    list.innerHTML = tickets.map(t => `
                        <div class="notif-item" onclick="fillLookup('${t.display_id}')">
                            <div class="notif-id">#${t.display_id}</div>
                            <div class="notif-subject">${t.subject}</div>
                            <div class="notif-status status-${t.status.toLowerCase().replace(' ','-')}">${t.status}</div>
                        </div>`).join('');
                });
        }

        function fillLookup(id) {
            document.getElementById('notifDropdown').classList.remove('open');
            document.getElementById('ticketIdInput').value = id;
            document.getElementById('ticketIdInput').scrollIntoView({ behavior: 'smooth', block: 'center' });
            lookupTicket();
        }

        // Auto-load on page open to set dot visibility, then poll every 10s
        function refreshNotifDot() {
            fetch('get_pending_tickets.php').then(r => r.json()).then(t => {
                document.getElementById('notifDot').style.display = t.some(x => x.status !== 'Resolved') ? '' : 'none';
            });
        }
        refreshNotifDot();
        setInterval(refreshNotifDot, 10000);

        function toggleFaq(btn) {
            const item = btn.closest('.faq-item');
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
            if (!isOpen) {
                item.classList.add('open');
                const answer = item.querySelector('.faq-answer');
                answer.style.maxHeight = answer.scrollHeight + 'px';
            }
            document.querySelectorAll('.faq-item:not(.open) .faq-answer').forEach(a => a.style.maxHeight = '');
        }

        /* ── LIGHTBOX ── */
        let _lbScale = 1;
        function openLightbox(src) {
            _lbScale = 1;
            const img = document.getElementById('lightboxImg');
            img.src = src;
            img.style.transform = 'scale(1)';
            document.getElementById('lightboxOverlay').classList.add('open');
        }
        function closeLightbox() {
            document.getElementById('lightboxOverlay').classList.remove('open');
        }
        function zoomLightbox(delta) {
            if (delta === 0) { _lbScale = 1; }
            else { _lbScale = Math.min(4, Math.max(0.25, _lbScale + delta)); }
            document.getElementById('lightboxImg').style.transform = 'scale(' + _lbScale + ')';
        }
        // Mouse wheel zoom
        document.getElementById('lightboxOverlay').addEventListener('wheel', function(e) {
            e.preventDefault();
            zoomLightbox(e.deltaY < 0 ? 0.15 : -0.15);
        }, { passive: false });
        // Close on Escape
        document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });
    </script>
</body>

<script src="https://cdn.botpress.cloud/webchat/v3.6/inject.js"></script>
<script src="https://files.bpcontent.cloud/2025/03/01/02/20250301022520-TR4PWHYI.js" defer></script>

</html>
