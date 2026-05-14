/**
 * Nepal Travel – Live Support Chat Widget
 * Real-time user ↔ admin chat via AJAX polling
 */

(function () {
  'use strict';

  // ─── Config ───────────────────────────────────────────────
  const API_URL   = '/Nepal-Travel/user/support_chat_api.php';
  const POLL_MS   = 3000; // poll every 3 seconds

  // ─── State ────────────────────────────────────────────────
  let sessionId   = null;
  let lastMsgId   = 0;
  let pollTimer   = null;
  let isOpen      = false;
  let isSending   = false;
  let isStarted   = false;

  // ─── DOM refs (assigned after inject) ────────────────────
  let widget, toggleBtn, badge, messagesEl, inputEl, sendBtn,
      closeBtn, preFormEl, mainChatEl;

  // ─────────────────────────────────────────────────────────
  // Build & inject HTML
  // ─────────────────────────────────────────────────────────
  function injectHTML() {
    const html = `
    <div id="support-chat-container">

      <!-- Toggle Button -->
      <button id="support-toggle-btn" aria-label="Open support chat">
        <span class="support-toggle-icon">💬</span>
        <span id="support-badge" class="support-badge" style="display:none">0</span>
      </button>

      <!-- Chat Window -->
      <div id="support-chat-window" class="support-hidden" role="dialog" aria-label="Live support chat">

        <!-- Header -->
        <div class="support-header">
          <div class="support-header-info">
            <div class="support-avatar">🏔️</div>
            <div>
              <div class="support-header-name">Nepal Travel Support</div>
              <div class="support-header-status" id="support-status-dot">
                <span class="support-dot"></span> Our team is here to help
              </div>
            </div>
          </div>
          <button id="support-close-btn" class="support-icon-btn" aria-label="Close chat">✕</button>
        </div>

        <!-- Pre-chat form (shown before session starts) -->
        <div id="support-pre-form">
          <div class="support-welcome">
            <div class="support-welcome-icon">👋</div>
            <h3>Hi there!</h3>
            <p>Need help planning your Nepal adventure? Send us a message and our team will reply shortly.</p>
          </div>
          <form id="support-start-form">
            <div class="support-form-group" id="support-guest-fields">
              <input type="text" id="support-guest-name" placeholder="Your name" autocomplete="name">
              <input type="email" id="support-guest-email" placeholder="Your email (optional)" autocomplete="email">
            </div>
            <textarea id="support-first-message" placeholder="How can we help you today?" rows="3" required></textarea>
            <button type="submit" class="support-start-btn" id="support-start-btn">
              <span>Start Chat</span> <span>→</span>
            </button>
          </form>
        </div>

        <!-- Main chat (shown after session starts) -->
        <div id="support-main-chat" style="display:none; flex-direction:column; flex:1; overflow:hidden;">
          <div id="support-messages" class="support-messages"></div>
          <div class="support-input-area">
            <textarea id="support-input" placeholder="Type your message…" rows="1" autocomplete="off"></textarea>
            <button id="support-send-btn" class="support-send-btn" aria-label="Send message">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </div>
        </div>

        <!-- Session closed notice -->
        <div id="support-closed-notice" style="display:none;" class="support-closed-notice">
          <span>🔒</span> This conversation has been closed by support.
        </div>

      </div>
    </div>`;

    const wrap = document.createElement('div');
    wrap.innerHTML = html;
    document.body.appendChild(wrap.firstElementChild);

    // Assign refs
    widget      = document.getElementById('support-chat-window');
    toggleBtn   = document.getElementById('support-toggle-btn');
    badge       = document.getElementById('support-badge');
    messagesEl  = document.getElementById('support-messages');
    inputEl     = document.getElementById('support-input');
    sendBtn     = document.getElementById('support-send-btn');
    closeBtn    = document.getElementById('support-close-btn');
    preFormEl   = document.getElementById('support-pre-form');
    mainChatEl  = document.getElementById('support-main-chat');
  }

  // ─────────────────────────────────────────────────────────
  // Toggle widget open/close
  // ─────────────────────────────────────────────────────────
  function toggleChat() {
    isOpen = !isOpen;
    widget.classList.toggle('support-hidden', !isOpen);
    toggleBtn.classList.toggle('support-toggle-active', isOpen);

    if (isOpen) {
      clearBadge();
      if (isStarted && inputEl) inputEl.focus();
    } else {
      stopPolling();
    }

    if (isOpen && isStarted) startPolling();
  }

  // ─────────────────────────────────────────────────────────
  // Start chat session
  // ─────────────────────────────────────────────────────────
  async function startSession(guestName, guestEmail, firstMessage) {
    const btn = document.getElementById('support-start-btn');
    btn.disabled = true;
    btn.innerHTML = '<span>Starting…</span>';

    try {
      const res = await fetch(API_URL + '?action=start_session', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ guest_name: guestName, guest_email: guestEmail })
      });
      const data = await res.json();
      if (!data.success) throw new Error(data.error || 'Failed to start session');

      sessionId = data.session_id;
      isStarted = true;

      // Switch UI
      preFormEl.style.display = 'none';
      mainChatEl.style.display = 'flex';

      // Send the first message
      await sendMessage(firstMessage);
      startPolling();
      inputEl.focus();

    } catch (err) {
      btn.disabled = false;
      btn.innerHTML = '<span>Start Chat</span><span>→</span>';
      showError('Could not connect. Please try again.');
    }
  }

  // ─────────────────────────────────────────────────────────
  // Send a message
  // ─────────────────────────────────────────────────────────
  async function sendMessage(text) {
    if (!text || isSending) return;
    isSending = true;

    // Optimistic UI
    appendMessage({ sender: 'user', message: text, sent_at: new Date().toISOString(), id: 'tmp' });
    if (inputEl) { inputEl.value = ''; inputEl.style.height = 'auto'; }

    try {
      await fetch(API_URL + '?action=send_message', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ session_id: sessionId, message: text })
      });
    } catch (e) {
      console.error('Send failed', e);
    }

    isSending = false;
  }

  // ─────────────────────────────────────────────────────────
  // Polling – fetch new messages
  // ─────────────────────────────────────────────────────────
  function startPolling() {
    if (pollTimer) return;
    poll();
    pollTimer = setInterval(poll, POLL_MS);
  }

  function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
  }

  async function poll() {
    if (!sessionId) return;
    try {
      const res = await fetch(`${API_URL}?action=get_messages&session_id=${sessionId}&last_id=${lastMsgId}`);
      const data = await res.json();
      if (!data.success) return;

      data.messages.forEach(msg => {
        // Skip messages we already rendered (temp user ones)
        if (msg.sender === 'user') {
          // Update lastMsgId but don't re-render user msgs (already shown optimistically)
          if (parseInt(msg.id) > lastMsgId) lastMsgId = parseInt(msg.id);
          return;
        }
        appendMessage(msg);
      });

      // Handle session closed
      if (data.session_status === 'closed') {
        stopPolling();
        document.getElementById('support-closed-notice').style.display = 'flex';
        document.querySelector('.support-input-area').style.display = 'none';
      }
    } catch (e) { /* silent */ }
  }

  // ─────────────────────────────────────────────────────────
  // Append a message bubble
  // ─────────────────────────────────────────────────────────
  function appendMessage(msg) {
    if (msg.id !== 'tmp' && parseInt(msg.id) <= lastMsgId) return;
    if (msg.id !== 'tmp') lastMsgId = parseInt(msg.id);

    const isUser  = msg.sender === 'user';
    const wrapper = document.createElement('div');
    wrapper.className = `support-msg-wrap support-msg-${msg.sender}`;

    const time = new Date(msg.sent_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

    wrapper.innerHTML = `
      <div class="support-bubble support-bubble-${msg.sender}">
        ${escapeHtml(msg.message)}
      </div>
      <div class="support-msg-time">${time}</div>
    `;

    messagesEl.appendChild(wrapper);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    // Show badge if chat closed
    if (!isOpen && msg.sender === 'admin') showBadge();
  }

  // ─────────────────────────────────────────────────────────
  // Badge
  // ─────────────────────────────────────────────────────────
  function showBadge() {
    let count = parseInt(badge.textContent || '0') + 1;
    badge.textContent = count;
    badge.style.display = 'flex';
  }

  function clearBadge() {
    badge.textContent = '0';
    badge.style.display = 'none';
  }

  // ─────────────────────────────────────────────────────────
  // Helpers
  // ─────────────────────────────────────────────────────────
  function showError(msg) {
    const div = document.createElement('div');
    div.className = 'support-error-toast';
    div.textContent = msg;
    document.getElementById('support-chat-container').appendChild(div);
    setTimeout(() => div.remove(), 4000);
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;')
      .replace(/>/g, '&gt;').replace(/"/g, '&quot;')
      .replace(/\n/g, '<br>');
  }

  // ─────────────────────────────────────────────────────────
  // Auto-resize textarea
  // ─────────────────────────────────────────────────────────
  function autoResize(el) {
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
  }

  // ─────────────────────────────────────────────────────────
  // Event Listeners
  // ─────────────────────────────────────────────────────────
  function bindEvents() {
    // Toggle
    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Pre-form submit
    document.getElementById('support-start-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      const name    = (document.getElementById('support-guest-name').value || '').trim() || 'Guest';
      const email   = (document.getElementById('support-guest-email').value || '').trim();
      const message = (document.getElementById('support-first-message').value || '').trim();
      if (!message) return;
      await startSession(name, email, message);
    });

    // Send button
    sendBtn.addEventListener('click', () => {
      const text = inputEl.value.trim();
      if (text) sendMessage(text);
    });

    // Enter to send (Shift+Enter for new line)
    inputEl.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const text = inputEl.value.trim();
        if (text) sendMessage(text);
      }
    });

    inputEl.addEventListener('input', () => autoResize(inputEl));
  }

  // ─────────────────────────────────────────────────────────
  // Init
  // ─────────────────────────────────────────────────────────
  function init() {
    injectHTML();
    bindEvents();

    // If user already has a session in PHP session, restore it
    // (we check via a small ping; server uses PHP $_SESSION)
    fetch(API_URL + '?action=get_unread_count&session_id=0')
      .then(r => r.json())
      .then(data => {
        if (data.count > 0) showBadge();
      }).catch(() => {});
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
