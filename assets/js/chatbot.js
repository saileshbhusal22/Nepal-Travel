(function () {
  const CHATBOT_API = "/Nepal-Travel/user/chatbot_api.php";
  const STORAGE_KEY = "sherpa_chat_history";
  const SUGGESTIONS = [
    { title: "Everest Base Camp", prompt: "Can you give me a detailed 12-day itinerary for the Everest Base Camp trek including costs? 🏔️", icon: "🏔️" },
    { title: "Pokhara Getaway", prompt: "What are the top things to do in Pokhara for a 3-day relaxed trip? 🌄", icon: "🌄" },
    { title: "Jungle Safari", prompt: "Plan a 2-night 3-day Chitwan National Park jungle safari with costs. 🐘", icon: "🐘" },
    { title: "Budget Nepal", prompt: "How can I travel Nepal on a budget of $30 per day? 💰", icon: "💰" }
  ];

  let chatHistory = [];
  let isSending = false;
  let conversationId = null;

  // ==================== INITIALIZATION ====================

  function init() {
    createChatbotWidget();
    attachEvents();
    loadChatHistory();
    loadCDN(); // Load marked.js for markdown rendering
  }

  function loadCDN() {
    const script = document.createElement("script");
    script.src = "https://cdn.jsdelivr.net/npm/marked/marked.min.js";
    document.head.appendChild(script);
  }

  // ==================== LOAD/SAVE CHAT HISTORY ====================

  function saveChatHistory() {
    localStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({
        messages: chatHistory,
        timestamp: new Date().toISOString(),
      }),
    );
  }

  function loadChatHistory() {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      try {
        const data = JSON.parse(saved);
        chatHistory = data.messages || [];
        // Show previous messages
        chatHistory.forEach((msg) => {
          if (msg.role === "user") {
            addMessageToUI(msg.content, "user");
          } else if (msg.role === "assistant") {
            addMessageToUI(msg.content, "assistant");
          }
        });
      } catch (e) {
        console.log("Could not load chat history");
      }
    }
  }

  function clearChatHistory() {
    chatHistory = [];
    localStorage.removeItem(STORAGE_KEY);
    document.getElementById("chatbot-messages").innerHTML = "";
    showSuggestions();
  }

  // ==================== UI CREATION ====================

  function createChatbotWidget() {
    const container = document.createElement("div");
    container.id = "sherpa-chatbot-container";

    container.innerHTML = `
      <div class="sherpa-chatbot sherpa-hidden">
        <!-- Header -->
        <div class="sherpa-header">
          <div class="sherpa-header-title">
            <span class="sherpa-icon">🇳🇵</span>
            <h3>Sherpa</h3>
          </div>
          <div class="sherpa-header-actions">
            <button class="sherpa-clear-btn" title="Clear chat">🗑️</button>
            <button class="sherpa-close-btn" title="Close">&times;</button>
          </div>
        </div>

        <!-- Messages Area -->
        <div id="chatbot-messages" class="sherpa-messages"></div>

        <!-- Suggestions -->
        <div id="sherpa-suggestions" class="sherpa-suggestions"></div>

        <!-- Input Area -->
        <div class="sherpa-input-area">
          <input 
            id="chatbot-input" 
            type="text"
            placeholder="Ask about Nepal travel..." 
            autocomplete="off"
          />
          <button id="chatbot-send" class="sherpa-send-btn">Send</button>
        </div>
      </div>

      <!-- Toggle Button -->
      <button id="chatbot-toggle" class="sherpa-toggle">
        <span class="sherpa-toggle-icon">🧑‍🏔️</span>
        <span class="sherpa-badge">1</span>
      </button>
    `;

    document.body.appendChild(container);
  }

  // ==================== MESSAGE HANDLING ====================

  function getTimestamp() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function makeCopyBtn(getMsgText) {
    const btn = document.createElement("button");
    btn.className = "sherpa-copy-btn";
    btn.title = "Copy message";
    btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
    btn.onclick = () => {
      const text = getMsgText();
      navigator.clipboard.writeText(text).then(() => {
        btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>`;
        btn.classList.add("sherpa-copy-btn--done");
        setTimeout(() => {
          btn.innerHTML = `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>`;
          btn.classList.remove("sherpa-copy-btn--done");
        }, 2000);
      });
    };
    return btn;
  }

  function addMessageToUI(text, sender) {
    const messagesDiv = document.getElementById("chatbot-messages");

    const wrapper = document.createElement("div");
    wrapper.className = `sherpa-msg-wrapper sherpa-msg-wrapper--${sender}`;

    const msg = document.createElement("div");
    msg.className = `sherpa-message sherpa-${sender}`;

    if (sender === "assistant") {
      msg.innerHTML = window.marked ? marked.parse(text) : text;
      msg.classList.add("sherpa-markdown");
    } else {
      msg.textContent = text;
    }

    wrapper.appendChild(msg);

    // Meta row: timestamp + copy button
    const meta = document.createElement("div");
    meta.className = "sherpa-msg-meta";

    const ts = document.createElement("span");
    ts.className = "sherpa-timestamp";
    ts.textContent = getTimestamp();
    meta.appendChild(ts);

    if (sender === "assistant") {
      const copyBtn = makeCopyBtn(() => msg.innerText);
      meta.appendChild(copyBtn);
    }

    wrapper.appendChild(meta);
    messagesDiv.appendChild(wrapper);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    return msg;
  }

  function showSuggestions() {
    const suggestionsDiv = document.getElementById("sherpa-suggestions");
    suggestionsDiv.style.display = "flex"; // Ensure it's visible if it was ever hidden
    
    // Create header with toggle button
    suggestionsDiv.innerHTML = `
      <div class="sherpa-suggestions-header">
        <span class="sherpa-sugg-title">✨ Popular Ideas</span>
        <button id="sherpa-sugg-toggle" class="sherpa-sugg-toggle-btn" title="Toggle Suggestions">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
        </button>
      </div>
    `;

    const scrollContainer = document.createElement("div");
    scrollContainer.className = "sherpa-suggestions-scroll";
    scrollContainer.id = "sherpa-suggestions-scroll";

    SUGGESTIONS.forEach((suggestion) => {
      const btn = document.createElement("button");
      btn.className = "sherpa-suggestion-btn";
      btn.innerHTML = `<span class="sherpa-sugg-icon">${suggestion.icon}</span><span class="sherpa-sugg-text">${suggestion.title}</span>`;
      btn.onclick = () => {
        document.getElementById("chatbot-input").value = suggestion.prompt;
        sendMessage();
      };
      scrollContainer.appendChild(btn);
    });

    suggestionsDiv.appendChild(scrollContainer);

    // Toggle logic with smooth class toggling
    document.getElementById("sherpa-sugg-toggle").onclick = function() {
      scrollContainer.classList.toggle("sherpa-suggestions-collapsed");
      this.classList.toggle("sherpa-sugg-btn-collapsed");
    };
  }

  // ==================== LANGUAGE DETECTION ====================

  function detectLanguage(text) {
    // Simple detection: look for Nepali/Hindi Unicode patterns
    const nepaliRegex = /[\u0900-\u097F]/g; // Devanagari script
    const hindiRegex = /[\u0900-\u097F]/g;

    if (text.match(nepaliRegex) || text.match(hindiRegex)) {
      return text.match(/[\u0981-\u0983\u0981-\u098C\u0993-\u0994]/)
        ? "nepali"
        : "hindi";
    }
    return "english";
  }

  // ==================== SEND MESSAGE ====================

  async function sendMessage() {
    const input = document.getElementById("chatbot-input");
    const message = input.value.trim();

    if (!message || isSending) return;

    isSending = true;
    document.getElementById("chatbot-send").disabled = true;
    input.disabled = true;

    // Automatically collapse popular suggestions
    const scrollContainer = document.getElementById("sherpa-suggestions-scroll");
    const toggleBtn = document.getElementById("sherpa-sugg-toggle");
    if (scrollContainer && !scrollContainer.classList.contains("sherpa-suggestions-collapsed")) {
      scrollContainer.classList.add("sherpa-suggestions-collapsed");
      if (toggleBtn) toggleBtn.classList.add("sherpa-sugg-btn-collapsed");
    }

    // Add user message
    addMessageToUI(message, "user");
    input.value = "";
    chatHistory.push({ role: "user", content: message });

    // Add loading indicator
    const loadingWrapper = document.createElement("div");
    loadingWrapper.className = "sherpa-msg-wrapper sherpa-msg-wrapper--assistant";
    const loadingMsg = document.createElement("div");
    loadingMsg.className = "sherpa-message sherpa-assistant sherpa-loading";
    loadingMsg.innerHTML = `
      <span class="sherpa-typing">●</span>
      <span class="sherpa-typing">●</span>
      <span class="sherpa-typing">●</span>
      <span class="sherpa-thinking-label">Sherpa is thinking…</span>
    `;
    loadingWrapper.appendChild(loadingMsg);
    document.getElementById("chatbot-messages").appendChild(loadingWrapper);
    document.getElementById("chatbot-messages").scrollTop =
      document.getElementById("chatbot-messages").scrollHeight;

    try {
      const language = detectLanguage(message);
      const res = await fetch(CHATBOT_API, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          messages: chatHistory,
          conversation_id: conversationId,
          language: language,
        }),
      });

      // Remove loading indicator
      loadingWrapper.remove();

      const contentType = res.headers.get("content-type");
      if (contentType && contentType.includes("application/json")) {
        // Fallback for standard JSON errors
        const data = await res.json();
        if (!data.success) {
          addMessageToUI("❌ Error: " + (data.error || "Unknown error"), "error");
        }
      } else {
        // Handle Server-Sent Events stream
        const reader = res.body.getReader();
        const decoder = new TextDecoder("utf-8");
        let done = false;
        let fullMessage = "";
        let buffer = "";

        const responseMsg = addMessageToUI("", "assistant");
        const msgsDiv = document.getElementById("chatbot-messages");

        while (!done) {
          const { value, done: readerDone } = await reader.read();
          done = readerDone;
          
          if (value) {
            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split("\n");
            buffer = lines.pop(); // Keep partial line in buffer

            for (const line of lines) {
              const trimmedLine = line.trim();
              if (trimmedLine.startsWith("data: ")) {
                const dataStr = trimmedLine.substring(6).trim();
                
                if (dataStr === "[DONE]") continue;
                
                try {
                  const data = JSON.parse(dataStr);
                  
                  // Check for streaming errors
                  if (data.error) {
                    responseMsg.innerHTML += "<br/>❌ Error: " + data.error;
                    continue;
                  }

                  if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                    fullMessage += data.choices[0].delta.content;
                    responseMsg.innerHTML = window.marked ? marked.parse(fullMessage) : fullMessage;
                    
                    // Keep scroll at bottom during stream
                    msgsDiv.scrollTop = msgsDiv.scrollHeight;
                  }
                } catch (e) {
                  // Ignore parse errors for fragmented chunks
                }
              }
            }
          }
        }
        
        if (fullMessage.trim() !== "") {
          chatHistory.push({
            role: "assistant",
            content: fullMessage,
          });
          saveChatHistory();

          // Add timestamp + copy button after streaming ends
          const parentWrapper = responseMsg.closest(".sherpa-msg-wrapper");
          if (parentWrapper && !parentWrapper.querySelector(".sherpa-msg-meta")) {
            const meta = document.createElement("div");
            meta.className = "sherpa-msg-meta";
            const ts = document.createElement("span");
            ts.className = "sherpa-timestamp";
            ts.textContent = getTimestamp();
            meta.appendChild(ts);
            const copyBtn = makeCopyBtn(() => responseMsg.innerText);
            meta.appendChild(copyBtn);
            parentWrapper.appendChild(meta);
          }
        }
      }
    } catch (err) {
      if (document.querySelector(".sherpa-loading")) {
        document.querySelector(".sherpa-loading").remove();
      }
      addMessageToUI("❌ Connection error: " + err.message, "error");
    } finally {
      isSending = false;
      document.getElementById("chatbot-send").disabled = false;
      input.disabled = false;
      input.focus();
    }
  }

  // ==================== UI INTERACTIONS ====================

  function toggleChatbot() {
    const chatbot = document.querySelector(".sherpa-chatbot");
    const toggle = document.getElementById("chatbot-toggle");
    const badge = document.querySelector(".sherpa-badge");

    chatbot.classList.toggle("sherpa-hidden");
    toggle.classList.toggle("sherpa-hidden");

    if (badge) {
      badge.style.display = badge.style.display === "none" ? "block" : "none";
    }

    if (!chatbot.dataset.opened) {
      addMessageToUI(
        `Hello! I'm Sherpa, your Nepal travel expert! How can I help you plan your trip today? You can ask me things like:
        <div class="sherpa-inline-suggestions">
          <button class="sherpa-inline-btn" data-prompt="What is the best time to visit Nepal?">What is the best time to visit Nepal?</button>
          <button class="sherpa-inline-btn" data-prompt="Can you plan a 5-day budget trip to Pokhara?">Can you plan a 5-day budget trip to Pokhara?</button>
          <button class="sherpa-inline-btn" data-prompt="What are the top short treks?">What are the top short treks?</button>
        </div>`,
        "assistant",
      );
      showSuggestions();
      chatbot.dataset.opened = true;
    }
  }

  function attachEvents() {
    document.getElementById("chatbot-send").onclick = sendMessage;
    document.getElementById("chatbot-toggle").onclick = toggleChatbot;
    document.querySelector(".sherpa-close-btn").onclick = toggleChatbot;
    document.querySelector(".sherpa-clear-btn").onclick = clearChatHistory;

    document
      .getElementById("chatbot-input")
      .addEventListener("keypress", (e) => {
        if (e.key === "Enter" && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });

    // Delegate clicks for inline suggestion buttons
    document.getElementById("chatbot-messages").addEventListener("click", (e) => {
      const btn = e.target.closest(".sherpa-inline-btn");
      if (btn && !isSending) {
        document.getElementById("chatbot-input").value = btn.dataset.prompt;
        sendMessage();
      }
    });
  }

  // ==================== INITIALIZE ====================

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
