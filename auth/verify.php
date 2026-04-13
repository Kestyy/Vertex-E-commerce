<?php
session_start();

define('ROOT_PATH', dirname(__DIR__));
define('ROOT_URL', rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/'));

require_once ROOT_PATH . '/assets/php/db.php';

// Check if user is in verification flow
if (!isset($_SESSION['verify_user_id']) || !isset($_SESSION['verify_email'])) {
    header('Location: ' . ROOT_URL . '/auth/login.php');
    exit;
}

$user_email = $_SESSION['verify_email'];
$user_name  = $_SESSION['verify_name'] ?? 'there'; // This is first_name from auth_handler.php

function asset(string $path): string {
    return ROOT_URL . '/' . ltrim($path, '/');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Verify Your Email — Vertex</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="<?= asset('assets/css/style.css') ?>"/>
    <style>
        .verify-page-wrap {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background:
                radial-gradient(ellipse 80% 60% at 70% -10%, rgba(59,130,246,0.10) 0%, transparent 65%),
                radial-gradient(ellipse 60% 50% at 10% 100%, rgba(26,107,255,0.07) 0%, transparent 60%),
                #f0f4f8;
        }
        .verify-card {
            background: #fff;
            border-radius: 2rem;
            padding: 3rem;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 40px rgba(15,23,42,0.10);
            border: 1px solid rgba(226,232,240,0.8);
            animation: fade-in 0.5s ease;
        }
        @keyframes fade-in {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .verify-header { text-align: center; margin-bottom: 2rem; }
        .verify-header h2 { font-size: 2rem; font-weight: 700; color: #0f172a; margin-bottom: 0.5rem; }
        .verify-icon {
            width: 80px; height: 80px; margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .verify-icon i { font-size: 2.5rem; color: #fff; }
        .verify-icon .notification-badge {
            position: absolute; top: 8px; right: 8px;
            width: 24px; height: 24px; background: #8b5cf6; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700; color: #fff; border: 2px solid #fff;
        }
        .verify-desc { text-align: center; color: #64748b; font-size: 1.05rem; margin-bottom: 2rem; line-height: 1.6; }
        .verify-desc strong { color: #0f172a; }
        .otp-container { display: flex; gap: 12px; justify-content: center; margin-bottom: 2.5rem; }
        .otp-input {
            width: 64px; height: 72px; border: 2px solid #e2e8f0; border-radius: 14px;
            text-align: center; font-size: 2rem; font-weight: 600; font-family: 'Poppins', sans-serif;
            color: #0f172a; transition: all 0.2s; background: #fff;
        }
        .otp-input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); transform: translateY(-2px); }
        .otp-input.filled { border-color: #10b981; background: #f0fdf4; }
        .otp-input.error { border-color: #ef4444; animation: shake 0.4s ease; }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-8px); } 75% { transform: translateX(8px); } }
        .resend-section { text-align: center; margin-bottom: 1.5rem; }
        .resend-text { color: #64748b; font-size: 0.95rem; margin-bottom: 0.5rem; }
        .resend-btn { background: none; border: none; color: #3b82f6; font-size: 0.95rem; font-weight: 600; cursor: pointer; padding: 0; transition: color 0.2s; }
        .resend-btn:hover:not(:disabled) { color: #2563eb; text-decoration: underline; }
        .resend-btn:disabled { color: #94a3b8; cursor: not-allowed; }
        .countdown { color: #64748b; font-size: 0.95rem; }
        .logout-link { display: inline-flex; align-items: center; gap: 6px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; transition: color 0.2s; }
        .logout-link:hover { color: #ef4444; }
        .divider { height: 1px; background: #e2e8f0; margin: 1.5rem 0; }
        .support-section { text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e2e8f0; }
        .support-text { color: #64748b; font-size: 0.9rem; margin-bottom: 0.25rem; }
        .support-email { color: #10b981; text-decoration: none; font-size: 0.9rem; font-weight: 500; }
        .support-email:hover { text-decoration: underline; }
        .verify-alert { padding: 12px 16px; border-radius: 10px; margin-bottom: 0rem; font-size: 0.9rem; display: none; animation: slide-down 0.3s ease; }
        @keyframes slide-down { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
        .verify-alert.success { display: block; background: #f0fdf4; border: 1.5px solid #86efac; color: #166534; margin-bottom: 1.5rem; }
        .verify-alert.error { display: block; background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; margin-bottom: 1.5rem; }
        .verify-btn {
            width: 100%; padding: 1rem; background: #3b82f6; color: #fff; border: none; border-radius: 1rem;
            font-family: 'Poppins', sans-serif; font-size: 1rem; font-weight: 600; cursor: pointer;
            transition: all 0.2s; box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
        }
        .verify-btn:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59, 130, 246, 0.42); }
        .verify-btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        @media (max-width: 520px) {
            .verify-card { padding: 2rem 1.5rem; }
            .otp-input { width: 56px; height: 64px; font-size: 1.5rem; }
            .otp-container { gap: 8px; margin-bottom: 2rem; }
        }
    </style>
</head>
<body>
<div class="verify-page-wrap">
    <div class="verify-card">
        <div id="alertSuccess" class="verify-alert success" role="alert"></div>
        <div id="alertError" class="verify-alert error" role="alert"></div>
        <div class="verify-icon"><i class="fas fa-envelope-open-text"></i><span class="notification-badge">!</span></div>
        <div class="verify-header"><h2>Verify your email</h2></div>
        <p class="verify-desc">Enter the 6-digit OTP sent to<br><strong><?= htmlspecialchars($user_email) ?></strong></p>
        <form id="verifyForm" method="POST" action="<?= asset('auth/verify_handler.php') ?>">
            <div class="otp-container">
                <input type="text" class="otp-input" maxlength="1" data-index="0" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-input" maxlength="1" data-index="1" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-input" maxlength="1" data-index="2" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-input" maxlength="1" data-index="3" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-input" maxlength="1" data-index="4" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
                <input type="text" class="otp-input" maxlength="1" data-index="5" autocomplete="off" inputmode="numeric" pattern="[0-9]*">
            </div>
            <input type="hidden" name="otp_code" id="otpCode">
            <button type="submit" class="verify-btn" id="verifyBtn" disabled><i class="fas fa-check-circle me-2"></i>Verify Email</button>
        </form>
        <div class="resend-section">
            <p class="resend-text">Didn't get the code? <button type="button" class="resend-btn" id="resendBtn" disabled>Resend</button><span class="countdown" id="countdown">(in 30 sec)</span></p>
        </div>
        <div style="text-align: center;"><a href="<?= asset('auth/logout.php') ?>" class="logout-link"><i class="fas fa-sign-out-alt"></i> Log out</a></div>
        <div class="divider"></div>
        <div class="support-section"><p class="support-text">Need assistance?</p><a href="mailto:support@vertex.com" class="support-email">Contact us at support@vertex.com</a></div>
    </div>
</div>
<script>
(function(){const inputs=document.querySelectorAll('.otp-input'),form=document.getElementById('verifyForm'),btn=document.getElementById('verifyBtn'),otpInput=document.getElementById('otpCode'),resendBtn=document.getElementById('resendBtn'),countdownEl=document.getElementById('countdown'),successAlert=document.getElementById('alertSuccess'),errorAlert=document.getElementById('alertError');let timer=30,interval;inputs[0].focus();inputs.forEach((inp,i)=>{inp.addEventListener('input',e=>{if(!/^\d*$/.test(e.target.value)){e.target.value='';return}if(e.target.value&&i<5)inputs[i+1].focus();checkComplete()});inp.addEventListener('keydown',e=>{if(e.key==='Backspace'&&!e.target.value&&i>0){inputs[i-1].focus();inputs[i-1].value=''}});inp.addEventListener('paste',e=>{e.preventDefault();const paste=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);paste.split('').forEach((c,j)=>{if(inputs[j]){inputs[j].value=c;inputs[j].classList.add('filled')}});if(paste.length>0&&inputs[paste.length])inputs[paste.length].focus();checkComplete()})});function checkComplete(){let code='';inputs.forEach(i=>code+=i.value);if(code.length===6){otpInput.value=code;btn.disabled=false}else btn.disabled=true}form.addEventListener('submit',async e=>{e.preventDefault();btn.disabled=true;btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Verifying...';try{const res=await fetch(form.action,{method:'POST',body:new FormData(form)}),data=await res.json();if(data.success){successAlert.textContent='Verified! Redirecting...';setTimeout(()=>window.location.href=data.redirect||'<?= asset('index.php') ?>',1500)}else{errorAlert.textContent=data.message||'Invalid code.';inputs.forEach(i=>{i.classList.add('error');setTimeout(()=>i.classList.remove('error'),400)});btn.disabled=false;btn.innerHTML='Verify Email'}}catch{errorAlert.textContent='Network error. Try again.';btn.disabled=false;btn.innerHTML='Verify Email'}});resendBtn.addEventListener('click',async()=>{resendBtn.disabled=true;timer=30;countdownEl.textContent=`(in ${timer} sec)`;try{const res=await fetch('<?= asset('auth/resend_verification.php') ?>',{method:'POST'}),data=await res.json();if(data.success){successAlert.textContent='Code resent! Check email.';startTimer()}else{errorAlert.textContent=data.message||'Failed to resend.';resendBtn.disabled=false}}catch{errorAlert.textContent='Failed to resend.';resendBtn.disabled=false}});function startTimer(){interval=setInterval(()=>{timer--;if(timer<=0){clearInterval(interval);countdownEl.textContent='';resendBtn.disabled=false;resendBtn.textContent='Resend'}else countdownEl.textContent=`(in ${timer} sec)`},1000)}startTimer()})();
</script>
</body>
</html>