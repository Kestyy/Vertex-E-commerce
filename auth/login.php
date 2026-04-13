<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ROOT_URL . '/index.php');
    exit;
}

$error     = '';
$activeTab = 'login';

if (isset($_SESSION['auth_error'])) {
    $error = $_SESSION['auth_error'];
    unset($_SESSION['auth_error']);
}
if (isset($_SESSION['auth_tab'])) {
    $activeTab = $_SESSION['auth_tab'];
    unset($_SESSION['auth_tab']);
}

$isLoggedIn = false;
$cartCount  = 0;

function asset(string $path): string {
    return ROOT_URL . '/' . ltrim($path, '/');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= ($activeTab === 'register') ? 'Sign Up' : 'Log In' ?> — Vertex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>"/>
    <style>
        .req { color: #ef4444; margin-left: 3px; }
        .auth-page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px 120px;
            background:
                radial-gradient(ellipse 120% 80% at 0% 0%, rgba(22, 112, 255, 0.8) 0%, transparent 60%),
                radial-gradient(ellipse 120% 80% at 100% 0%, rgba(137, 174, 255, 0.65) 0%, transparent 55%),
                radial-gradient(ellipse 150% 80% at 50% 130%, rgba(251, 249, 255, 0.6) 0%, transparent 60%),
                #bfdbfe;
        }
        .auth-card {
            background: #fff;
            border-radius: 2.4rem;
            padding: 4.2rem;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 8px 40px rgba(15,23,42,0.10), 0 2px 8px rgba(15,23,42,0.04);
            border: 1px solid rgba(226,232,240,0.8);
            animation: card-in 0.45s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        @keyframes card-in {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .auth-switcher {
            display: flex;
            background: #f1f5f9;
            border-radius: 1.4rem;
            padding: 5px;
            margin-bottom: 3.2rem;
            position: relative;
        }
        .auth-switcher-slider {
            position: absolute;
            top: 5px; bottom: 5px; left: 5px;
            width: calc(50% - 7.5px);
            background: #fff;
            border-radius: 0.9rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            z-index: 0;
            transition: transform 0.38s cubic-bezier(0.65, 0, 0.35, 1);
        }
        .auth-switcher-slider.to-register {
            transform: translateX(calc(100% + 5px));
        }
        .auth-switcher-btn {
            flex: 1;
            border: none;
            background: transparent;
            padding: 1.2rem;
            border-radius: 1rem;
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 600;
            color: #94a3b8;
            cursor: pointer;
            position: relative;
            z-index: 1;
            transition: color 0.3s ease;
        }
        .auth-switcher-btn.active { color: #0f172a; }
        .auth-panel { display: none; }
        .auth-panel.active { display: block; }
        .auth-panel.panel-enter {
            animation: panel-in 0.32s cubic-bezier(0.22, 1, 0.36, 1) both;
        }
        .auth-panel.panel-exit {
            animation: panel-out 0.18s ease forwards;
            pointer-events: none;
        }
        @keyframes panel-in {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes panel-out {
            from { opacity: 1; transform: translateY(0); }
            to   { opacity: 0; transform: translateY(-6px); }
        }
        .auth-header { margin-bottom: 2.4rem; }
        .auth-header h2 { font-size: 2.8rem; font-weight: 700; color: #0f172a; margin: 0 0 4px; letter-spacing: -0.02em; }
        .auth-header p  { font-size: 1.4rem; color: #94a3b8; margin: 0; }
        .auth-field { margin-bottom: 1.6rem; }
        .auth-field label { display: block; font-size: 1.3rem; font-weight: 500; color: #64748b; margin-bottom: 6px; }
        .auth-input-wrap {
            display: flex; align-items: center; gap: 10px;
            border: 1.5px solid #e2e8f0; border-radius: 1rem;
            padding: 13px 16px; background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .auth-input-wrap:focus-within { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.10); }
        .auth-input-wrap.error-field  { border-color: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.08); }
        .auth-input-wrap i { color: #94a3b8; flex-shrink: 0; font-size: 1.5rem; width: 18px; text-align: center; }
        .auth-input-wrap input {
            flex: 1; border: none; outline: none;
            font-family: 'Poppins', sans-serif; font-size: 1.4rem;
            color: #0f172a; background: transparent; min-width: 0;
        }
        .auth-input-wrap input::placeholder { color: #cbd5e1; }
        .eye-btn { background: none; border: none; padding: 0; cursor: pointer; color: #94a3b8; display: flex; align-items: center; flex-shrink: 0; transition: color 0.2s; }
        .eye-btn:hover { color: #475569; }
        .auth-remember-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.8rem;
            flex-wrap: wrap;
            gap: 10px;
        }
        .auth-remember {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.3rem;
            color: #64748b;
            cursor: pointer;
        }
        .auth-remember input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
        }
        .auth-forgot { font-size: 1.3rem; color: #3b82f6; font-weight: 500; text-decoration: none; }
        .auth-forgot:hover { text-decoration: underline; }
        .auth-terms {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.3rem;
            color: #64748b;
            margin-bottom: 2rem;
            line-height: 1.6;
            cursor: pointer;
        }
        .auth-terms input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #3b82f6;
            cursor: pointer;
            flex-shrink: 0;
        }
        .auth-terms span { display: inline; }
        .auth-terms a { color: #3b82f6; text-decoration: none; font-weight: 500; }
        .auth-terms a:hover { text-decoration: underline; }
        .auth-btn {
            width: 100%; padding: 1.5rem; background: #3b82f6; color: #fff;
            border: none; border-radius: 1rem; font-family: 'Poppins', sans-serif;
            font-size: 1.5rem; font-weight: 600; cursor: pointer;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s, opacity 0.2s;
            box-shadow: 0 4px 14px rgba(59,130,246,0.35); margin-bottom: 1.8rem;
            position: relative;
        }
        .auth-btn:hover  { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(59,130,246,0.42); }
        .auth-btn:active { transform: translateY(0); }
        .auth-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }
        .auth-btn .spinner {
            display: none;
            width: 1.3rem;
            height: 1.3rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        .auth-btn.loading .spinner { display: inline-block; }
        .auth-btn.loading .btn-text { opacity: 0.85; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .auth-divider { display: flex; align-items: center; gap: 12px; margin-bottom: 1.6rem; font-size: 1.2rem; color: #cbd5e1; }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #f1f5f9; }
        .auth-social-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 12px; width: 100%;
            border: 1.5px solid #e2e8f0; border-radius: 1rem; background: #fff;
            font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 500; color: #374151;
            cursor: pointer; transition: background 0.15s, border-color 0.15s, transform 0.15s;
            text-decoration: none;
        }
        .auth-social-btn:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .auth-social-btn img { width: 18px; height: 18px; object-fit: contain; }
        .auth-alert {
            display: flex; align-items: center; gap: 10px; padding: 12px 16px;
            border-radius: 10px; font-size: 1.3rem; font-weight: 500; margin-bottom: 2rem;
            background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c;
            animation: alert-in 0.3s ease;
        }
        @keyframes alert-in { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: translateY(0); } }
        .field-error { font-size: 1.1rem; color: #ef4444; margin-top: 4px; display: none; }
        @media (max-width: 520px) {
            .auth-card { padding: 3rem 2.4rem; border-radius: 2rem; }
            .auth-header h2 { font-size: 2.4rem; }
            .auth-remember-row { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<?php include ROOT_PATH . '/navbar.php'; ?>

<div class="auth-page-wrap">
    <div class="auth-card">

        <?php if ($error): ?>
            <div class="auth-alert" role="alert" aria-live="polite">
                <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Switcher pill -->
        <div class="auth-switcher" role="tablist" aria-label="Authentication tabs">
            <div class="auth-switcher-slider <?= ($activeTab === 'register') ? 'to-register' : '' ?>" id="switcherSlider" aria-hidden="true"></div>
            <button class="auth-switcher-btn <?= ($activeTab === 'login') ? 'active' : '' ?>" id="btnLogin" role="tab" aria-selected="<?= ($activeTab === 'login') ? 'true' : 'false' ?>" aria-controls="panelLogin">Log In</button>
            <button class="auth-switcher-btn <?= ($activeTab === 'register') ? 'active' : '' ?>" id="btnRegister" role="tab" aria-selected="<?= ($activeTab === 'register') ? 'true' : 'false' ?>" aria-controls="panelRegister">Sign Up</button>
        </div>

        <!-- LOGIN PANEL -->
        <div class="auth-panel <?= ($activeTab === 'login') ? 'active' : '' ?>" id="panelLogin" role="tabpanel" aria-labelledby="btnLogin">
            <div class="auth-header">
                <h2>Welcome back</h2>
                <p>Log in to your Vertex account</p>
            </div>
            <form method="POST" action="<?= asset('auth/auth_handler.php') ?>" id="formLogin" novalidate>
                <input type="hidden" name="action"   value="login"/>
                <input type="hidden" name="redirect" value="index.php"/>
                <div class="auth-field">
                    <label for="loginEmail">Email Address</label>
                    <div class="auth-input-wrap" id="wrapLoginEmail">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="loginEmail" name="email" placeholder="your@email.com" autocomplete="email" required aria-required="true" aria-describedby="errLoginEmail"/>
                    </div>
                    <p class="field-error" id="errLoginEmail" role="alert">Please enter a valid email address.</p>
                </div>
                <div class="auth-field">
                    <label for="loginPassword">Password</label>
                    <div class="auth-input-wrap" id="wrapLoginPassword">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" id="loginPassword" name="password" placeholder="••••••••" autocomplete="current-password" required aria-required="true" aria-describedby="errLoginPassword"/>
                        <button type="button" class="eye-btn" data-target="loginPassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" aria-hidden="true" style="font-size:1.4rem;"></i>
                        </button>
                    </div>
                    <p class="field-error" id="errLoginPassword" role="alert">Password is required.</p>
                </div>
                <div class="auth-remember-row">
                    <label class="auth-remember">
                        <input type="checkbox" name="remember" value="1"/>
                        <span>Keep me signed in</span>
                    </label>
                    <a href="<?= ROOT_URL ?>/auth/forgot-password.php" class="auth-forgot">Forgot password?</a>
                </div>
                <button type="submit" class="auth-btn" id="btnSubmitLogin">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text">Log In</span>
                </button>
            </form>
            <div class="auth-divider">or continue with</div>
            <a href="<?= asset('auth/oauth.php') ?>?provider=google" class="auth-social-btn" aria-label="Continue with Google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" aria-hidden="true"/> Continue with Google
            </a>
        </div>

        <!-- REGISTER PANEL -->
        <div class="auth-panel <?= ($activeTab === 'register') ? 'active' : '' ?>" id="panelRegister" role="tabpanel" aria-labelledby="btnRegister">
            <div class="auth-header">
                <h2>Create account</h2>
                <p>Join Vertex and start shopping</p>
            </div>
            <form method="POST" action="<?= asset('auth/auth_handler.php') ?>" id="formRegister" novalidate>
                <input type="hidden" name="action" value="register"/>

                <!-- First Name + Last Name in one row -->
                <div class="auth-field" style="display:flex; gap:12px;">
                    <div style="flex:1; min-width:0;">
                        <label for="regFirstName">First Name<span class="req" aria-hidden="true">*</span></label>
                        <div class="auth-input-wrap" id="wrapRegFirstName">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input type="text" id="regFirstName" name="first_name" placeholder="Juan"
                                   maxlength="50" required aria-required="true"
                                   aria-describedby="errRegFirstName" autocomplete="given-name"/>
                        </div>
                        <p class="field-error" id="errRegFirstName" role="alert">Letters and one hyphen only.</p>
                    </div>
                    <div style="flex:1; min-width:0;">
                        <label for="regLastName">Last Name<span class="req" aria-hidden="true">*</span></label>
                        <div class="auth-input-wrap" id="wrapRegLastName">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            <input type="text" id="regLastName" name="last_name" placeholder="Dela Cruz"
                                   maxlength="50" required aria-required="true"
                                   aria-describedby="errRegLastName" autocomplete="family-name"/>
                        </div>
                        <p class="field-error" id="errRegLastName" role="alert">Letters and one hyphen only.</p>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="regEmail">Email Address<span class="req" aria-hidden="true">*</span></label>
                    <div class="auth-input-wrap" id="wrapRegEmail">
                        <i class="fas fa-envelope" aria-hidden="true"></i>
                        <input type="email" id="regEmail" name="email" placeholder="your@email.com" autocomplete="email" required aria-required="true" aria-describedby="errRegEmail"/>
                    </div>
                    <p class="field-error" id="errRegEmail" role="alert">Please enter a valid email address.</p>
                </div>
                <div class="auth-field">
                    <label for="regPassword">Password<span class="req" aria-hidden="true">*</span></label>
                    <div class="auth-input-wrap" id="wrapRegPassword">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" id="regPassword" name="password" placeholder="Create a strong password" autocomplete="new-password" required aria-required="true" aria-describedby="errRegPassword"/>
                        <button type="button" class="eye-btn" data-target="regPassword" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" aria-hidden="true" style="font-size:1.4rem;"></i>
                        </button>
                    </div>
                    <p class="field-error" id="errRegPassword" role="alert">Password must be at least 8 characters.</p>
                </div>
                <label class="auth-terms">
                    <input type="checkbox" id="regTerms" name="terms" value="1" required aria-required="true" aria-describedby="errTerms"/>
                    <span>I agree to the <a href="<?= asset('terms.php') ?>" target="_blank" rel="noopener">Terms</a> and <a href="<?= asset('privacy.php') ?>" target="_blank" rel="noopener">Privacy Policy</a><span class="req" aria-hidden="true">*</span></span>
                </label>
                <p class="field-error" id="errTerms" role="alert" style="margin-bottom:16px;">You must agree to continue.</p>
                <button type="submit" class="auth-btn" id="btnSubmitRegister">
                    <span class="spinner" aria-hidden="true"></span>
                    <span class="btn-text">Create Account</span>
                </button>
            </form>
            <div class="auth-divider">or sign up with</div>
            <a href="<?= asset('auth/oauth.php') ?>?provider=google" class="auth-social-btn" aria-label="Sign up with Google">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="" aria-hidden="true"/> Sign up with Google
            </a>
        </div>

    </div>
</div>

<?php include ROOT_PATH . '/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('assets/js/main.js') ?>"></script>
<script>
(function () {
    const slider   = document.getElementById('switcherSlider');
    const btnLogin = document.getElementById('btnLogin');
    const btnReg   = document.getElementById('btnRegister');
    const pLogin   = document.getElementById('panelLogin');
    const pReg     = document.getElementById('panelRegister');
    let current    = '<?= $activeTab ?>';
    let locked     = false;

    function switchTo(tab) {
        if (tab === current || locked) return;
        locked = true;

        const outPanel = current === 'login' ? pLogin : pReg;
        const inPanel  = tab     === 'login' ? pLogin : pReg;
        current = tab;

        slider.classList.toggle('to-register', tab === 'register');
        btnLogin.classList.toggle('active', tab === 'login');
        btnReg.classList.toggle('active',   tab === 'register');
        btnLogin.setAttribute('aria-selected', tab === 'login');
        btnReg.setAttribute('aria-selected',   tab === 'register');

        outPanel.classList.add('panel-exit');
        setTimeout(() => {
            outPanel.classList.remove('active', 'panel-exit');
            inPanel.classList.add('active', 'panel-enter');
            inPanel.addEventListener('animationend', () => {
                inPanel.classList.remove('panel-enter');
            }, { once: true });
            history.replaceState(null, '', tab === 'login' ? 'login.php' : 'signup.php');
            setTimeout(() => { locked = false; }, 340);
        }, 180);
    }

    btnLogin.addEventListener('click', () => switchTo('login'));
    btnReg.addEventListener('click',   () => switchTo('register'));

    // ── Eye toggles ───────────────────────────────────────────────────────────
    document.querySelectorAll('.eye-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp  = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    // ── Name helpers ──────────────────────────────────────────────────────────
    const MAX_NAME_LENGTH = 50;

    function capitalizeName(val) {
        return val
            .split(' ')
            .map(word =>
                word.split('-').map(part =>
                    part.length ? part[0].toUpperCase() + part.slice(1).toLowerCase() : ''
                ).join('-')
            )
            .join(' ');
    }

    function enforceNameInput(e) {
        const input  = e.target;
        let val      = input.value;
        const cursor = input.selectionStart;

        val = val.replace(/[^a-zA-ZÀ-ÿ\- ]/g, '');

        val = val.split(' ').map(word => {
            const idx = word.indexOf('-');
            if (idx === -1) return word;
            return word.slice(0, idx + 1) + word.slice(idx + 1).replace(/-/g, '');
        }).join(' ');

        val = capitalizeName(val);

        if (input.value !== val) {
            input.value = val;
            try { input.setSelectionRange(cursor, cursor); } catch (_) {}
        }
    }

    function isValidName(val) {
        const trimmed = val.trim();
        if (!trimmed) return false;
        if (trimmed.length > MAX_NAME_LENGTH) return false;
        return trimmed.split(' ').every(word =>
            /^[a-zA-ZÀ-ÿ]+(-[a-zA-ZÀ-ÿ]+)?$/u.test(word)
        );
    }

    ['regFirstName', 'regLastName'].forEach(id => {
        const el = document.getElementById(id);
        el.addEventListener('input', enforceNameInput);
        el.addEventListener('blur', e => {
            e.target.value = capitalizeName(e.target.value.trim());
        });
    });

    // ── Login form validation ─────────────────────────────────────────────────
    document.getElementById('formLogin').addEventListener('submit', function (e) {
        let ok = true;

        ['wrapLoginEmail','wrapLoginPassword'].forEach(id =>
            document.getElementById(id).classList.remove('error-field'));
        ['errLoginEmail','errLoginPassword'].forEach(id =>
            document.getElementById(id).style.display = 'none');

        const email = document.getElementById('loginEmail').value.trim();
        const pass  = document.getElementById('loginPassword').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!email || !emailRegex.test(email)) {
            document.getElementById('wrapLoginEmail').classList.add('error-field');
            document.getElementById('errLoginEmail').style.display = 'block';
            ok = false;
        }
        if (!pass) {
            document.getElementById('wrapLoginPassword').classList.add('error-field');
            document.getElementById('errLoginPassword').style.display = 'block';
            if (ok) document.getElementById('loginPassword').focus();
            ok = false;
        }

        if (!ok) { e.preventDefault(); return; }

        const btn = document.getElementById('btnSubmitLogin');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    // ── Register form validation ──────────────────────────────────────────────
    document.getElementById('formRegister').addEventListener('submit', function (e) {
        let ok      = true;
        let focusEl = null;

        const firstName = document.getElementById('regFirstName').value.trim();
        const lastName  = document.getElementById('regLastName').value.trim();
        const email     = document.getElementById('regEmail').value.trim();
        const pass      = document.getElementById('regPassword').value;
        const terms     = document.getElementById('regTerms').checked;

        ['wrapRegFirstName','wrapRegLastName','wrapRegEmail','wrapRegPassword'].forEach(id =>
            document.getElementById(id).classList.remove('error-field'));
        ['errRegFirstName','errRegLastName','errRegEmail','errRegPassword','errTerms'].forEach(id =>
            document.getElementById(id).style.display = 'none');

        // First name
        if (!firstName) {
            document.getElementById('wrapRegFirstName').classList.add('error-field');
            document.getElementById('errRegFirstName').textContent = 'First name is required.';
            document.getElementById('errRegFirstName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regFirstName');
            ok = false;
        } else if (firstName.length > MAX_NAME_LENGTH) {
            document.getElementById('wrapRegFirstName').classList.add('error-field');
            document.getElementById('errRegFirstName').textContent = 'First name is too long (max 50 characters).';
            document.getElementById('errRegFirstName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regFirstName');
            ok = false;
        } else if (!isValidName(firstName)) {
            document.getElementById('wrapRegFirstName').classList.add('error-field');
            document.getElementById('errRegFirstName').textContent = 'Letters, spaces, and one hyphen per word only.';
            document.getElementById('errRegFirstName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regFirstName');
            ok = false;
        }

        // Last name
        if (!lastName) {
            document.getElementById('wrapRegLastName').classList.add('error-field');
            document.getElementById('errRegLastName').textContent = 'Last name is required.';
            document.getElementById('errRegLastName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regLastName');
            ok = false;
        } else if (lastName.length > MAX_NAME_LENGTH) {
            document.getElementById('wrapRegLastName').classList.add('error-field');
            document.getElementById('errRegLastName').textContent = 'Last name is too long (max 50 characters).';
            document.getElementById('errRegLastName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regLastName');
            ok = false;
        } else if (!isValidName(lastName)) {
            document.getElementById('wrapRegLastName').classList.add('error-field');
            document.getElementById('errRegLastName').textContent = 'Letters, spaces, and one hyphen per word only.';
            document.getElementById('errRegLastName').style.display = 'block';
            focusEl = focusEl || document.getElementById('regLastName');
            ok = false;
        }

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email) {
            document.getElementById('wrapRegEmail').classList.add('error-field');
            document.getElementById('errRegEmail').textContent = 'Email is required.';
            document.getElementById('errRegEmail').style.display = 'block';
            focusEl = focusEl || document.getElementById('regEmail');
            ok = false;
        } else if (!emailRegex.test(email)) {
            document.getElementById('wrapRegEmail').classList.add('error-field');
            document.getElementById('errRegEmail').textContent = 'Please enter a valid email address.';
            document.getElementById('errRegEmail').style.display = 'block';
            focusEl = focusEl || document.getElementById('regEmail');
            ok = false;
        }

        // Password
        if (!pass) {
            document.getElementById('wrapRegPassword').classList.add('error-field');
            document.getElementById('errRegPassword').textContent = 'Password is required.';
            document.getElementById('errRegPassword').style.display = 'block';
            focusEl = focusEl || document.getElementById('regPassword');
            ok = false;
        } else if (pass.length < 8) {
            document.getElementById('wrapRegPassword').classList.add('error-field');
            document.getElementById('errRegPassword').textContent = 'Password must be at least 8 characters.';
            document.getElementById('errRegPassword').style.display = 'block';
            focusEl = focusEl || document.getElementById('regPassword');
            ok = false;
        }

        // Terms
        if (!terms) {
            document.getElementById('errTerms').style.display = 'block';
            focusEl = focusEl || document.getElementById('regTerms');
            ok = false;
        }

        if (!ok) {
            e.preventDefault();
            if (focusEl) focusEl.focus();
            return;
        }

        const btn = document.getElementById('btnSubmitRegister');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    // ── Clear errors ──────────────────────────────────────────────────────────
    function clearError(wrapId, errId) {
        document.getElementById(wrapId)?.classList.remove('error-field');
        const err = document.getElementById(errId);
        if (err) err.style.display = 'none';
    }

    document.getElementById('loginEmail').addEventListener('input',    () => clearError('wrapLoginEmail',    'errLoginEmail'));
    document.getElementById('loginPassword').addEventListener('input',  () => clearError('wrapLoginPassword', 'errLoginPassword'));
    document.getElementById('regFirstName').addEventListener('input',   () => clearError('wrapRegFirstName',  'errRegFirstName'));
    document.getElementById('regLastName').addEventListener('input',    () => clearError('wrapRegLastName',   'errRegLastName'));
    document.getElementById('regEmail').addEventListener('input',       () => clearError('wrapRegEmail',      'errRegEmail'));
    document.getElementById('regPassword').addEventListener('input',    () => clearError('wrapRegPassword',   'errRegPassword'));
    document.getElementById('regTerms').addEventListener('change', function () {
        if (this.checked) document.getElementById('errTerms').style.display = 'none';
    });
})();
</script>
</body>
</html>