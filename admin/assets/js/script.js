document.addEventListener('DOMContentLoaded', function () {
 
    // ═══════════════════════════════════════
    // ACTIVE NAV LINKS
    // ═══════════════════════════════════════
    const navLinks = document.querySelectorAll('.navbar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            navLinks.forEach(l => l.classList.remove('active'));
            link.classList.add('active');
        });
    });
 
    // ═══════════════════════════════════════
    // SCROLL TO TOP
    // ═══════════════════════════════════════
    const scrollTopBtn = document.getElementById('fw-scroll-top');
    if (scrollTopBtn) {
        window.addEventListener('scroll', () => {
            scrollTopBtn.classList.toggle('show', window.scrollY > 300);
        });
        scrollTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
 
    // ═══════════════════════════════════════
    // USER DROPDOWN
    // ═══════════════════════════════════════
    const userBtn      = document.getElementById('userBtn');
    const userDropdown = document.getElementById('userDropdown');
 
    if (userBtn && userDropdown) {
        userBtn.addEventListener('click', (e) => {
            if (window.innerWidth <= 991) {
                window.location.href = 'login.html';
            } else {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            }
        });
 
        document.addEventListener('click', () => {
            if (window.innerWidth > 991) {
                userDropdown.classList.remove('active');
            }
        });
    }
 
    // ═══════════════════════════════════════
    // AVATAR CLOSE
    // ═══════════════════════════════════════
    const avatarClose = document.querySelector('.fw-avatar-close');
    if (avatarClose) {
        avatarClose.addEventListener('click', (e) => {
            e.stopPropagation();
            const wrap    = document.getElementById('fw-wrap');
            const tooltip = document.getElementById('fw-tooltip');
            if (wrap)    wrap.style.display    = 'none';
            if (tooltip) tooltip.style.display = 'none';
        });
    }
 
    // ═══════════════════════════════════════
    // CHAT WIDGET
    // ═══════════════════════════════════════
    const tooltip      = document.getElementById('fw-tooltip');
    const tooltipClose = document.getElementById('fw-tooltip-close');
    const tooltipCta   = document.getElementById('fw-tooltip-cta');
    const avatarBtn    = document.getElementById('fw-avatar-btn');
    const chatWindow   = document.getElementById('fw-window');
    const windowClose  = document.getElementById('fw-window-close');
    const input        = document.getElementById('fw-input');
    const sendBtn      = document.getElementById('fw-send');
    const messages     = document.getElementById('fw-messages');
    const quickReplies = document.getElementById('fw-quick-replies');
    const badge        = document.querySelector('.fw-badge');
 
    if (avatarBtn) {
        let tooltipDismissed = false;
        let chatOpen = false;
 
        setTimeout(() => {
            if (!tooltipDismissed && !chatOpen) tooltip.classList.add('fw-tooltip-show');
        }, 2500);
 
        function openChat() {
            chatOpen = true;
            tooltip.classList.remove('fw-tooltip-show');
            tooltipDismissed = true;
            chatWindow.classList.add('fw-window-open');
            if (badge) badge.style.display = 'none';
            setTimeout(() => input.focus(), 300);
        }
 
        function closeChat() {
            chatOpen = false;
            chatWindow.classList.remove('fw-window-open');
        }
 
        avatarBtn.addEventListener('click', () => {
            if (chatOpen) closeChat();
            else openChat();
        });
 
        tooltipCta.addEventListener('click', openChat);
 
        tooltipClose.addEventListener('click', (e) => {
            e.stopPropagation();
            tooltip.classList.remove('fw-tooltip-show');
            tooltipDismissed = true;
        });
 
        windowClose.addEventListener('click', closeChat);
 
        document.querySelectorAll('.fw-qr').forEach(btn => {
            btn.addEventListener('click', () => {
                const msg = btn.getAttribute('data-msg');
                addMessage(msg, 'user');
                quickReplies.style.display = 'none';
                setTimeout(() => agentReply(msg), 900);
            });
        });
 
        function sendMessage() {
            const text = input.value.trim();
            if (!text) return;
            addMessage(text, 'user');
            input.value = '';
            setTimeout(() => agentReply(text), 900);
        }
 
        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', (e) => { if (e.key === 'Enter') sendMessage(); });
 
        function addMessage(text, type) {
            const div = document.createElement('div');
            div.className = `fw-msg fw-msg-${type}`;
            if (type === 'agent') {
                const img = document.createElement('img');
                img.src = 'https://i.pravatar.cc/28?img=47';
                img.className = 'fw-msg-avatar';
                div.appendChild(img);
            }
            const bubble = document.createElement('div');
            bubble.className = 'fw-msg-bubble';
            bubble.textContent = text;
            div.appendChild(bubble);
            messages.insertBefore(div, quickReplies);
            messages.scrollTop = messages.scrollHeight;
        }
 
        function addTyping() {
            const div = document.createElement('div');
            div.className = 'fw-msg fw-msg-agent fw-typing-wrap';
            div.id = 'fw-typing';
            div.innerHTML = `
                <img src="https://i.pravatar.cc/28?img=47" class="fw-msg-avatar"/>
                <div class="fw-msg-bubble fw-typing">
                    <span></span><span></span><span></span>
                </div>`;
            messages.insertBefore(div, quickReplies);
            messages.scrollTop = messages.scrollHeight;
        }
 
        function removeTyping() {
            const t = document.getElementById('fw-typing');
            if (t) t.remove();
        }
 
        const replies = [
            "Great choice! We have some amazing laptop deals right now 💻",
            "Let me check that for you — one moment! ⏳",
            "Sure! Our best sellers are on up to 50% off this week 🔥",
            "I'd be happy to help with your order. Can you share your order number?",
            "All products come with a 1-year manufacturer warranty plus our 30-day return policy 🛡️",
            "Anything else I can help you with? 😊"
        ];
        let replyIdx = 0;
 
        function agentReply(userMsg) {
            addTyping();
            setTimeout(() => {
                removeTyping();
                const msg = replies[replyIdx % replies.length];
                replyIdx++;
                addMessage(msg, 'agent');
            }, 1400);
        }
    }
 
});