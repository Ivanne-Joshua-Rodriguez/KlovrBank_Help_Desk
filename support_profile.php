<?php
ini_set('session.gc_maxlifetime', 28800);
session_set_cookie_params(28800);
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'support') {
    header("Location: admin_login.php?error=unauthorized");
    exit();
}

$conn = new mysqli("localhost", "root", "", "help_desk_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$stmt = $conn->prepare("SELECT id, email, is_active, created_at, profile_picture, profile_picture_offset FROM users WHERE email = ? AND role = 'support' LIMIT 1");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$agent = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Ticket stats for this agent
$email_esc = $conn->real_escape_string($agent['email']);
$stats = [];
foreach ([
    'total'    => "",
    'open'     => "AND status='Open'",
    'ongoing'  => "AND status='On-Going'",
    'resolved' => "AND status='Resolved'",
] as $key => $cond) {
    $r = $conn->query("SELECT COUNT(*) as c FROM tickets WHERE assigned_support_email='$email_esc' $cond");
    $stats[$key] = $r->fetch_assoc()['c'];
}

$conn->close();
$member_since = date('F d, Y', strtotime($agent['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Support</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { min-height: 100vh; display: flex; flex-direction: column; }
        .bg-video { position: fixed; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }

        .heading {
            background: rgba(255,255,255,0.97);
            padding: 1rem 2rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.12);
            display: flex; align-items: center; justify-content: space-between;
        }
        .welcome { display: flex; align-items: center; gap: 1rem; }
        .welcome video { height: 48px; width: auto; }
        .welcome-text h1 { color: #2d3748; font-size: 1.4rem; }
        .welcome-text span { color: #10b981; font-size: 0.85rem; font-weight: 500; }
        .header-actions { display: flex; align-items: center; gap: 1rem; }
        .back-btn-header {
            background: #f0fdf4; color: #10b981;
            border: 1.5px solid #10b981; border-radius: 8px;
            padding: 0.5rem 1.2rem; font-weight: 600; font-size: 0.9rem;
            cursor: pointer; display: flex; align-items: center; gap: 0.4rem;
            text-decoration: none; transition: background 0.2s;
        }
        .back-btn-header:hover { background: #10b981; color: white; }
        .logout-btn {
            background: #f56565; color: white;
            padding: 0.5rem 1.5rem; border-radius: 8px;
            text-decoration: none; font-weight: 600;
            display: flex; align-items: center; gap: 0.4rem; transition: all 0.3s;
        }
        .logout-btn:hover { background: #e53e3e; transform: translateY(-2px); }

        .page-wrapper {
            flex: 1; display: flex; flex-direction: column;
            gap: 1.5rem; padding: 2rem;
            max-width: 860px; margin: 0 auto; width: 100%;
        }

        /* HERO */
        .profile-hero {
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 16px; padding: 2rem 2.5rem;
            display: flex; align-items: center; gap: 2rem;
            box-shadow: 0 4px 20px rgba(16,185,129,0.3);
            animation: fadeInDown 0.5s ease both;
        }
        .avatar {
            width: 90px; height: 90px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            border: 3px solid rgba(255,255,255,0.5);
            display: flex; align-items: center; justify-content: center;
            font-size: 2.5rem; color: white; flex-shrink: 0;
            overflow: hidden; cursor: pointer; position: relative;
        }
        .avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-edit-hint {
            position: absolute; inset: 0; background: rgba(0,0,0,0.45);
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s; border-radius: 50%;
            color: white; font-size: 1.4rem;
        }
        .avatar:hover .avatar-edit-hint { opacity: 1; }
        .hero-info h2 { color: white; font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem; }
        .hero-info p  { color: rgba(255,255,255,0.8); font-size: 0.9rem; }
        .hero-badges  { display: flex; gap: 0.6rem; margin-top: 0.6rem; flex-wrap: wrap; }
        .hero-badge {
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4);
            color: white; border-radius: 20px; padding: 0.2rem 0.75rem;
            font-size: 0.78rem; font-weight: 600; display: flex; align-items: center; gap: 0.3rem;
        }

        /* STATS */
        .stats-row {
            display: grid; grid-template-columns: repeat(4, 1fr);
            gap: 1rem; animation: fadeInUp 0.5s 0.1s ease both;
        }
        .stat-card {
            background: rgba(255,255,255,0.97); border-radius: 12px;
            padding: 1.2rem 1rem; text-align: center;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: transform 0.2s;
        }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-card i { font-size: 1.8rem; padding: 0.5rem; border-radius: 8px; margin-bottom: 0.4rem; display: inline-block; }
        .stat-card.total i   { color: #10b981; background: #f0fdf4; }
        .stat-card.open i    { color: #f59e0b; background: #fffbeb; }
        .stat-card.ongoing i { color: #3b82f6; background: #eff6ff; }
        .stat-card.resolved i{ color: #8b5cf6; background: #f5f3ff; }
        .stat-card .val   { font-size: 1.8rem; font-weight: 700; color: #2d3748; line-height: 1; }
        .stat-card .label { font-size: 0.78rem; color: #718096; margin-top: 0.2rem; }

        /* DETAIL CARD */
        .detail-card {
            background: rgba(255,255,255,0.97); border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden;
            animation: fadeInUp 0.5s 0.2s ease both;
        }
        .detail-card-header {
            background: #f7fafc; padding: 1rem 1.5rem;
            border-bottom: 2px solid #e2e8f0;
            display: flex; align-items: center; gap: 0.5rem;
        }
        .detail-card-header h3 { font-size: 0.95rem; font-weight: 700; color: #2d3748; text-transform: uppercase; letter-spacing: 0.5px; }
        .detail-card-header i  { color: #10b981; font-size: 1.1rem; }
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .detail-field {
            padding: 1.1rem 1.5rem; border-bottom: 1px solid #f0f4f8;
            display: flex; flex-direction: column; gap: 0.25rem;
        }
        .detail-field:nth-child(odd) { border-right: 1px solid #f0f4f8; }
        .detail-field.full { grid-column: 1 / -1; border-right: none; }
        .detail-field label { font-size: 0.72rem; font-weight: 700; color: #a0aec0; letter-spacing: 0.5px; text-transform: uppercase; }
        .detail-field .val  { font-size: 0.95rem; color: #2d3748; font-weight: 500; display: flex; align-items: center; gap: 0.5rem; }
        .detail-field .val i { color: #10b981; font-size: 1rem; }
        .badge-active   { background: #d1fae5; color: #065f46; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-inactive { background: #fee2e2; color: #991b1b; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-support  { background: #e0e7ff; color: #3730a3; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }

        /* CROP MODAL */
        .crop-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center; }
        .crop-overlay.open { display: flex; }
        .crop-modal { background: white; border-radius: 16px; width: 380px; max-width: 92vw; overflow: hidden; box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
        .crop-modal-header { background: linear-gradient(135deg,#10b981,#059669); color: white; padding: 1rem 1.4rem; font-weight: 700; font-size: 1rem; display: flex; justify-content: space-between; align-items: center; }
        .crop-modal-body { padding: 1.25rem; }
        .crop-viewport {
            width: 100%; height: 260px; border-radius: 10px; overflow: hidden;
            border: 2px solid #e2e8f0; position: relative; cursor: grab; background: #f0f0f0; user-select: none;
        }
        .crop-viewport:active { cursor: grabbing; }
        .crop-viewport img { position: absolute; width: 100%; height: 100%; object-fit: cover; pointer-events: none; }
        .crop-circle-guide {
            position: absolute; inset: 0;
            background: radial-gradient(circle 110px at center, transparent 110px, rgba(0,0,0,0.45) 110px);
            pointer-events: none;
        }
        .crop-modal-footer { padding: 0 1.25rem 1.25rem; display: flex; gap: 0.75rem; justify-content: flex-end; }
        .crop-cancel { padding: 0.55rem 1.2rem; border: 1.5px solid #cbd5e0; border-radius: 8px; background: white; color: #4a5568; font-weight: 600; cursor: pointer; }
        .crop-save   { padding: 0.55rem 1.4rem; border: none; border-radius: 8px; background: linear-gradient(135deg,#10b981,#059669); color: white; font-weight: 600; cursor: pointer; }

        /* LOGOUT MODAL */
        .logout-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 1001; align-items: center; justify-content: center; }
        .logout-modal-overlay.open { display: flex; }
        .logout-modal-box { background: white; border-radius: 14px; padding: 2rem; width: 340px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); text-align: center; }
        .logout-modal-box .modal-icon { font-size: 2.5rem; color: #f56565; margin-bottom: 0.75rem; }
        .logout-modal-box h3 { font-size: 1.1rem; color: #2d3748; margin-bottom: 0.5rem; }
        .logout-modal-box p  { font-size: 0.88rem; color: #718096; margin-bottom: 1.5rem; }
        .logout-modal-actions { display: flex; gap: 0.75rem; justify-content: center; }
        .logout-modal-cancel  { padding: 0.55rem 1.4rem; border: 1.5px solid #cbd5e0; border-radius: 8px; background: white; color: #4a5568; font-weight: 600; cursor: pointer; }
        .logout-modal-cancel:hover { background: #f7fafc; }
        .logout-modal-confirm { padding: 0.55rem 1.4rem; border: none; border-radius: 8px; background: #f56565; color: white; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 0.4rem; }
        .logout-modal-confirm:hover { opacity: 0.85; }

        .footer {
            background: rgba(255,255,255,0.97); padding: 1.2rem 2rem;
            display: flex; justify-content: center; align-items: center;
            box-shadow: 0 -2px 12px rgba(0,0,0,0.1); border-top: 2px solid #e2e8f0;
        }
        .footer-left { display: flex; align-items: center; gap: 0.5rem; color: #4a5568; font-size: 0.9rem; }
        .footer-left i { color: #10b981; font-size: 1.1rem; }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-24px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp   { from { opacity: 0; transform: translateY(28px);  } to { opacity: 1; transform: translateY(0); } }
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
                <h1>KlovrBank</h1>
                <span>Support Profile</span>
            </div>
        </div>
        <div class="header-actions">
            <a href="support_dashboard.php" class="back-btn-header"><i class='bx bx-arrow-back'></i> Back to Dashboard</a>
            <button onclick="confirmLogout()" class="logout-btn"><i class='bx bx-log-out'></i> Logout</button>
        </div>
    </div>

    <div class="page-wrapper">

        <!-- HERO -->
        <div class="profile-hero">
            <div class="avatar" onclick="document.getElementById('avatarFileInput').click()" title="Change profile picture">
                <?php if (!empty($agent['profile_picture'])): ?>
                    <img src="<?php echo htmlspecialchars($agent['profile_picture']); ?>" style="object-position:<?php echo htmlspecialchars($agent['profile_picture_offset'] ?? '50% 50%'); ?>">
                <?php else: ?>
                    <i class='bx bx-user'></i>
                <?php endif; ?>
                <div class="avatar-edit-hint"><i class='bx bx-camera'></i></div>
            </div>
            <input type="file" id="avatarFileInput" accept="image/*" style="display:none" onchange="openCropModal(this)">
            <div class="hero-info">
                <h2><?php echo htmlspecialchars($agent['email']); ?></h2>
                <p>Member since <?php echo $member_since; ?></p>
                <div class="hero-badges">
                    <span class="hero-badge"><i class='bx bx-headphone'></i> Support Agent</span>
                    <?php if ($agent['is_active']): ?>
                        <span class="hero-badge"><i class='bx bx-check-circle'></i> Active</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card total">
                <i class='bx bx-list-ul'></i>
                <div class="val"><?php echo $stats['total']; ?></div>
                <div class="label">Assigned</div>
            </div>
            <div class="stat-card open">
                <i class='bx bx-error-circle'></i>
                <div class="val"><?php echo $stats['open']; ?></div>
                <div class="label">Open</div>
            </div>
            <div class="stat-card ongoing">
                <i class='bx bx-time-five'></i>
                <div class="val"><?php echo $stats['ongoing']; ?></div>
                <div class="label">On-Going</div>
            </div>
            <div class="stat-card resolved">
                <i class='bx bx-check-circle'></i>
                <div class="val"><?php echo $stats['resolved']; ?></div>
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
                    <div class="val"><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($agent['email']); ?></div>
                </div>
                <div class="detail-field">
                    <label>Account Status</label>
                    <div class="val">
                        <?php if ($agent['is_active']): ?>
                            <span class="badge-active"><i class='bx bx-check'></i> Active</span>
                        <?php else: ?>
                            <span class="badge-inactive">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="detail-field">
                    <label>Role</label>
                    <div class="val"><i class='bx bx-headphone'></i> <span class="badge-support">Support Agent</span></div>
                </div>
                <div class="detail-field">
                    <label>Account ID</label>
                    <div class="val"><i class='bx bx-hash'></i> <?php echo $agent['id']; ?></div>
                </div>
                <div class="detail-field">
                    <label>Member Since</label>
                    <div class="val"><i class='bx bx-calendar'></i> <?php echo $member_since; ?></div>
                </div>
                <div class="detail-field">
                    <label>Resolution Rate</label>
                    <div class="val">
                        <i class='bx bx-bar-chart-alt-2'></i>
                        <?php echo $stats['total'] > 0 ? round($stats['resolved'] / $stats['total'] * 100) : 0; ?>%
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- CROP MODAL -->
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

    <div class="footer">
        <div class="footer-left">
            <i class='bx bxs-leaf'></i>
            <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
        </div>
    </div>

    <script>
        function confirmLogout() { document.getElementById('logoutModal').classList.add('open'); }
        document.getElementById('logoutModal').addEventListener('click', function(e) { if (e.target === this) this.classList.remove('open'); });

        let _cropFile = null, _cropOffsetX = 50, _cropOffsetY = 50, _dragStart = null;

        function openCropModal(input) {
            if (!input.files[0]) return;
            _cropFile = input.files[0];
            if (_cropFile.size > 5 * 1024 * 1024) { alert('Image must be under 5MB.'); return; }
            const img = document.getElementById('cropImg');
            img.src = URL.createObjectURL(_cropFile);
            _cropOffsetX = 50; _cropOffsetY = 50;
            img.style.objectPosition = '50% 50%';
            document.getElementById('cropOverlay').classList.add('open');
            input.value = '';
        }

        function closeCropModal() {
            document.getElementById('cropOverlay').classList.remove('open');
            _cropFile = null;
        }

        const vp = document.getElementById('cropViewport');
        vp.addEventListener('mousedown', e => { _dragStart = { x: e.clientX, y: e.clientY, ox: _cropOffsetX, oy: _cropOffsetY }; });
        window.addEventListener('mousemove', e => {
            if (!_dragStart) return;
            const r = vp.getBoundingClientRect();
            _cropOffsetX = Math.min(100, Math.max(0, _dragStart.ox - (e.clientX - _dragStart.x) / r.width * 100));
            _cropOffsetY = Math.min(100, Math.max(0, _dragStart.oy - (e.clientY - _dragStart.y) / r.height * 100));
            document.getElementById('cropImg').style.objectPosition = _cropOffsetX + '% ' + _cropOffsetY + '%';
        });
        window.addEventListener('mouseup', () => { _dragStart = null; });
        vp.addEventListener('touchstart', e => { const t = e.touches[0]; _dragStart = { x: t.clientX, y: t.clientY, ox: _cropOffsetX, oy: _cropOffsetY }; }, { passive: true });
        window.addEventListener('touchmove', e => {
            if (!_dragStart) return;
            const t = e.touches[0], r = vp.getBoundingClientRect();
            _cropOffsetX = Math.min(100, Math.max(0, _dragStart.ox - (t.clientX - _dragStart.x) / r.width * 100));
            _cropOffsetY = Math.min(100, Math.max(0, _dragStart.oy - (t.clientY - _dragStart.y) / r.height * 100));
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
                        const avatarDiv = document.querySelector('.avatar');
                        let img = avatarDiv.querySelector('img');
                        if (!img) {
                            avatarDiv.innerHTML = '<img src="" style="width:100%;height:100%;object-fit:cover;"><div class="avatar-edit-hint"><i class="bx bx-camera"></i></div>';
                            img = avatarDiv.querySelector('img');
                        }
                        img.src = res.path + '?t=' + Date.now();
                        img.style.objectPosition = offset;
                        closeCropModal();
                    } else { alert(res.error || 'Upload failed.'); }
                });
        }
    </script>
</body>
</html>
