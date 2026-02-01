/**
 * Customer Service AI Widget
 * 
 * Embeddable chat widget untuk website
 * 
 * Usage:
 * <script>
 *   window.CSAIConfig = {
 *     widgetId: 'your-widget-id'
 *   };
 * </script>
 * <script src="https://cekat.biz.id/widget/widget.js"></script>
 */

(function () {
  'use strict';

  // Detect script origin to build absolute API URL
  function getScriptOrigin() {
    const scripts = document.getElementsByTagName('script');
    for (let i = 0; i < scripts.length; i++) {
      const src = scripts[i].src;
      // Check for both widget.js and widget.min.js
      if (src && (src.includes('widget.min.js') || src.includes('widget.js'))) {
        // Extract origin from script URL
        try {
          const url = new URL(src);
          return url.origin;
        } catch (e) {
          console.warn('CSAI: Failed to parse script URL');
        }
      }
    }
    // Fallback to current origin
    return window.location.origin;
  }

  const scriptOrigin = getScriptOrigin();

  // Default configuration
  const defaultConfig = {
    widgetId: 'default',
    apiUrl: scriptOrigin + '/api/chat',
    configUrl: scriptOrigin + '/api/widget/',
    position: 'bottom-right',
    primaryColor: '#6366f1',
    textColor: '#ffffff',
    greeting: 'Halo! 👋 Ada yang bisa saya bantu hari ini?',
    placeholder: 'Ketik pesan...',
    title: 'Customer Service',
    subtitle: 'Biasanya membalas dalam beberapa detik',
    offlineMessage: 'Maaf, layanan sedang tidak tersedia. Silakan coba lagi nanti.',
    storageKey: 'csai_chat_history',
    maxHistoryLength: 50,
    // Avatar settings
    avatarType: 'icon',
    avatarIcon: 'robot',
    avatarUrl: '',
    // Branding settings
    showBranding: true,
    // Auto-close settings
    inactivityTimeout: 90000, // 90 seconds
    closeOfferTimeout: 60000, // 60 seconds after offer
    enableEmoji: true,
    autoCloseEnabled: true
  };

  // Merge with user config (initial)
  let config = { ...defaultConfig, ...(window.CSAIConfig || {}) };

  // State
  let isOpen = false;
  let isLoading = false;
  let chatHistory = [];
  let sessionId = null;
  let configLoaded = false;

  // New State for Enhancement
  let inactivityTimer = null;
  let closeOfferSent = false;
  let closeOfferTimer = null;
  let emojiPickerOpen = false;

  // Emoji Dictionary
  const EMOJI_SET = {
    'Sering': ['😊', '👍', '❤️', '🙏', '😂', '🤔', '👋', '✅'],
    'Wajah': ['😀', '😃', '😄', '😁', '😅', '🤣', '😇', '🥰', '😍', '🤗'],
    'Gesture': ['👍', '👎', '👌', '✌️', '🤝', '👏', '🙌', '💪'],
    'Simbol': ['✅', '❌', '⭐', '💯', '🔥', '💡', '📌', '🎉']
  };

  // Fetch config from API
  async function fetchConfig(widgetId) {
    try {
      const response = await fetch(scriptOrigin + '/api/widget/' + widgetId + '/config');
      if (response.ok) {
        const serverConfig = await response.json();
        // Merge server config with existing config (server takes priority)
        config = { ...config, ...serverConfig };
        config.apiUrl = scriptOrigin + '/api/chat';
        return true;
      }
    } catch (e) {
      console.warn('CSAI: Failed to fetch widget config');
    }
    return false;
  }

  // Domain validation - check if widget is allowed on current domain
  function isDomainAllowed() {
    const allowedDomain = config.allowedDomain || '';

    // If no domain restriction, allow all (backwards compatible)
    if (!allowedDomain || allowedDomain.trim() === '') {
      return true;
    }

    const currentHost = window.location.hostname.toLowerCase();
    const allowed = allowedDomain.toLowerCase().trim();

    // Allow localhost for development
    if (currentHost === 'localhost' || currentHost === '127.0.0.1') {
      return true;
    }

    // Exact match
    if (currentHost === allowed) {
      return true;
    }

    // Subdomain match: check if current host ends with .allowed
    // e.g., allowed = "mysite.com" matches "www.mysite.com", "blog.mysite.com"
    if (currentHost.endsWith('.' + allowed)) {
      return true;
    }

    // www prefix handling: "mysite.com" should match "www.mysite.com" and vice versa
    if (allowed.startsWith('www.')) {
      const withoutWww = allowed.substring(4);
      if (currentHost === withoutWww || currentHost.endsWith('.' + withoutWww)) {
        return true;
      }
    } else {
      if (currentHost === 'www.' + allowed) {
        return true;
      }
    }

    return false;
  }

  // Show domain error message
  function showDomainError() {
    console.error('CSAI Widget Error: This widget is not authorized for this domain.');
    console.error('Current domain: ' + window.location.hostname);
    console.error('Allowed domain: ' + (config.allowedDomain || 'Not configured'));
    console.error('Please configure the allowed domain in your Cekat dashboard.');
  }

  // Generate unique session ID
  function generateSessionId() {
    return 'sess_' + Math.random().toString(36).substr(2, 9) + Date.now().toString(36);
  }

  // Load chat history from localStorage
  function loadHistory() {
    try {
      const stored = localStorage.getItem(config.storageKey + '_' + config.widgetId);
      if (stored) {
        const data = JSON.parse(stored);
        chatHistory = data.history || [];
        sessionId = data.sessionId || generateSessionId();
      } else {
        sessionId = generateSessionId();
      }
    } catch (e) {
      sessionId = generateSessionId();
    }
  }

  // Save chat history to localStorage
  function saveHistory() {
    try {
      localStorage.setItem(config.storageKey + '_' + config.widgetId, JSON.stringify({
        history: chatHistory.slice(-config.maxHistoryLength),
        sessionId: sessionId
      }));
    } catch (e) {
      console.warn('CSAI: Failed to save chat history');
    }
  }

  // Clear chat history
  function clearHistory() {
    try {
      localStorage.removeItem(config.storageKey + '_' + config.widgetId);
      chatHistory = [];
      sessionId = generateSessionId();
      const messagesEl = document.getElementById('csai-messages');
      if (messagesEl) messagesEl.innerHTML = '';
    } catch (e) {
      console.error('CSAI: Failed to clear history');
    }
  }

  // Reset Inactivity Timer
  function resetInactivityTimer() {
    if (!isOpen || !config.autoCloseEnabled) return;

    // Clear existing timers
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (closeOfferTimer) clearTimeout(closeOfferTimer);

    closeOfferSent = false;

    // Set new timer
    inactivityTimer = setTimeout(showCloseOffer, config.inactivityTimeout);
  }

  // Show Close Offer (Bot Message)
  function showCloseOffer() {
    if (!isOpen || closeOfferSent) return;

    closeOfferSent = true;

    const offerHtml = `
      Apakah Anda masih membutuhkan bantuan? 
      <div class="csai-quick-actions">
        <button class="csai-quick-btn" onclick="window.CSAI_continueChat()">Ya, lanjutkan</button>
        <button class="csai-quick-btn danger" onclick="window.CSAI_endChat()">Tutup percakapan</button>
      </div>
    `;

    addMessage('assistant', offerHtml, true); // Skip adding to permanent history

    // Set auto-close timer if no response
    closeOfferTimer = setTimeout(() => {
      if (isOpen) {
        toggleChat(); // Minimize
      }
    }, config.closeOfferTimeout);
  }

  // Global functions for inline onclick handlers
  window.CSAI_continueChat = function () {
    resetInactivityTimer();
    // Remove the offer message or just add a system note?
    // For simplicity, we just insert a user action note
    // addMessage('user', 'Dilanjutkan...', true);
  };

  window.CSAI_endChat = function () {
    clearHistory();
    toggleChat();
  };

  // Inject CSS styles
  function injectStyles() {
    const styles = `
      .csai-widget * {
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      }

      .csai-widget {
        position: fixed;
        z-index: 999999;
        font-size: 14px;
        line-height: normal;
        text-align: left;
        color: #1e293b;
      }

      /* Floating Button */
      .csai-button {
        position: fixed;
        ${config.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
        ${config.position.includes('bottom') ? 'bottom: 20px;' : 'top: 20px;'}
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, ${config.primaryColor}, ${adjustColor(config.primaryColor, -20)});
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 1000000;
      }

      .csai-button:hover {
        transform: scale(1.05);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.2);
      }

      .csai-button svg {
        width: 28px;
        height: 28px;
        fill: ${config.textColor};
        transition: all 0.3s ease;
      }

      .csai-button.open svg.chat-icon { display: none; }
      .csai-button.open svg.close-icon { display: block; }
      .csai-button svg.close-icon { display: none; }

      .csai-button.hidden {
        display: none !important;
      }

      /* Notification Badge */
      .csai-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #ef4444;
        color: white;
        font-size: 12px;
        font-weight: bold;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: none;
        align-items: center;
        justify-content: center;
        border: 2px solid white;
      }

      /* Chat Window - Mobile First (Full Screen default for small screens) */
      .csai-window {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100dvh;
        background: #ffffff;
        display: none;
        flex-direction: column;
        overflow: hidden;
        animation: csai-slide-up 0.3s ease;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        z-index: 1000001; /* Above button */
      }

      @media (min-width: 481px) {
        .csai-window {
          position: fixed;
          top: auto;
          left: auto;
          ${config.position.includes('right') ? 'right: 20px;' : 'left: 20px;'}
          bottom: 100px;
          width: 380px;
          height: 600px;
          max-height: calc(100vh - 120px);
          border-radius: 16px;
        }
      }

      .csai-window.open {
        display: flex;
      }

      @keyframes csai-slide-up {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }

      /* Header */
      .csai-header {
        background: linear-gradient(135deg, ${config.primaryColor}, ${adjustColor(config.primaryColor, -20)});
        color: ${config.textColor};
        padding: 16px 20px;
        padding-top: max(16px, env(safe-area-inset-top)); /* Handle notch */
        display: flex;
        align-items: center;
        gap: 12px;
        flex-shrink: 0;
      }

      .csai-header-avatar {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
      }

      .csai-header-info {
        flex: 1;
        min-width: 0; /* Prevent flex overflow */
      }

      .csai-header-title {
        font-size: 16px;
        font-weight: 600;
        margin: 0 0 2px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .csai-header-subtitle {
        font-size: 12px;
        opacity: 0.9;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .csai-header-subtitle::before {
        content: '';
        width: 8px;
        height: 8px;
        background: #22c55e;
        border-radius: 50%;
        display: inline-block;
      }

      /* Close Button in Header */
      .csai-header-close {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        flex-shrink: 0;
      }

      .csai-header-close:hover {
        background: rgba(255, 255, 255, 0.3);
      }

      .csai-header-close svg {
        width: 20px;
        height: 20px;
        fill: ${config.textColor};
      }

      /* Messages Container */
      .csai-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        background: #f8fafc;
        -webkit-overflow-scrolling: touch; /* Smooth scroll on iOS */
      }

      /* Message Bubbles */
      .csai-message {
        max-width: 85%;
        padding: 12px 16px;
        border-radius: 16px;
        line-height: 1.5;
        font-size: 14px;
        word-wrap: break-word;
        animation: csai-fade-in 0.3s ease;
      }

      @keyframes csai-fade-in {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .csai-message.user {
        background: ${config.primaryColor};
        color: ${config.textColor};
        margin-left: auto;
        border-bottom-right-radius: 4px;
      }

      .csai-message.assistant {
        background: #ffffff;
        color: #1e293b;
        margin-right: auto;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      }

      /* Typing Indicator */
      .csai-typing {
        display: flex;
        gap: 4px;
        padding: 16px;
        background: #ffffff;
        border-radius: 16px;
        border-bottom-left-radius: 4px;
        width: fit-content;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
      }

      .csai-typing span {
        width: 8px;
        height: 8px;
        background: #94a3b8;
        border-radius: 50%;
        animation: csai-bounce 1.4s infinite ease-in-out;
      }

      .csai-typing span:nth-child(1) { animation-delay: -0.32s; }
      .csai-typing span:nth-child(2) { animation-delay: -0.16s; }
      .csai-typing span:nth-child(3) { animation-delay: 0s; }

      @keyframes csai-bounce {
        0%, 80%, 100% { transform: scale(0.6); }
        40% { transform: scale(1); }
      }

      /* Input Area */
      .csai-input-area {
        padding: 12px 16px;
        padding-bottom: max(12px, env(safe-area-inset-bottom)); /* Handle notch */
        background: #ffffff;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 8px;
        align-items: flex-end;
        position: relative;
        flex-shrink: 0;
      }

      .csai-input {
        flex: 1;
        border: 1px solid #e2e8f0;
        border-radius: 24px;
        padding: 12px 16px;
        font-size: 16px; /* 16px prevents zoom on iOS */
        outline: none;
        resize: none;
        max-height: 100px;
        min-height: 48px; /* Touch friendly */
        color: #1e293b !important;
        background-color: #ffffff !important;
        line-height: 1.5 !important;
        box-shadow: none;
        -webkit-appearance: none;
      }

      /* Specificity Fixes */
      .csai-widget .csai-input, 
      .csai-window .csai-input,
      textarea.csai-input,
      #csai-input {
        color: #1e293b !important;
        background-color: #ffffff !important;
        -webkit-text-fill-color: #1e293b !important;
      }
      
      .csai-input::placeholder {
        color: #94a3b8 !important;
        opacity: 1;
        -webkit-text-fill-color: #94a3b8 !important;
      }

      .csai-input:focus {
        border-color: ${config.primaryColor};
        box-shadow: 0 0 0 3px ${config.primaryColor}20;
      }

      /* Action Buttons */
      .csai-action-btn {
        width: 48px; /* Touch friendly */
        height: 48px;
        border-radius: 50%;
        background: transparent;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s;
        flex-shrink: 0;
        color: #64748b;
      }

      .csai-action-btn:hover {
        background: #f1f5f9;
        color: ${config.primaryColor};
      }
      
      .csai-send-btn {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: ${config.primaryColor};
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        flex-shrink: 0;
        color: ${config.textColor};
      }
      
      .csai-send-btn:hover:not(:disabled) {
        background: ${adjustColor(config.primaryColor, -15)};
        transform: scale(1.05);
      }
      
      .csai-send-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }

      .csai-send-btn svg, .csai-action-btn svg {
        width: 24px;
        height: 24px;
        fill: currentColor;
      }

      /* Emoji Picker */
      .csai-emoji-picker {
        position: absolute;
        bottom: 100%;
        left: 0;
        width: 100%;
        background: white;
        border-top: 1px solid #e2e8f0;
        box-shadow: 0 -4px 12px rgba(0,0,0,0.1);
        display: none;
        flex-direction: column;
        max-height: 250px;
        overflow: hidden;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
      }

      .csai-emoji-picker.open {
        display: flex;
      }

      .csai-emoji-header {
        display: flex;
        overflow-x: auto;
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        gap: 8px;
      }
      
      .csai-emoji-category {
        padding: 4px 8px;
        font-size: 12px;
        border-radius: 12px;
        background: #f1f5f9;
        cursor: pointer;
        white-space: nowrap;
      }
      
      .csai-emoji-category.active {
        background: ${config.primaryColor};
        color: white;
      }

      .csai-emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        padding: 12px;
        gap: 8px;
        overflow-y: auto;
      }

      .csai-emoji-item {
        font-size: 24px;
        cursor: pointer;
        text-align: center;
        padding: 4px;
        border-radius: 4px;
        transition: background 0.2s;
      }

      .csai-emoji-item:hover {
        background: #f1f5f9;
      }

      /* Auto-Close Offer Actions */
      .csai-quick-actions {
        display: flex;
        gap: 8px;
        margin-top: 8px;
        flex-wrap: wrap;
      }

      .csai-quick-btn {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 13px;
        cursor: pointer;
        border: 1px solid ${config.primaryColor};
        background: white;
        color: ${config.primaryColor};
        transition: all 0.2s;
      }

      .csai-quick-btn:hover {
        background: ${config.primaryColor};
        color: white;
      }
      
      .csai-quick-btn.danger {
        border-color: #ef4444;
        color: #ef4444;
      }
      
      .csai-quick-btn.danger:hover {
        background: #ef4444;
        color: white;
      }

      /* Powered By */
      .csai-powered {
        text-align: center;
        padding: 8px;
        padding-bottom: max(8px, env(safe-area-inset-bottom));
        font-size: 11px;
        color: #94a3b8;
        background: #f8fafc;
      }

      .csai-powered a {
        color: ${config.primaryColor};
        text-decoration: none;
      }

      /* Markdown Styles */
      .csai-ul, .csai-ol { margin: 8px 0; padding-left: 20px; }
      .csai-ul li { list-style-type: disc; margin-bottom: 4px; }
      .csai-ol li { list-style-type: decimal; margin-bottom: 4px; }
      strong { font-weight: 600; }
      em { font-style: italic; }
      code { background: #f1f5f9; padding: 2px 4px; border-radius: 4px; font-family: monospace; font-size: 0.9em; color: #e11d48; }
      pre { background: #1e293b; color: #fff; padding: 12px; border-radius: 8px; overflow-x: auto; margin: 8px 0; }
      pre code { background: transparent; color: inherit; padding: 0; }
      .csai-link { color: #2563eb; text-decoration: underline; }
    `;

    // Hide scrollbar for input but keep functionality
    const extraStyles = `
      .csai-input::-webkit-scrollbar {
        display: none;
      }
      .csai-input {
        -ms-overflow-style: none;
        scrollbar-width: none;
      }
      /* Hide scrollbar for emoji grid but keep functionality */
      .csai-emoji-grid::-webkit-scrollbar {
        width: 4px;
      }
      .csai-emoji-grid::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 2px;
      }
    `;

    const styleEl = document.createElement('style');
    styleEl.id = 'csai-styles';
    styleEl.textContent = styles + extraStyles;
    document.head.appendChild(styleEl);
  }

  // Adjust color brightness
  function adjustColor(color, amount) {
    const hex = color.replace('#', '');
    const num = parseInt(hex, 16);
    const r = Math.min(255, Math.max(0, (num >> 16) + amount));
    const g = Math.min(255, Math.max(0, ((num >> 8) & 0x00FF) + amount));
    const b = Math.min(255, Math.max(0, (num & 0x0000FF) + amount));
    return '#' + (0x1000000 + r * 0x10000 + g * 0x100 + b).toString(16).slice(1);
  }

  // Get avatar HTML based on config
  function getAvatarHtml() {
    // If custom avatar URL is provided
    if (config.avatarType === 'url' && config.avatarUrl) {
      return `<img src="${config.avatarUrl}" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
    }

    // Icon-based avatars
    const icons = {
      robot: `<svg viewBox="0 0 24 24" fill="currentColor" style="width:24px;height:24px;">
        <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2M7.5 13A1.5 1.5 0 006 14.5 1.5 1.5 0 007.5 16 1.5 1.5 0 009 14.5 1.5 1.5 0 007.5 13m9 0a1.5 1.5 0 00-1.5 1.5 1.5 1.5 0 001.5 1.5 1.5 1.5 0 001.5-1.5 1.5 1.5 0 00-1.5-1.5M12 9a5 5 0 00-5 5v1h10v-1a5 5 0 00-5-5z"/>
      </svg>`,
      support: `<svg viewBox="0 0 24 24" fill="currentColor" style="width:24px;height:24px;">
        <path d="M12 1c-4.97 0-9 4.03-9 9v7c0 1.66 1.34 3 3 3h3v-8H5v-2c0-3.87 3.13-7 7-7s7 3.13 7 7v2h-4v8h3c1.66 0 3-1.34 3-3v-7c0-4.97-4.03-9-9-9z"/>
      </svg>`,
      user: `<svg viewBox="0 0 24 24" fill="currentColor" style="width:24px;height:24px;">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
      </svg>`
    };

    return icons[config.avatarIcon] || icons.robot;
  }

  // Generate Emoji Picker HTML
  function createEmojiPickerHtml() {
    let categoriesHtml = '';
    let gridsHtml = '';

    // First category is active by default
    let isFirst = true;

    for (const [category, emojis] of Object.entries(EMOJI_SET)) {
      categoriesHtml += `<div class="csai-emoji-category ${isFirst ? 'active' : ''}" data-category="${category}">${category}</div>`;

      // We'll show all emojis in one grid for simplicity, or we could separate them
      // For now, let's put them all in one grid but use the categories for filtering if we wanted (not implemented here)
      // Actually, simple list is better for MVP
      emojis.forEach(emoji => {
        gridsHtml += `<div class="csai-emoji-item" onclick="window.CSAI_insertEmoji('${emoji}')">${emoji}</div>`;
      });

      isFirst = false;
    }

    return `
      <div class="csai-emoji-picker" id="csai-emoji-picker">
        <div class="csai-emoji-header" id="csai-emoji-header">
          ${categoriesHtml}
        </div>
        <div class="csai-emoji-grid">
          ${gridsHtml}
        </div>
      </div>
    `;
  }

  // Create widget HTML
  function createWidget() {
    const widget = document.createElement('div');
    widget.className = 'csai-widget';
    widget.id = 'csai-widget';

    widget.innerHTML = `
      <!-- Floating Button -->
      <button class="csai-button" id="csai-toggle">
        <svg class="chat-icon" viewBox="0 0 24 24">
          <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
          <path d="M7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/>
        </svg>
        <svg class="close-icon" viewBox="0 0 24 24">
          <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
        </svg>
        <span class="csai-badge" id="csai-badge">0</span>
      </button>

      <!-- Chat Window -->
      <div class="csai-window" id="csai-window">
        <!-- Header -->
        <div class="csai-header">
          <div class="csai-header-avatar">${getAvatarHtml()}</div>
          <div class="csai-header-info">
            <p class="csai-header-title">${config.title}</p>
            <p class="csai-header-subtitle">${config.subtitle}</p>
          </div>
          <!-- Close Button Integrated in Header -->
          <button class="csai-header-close" id="csai-close">
            <svg viewBox="0 0 24 24">
              <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            </svg>
          </button>
        </div>

        <!-- Messages -->
        <div class="csai-messages" id="csai-messages"></div>

        <!-- Input Area -->
        <div class="csai-input-area">
          ${config.enableEmoji ? createEmojiPickerHtml() : ''}
          
          ${config.enableEmoji ? `
            <button class="csai-action-btn" id="csai-emoji-btn">
              <svg viewBox="0 0 24 24">
                <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
              </svg>
            </button>
          ` : ''}

          <textarea 
            class="csai-input" 
            id="csai-input" 
            placeholder="${config.placeholder}"
            rows="1"
          ></textarea>
          
          <button class="csai-send-btn" id="csai-send">
            <svg viewBox="0 0 24 24">
              <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
          </button>
        </div>

        <!-- Powered By -->
        ${config.showBranding ? `<div class="csai-powered">
          Powered by <a href="https://cekat.biz.id" target="_blank">cekat.biz.id</a>
        </div>` : ''}
      </div>
    `;

    document.body.appendChild(widget);
  }

  // Insert Emoji
  window.CSAI_insertEmoji = function (emoji) {
    const inputEl = document.getElementById('csai-input');
    const start = inputEl.selectionStart;
    const end = inputEl.selectionEnd;
    const text = inputEl.value;
    const before = text.substring(0, start);
    const after = text.substring(end, text.length);

    inputEl.value = before + emoji + after;
    inputEl.selectionStart = inputEl.selectionEnd = start + emoji.length;
    inputEl.focus();

    resetInactivityTimer();

    // Trigger input event to resize
    inputEl.dispatchEvent(new Event('input'));
  };

  // Toggle Chat with Auto-Close Logic
  function toggleChat() {
    const widget = document.getElementById('csai-widget');
    const windowEl = document.getElementById('csai-window');
    const buttonEl = document.getElementById('csai-toggle');
    const badgeEl = document.getElementById('csai-badge');
    const inputEl = document.getElementById('csai-input');

    isOpen = !isOpen;

    if (isOpen) {
      windowEl.classList.add('open');
      buttonEl.classList.add('open');
      // Hide badge
      badgeEl.style.display = 'none';
      badgeEl.textContent = '0';

      // Also hide main button if on mobile (to avoid overlap or distraction?) 
      // Actually usually we want to keep it to close, but in full screen we used header close
      // Let's hide the floating button when open on mobile to make room
      if (window.innerWidth <= 480) {
        buttonEl.classList.add('hidden');
      }

      // Focus input (with slight delay for animation)
      setTimeout(() => {
        if (inputEl) inputEl.focus();
      }, 300);

      // Scroll to bottom
      setTimeout(() => {
        const messagesEl = document.getElementById('csai-messages');
        if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
      }, 100);

      // Start inactivity timer
      resetInactivityTimer();

    } else {
      windowEl.classList.remove('open');
      buttonEl.classList.remove('open');
      buttonEl.classList.remove('hidden'); // Show button again

      // Clear timers
      if (inactivityTimer) clearTimeout(inactivityTimer);
      if (closeOfferTimer) clearTimeout(closeOfferTimer);
      closeOfferSent = false;
    }
  }

  // Toggle Emoji Picker
  function toggleEmojiPicker() {
    const picker = document.getElementById('csai-emoji-picker');
    if (!picker) return;

    emojiPickerOpen = !emojiPickerOpen;
    if (emojiPickerOpen) {
      picker.classList.add('open');
    } else {
      picker.classList.remove('open');
    }

    resetInactivityTimer();
  }

  // Add message to UI
  function addMessage(role, content, skipHistory = false) {
    const messagesEl = document.getElementById('csai-messages');
    const messageEl = document.createElement('div');
    messageEl.className = `csai-message ${role}`;

    // Parse Markdown for assistant
    if (role === 'assistant') {
      messageEl.innerHTML = parseMarkdown(content);
    } else {
      messageEl.textContent = content; // Keep user input as plain text for safety
    }

    messagesEl.appendChild(messageEl);
    messagesEl.scrollTop = messagesEl.scrollHeight;

    if (!skipHistory) {
      chatHistory.push({ role, content });
      saveHistory();
    }

    resetInactivityTimer();
  }

  // Simple Markdown Parser
  function parseMarkdown(text) {
    if (!text) return '';
    let placeholders = [];

    // 1. Hide Code Blocks and stash them
    text = text.replace(/```([\s\S]*?)```/g, function (match) {
      placeholders.push(match);
      return `__CODE_BLOCK_${placeholders.length - 1}__`;
    });

    // 2. Sanitize remaining text (Basic XSS protection)
    text = text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");

    // 3. Inline Formats
    text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
    text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');

    // 4. Links (Simple URL detection)
    text = text.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" class="csai-link">$1</a>');

    // 5. Lists (Regex)
    // Unordered
    text = text.replace(/^\s*-\s+(.*)$/gm, '<li class="ul-item">$1</li>');
    // Wrap UL groups
    text = text.replace(/((?:<li class="ul-item">.*<\/li>\n?)+)/g, '<ul class="csai-ul">$1</ul>');

    // Ordered
    text = text.replace(/^\s*\d+\.\s+(.*)$/gm, '<li class="ol-item">$1</li>');
    // Wrap OL groups
    text = text.replace(/((?:<li class="ol-item">.*<\/li>\n?)+)/g, '<ol class="csai-ol">$1</ol>');

    // 6. Newlines to <br>, but be careful around lists
    // We already wrapped lists in <ul>...</ul>, so we replace \n that are NOT inside tags? 
    // Simplify: replace \n with <br>, but remove <br> after </ul> or </ol> or </pre>
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/(<\/ul>|<\/ol>|<\/pre>|<pre>)<br>/g, '$1');

    // 7. Restore Code Blocks
    text = text.replace(/__CODE_BLOCK_(\d+)__/g, function (match, id) {
      let code = placeholders[id];
      // Strip backticks
      code = code.substring(3, code.length - 3);
      // Sanitize code content
      code = code.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
      return `<pre><code>${code}</code></pre>`;
    });

    return text;
  }

  // Show typing indicator
  function showTyping() {
    const messagesEl = document.getElementById('csai-messages');
    const typingEl = document.createElement('div');
    typingEl.className = 'csai-typing';
    typingEl.id = 'csai-typing';
    typingEl.innerHTML = '<span></span><span></span><span></span>';
    messagesEl.appendChild(typingEl);
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  // Hide typing indicator
  function hideTyping() {
    const typingEl = document.getElementById('csai-typing');
    if (typingEl) typingEl.remove();
  }

  // Send message to API
  async function sendMessage(message) {
    if (isLoading || !message.trim()) return;

    isLoading = true;
    const sendBtn = document.getElementById('csai-send');
    sendBtn.disabled = true;

    // Add user message
    addMessage('user', message);

    // Show typing
    showTyping();

    try {
      const response = await fetch(config.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          message: message,
          widgetId: config.widgetId,
          history: chatHistory.map(m => ({
            role: m.role,
            content: m.content
          })),
          sessionId: sessionId
        })
      });

      const data = await response.json();

      hideTyping();

      if (data.success && data.response) {
        addMessage('assistant', data.response);
        if (data.sessionId) sessionId = data.sessionId;
      } else if (data.error === 'quota_exceeded') {
        // Handle quota exceeded error
        addMessage('assistant', '⚠️ **Kuota Pesan Habis**\n\nMaaf, chatbot ini sudah mencapai batas pesan bulanan.\n\nSilakan hubungi pemilik website untuk informasi lebih lanjut.');
      } else {
        addMessage('assistant', data.message || data.error || config.offlineMessage);
      }
    } catch (error) {
      console.error('CSAI Error:', error);
      hideTyping();
      addMessage('assistant', config.offlineMessage);
    } finally {
      isLoading = false;
      sendBtn.disabled = false;
    }
  }

  // Toggle chat window
  function toggleChat() {
    isOpen = !isOpen;
    const windowEl = document.getElementById('csai-window');
    const buttonEl = document.getElementById('csai-toggle');

    if (isOpen) {
      windowEl.classList.add('open');
      buttonEl.classList.add('open');
      document.getElementById('csai-input').focus();

      // Show greeting if first time
      if (chatHistory.length === 0) {
        setTimeout(() => {
          addMessage('assistant', config.greeting);
          // Scroll to bottom after greeting
          const messagesEl = document.getElementById('csai-messages');
          if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
        }, 500);
      }

      // Always scroll to bottom when opening
      setTimeout(() => {
        const messagesEl = document.getElementById('csai-messages');
        if (messagesEl) messagesEl.scrollTop = messagesEl.scrollHeight;
      }, 100);

    } else {
      windowEl.classList.remove('open');
      buttonEl.classList.remove('open');
    }
  }

  // Render chat history
  function renderHistory() {
    const messagesEl = document.getElementById('csai-messages');
    messagesEl.innerHTML = '';

    chatHistory.forEach(msg => {
      addMessage(msg.role, msg.content, true);
    });
  }

  // Initialize event listeners
  function initEventListeners() {
    const toggleBtn = document.getElementById('csai-toggle');
    const closeBtn = document.getElementById('csai-close');
    const sendBtn = document.getElementById('csai-send');
    const inputEl = document.getElementById('csai-input');
    const emojiBtn = document.getElementById('csai-emoji-btn');
    const messagesEl = document.getElementById('csai-messages');

    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    // Emoji button
    if (emojiBtn) {
      emojiBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        toggleEmojiPicker();
      });
    }

    // Emoji categories
    const categories = document.querySelectorAll('.csai-emoji-category');
    categories.forEach(cat => {
      cat.addEventListener('click', (e) => {
        // Toggle active class
        document.querySelectorAll('.csai-emoji-category').forEach(c => c.classList.remove('active'));
        e.target.classList.add('active');

        // Filter emojis (placeholder for now as we show all)
        // In full implementation, we would hide/show emoji items based on category
        const category = e.target.dataset.category;
        console.log('Selected category:', category);
      });
    });

    sendBtn.addEventListener('click', () => {
      sendMessage(inputEl.value);
      inputEl.value = '';
      inputEl.style.height = 'auto';
      resetInactivityTimer();
    });

    inputEl.addEventListener('keydown', (e) => {
      resetInactivityTimer();
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(inputEl.value);
        inputEl.value = '';
        inputEl.style.height = 'auto';
      }
    });

    // Auto-resize textarea
    inputEl.addEventListener('input', () => {
      inputEl.style.height = 'auto';
      inputEl.style.height = Math.min(inputEl.scrollHeight, 100) + 'px';
      resetInactivityTimer();
    });

    // Reset timer on scroll
    messagesEl.addEventListener('scroll', () => {
      resetInactivityTimer();
    });

    // Close emoji picker on click outside
    document.addEventListener('click', (e) => {
      const picker = document.getElementById('csai-emoji-picker');
      const emojiBtn = document.getElementById('csai-emoji-btn');

      if (emojiPickerOpen && picker && !picker.contains(e.target) && emojiBtn && !emojiBtn.contains(e.target)) {
        toggleEmojiPicker();
      }
    });
  }

  // Initialize widget
  async function init() {
    if (document.getElementById('csai-widget')) return;

    // If only widgetId is provided, fetch full config from server
    const userConfig = window.CSAIConfig || {};
    const hasMinimalConfig = userConfig.widgetId && !userConfig.title;

    if (hasMinimalConfig && userConfig.widgetId !== 'default') {
      await fetchConfig(userConfig.widgetId);
    }

    // Check domain authorization
    if (!isDomainAllowed()) {
      showDomainError();
      return; // Don't initialize widget if domain not allowed
    }

    loadHistory();
    injectStyles();
    createWidget();
    initEventListeners();
    renderHistory();

    console.log('CSAI Widget initialized');

    // Auto-open after 3 seconds
    setTimeout(() => {
      if (!isOpen && !localStorage.getItem('csai_closed_once')) {
        // Only auto-open if never explicitly closed (optional improvement)
        // For now keep existing behavior
        toggleChat();
      }
    }, 3000);
  }

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
