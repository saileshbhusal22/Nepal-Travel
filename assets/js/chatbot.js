/**
 * Sherpa AI Chatbot — Auth-gated, DB-persisted, multi-conversation
 * Requires: marked.min.js, PHP session with user_id
 */
(function () {
  "use strict";

  // ── Config ──────────────────────────────────────────────────────────────
  const API_URL = "/Nepal-Travel/user/chatbot_api.php";
  const HISTORY_URL = "/Nepal-Travel/user/chatbot_history_api.php";
  const RECOMMENDATIONS_URL =
    "/Nepal-Travel/user/chatbot_recommendations_api.php";
  const SUGGESTIONS = [
    { icon: "🏔️", text: "Plan a 7-day Everest Base Camp trek" },
    { icon: "🏙️", text: "Best places to visit in Kathmandu" },
    { icon: "🦏", text: "Chitwan National Park safari guide" },
    { icon: "🌊", text: "Pokhara lakeside 3-day itinerary" },
    { icon: "💰", text: "Budget trip to Nepal under $500" },
    { icon: "🎒", text: "What to pack for Annapurna circuit" },
    { icon: "📅", text: "Best time to visit Nepal" },
    { icon: "🍛", text: "Must-try Nepali foods and restaurants" },
  ];

  // ── State ────────────────────────────────────────────────────────────────
  let isLoggedIn = false;
  let currentUserId = null;
  let currentUserName = "Traveller";
  let conversationId = null;
  let messages = []; // [{role,content}] for API context
  let isStreaming = false;
  let isOpen = false;
  let historyOpen = false;
  let language = "english";
  let suggestionsCollapsed = false;

  // ── DOM refs ─────────────────────────────────────────────────────────────
  let container,
    toggle,
    chatWindow,
    messagesEl,
    inputEl,
    sendBtn,
    closeBtn,
    clearBtn,
    langSelect,
    historyBtn,
    historyPanel,
    historyList,
    newChatBtn,
    loginBanner;

  // ═══════════════════════════════════════════════════════════════════════
  // 1. BUILD HTML
  // ═══════════════════════════════════════════════════════════════════════
  /**
   * Dynamically constructs the HTML layout for the chatbot and injects it into the DOM.
   * Also binds necessary element references to internal variables.
   */
  function buildHTML() {
    const html = `
<div id="sherpa-chatbot-container">
  <!-- Toggle -->
  <button class="sherpa-toggle" id="sherpa-toggle" aria-label="Open Sherpa AI">
    <span class="sherpa-toggle-icon">🏔️</span>
  </button>

  <!-- Chat Window -->
  <div class="sherpa-chatbot sherpa-hidden" id="sherpa-window" role="dialog" aria-label="Sherpa AI Travel Assistant">

    <!-- Header -->
    <div class="sherpa-header">
      <div class="sherpa-header-title">
        <span class="sherpa-icon">🏔️</span>
        <div>
          <h3>Sherpa AI</h3>
          <span class="sherpa-header-sub">Nepal Travel Assistant</span>
        </div>
      </div>
      <div class="sherpa-header-actions">
        <button class="sherpa-hdr-btn" id="sherpa-history-btn" title="Chat History">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 3a9 9 0 0 0-9 9H1l3.89 3.89.07.14L9 12H6c0-3.87 3.13-7 7-7s7 3.13 7 7-3.13 7-7 7c-1.93 0-3.68-.79-4.94-2.06l-1.42 1.42A8.954 8.954 0 0 0 13 21a9 9 0 0 0 0-18zm-1 5v5l4.28 2.54.72-1.21-3.5-2.08V8H12z"/></svg>
        </button>
        <button class="sherpa-hdr-btn" id="sherpa-new-chat-btn" title="New Chat">
          <svg viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
        </button>
        <button class="sherpa-hdr-btn" id="sherpa-lang-btn" title="Language"></button>
        <button class="sherpa-hdr-btn sherpa-close-btn" id="sherpa-close" title="Close">✕</button>
      </div>
    </div>

    <!-- Login Banner (shown when not logged in) -->
    <div class="sherpa-login-banner" id="sherpa-login-banner" style="display:none">
      <div class="sherpa-login-inner">
        <div class="slb-icon">🔒</div>
        <h4>Login Required</h4>
        <p>Sign in to chat with Sherpa and save your travel plans forever.</p>
        <a href="/Nepal-Travel/user/login.php" class="slb-btn">Login to Continue</a>
        <a href="/Nepal-Travel/user/Register.php" class="slb-signup">Don't have an account? Sign Up →</a>
      </div>
    </div>

    <!-- History Panel (sidebar) -->
    <div class="sherpa-history-panel" id="sherpa-history-panel">
      <div class="shp-header">
        <span>Your Chats</span>
        <button class="sherpa-hdr-btn" id="sherpa-history-close">✕</button>
      </div>
      <div class="shp-new-btn" id="sherpa-shp-new">
        <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M19 13H13v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        New Chat
      </div>
      <div class="shp-list" id="sherpa-history-list">
        <div class="shp-loading">Loading history…</div>
      </div>
    </div>

    <!-- Messages -->
    <div class="sherpa-messages" id="sherpa-messages"></div>

    <!-- Suggestions -->
    <div class="sherpa-suggestions" id="sherpa-suggestions">
      <div class="sherpa-suggestions-header">
        <span class="sherpa-sugg-title">✨ Popular Ideas</span>
        <button class="sherpa-sugg-toggle-btn" id="sherpa-sugg-toggle" title="Toggle">▼</button>
      </div>
      <div class="sherpa-suggestions-scroll" id="sherpa-sugg-scroll"></div>
    </div>

    <!-- Input -->
    <div class="sherpa-input-area">
      <textarea id="chatbot-input" placeholder="Ask about Nepal travel…" rows="1" autocomplete="off"></textarea>
      <button class="sherpa-send-btn" id="sherpa-send">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      </button>
    </div>

  </div>
</div>`;

    const wrap = document.createElement("div");
    wrap.innerHTML = html;
    document.body.appendChild(wrap.firstElementChild);

    container = document.getElementById("sherpa-chatbot-container");
    toggle = document.getElementById("sherpa-toggle");
    chatWindow = document.getElementById("sherpa-window");
    messagesEl = document.getElementById("sherpa-messages");
    inputEl = document.getElementById("chatbot-input");
    sendBtn = document.getElementById("sherpa-send");
    closeBtn = document.getElementById("sherpa-close");
    historyBtn = document.getElementById("sherpa-history-btn");
    historyPanel = document.getElementById("sherpa-history-panel");
    historyList = document.getElementById("sherpa-history-list");
    newChatBtn = document.getElementById("sherpa-new-chat-btn");
    loginBanner = document.getElementById("sherpa-login-banner");

    // Language button label
    const lb = document.getElementById("sherpa-lang-btn");
    lb.textContent = "EN";
    lb.addEventListener("click", cycleLanguage);

    buildSuggestions();
  }

  function buildSuggestions() {
    const scroll = document.getElementById("sherpa-sugg-scroll");
    SUGGESTIONS.forEach((s) => {
      const btn = document.createElement("button");
      btn.className = "sherpa-suggestion-btn";
      btn.innerHTML = `<span class="sherpa-sugg-icon">${s.icon}</span>${s.text}`;
      btn.addEventListener("click", () => sendUserMessage(s.text));
      scroll.appendChild(btn);
    });
    document
      .getElementById("sherpa-sugg-toggle")
      .addEventListener("click", toggleSuggestions);
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 2. AUTH CHECK
  // ═══════════════════════════════════════════════════════════════════════
  /**
   * Checks the user's authentication status using meta tags injected by the backend.
   * Updates internal state variables accordingly.
   */
  function checkAuth() {
    // Read from meta tags injected by PHP header
    const meta = document.getElementById("sherpa-user-meta");
    if (meta) {
      isLoggedIn = meta.dataset.loggedIn === "1";
      currentUserId = meta.dataset.userId || null;
      currentUserName = meta.dataset.userName || "Traveller";
    } else {
      // Fallback: call history API; 401 = not logged in
      isLoggedIn = false;
    }
  }

  function showLoginBanner() {
    loginBanner.style.display = "flex";
    messagesEl.style.display = "none";
    document.getElementById("sherpa-suggestions").style.display = "none";
    document.querySelector(".sherpa-input-area").style.display = "none";
  }

  function showChatUI() {
    loginBanner.style.display = "none";
    messagesEl.style.display = "flex";
    document.getElementById("sherpa-suggestions").style.display = "block";
    document.querySelector(".sherpa-input-area").style.display = "flex";
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 3. TOGGLE OPEN / CLOSE
  // ═══════════════════════════════════════════════════════════════════════
  function openChat() {
    isOpen = true;
    chatWindow.classList.remove("sherpa-hidden");
    toggle.classList.add("sherpa-active");

    if (!isLoggedIn) {
      showLoginBanner();
      return;
    }

    if (messagesEl.children.length === 0) {
      showWelcome();
    }
    inputEl.focus();
  }

  function closeChat() {
    isOpen = false;
    chatWindow.classList.add("sherpa-hidden");
    toggle.classList.remove("sherpa-active");
    closeHistory();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 4. WELCOME MESSAGE
  // ═══════════════════════════════════════════════════════════════════════
  function showWelcome() {
    const firstName = currentUserName.split(" ")[0];
    appendMessage(
      "assistant",
      `**Namaste ${firstName}! 🙏**\n\nI'm **Sherpa**, your personal Nepal travel AI. I can help you plan trips, find the best destinations, estimate budgets, and discover hidden gems across Nepal.\n\nWhat adventure are you planning? 🏔️`,
    );
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 5. HISTORY PANEL
  // ═══════════════════════════════════════════════════════════════════════
  function openHistory() {
    historyOpen = true;
    historyPanel.classList.add("shp-open");
    loadHistory();
  }

  function closeHistory() {
    historyOpen = false;
    historyPanel.classList.remove("shp-open");
  }

  async function loadHistory() {
    historyList.innerHTML = '<div class="shp-loading">Loading…</div>';
    try {
      const res = await fetch(HISTORY_URL + "?action=list_conversations");
      const data = await res.json();
      if (!data.success) {
        historyList.innerHTML = '<div class="shp-empty">No chats yet</div>';
        return;
      }

      const convs = data.conversations;
      if (!convs.length) {
        historyList.innerHTML =
          '<div class="shp-empty">No previous chats yet.<br>Start a new conversation!</div>';
        return;
      }

      historyList.innerHTML = "";
      convs.forEach((c) => {
        const item = document.createElement("div");
        item.className =
          "shp-item" + (c.id == conversationId ? " shp-item-active" : "");
        item.dataset.id = c.id;

        const date = new Date(c.updated_at).toLocaleDateString("en-US", {
          month: "short",
          day: "numeric",
        });
        const preview =
          (c.last_message || "No messages").substring(0, 55) +
          (c.last_message && c.last_message.length > 55 ? "…" : "");

        item.innerHTML = `
          <div class="shp-item-main">
            <div class="shp-item-title">${escHtml(c.title)}</div>
            <div class="shp-item-preview">${escHtml(preview)}</div>
            <div class="shp-item-date">${date} · ${c.msg_count} msg${c.msg_count != 1 ? "s" : ""}</div>
          </div>
          <div class="shp-item-actions">
            <button class="shp-del-btn" data-id="${c.id}" title="Delete">🗑</button>
          </div>`;

        item.addEventListener("click", (e) => {
          if (e.target.closest(".shp-del-btn")) return;
          loadConversation(c.id, c.title);
        });
        item.querySelector(".shp-del-btn").addEventListener("click", (e) => {
          e.stopPropagation();
          deleteConversation(c.id, item);
        });

        historyList.appendChild(item);
      });
    } catch (e) {
      historyList.innerHTML =
        '<div class="shp-empty">Failed to load history.</div>';
    }
  }

  async function loadConversation(id, title) {
    conversationId = id;
    messages = [];
    messagesEl.innerHTML = "";
    closeHistory();

    try {
      const res = await fetch(
        HISTORY_URL + `?action=get_messages&conversation_id=${id}`,
      );
      const data = await res.json();
      if (!data.success) {
        showToast("Failed to load conversation", "error");
        return;
      }

      // Restore context array for API calls
      data.messages.forEach((m) => {
        messages.push({ role: m.role, content: m.content });
        appendMessage(m.role, m.content, new Date(m.created_at));
      });

      // Update language if stored
      if (data.conversation.language) language = data.conversation.language;

      showToast(`Continued: ${title}`, "info");
    } catch (e) {
      showToast("Error loading conversation", "error");
    }
  }

  async function deleteConversation(id, el) {
    if (!confirm("Delete this conversation? This cannot be undone.")) return;
    try {
      const res = await fetch(HISTORY_URL + "?action=delete_conversation", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ conversation_id: id }),
      });
      const data = await res.json();
      if (data.success) {
        el.remove();
        if (id == conversationId) startNewChat();
        if (!historyList.children.length)
          historyList.innerHTML = '<div class="shp-empty">No chats yet.</div>';
      }
    } catch (e) {}
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 6. NEW CHAT
  // ═══════════════════════════════════════════════════════════════════════
  function startNewChat() {
    if (!isLoggedIn) {
      showLoginBanner();
      return;
    }

    conversationId = null;
    messages = [];
    messagesEl.innerHTML = "";
    showWelcome();
    inputEl.focus();
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 7. SEND MESSAGE
  // ═══════════════════════════════════════════════════════════════════════
  /**
   * Processes and sends the user's message to the chatbot.
   * Displays the message in the UI, saves it to the database, and requests the AI response.
   * @param {string} text - The input text from the user.
   */
  async function sendUserMessage(text) {
    text = text.trim();
    if (!text || isStreaming) return;

    if (!isLoggedIn) {
      showLoginBanner();
      return;
    }

    // Collapse suggestions after first message
    if (!suggestionsCollapsed) toggleSuggestions();

    // Ensure we have a conversation in DB
    if (!conversationId) {
      conversationId = await createConversation(text);
    }

    // Add to local context
    messages.push({ role: "user", content: text });
    appendMessage("user", text);
    inputEl.value = "";
    inputEl.style.height = "auto";

    // Save user message to DB
    saveMessageToDB("user", text);

    // Stream AI response
    await streamResponse();
  }

  async function createConversation(firstMsg) {
    const title = firstMsg.substring(0, 60) + (firstMsg.length > 60 ? "…" : "");
    try {
      const res = await fetch(HISTORY_URL + "?action=create_conversation", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ title, language }),
      });
      const data = await res.json();
      return data.success ? data.conversation_id : null;
    } catch (e) {
      return null;
    }
  }

  async function saveMessageToDB(role, content) {
    if (!conversationId) return;
    try {
      await fetch(HISTORY_URL + "?action=save_message", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          conversation_id: conversationId,
          role,
          content,
        }),
      });
    } catch (e) {}
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 8. STREAM AI RESPONSE
  // ═══════════════════════════════════════════════════════════════════════
  /**
   * Sends the conversation context to the backend API and streams the AI's response
   * character-by-character into the chat window.
   */
  async function streamResponse() {
    isStreaming = true;
    sendBtn.disabled = true;
    inputEl.disabled = true;

    // Build payload from context (include system prompt via API)
    const payload = {
      messages: messages.filter((m) => m.role !== "system"),
      language,
      conversation_id: conversationId,
    };

    const thinkingEl = appendThinking();

    let fullResponse = "";
    let assistantBubble = null;

    try {
      const res = await fetch(API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload),
      });

      // Check for auth error
      if (res.status === 401) {
        const errData = await res.json();
        thinkingEl.remove();
        showLoginBanner();
        return;
      }

      // Check for other HTTP errors
      if (!res.ok) {
        const errorText = await res.text();
        console.error("API Error:", res.status, errorText);
        thinkingEl.remove();
        appendMessage(
          "assistant",
          `⚠️ API Error (${res.status}): ${errorText.substring(0, 200)}`,
        );
        return;
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = "";

      thinkingEl.remove();
      assistantBubble = appendStreamBubble();

      while (true) {
        const { done, value } = await reader.read();
        if (done) break;

        buffer += decoder.decode(value, { stream: true });
        const lines = buffer.split("\n");
        buffer = lines.pop();

        for (const line of lines) {
          if (!line.startsWith("data:")) continue;
          const raw = line.slice(5).trim();
          if (raw === "[DONE]") break;

          try {
            const parsed = JSON.parse(raw);
            if (parsed.error) {
              assistantBubble.innerHTML = `⚠️ ${escHtml(parsed.error)}`;
              break;
            }
            const delta = parsed.choices?.[0]?.delta?.content;
            if (delta) {
              fullResponse += delta;
              assistantBubble.innerHTML = renderMarkdown(fullResponse);
              messagesEl.scrollTop = messagesEl.scrollHeight;
            }
          } catch (_) {}
        }
      }

      if (fullResponse) {
        messages.push({ role: "assistant", content: fullResponse });
        // Wrap in meta div with timestamp + copy
        finaliseBubble(assistantBubble, fullResponse);
        // Save to DB
        saveMessageToDB("assistant", fullResponse);
        // Delay recommendations to ensure they appear after chat ends
        setTimeout(() => {
          const userMsg = messages[messages.length - 2]?.content || "";
          if (userMsg) fetchRecommendations(userMsg);
        }, 300);
      }
    } catch (e) {
      if (thinkingEl.parentElement) thinkingEl.remove();
      appendMessage("assistant", "⚠️ Connection error. Please try again.");
    } finally {
      isStreaming = false;
      sendBtn.disabled = false;
      inputEl.disabled = false;
      inputEl.focus();
    }
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 8B. RECOMMENDATIONS
  // ═══════════════════════════════════════════════════════════════════════
  async function fetchRecommendations(userQuery) {
    if (!isLoggedIn || !userQuery) return;
    try {
      const res = await fetch(RECOMMENDATIONS_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ query: userQuery }),
      });
      const data = await res.json();
      if (
        data.success &&
        data.recommendations &&
        data.recommendations.length > 0
      ) {
        appendRecommendationCards(data.recommendations);
      }
    } catch (e) {
      // Silently fail - recommendations are optional
    }
  }

  function appendRecommendationCards(recommendations) {
    const wrapper = document.createElement("div");
    wrapper.className = "sherpa-recommendations-wrapper";
    wrapper.innerHTML =
      '<div class="sherpa-rec-label">✨ Recommended for You</div>';

    const scroll = document.createElement("div");
    scroll.className = "sherpa-rec-scroll";

    recommendations.forEach((rec) => {
      const card = document.createElement("a");
      card.href = rec.detail_link || "#";
      card.className = "sherpa-rec-card";
      card.target = "_blank";

      // Build rating display if available
      const rating = rec.avg_rating || rec.rating || 0;
      const reviewCount = rec.review_count || rec.reviews_count || 0;
      const stars = rating > 0 ? "⭐".repeat(Math.round(rating)) : "";
      const reviewText = reviewCount > 0 ? `(${reviewCount} reviews)` : "";

      card.innerHTML = `
        <div class="sherpa-rec-header">
          <div class="sherpa-rec-type">${rec.type === "deal" ? "🎫" : rec.type === "user_deal" ? "👤" : "🎉"}</div>
          <div class="sherpa-rec-title">${escHtml(rec.title)}</div>
        </div>
        <div class="sherpa-rec-body">
          <p class="sherpa-rec-desc">${escHtml(rec.description || "")}</p>
          ${rec.price ? `<div class="sherpa-rec-price">NPR ${rec.price.toLocaleString()}</div>` : ""}
          ${rating > 0 ? `<div class="sherpa-rec-rating">${stars} ${rating} ${reviewText}</div>` : ""}
          <div class="sherpa-rec-meta">
            ${rec.location ? `<span>${escHtml(rec.location)}</span>` : ""}
            ${rec.category ? `<span>${escHtml(rec.category)}</span>` : ""}
            ${rec.date ? `<span>${escHtml(rec.date)}</span>` : ""}
          </div>
        </div>
      `;
      scroll.appendChild(card);
    });

    wrapper.appendChild(scroll);
    messagesEl.appendChild(wrapper);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 9. DOM HELPERS
  // ═══════════════════════════════════════════════════════════════════════
  function appendMessage(role, content, dateObj) {
    const wrapper = document.createElement("div");
    wrapper.className = `sherpa-msg-wrapper sherpa-msg-wrapper--${role}`;

    const bubble = document.createElement("div");
    bubble.className = `sherpa-message sherpa-${role}`;

    if (role === "assistant") {
      const md = document.createElement("div");
      md.className = "sherpa-markdown";
      md.innerHTML = renderMarkdown(content);
      bubble.appendChild(md);
    } else {
      bubble.textContent = content;
    }

    const meta = document.createElement("div");
    meta.className = "sherpa-msg-meta";
    const ts = dateObj ? dateObj : new Date();
    meta.innerHTML = `<span class="sherpa-timestamp">${formatTime(ts)}</span>`;

    if (role === "assistant") {
      const copyBtn = document.createElement("button");
      copyBtn.className = "sherpa-copy-btn";
      copyBtn.title = "Copy";
      copyBtn.innerHTML = "📋";
      copyBtn.addEventListener("click", () => copyText(content, copyBtn));
      meta.appendChild(copyBtn);
    }

    wrapper.appendChild(bubble);
    wrapper.appendChild(meta);
    messagesEl.appendChild(wrapper);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return bubble;
  }

  function appendThinking() {
    const wrap = document.createElement("div");
    wrap.className = "sherpa-msg-wrapper sherpa-msg-wrapper--assistant";
    wrap.innerHTML = `<div class="sherpa-message sherpa-assistant sherpa-loading">
      <span class="sherpa-typing">●</span>
      <span class="sherpa-typing">●</span>
      <span class="sherpa-typing">●</span>
    </div>`;
    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return wrap;
  }

  function appendStreamBubble() {
    const wrap = document.createElement("div");
    wrap.className = "sherpa-msg-wrapper sherpa-msg-wrapper--assistant";
    const bubble = document.createElement("div");
    bubble.className = "sherpa-message sherpa-assistant sherpa-markdown";
    wrap.appendChild(bubble);
    messagesEl.appendChild(wrap);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return bubble;
  }

  function finaliseBubble(bubble, fullContent) {
    const meta = document.createElement("div");
    meta.className = "sherpa-msg-meta";
    meta.innerHTML = `<span class="sherpa-timestamp">${formatTime(new Date())}</span>`;
    const copyBtn = document.createElement("button");
    copyBtn.className = "sherpa-copy-btn";
    copyBtn.innerHTML = "📋";
    copyBtn.title = "Copy";
    copyBtn.addEventListener("click", () => copyText(fullContent, copyBtn));
    meta.appendChild(copyBtn);
    bubble.parentElement.appendChild(meta);
  }

  function renderMarkdown(text) {
    if (typeof marked !== "undefined") {
      return marked.parse(text);
    }
    return text.replace(/\n/g, "<br>");
  }

  function formatTime(d) {
    return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
      btn.innerHTML = "✅";
      setTimeout(() => (btn.innerHTML = "📋"), 1500);
    });
  }

  function showToast(msg, type = "info") {
    const t = document.createElement("div");
    t.className = `sherpa-toast sherpa-toast-${type}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add("sherpa-toast-show"), 50);
    setTimeout(() => {
      t.classList.remove("sherpa-toast-show");
      setTimeout(() => t.remove(), 400);
    }, 2500);
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 10. LANGUAGE CYCLE
  // ═══════════════════════════════════════════════════════════════════════
  function cycleLanguage() {
    const langs = ["english", "nepali", "hindi"];
    const labels = ["EN", "नेपाली", "हिंदी"];
    let idx = langs.indexOf(language);
    idx = (idx + 1) % langs.length;
    language = langs[idx];
    document.getElementById("sherpa-lang-btn").textContent = labels[idx];
    showToast(`Language: ${labels[idx]}`, "info");
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 11. SUGGESTIONS TOGGLE
  // ═══════════════════════════════════════════════════════════════════════
  function toggleSuggestions() {
    suggestionsCollapsed = !suggestionsCollapsed;
    const scroll = document.getElementById("sherpa-sugg-scroll");
    const btn = document.getElementById("sherpa-sugg-toggle");
    scroll.classList.toggle(
      "sherpa-suggestions-collapsed",
      suggestionsCollapsed,
    );
    btn.classList.toggle("sherpa-sugg-btn-collapsed", suggestionsCollapsed);
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 12. EVENTS
  // ═══════════════════════════════════════════════════════════════════════
  function bindEvents() {
    toggle.addEventListener("click", () => (isOpen ? closeChat() : openChat()));
    closeBtn.addEventListener("click", closeChat);
    historyBtn.addEventListener("click", () => {
      if (!isLoggedIn) {
        showLoginBanner();
        return;
      }
      historyOpen ? closeHistory() : openHistory();
    });
    document
      .getElementById("sherpa-history-close")
      .addEventListener("click", closeHistory);
    newChatBtn.addEventListener("click", startNewChat);
    document.getElementById("sherpa-shp-new").addEventListener("click", () => {
      startNewChat();
      closeHistory();
    });

    sendBtn.addEventListener("click", () => sendUserMessage(inputEl.value));
    inputEl.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        sendUserMessage(inputEl.value);
      }
    });
    inputEl.addEventListener("input", () => {
      inputEl.style.height = "auto";
      inputEl.style.height = Math.min(inputEl.scrollHeight, 120) + "px";
    });

    // Close history when clicking messages area
    messagesEl.addEventListener("click", () => {
      if (historyOpen) closeHistory();
    });
  }

  // ═══════════════════════════════════════════════════════════════════════
  // 13. INIT
  // ═══════════════════════════════════════════════════════════════════════
  function init() {
    buildHTML();
    checkAuth();
    bindEvents();

    if (!isLoggedIn) {
      // Show login banner by default
      showLoginBanner();

      // Show lock badge on toggle
      const badge = document.createElement("span");
      badge.className = "sherpa-lock-badge";
      badge.textContent = "🔒";
      toggle.appendChild(badge);
    } else {
      // If logged in, show chat UI by default
      showChatUI();
    }

    // If opened via URL hash
    if (window.location.hash === "#sherpa") openChat();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
