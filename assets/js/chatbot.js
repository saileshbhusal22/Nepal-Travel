(function () {
  const CHATBOT_API = "/Nepal-Travel/user/chatbot_api.php";
  const STORAGE_KEY = "sherpa_chat_history";
  const SUGGESTIONS = [
    { text: "Everest Trek 🏔️", emoji: "🏔️" },
    { text: "Pokhara Trip 🌄", emoji: "🌄" },
    { text: "Chitwan Safari 🐘", emoji: "🐘" },
    { text: "Budget Nepal Trip 💰", emoji: "💰" },
    { text: "Nepali Food 🍛", emoji: "🍛" },
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

  function addMessageToUI(text, sender) {
    const messagesDiv = document.getElementById("chatbot-messages");
    const msg = document.createElement("div");
    msg.className = `sherpa-message sherpa-${sender}`;

    if (sender === "assistant") {
      // Render markdown
      msg.innerHTML = window.marked ? marked.parse(text) : text;
      msg.classList.add("sherpa-markdown");
    } else {
      msg.textContent = text;
    }

    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    return msg;
  }

  function showSuggestions() {
    const suggestionsDiv = document.getElementById("sherpa-suggestions");
    suggestionsDiv.innerHTML =
      "<div class='sherpa-suggestions-label'>Popular queries:</div>";

    SUGGESTIONS.forEach((suggestion) => {
      const btn = document.createElement("button");
      btn.className = "sherpa-suggestion-btn";
      btn.textContent = suggestion.text;
      btn.onclick = () => {
        document.getElementById("chatbot-input").value = suggestion.text;
        sendMessage();
      };
      suggestionsDiv.appendChild(btn);
    });
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

    // Hide suggestions
    document.getElementById("sherpa-suggestions").style.display = "none";

    // Add user message
    addMessageToUI(message, "user");
    input.value = "";
    chatHistory.push({ role: "user", content: message });

    // Add loading indicator
    const loadingMsg = document.createElement("div");
    loadingMsg.className = "sherpa-message sherpa-assistant sherpa-loading";
    loadingMsg.innerHTML =
      '<span class="sherpa-typing">●</span><span class="sherpa-typing">●</span><span class="sherpa-typing">●</span>';
    document.getElementById("chatbot-messages").appendChild(loadingMsg);
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

      const data = await res.json();

      // Remove loading indicator
      loadingMsg.remove();

      if (data.success) {
        if (data.conversation_id) {
          conversationId = data.conversation_id;
        }

        // Add response with streaming effect
        const responseMsg = addMessageToUI("", "assistant");
        responseMsg.innerHTML = ""; // Clear initial content

        // Stream response word by word
        const words = data.message.split(" ");
        let wordIndex = 0;

        const streamWord = () => {
          if (wordIndex < words.length) {
            responseMsg.innerHTML = window.marked
              ? marked.parse(words.slice(0, wordIndex + 1).join(" "))
              : words.slice(0, wordIndex + 1).join(" ");
            wordIndex++;
            document.getElementById("chatbot-messages").scrollTop =
              document.getElementById("chatbot-messages").scrollHeight;
            setTimeout(streamWord, 30); // 30ms between words for smooth effect
          }
        };

        streamWord();

        chatHistory.push({
          role: "assistant",
          content: data.message,
        });

        saveChatHistory();
      } else {
        addMessageToUI("❌ Error: " + (data.error || "Unknown error"), "error");
      }
    } catch (err) {
      loadingMsg.remove();
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
        "नमस्ते! मैं Sherpa हूँ, आपका Nepal ट्रैवल गाइड 🇳🇵\n\nHello! I'm Sherpa, your Nepal travel expert 🇳🇵",
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
  }

  // ==================== INITIALIZE ====================

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
