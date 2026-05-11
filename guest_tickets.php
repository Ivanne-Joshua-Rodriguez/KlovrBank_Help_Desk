<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Ticket | KlovrBank</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body { min-height:100vh; display:flex; flex-direction:column; background:#F1F3E0; }

        .bg-video { position:fixed; inset:0; width:100%; height:100%; object-fit:cover; z-index:-1; }

        .navbar-wrapper { overflow:hidden; position:relative; z-index:1; background:#F1F3E0; }
        .navbar { width:100%; height:100px; display:flex; justify-content:space-between; align-items:center; padding:0 5%;background: linear-gradient(to bottom, rgba(0,0,0,0.15) 0%, #F1F3E0 40%) !important; }
        .nav-brand { display:flex; align-items:center; margin-left:0; }
        .nav-brand video { height:60px; width:auto; margin-right:5px; mix-blend-mode:multiply; }
        @font-face { font-family:'KugileDemo'; src:url('fonts/Kugile_Demo.ttf') format('truetype'); }
        .nav-brand h1 { color:#2d5a27; font-size:42px; font-weight:bold; font-family:'KugileDemo',Arial,sans-serif; line-height:1; vertical-align:middle; margin-top:15px; }
        .nav-actions { display:flex; list-style:none; margin:0; padding:0; justify-content:flex-end; }
        .nav-btn { text-decoration:none; color:white; background-color:#068700; padding:8px 20px; border-radius:20px; margin-left:10px; font-size:14px; transition:0.4s; display:inline-block; }
        .nav-btn:hover { background-color:#2d5a27; }

        .page-center { flex:1; display:flex; align-items:flex-start; justify-content:center; padding:2rem; gap:1.5rem; flex-wrap:wrap; }

        .checker-card { background:rgba(255,255,255,0.97); border-radius:16px; padding:2.5rem; width:100%; max-width:520px; box-shadow:0 8px 32px rgba(0,0,0,0.15); }
        .checker-card h2 { color:#2d3748; font-size:1.4rem; margin-bottom:0.4rem; display:flex; align-items:center; gap:0.5rem; }
        .checker-card h2 i { color:#10b981; }
        .checker-card p.sub { color:#718096; font-size:0.88rem; margin-bottom:1.75rem; }

        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; font-size:0.85rem; font-weight:600; color:#4a5568; margin-bottom:0.35rem; }
        .form-group input { width:100%; padding:0.7rem 0.9rem; border:2px solid #e2e8f0; border-radius:8px; font-size:0.95rem; background:#fafafa; transition:border-color 0.2s; }
        .form-group input:focus { outline:none; border-color:#10b981; background:white; }

        .btn-check { width:100%; background:linear-gradient(135deg,#10b981,#059669); color:white; border:none; padding:0.8rem; border-radius:8px; font-size:1rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:0.5rem; transition:opacity 0.2s; margin-top:0.5rem; }
        .btn-check:hover { opacity:0.88; }

        .error-msg { background:#fff5f5; border:1.5px solid #fed7d7; border-radius:8px; padding:0.85rem 1rem; color:#c53030; font-size:0.88rem; display:none; align-items:center; gap:0.5rem; margin-top:1rem; }
        .error-msg.show { display:flex; }

        /* Result card */
        .result-card { margin-top:1.5rem; border:1.5px solid #e2e8f0; border-radius:12px; overflow:hidden; display:none; }
        .result-card.show { display:block; }
        .result-header { background:linear-gradient(135deg,#10b981,#059669); color:white; padding:0.9rem 1.2rem; display:flex; justify-content:space-between; align-items:center; }
        .result-header span { font-weight:700; font-size:1rem; }
        .result-body { padding:1.2rem; display:grid; grid-template-columns:1fr 1fr; gap:0.75rem 1.5rem; }
        .result-field label { color:#a0aec0; font-size:0.72rem; font-weight:700; letter-spacing:0.5px; display:block; margin-bottom:0.2rem; text-transform:uppercase; }
        .result-field p { color:#2d3748; font-size:0.92rem; font-weight:500; }
        .result-field.full { grid-column:1/-1; }
        .status-badge { padding:0.25rem 0.65rem; border-radius:20px; font-size:0.78rem; font-weight:700; }
        .status-Open { background:#fef3c7; color:#92400e; }
        .status-Under-Review { background:#e0e7ff; color:#3730a3; }
        .status-On-Going { background:#dbeafe; color:#1e40af; }
        .status-Resolved { background:#d1fae5; color:#065f46; }

        .ticket-image { margin-top:0.75rem; }
        .ticket-image img { max-width:100%; max-height:260px; border-radius:8px; border:1.5px solid #e2e8f0; cursor:pointer; transition:opacity 0.2s; }
        .ticket-image img:hover { opacity:0.88; }

        /* Conversation */
        .convo-section { border-top:1.5px solid #e2e8f0; padding:1rem 1.2rem; }
        .convo-section h4 { font-size:0.72rem; font-weight:700; color:#a0aec0; letter-spacing:0.5px; text-transform:uppercase; margin-bottom:0.65rem; }
        .reply-thread { display:flex; flex-direction:column; gap:0.55rem; max-height:200px; overflow-y:auto; }
        .reply-bubble { max-width:78%; padding:0.55rem 0.8rem; border-radius:12px; font-size:0.86rem; line-height:1.6; }
        .reply-bubble.user { align-self:flex-end; background:linear-gradient(135deg,#10b981,#059669); color:white; border-bottom-right-radius:4px; }
        .reply-bubble.support { align-self:flex-start; background:#f0f4f8; color:#2d3748; border-bottom-left-radius:4px; }
        .bubble-meta { font-size:0.7rem; margin-bottom:0.2rem; opacity:0.75; }
        .reply-empty { font-size:0.83rem; color:#a0aec0; text-align:center; padding:0.4rem 0; }

        /* Image lightbox */
        .lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; }
        .lightbox.open { display:flex; }
        .lightbox img { max-width:90vw; max-height:90vh; border-radius:8px; }
        .lightbox-close { position:absolute; top:1rem; right:1.5rem; color:white; font-size:2rem; cursor:pointer; background:none; border:none; }

        .footer { background:rgba(255,255,255,0.97); padding:1rem 2rem; display:flex; justify-content:center; align-items:center; box-shadow:0 -2px 12px rgba(0,0,0,0.1); border-top:2px solid #e2e8f0; color:#4a5568; font-size:0.88rem; }
        .footer i { color:#10b981; margin-right:0.4rem; }

        @media (max-width: 768px) {
            .navbar { height: auto; padding: 0.75rem 1rem; flex-wrap: wrap; gap: 0.5rem; }
            .nav-brand h1 { font-size: 28px; }
            .nav-brand video { height: 42px; }
            .page-center { padding: 1rem; gap: 1rem; }
            .checker-card, .faq-card { padding: 1.5rem 1.25rem; max-width: 100%; }
            .result-body { grid-template-columns: 1fr; }
        }

        @media (max-width: 480px) {
            .nav-brand h1 { font-size: 22px; }
            .nav-brand video { height: 34px; }
            .nav-btn { padding: 6px 12px; font-size: 12px; }
            .checker-card h2, .faq-card h2 { font-size: 1.15rem; }
        }

        /* FAQ */
        .faq-card { background:rgba(255,255,255,0.97); border-radius:16px; padding:2.5rem; width:100%; max-width:480px; box-shadow:0 8px 32px rgba(0,0,0,0.15); align-self:flex-start; }
        .faq-card h2 { color:#2d3748; font-size:1.4rem; margin-bottom:0.4rem; display:flex; align-items:center; gap:0.5rem; }
        .faq-card h2 i { color:#10b981; }
        .faq-card p.sub { color:#718096; font-size:0.88rem; margin-bottom:1.5rem; }
        .faq-item { border:1.5px solid #e2e8f0; border-radius:10px; margin-bottom:0.65rem; overflow:hidden; transition:border-color 0.2s; }
        .faq-item.open { border-color:#10b981; }
        .faq-question { width:100%; background:none; border:none; padding:0.85rem 1rem; display:flex; justify-content:space-between; align-items:center; cursor:pointer; font-size:0.88rem; font-weight:600; color:#2d3748; text-align:left; gap:0.75rem; }
        .faq-question:hover { background:#f0fdf4; }
        .faq-question i { color:#10b981; font-size:1.1rem; flex-shrink:0; transition:transform 0.3s; }
        .faq-item.open .faq-question i { transform:rotate(45deg); }
        .faq-answer { max-height:0; overflow:hidden; transition:max-height 0.35s ease; }
        .faq-answer-inner { padding:0.75rem 1rem 1rem; color:#4a5568; font-size:0.85rem; line-height:1.7; border-top:1px solid #e2e8f0; }
    </style>
</head>
<body>
    <video class="bg-video" autoplay loop muted playsinline>
        <source src="Videos/KLVER1VID.mp4" type="video/mp4">
    </video>
    <script>document.querySelector('.bg-video').playbackRate = 0.4;</script>

    <div class="navbar-wrapper">
        <nav class="navbar" style="background:#F1F3E0;">
            <div class="nav-brand">
                <video src="Videos/MOVING LOGO.mp4" autoplay loop muted playsinline></video>
                <h1>KlovrBank</h1>
            </div>
            <div class="nav-actions">
                <a href="login.php" class="nav-btn"><i class='bx bx-log-in'></i> Log In</a>
                <a href="signup.php" class="nav-btn" style="background:#2d5a27;">Sign Up</a>
            </div>
        </nav>
    </div>

    <div class="page-center">
        <div class="checker-card">
            <h2><i class='bx bx-search-alt'></i> Check Your Ticket</h2>
            <p class="sub">Enter your email and ticket ID to view your ticket status and conversation.</p>

            <div class="form-group">
                <label for="guestEmail">Your Email Address</label>
                <input type="email" id="guestEmail" placeholder="example@domain.com" required>
            </div>
            <div class="form-group">
                <label for="guestTicketId">Ticket ID</label>
                <input type="text" id="guestTicketId" placeholder="e.g. TK261A2B">
            </div>
            <button class="btn-check" onclick="checkTicket()">
                <i class='bx bx-search'></i> Check Ticket
            </button>

            <div class="error-msg" id="errorMsg">
                <i class='bx bx-error-circle'></i>
                <span id="errorText">No ticket found. Please check your email and ticket ID.</span>
            </div>

            <div class="result-card" id="resultCard">
                <div class="result-header">
                    <span id="resId"></span>
                    <span id="resStatus"></span>
                </div>
                <div class="result-body">
                    <div class="result-field">
                        <label>Subject</label>
                        <p id="resSubject"></p>
                    </div>
                    <div class="result-field">
                        <label>Category</label>
                        <p id="resCategory"></p>
                    </div>
                    <div class="result-field">
                        <label>Date Submitted</label>
                        <p id="resDate"></p>
                    </div>
                    <div class="result-field" id="resSupportWrap" style="display:none;">
                        <label>Assigned Support</label>
                        <p id="resSupport"></p>
                    </div>
                    <div class="result-field full" id="resImageWrap" style="display:none;">
                        <label>Attached Image</label>
                        <div class="ticket-image">
                            <img id="resImage" src="" alt="Ticket attachment" onclick="openLightbox(this.src)">
                        </div>
                    </div>
                </div>
                <div class="convo-section">
                    <h4><i class='bx bx-conversation'></i> Conversation</h4>
                    <div class="reply-thread" id="replyThread"></div>
                </div>
            </div>
        </div>

        <!-- FAQ Card -->
        <div class="faq-card">
            <h2><i class='bx bx-help-circle'></i> FAQs</h2>
            <p class="sub">Frequently Asked Questions</p>

            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">How do I change my password safely? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">You can change your password through the <strong>user profile</strong>. Always choose a strong password that combines letters, numbers, and symbols for better security. Avoid reusing old passwords or using the same password across multiple services.</div></div>
            </div>
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">Why is my card transaction being declined? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">Transactions may be declined due to insufficient funds, an expired card, or a security hold. Check your balance and card expiry. If the issue persists, submit a <strong>Billing</strong> ticket and our team will investigate within 2 hours for high-priority cases.</div></div>
            </div>
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">How do I change my card PIN? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">You can change your PIN at an ATM or through our mobile app. For security, you may be required to verify your identity before setting a new PIN. Always choose a PIN that is secure and not easy to guess.</div></div>
            </div>
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">What should I do if my statement has an error? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">If you find an error, you should report it to us <strong>immediately</strong> for investigation. Provide details such as transaction date, amount, and description. The bank will review and correct valid discrepancies.</div></div>
            </div>
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">Why can't I make international transactions? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">International transactions may be <strong>disabled by default</strong> or restricted due to account type or security settings. You may need to activate overseas usage in your app or contact your bank. Some banks also require additional verification.</div></div>
            </div>
            <div class="faq-item">
                <button class="faq-question" onclick="toggleFaq(this)">What are bank fees and why do I have to pay them? <i class='bx bx-plus'></i></button>
                <div class="faq-answer"><div class="faq-answer-inner">Bank fees are charged for certain services like account maintenance, transfers, or special transactions. They help cover operational costs and support banking services and systems. Not all accounts have the same fees depending on their type and features.</div></div>
            </div>
        </div>

    </div>

    <div class="footer">
        <i class='bx bxs-leaf'></i>
        <span>© 2026 KlovrBank — Digital Banking Help Desk</span>
    </div>

    <script>
        document.getElementById('guestTicketId').addEventListener('keydown', e => { if (e.key === 'Enter') checkTicket(); });
        document.getElementById('guestEmail').addEventListener('keydown', e => { if (e.key === 'Enter') checkTicket(); });

        function checkTicket() {
            const email    = document.getElementById('guestEmail').value.trim();
            const ticketId = document.getElementById('guestTicketId').value.trim().replace('#','');
            const errEl    = document.getElementById('errorMsg');
            const resCard  = document.getElementById('resultCard');

            if (!email || !ticketId) {
                document.getElementById('errorText').textContent = 'Please enter both your email and ticket ID.';
                errEl.classList.add('show'); resCard.classList.remove('show'); return;
            }

            fetch(`check_status.php?ticket_id=${encodeURIComponent(ticketId)}&email=${encodeURIComponent(email)}`)
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('errorText').textContent = 'No ticket found matching that email and ticket ID.';
                        errEl.classList.add('show'); resCard.classList.remove('show');
                    } else {
                        errEl.classList.remove('show');
                        document.getElementById('resId').textContent      = '#' + data.display_id;
                        const statusKey = data.status.replace(/ /g,'-');
                        document.getElementById('resStatus').innerHTML    = `<span class="status-badge status-${statusKey}">${data.status}</span>`;
                        document.getElementById('resSubject').textContent  = data.subject;
                        document.getElementById('resCategory').textContent = data.category.charAt(0).toUpperCase() + data.category.slice(1);
                        document.getElementById('resDate').textContent     = data.created_at ? data.created_at.substring(0,10) : '';

                        const supportWrap = document.getElementById('resSupportWrap');
                        if (data.assigned_support_email) {
                            document.getElementById('resSupport').textContent = data.assigned_support_email;
                            supportWrap.style.display = '';
                        } else { supportWrap.style.display = 'none'; }

                        const imgWrap = document.getElementById('resImageWrap');
                        if (data.image_path) {
                            document.getElementById('resImage').src = data.image_path;
                            imgWrap.style.display = '';
                        } else { imgWrap.style.display = 'none'; }

                        resCard.classList.add('show');
                        loadReplies(data.display_id, email);
                    }
                });
        }

        function loadReplies(displayId, email) {
            fetch(`get_replies.php?display_id=${encodeURIComponent(displayId)}&email=${encodeURIComponent(email)}`)
                .then(r => r.json())
                .then(replies => {
                    const thread = document.getElementById('replyThread');
                    if (!Array.isArray(replies) || !replies.length) {
                        thread.innerHTML = '<div class="reply-empty">No replies yet.</div>'; return;
                    }
                    thread.innerHTML = replies.map(r =>
                        `<div class="reply-bubble ${r.sender_role === 'user' ? 'user' : 'support'}">
                            <div class="bubble-meta">${r.sender_email} &bull; ${r.created_at}</div>
                            ${r.message.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')}
                        </div>`
                    ).join('');
                    thread.scrollTop = thread.scrollHeight;
                });
        }

        function openLightbox(src) { document.getElementById('lightboxImg').src = src; document.getElementById('lightbox').classList.add('open'); }
        function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }

        function toggleFaq(btn) {
            const item = btn.closest('.faq-item');
            const isOpen = item.classList.contains('open');
            document.querySelectorAll('.faq-item').forEach(i => { i.classList.remove('open'); i.querySelector('.faq-answer').style.maxHeight = ''; });
            if (!isOpen) { item.classList.add('open'); item.querySelector('.faq-answer').style.maxHeight = item.querySelector('.faq-answer').scrollHeight + 'px'; }
        }
    </script>
</body>
</html>
