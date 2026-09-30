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

  // Detect script origin  // Cekat AI Widget v2026.02.01 - Mobile First & Auto Close
  console.log('CSAI Widget v2026.02.01 loaded');

  // Helper function to get script origin
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
    closeGraceTimeout: 5000, // show closing summary this long before auto-minimize
    enableEmoji: true,
    autoCloseEnabled: true,
    // AI model (server config override when available)
    model: null,
    // Pre-chat form (Strategy 3) - server config overrides when enabled
    leadForm: null
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
  let minimizeTimer = null;
  let autoScrollUntil = 0;
  let emojiPickerOpen = false;
  // One closing per conversation: once the auto-close flow has run, no
  // new offer/close cycle may start until the visitor sends a message
  // (which begins a fresh conversation). Without this latch the closing
  // message's own activity reset re-armed the idle cycle and the
  // offer -> close -> offer loop repeated forever.
  let conversationClosed = false;
  // Pre-chat form (Strategy 3): details submitted before the first
  // message, attached to that first chat request only.
  let pendingLeadForm = null;

  // Emoji Dictionary
  const EMOJI_SET = {
    'Sering': ['😊', '👍', '❤️', '🙏', '😂', '🤔', '👋', '✅'],
    'Wajah': ['😀', '😃', '😄', '😁', '😅', '🤣', '😇', '🥰', '😍', '🤗'],
    'Gesture': ['👍', '👎', '👌', '✌️', '🤝', '👏', '🙌', '💪'],
    'Simbol': ['✅', '❌', '⭐', '💯', '🔥', '💡', '📌', '🎉']
  };

  // Fetch config from API
  // Returns 'ok' | 'disabled' (404/403/410: inactive widget or blocked
  // domain) | 'error' (network/server failure).
  async function fetchConfig(widgetId) {
    try {
      const response = await fetch(scriptOrigin + '/api/widget/' + widgetId + '/config');
      if (response.ok) {
        const serverConfig = await response.json();
        // Merge server config with existing config (server takes priority)
        config = { ...config, ...serverConfig };
        config.apiUrl = scriptOrigin + '/api/chat';
        return 'ok';
      }
      if (response.status === 404 || response.status === 403 || response.status === 410) {
        return 'disabled';
      }
    } catch (e) {
      console.warn('CSAI: Failed to fetch widget config');
    }
    return 'error';
  }

  // Domain validation - check if widget is allowed on current domain.
  // allowedDomain may contain several comma-separated domains (CSV), e.g.
  // "cekat.biz.id, www.cekat.biz.id" - each entry is matched individually.
  function isDomainAllowed() {
    const allowedDomain = config.allowedDomain || '';

    // If no domain restriction, allow all (backwards compatible)
    if (!allowedDomain || allowedDomain.trim() === '') {
      return true;
    }

    const currentHost = window.location.hostname.toLowerCase();

    // Allow localhost for development
    if (currentHost === 'localhost' || currentHost === '127.0.0.1') {
      return true;
    }

    // The app's own host may always use any widget (landing page, dashboard
    // preview, local dev) - mirrors DomainAccessService::ownHost() server-side.
    try {
      const scriptHost = new URL(scriptOrigin).hostname.toLowerCase();
      if (scriptHost && currentHost === scriptHost) {
        return true;
      }
    } catch (e) {
      // scriptOrigin unparseable - fall through to allowlist matching
    }

    const entries = allowedDomain.split(',')
      .map(entry => entry.trim().toLowerCase())
      .filter(entry => entry !== '');

    for (const entry of entries) {
      // Exact match
      if (currentHost === entry) {
        return true;
      }

      // Subdomain match: entry "mysite.com" matches "www.mysite.com", "blog.mysite.com"
      if (currentHost.endsWith('.' + entry)) {
        return true;
      }

      // www prefix handling: "mysite.com" matches "www.mysite.com" and vice versa
      if (entry.startsWith('www.')) {
        const withoutWww = entry.substring(4);
        if (currentHost === withoutWww || currentHost.endsWith('.' + withoutWww)) {
          return true;
        }
      } else if (currentHost === 'www.' + entry) {
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

  // Redact PII before persisting to localStorage. In-memory chatHistory
  // and the wire keep the raw text (the assistant needs it); only the
  // at-rest copy in the visitor's browser is scrubbed (email, phone,
  // NIK, invoice/order references).
  function redactPII(text) {
    if (typeof text !== 'string') return text;
    return text
      .replace(/[\w.+-]+@[\w-]+\.[\w.-]+/g, '[email dihapus]')
      .replace(/(^|[^0-9])((?:\+?62|0)8[1-9][0-9]{6,11})(?![0-9])/g,
        function (m, pre) { return pre + '[telepon dihapus]'; })
      .replace(/\b[0-9]{16}\b/g, '[nik dihapus]')
      .replace(/\b(invoice|faktur|pesanan|order)(\s+(?:no\.?|nomor|number|id|#)?\s*[:#-]?\s*|\s*[:#-]\s*)([A-Za-z0-9][A-Za-z0-9\-\/]{3,})/gi,
        function (m, kw, sep) { return kw + sep + '[referensi dihapus]'; });
  }

  // Save chat history to localStorage
  function saveHistory() {
    try {
      localStorage.setItem(config.storageKey + '_' + config.widgetId, JSON.stringify({
        history: chatHistory.slice(-config.maxHistoryLength).map(function (m) {
          return { role: m.role, content: redactPII(m.content) };
        }),
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

  // Programmatic scroll helper: flags the scroll so the scroll listener
  // does not treat it as user activity. Without the flag the auto-scroll
  // that follows every addMessage cancelled the close-offer/minimize
  // timers, which made the idle offer repeat forever and the widget never
  // close itself.
  function scrollToBottom(el) {
    if (!el) return;
    autoScrollUntil = Date.now() + 300;
    el.scrollTop = el.scrollHeight;
  }

  // Reset Inactivity Timer
  function resetInactivityTimer() {
    if (!isOpen || !config.autoCloseEnabled) return;

    // Clear existing timers
    if (inactivityTimer) clearTimeout(inactivityTimer);
    if (closeOfferTimer) clearTimeout(closeOfferTimer);
    // Any real visitor activity also cancels a pending auto-minimize
    // (grace period after the closing summary).
    if (minimizeTimer) {
      clearTimeout(minimizeTimer);
      minimizeTimer = null;
    }

    // A pending offer is no longer relevant once the visitor interacts:
    // remove its bubble so a later cycle does not stack a second one.
    if (closeOfferSent) removeOfferBubble();
    closeOfferSent = false;

    // Conversation already ended: activity may cancel the pending
    // minimize, but never re-arms the offer/close cycle.
    if (conversationClosed) return;

    // Set new timer
    inactivityTimer = setTimeout(showCloseOffer, config.inactivityTimeout);
  }

  // Show Close Offer (Bot Message)
  function showCloseOffer() {
    // isLoading: a reply is still in flight - the reply's own addMessage
    // will re-arm the cycle, and closing now would use a session id that
    // has not been signed by the chat response yet (403 on close).
    if (!isOpen || closeOfferSent || conversationClosed || isLoading) return;

    // Nothing to offer when the visitor never actually chatted
    // (opened the widget, glanced, walked away).
    if (!chatHistory.some(function (m) { return m.role === 'user'; })) return;

    const offerHtml = `<div data-csai-offer>
      Apakah Anda masih membutuhkan bantuan?
      <div class="csai-quick-actions">
        <button class="csai-quick-btn" onclick="window.CSAI_continueChat()">Ya, lanjutkan</button>
        <button class="csai-quick-btn danger" onclick="window.CSAI_endChat()">Tutup percakapan</button>
      </div>
    </div>`;

    addMessage('assistant', offerHtml, true, true); // Skip adding to permanent history

    // Set the guard AFTER addMessage: addMessage itself resets activity
    // state, so setting the flag first let it flip back to false and the
    // offer was re-sent every inactivity cycle.
    closeOfferSent = true;

    // No response after closeOfferTimeout -> end the conversation on the
    // server (status ended + AI summary), show it, then auto-minimize.
    closeOfferTimer = setTimeout(function () {
      if (isOpen && closeOfferSent) closeConversation();
    }, config.closeOfferTimeout);
  }

  function removeOfferBubble() {
    const offer = document.querySelector('[data-csai-offer]');
    if (offer) {
      const bubble = offer.closest('.csai-message');
      (bubble || offer).remove();
    }
  }

  // End the conversation server-side WITHOUT deleting it (the DELETE
  // endpoint above is the DSR wipe). Returns {success, noop, summary} or
  // null on network failure. Fire-and-forget callers ignore the promise.
  function closeSessionOnServer() {
    if (!sessionId) return Promise.resolve({ success: true, noop: true, summary: null });
    try {
      return fetch(config.configUrl + 'session/close', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ widgetId: config.widgetId, sessionId: sessionId }),
        keepalive: true
      }).then(function (r) { return r.json(); }).catch(function () { return null; });
    } catch (e) {
      return Promise.resolve(null);
    }
  }

  // Auto-close flow: end session + summary -> show closing message ->
  // rotate to a fresh sessionId -> auto-minimize after the grace period
  // (cancelled by any visitor activity via resetInactivityTimer).
  async function closeConversation() {
    // Latch immediately so nothing that happens during the await (or the
    // closing message's own activity reset) can start another cycle.
    conversationClosed = true;
    const data = await closeSessionOnServer();
    const summary = data && data.summary ? String(data.summary) : null;

    removeOfferBubble();

    const closingText = summary
      ? 'Percakapan ditutup. Berikut ringkasan percakapan Anda:\n\n' + summary
      : 'Percakapan ditutup. Terima kasih sudah menghubungi kami!';
    // Persisted: the summary stays visible when the widget is reopened.
    addMessage('assistant', closingText);

    // Next message starts a brand-new server session; the closed one
    // stays in the dashboard with its summary.
    sessionId = generateSessionId();
    saveHistory();

    minimizeTimer = setTimeout(function () {
      minimizeTimer = null;
      if (isOpen) toggleChat();
    }, config.closeGraceTimeout || 5000);
  }

  // Global functions for inline onclick handlers
  window.CSAI_continueChat = function () {
    removeOfferBubble();
    resetInactivityTimer();
  };

  window.CSAI_endChat = function () {
    // Manual close also ends the session server-side and generates the
    // summary (fire-and-forget; the local wipe must not wait on network).
    closeSessionOnServer();
    clearHistory();
    conversationClosed = true;
    toggleChat();
  };

  // Tell the server to forget this conversation (visitor data-subject
  // request). Fire-and-forget: the local clear must not depend on network.
  function forgetSessionOnServer() {
    if (!sessionId) return;
    let url = config.configUrl + 'session';
    if (config.apiUrl && /\/chat\/?$/.test(config.apiUrl)) {
      url = config.apiUrl.replace(/\/chat\/?$/, '/widget/session');
    }
    try {
      fetch(url, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ widgetId: config.widgetId, sessionId: sessionId }),
        keepalive: true
      }).catch(function () {});
    } catch (e) {}
  }

  // Header trash button: wipe the conversation locally AND on the server.
  window.CSAI_forgetChat = function () {
    if (chatHistory.length > 0 &&
        !window.confirm('Hapus percakapan ini dari perangkat dan server?')) {
      return;
    }
    forgetSessionOnServer();
    clearHistory();
    setTimeout(function () {
      greetingShown = false;
      showGreeting();
    }, 300);
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

      /* Pre-chat form (Strategy 3) - covers the window body, header stays
         clickable on top so the visitor can always close the widget. */
      .csai-prechat {
        position: absolute;
        inset: 0;
        z-index: 5;
        background: #ffffff;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
      }

      .csai-prechat.visible {
        display: flex;
      }

      .csai-prechat-card {
        width: 100%;
        max-width: 300px;
        text-align: center;
      }

      .csai-prechat-title {
        font-size: 17px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 6px;
      }

      .csai-prechat-desc {
        font-size: 13px;
        color: #6b7280;
        margin: 0 0 18px;
        line-height: 1.5;
      }

      .csai-prechat-field {
        display: block;
        text-align: left;
        margin-bottom: 12px;
      }

      .csai-prechat-field span {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 5px;
      }

      .csai-prechat-field input {
        width: 100%;
        box-sizing: border-box;
        padding: 9px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        outline: none;
      }

      .csai-prechat-field input:focus {
        border-color: ${config.primaryColor};
        box-shadow: 0 0 0 3px ${config.primaryColor}33;
      }

      .csai-prechat-error {
        display: none;
        font-size: 12px;
        color: #dc2626;
        margin: 0 0 10px;
      }

      .csai-prechat-submit {
        width: 100%;
        padding: 10px 0;
        border: none;
        border-radius: 8px;
        background: ${config.primaryColor};
        color: ${config.textColor};
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
      }

      .csai-prechat-skip {
        width: 100%;
        margin-top: 8px;
        padding: 8px 0;
        border: none;
        background: transparent;
        color: #6b7280;
        font-size: 13px;
        cursor: pointer;
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
        position: relative;
        z-index: 6; /* Keep close button clickable above the pre-chat form */
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

      /* Clear (Forget) Button in Header */
      .csai-header-clear {
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

      .csai-header-clear:hover {
        background: rgba(255, 255, 255, 0.3);
      }

      .csai-header-clear svg {
        width: 18px;
        height: 18px;
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

      /* Table Styles */
      .csai-table-wrap {
        overflow-x: auto;
        margin: 8px 0;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: #fff;
      }
      .csai-table {
        border-collapse: collapse;
        width: 100%;
        font-size: 13px;
      }
      .csai-table th {
        background: #f8fafc;
        color: #0f172a;
        font-weight: 600;
        text-align: left;
        padding: 8px 12px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
      }
      .csai-table td {
        padding: 7px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        vertical-align: top;
      }
      .csai-table tbody tr:nth-child(even) { background: #f8fafc; }
      .csai-table tbody tr:last-child td { border-bottom: none; }
      .csai-table tbody tr:hover { background: #f1f5f9; }
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

  // Escape text interpolated into innerHTML templates. Widget settings
  // (title/subtitle/placeholder/avatarUrl) are owner-controlled and flow in
  // through the public config API - without escaping they become stored XSS
  // on every embedding page, including our own dashboard previews.
  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  // http(s) absolute or root-relative URL without quotes/whitespace/brackets
  function isRenderableUrl(url) {
    return typeof url === 'string'
      && !/[\s<>"']/.test(url)
      && /^(https?:\/\/|\/(?!\/))/.test(url);
  }

  // Get avatar HTML based on config
  function getAvatarHtml() {
    // Custom uploaded avatar. Customizer stores 'image', admin stores 'url'
    // - accept both (the old check only matched 'url', so user uploads never rendered).
    if ((config.avatarType === 'url' || config.avatarType === 'image') && config.avatarUrl && isRenderableUrl(config.avatarUrl)) {
      return `<img src="${escapeHtml(config.avatarUrl)}" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
    }

    // Lucide-style stroke icons (keep names in sync with the customizer picker
    // + resources/views/components/widget-avatar.blade.php).
    const svgAttr = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:24px;height:24px;"';
    const icons = {
      'bot': `<svg ${svgAttr}><path d="M12 8V4H8"/><rect width="16" height="12" x="4" y="8" rx="2"/><path d="M2 14h2"/><path d="M20 14h2"/><path d="M15 13v2"/><path d="M9 13v2"/></svg>`,
      'headphones': `<svg ${svgAttr}><path d="M3 14h3v7H3z"/><path d="M21 14h-3v7h3z"/><path d="M3 14a9 9 0 0 1 18 0"/></svg>`,
      'user-round': `<svg ${svgAttr}><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>`,
      'smile': `<svg ${svgAttr}><circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/></svg>`,
      'message-circle': `<svg ${svgAttr}><path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/></svg>`,
      'heart': `<svg ${svgAttr}><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>`,
      'store': `<svg ${svgAttr}><path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/></svg>`,
      'briefcase': `<svg ${svgAttr}><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>`,
      'life-buoy': `<svg ${svgAttr}><circle cx="12" cy="12" r="10"/><path d="m4.93 4.93 4.24 4.24"/><path d="m14.83 9.17 4.24-4.24"/><path d="m14.83 14.83 4.24 4.24"/><path d="m9.17 14.83-4.24 4.24"/><circle cx="12" cy="12" r="4"/></svg>`,
      'sparkles': `<svg ${svgAttr}><path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/></svg>`,
      // Legacy aliases (settings saved before the icon set refresh)
      'robot': null,
      'support': null,
      'user': null
    };
    icons['robot'] = icons['bot'];
    icons['support'] = icons['headphones'];
    icons['user'] = icons['user-round'];

    return icons[config.avatarIcon] || icons['bot'];
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
        <!-- Pre-Chat Form (Strategy 3) -->
        <div class="csai-prechat" id="csai-prechat">
          <div class="csai-prechat-card">
            <p class="csai-prechat-title">Sebelum mulai 💬</p>
            <p class="csai-prechat-desc">Isi data singkat ini agar kami bisa membantu Anda lebih cepat.</p>
            <form id="csai-prechat-form" novalidate>
              <label class="csai-prechat-field">
                <span>Nama${(config.leadForm && config.leadForm.requireName) ? ' *' : ''}</span>
                <input type="text" name="name" maxlength="120" placeholder="Nama Anda" autocomplete="name">
              </label>
              <label class="csai-prechat-field">
                <span>Email${(config.leadForm && config.leadForm.requireEmail) ? ' *' : ''}</span>
                <input type="email" name="email" maxlength="190" placeholder="nama@email.com" autocomplete="email">
              </label>
              <label class="csai-prechat-field">
                <span>No HP/WA${(config.leadForm && config.leadForm.requirePhone) ? ' *' : ''}</span>
                <input type="tel" name="phone" maxlength="30" placeholder="08xxxxxxxxxx" autocomplete="tel">
              </label>
              <p class="csai-prechat-error" id="csai-prechat-error"></p>
              <button type="submit" class="csai-prechat-submit">Mulai Chat</button>
              ${!(config.leadForm && (config.leadForm.requireName || config.leadForm.requireEmail || config.leadForm.requirePhone))
                ? '<button type="button" class="csai-prechat-skip" id="csai-prechat-skip">Lewati</button>'
                : ''}
            </form>
          </div>
        </div>

        <!-- Header -->
        <div class="csai-header">
          <div class="csai-header-avatar">${getAvatarHtml()}</div>
          <div class="csai-header-info">
            <p class="csai-header-title">${escapeHtml(config.title)}</p>
            <p class="csai-header-subtitle">${escapeHtml(config.subtitle)}</p>
          </div>
          <!-- Clear (forget conversation) + Close Buttons in Header -->
          <button class="csai-header-clear" id="csai-clear" title="Hapus percakapan" aria-label="Hapus percakapan">
            <svg viewBox="0 0 24 24">
              <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
            </svg>
          </button>
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
            placeholder="${escapeHtml(config.placeholder)}"
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

  // Pre-chat form (Strategy 3): shown once per browser, before the first
  // message. All-required fields block chat until filled; otherwise a
  // skip link lets engagement-minded owners keep the form optional.
  let greetingShown = false;

  // Greeting that may address the visitor by name. Owner convention:
  // "Halo {name}, ..." -> substituted verbatim; without the token the
  // name is woven into the opening salutation ("Halo! ..." -> "Halo Dewi! ...").
  function renderGreetingText() {
    const text = config.greeting || 'Halo! 👋 Ada yang bisa saya bantu hari ini?';
    const name = (pendingLeadForm && String(pendingLeadForm.name || '').trim()) || '';

    if (!name) return text;
    if (text.includes('{name}')) return text.split('{name}').join(name);
    if (/^halo\b/i.test(text)) return text.replace(/^halo/i, 'Halo ' + name);

    return 'Halo ' + name + ', ' + text;
  }

  function showGreeting() {
    if (greetingShown || chatHistory.length > 0) return;
    greetingShown = true;
    addMessage('assistant', renderGreetingText());
  }

  function maybeShowPreChatForm() {
    const lf = config.leadForm;
    if (!lf || !lf.enabled) return false;
    if (localStorage.getItem('csai_leadform_done_' + config.widgetId)) return false;
    if (chatHistory.length > 0 || pendingLeadForm) return false;

    const el = document.getElementById('csai-prechat');
    if (!el) return false;
    el.classList.add('visible');

    setTimeout(() => {
      const first = el.querySelector('input');
      if (first) first.focus();
    }, 300);

    return true;
  }

  function hidePreChatForm() {
    const el = document.getElementById('csai-prechat');
    if (el) el.classList.remove('visible');
  }

  function markPreChatDone() {
    try {
      localStorage.setItem('csai_leadform_done_' + config.widgetId, '1');
    } catch (e) { /* private mode - just show again next visit */ }
  }

  function initPreChatForm() {
    const form = document.getElementById('csai-prechat-form');
    if (!form) return;

    const lf = config.leadForm || {};
    const errorEl = document.getElementById('csai-prechat-error');

    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const get = (name) => {
        const input = form.querySelector('input[name="' + name + '"]');
        return input ? input.value.trim() : '';
      };

      const name = get('name').slice(0, 120);
      const email = get('email').slice(0, 190);
      const phone = get('phone').slice(0, 30);

      if (lf.requireName && !name) return showPreChatError('Nama wajib diisi.');
      if (lf.requireEmail && !email) return showPreChatError('Email wajib diisi.');
      if (lf.requirePhone && !phone) return showPreChatError('No HP wajib diisi.');
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showPreChatError('Format email tidak valid.');
      if (errorEl) errorEl.style.display = 'none';

      const data = {};
      if (name) data.name = name;
      if (email) data.email = email;
      if (phone) data.phone = phone;

      if (Object.keys(data).length > 0) pendingLeadForm = data;
      markPreChatDone();
      hidePreChatForm();
      setTimeout(showGreeting, 300);

      const inputEl = document.getElementById('csai-input');
      if (inputEl) inputEl.focus();
    });

    const skipBtn = document.getElementById('csai-prechat-skip');
    if (skipBtn) {
      skipBtn.addEventListener('click', () => {
        markPreChatDone();
        hidePreChatForm();
        setTimeout(showGreeting, 300);

        const inputEl = document.getElementById('csai-input');
        if (inputEl) inputEl.focus();
      });
    }

    function showPreChatError(msg) {
      if (errorEl) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
      }
    }
  }

  // Toggle Chat with Auto-Close Logic
  function toggleChat() {
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

      // Strategy 3: one-time pre-chat form before the first message
      const prechatOpen = maybeShowPreChatForm();

      // Show greeting if first time - deferred while the pre-chat form
      // is open, so the greeting can address the visitor by name.
      if (chatHistory.length === 0 && !prechatOpen) {
        setTimeout(showGreeting, 500);
      }

      // Focus input (with slight delay for animation)
      setTimeout(() => {
        if (inputEl && !prechatOpen) inputEl.focus();
      }, 300);

      // Scroll to bottom
      setTimeout(() => {
        scrollToBottom(document.getElementById('csai-messages'));
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
      if (minimizeTimer) {
        clearTimeout(minimizeTimer);
        minimizeTimer = null;
      }
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
  function addMessage(role, content, skipHistory = false, isHtml = false) {
    const messagesEl = document.getElementById('csai-messages');
    const messageEl = document.createElement('div');
    messageEl.className = `csai-message ${role}`;

    // Parse Markdown for assistant
    if (role === 'assistant') {
      if (isHtml) {
        messageEl.innerHTML = content;
      } else {
        messageEl.innerHTML = parseMarkdown(content);
      }
    } else {
      messageEl.textContent = content; // Keep user input as plain text for safety
    }

    messagesEl.appendChild(messageEl);
    scrollToBottom(messagesEl);

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
    let tables = [];

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

    // 4. Links - Parse Markdown links FIRST [text](url), stashed as
    // placeholders so the plain-URL pass below cannot re-process URLs
    // already inside generated anchor href attributes (no nested anchors,
    // no encoded fragments like %3Ca%20href=).
    // Hardened: candidates are validated (http(s) only, no whitespace,
    // quotes, backticks, or angle brackets) and the href value is
    // attribute-escaped; unsafe candidates are left as plain text.
    function isSafeHttpUrl(url) {
      return /^https?:\/\/[^\s<>"'`]+$/i.test(url);
    }
    function linkAnchor(url, label) {
      const safeHref = url.replace(/"/g, '&quot;');
      return '<a href="' + safeHref + '" target="_blank" rel="noopener noreferrer" class="csai-link">' + label + '</a>';
    }
    let mdLinks = [];
    text = text.replace(/\[([^\]]+)\]\((https?:\/\/[^)\s]+)\)/g, function (match, label, url) {
      if (!isSafeHttpUrl(url)) return match;
      mdLinks.push(linkAnchor(url, label));
      return `__MD_LINK_${mdLinks.length - 1}__`;
    });

    // 5. Plain URLs (placeholders contain no http(s):// so they are skipped
    // automatically; trailing punctuation is kept outside the anchor).
    text = text.replace(/(https?:\/\/[^\s<]+)/g, function (raw) {
      let url = raw;
      let trail = '';
      const trailMatch = raw.match(/[.,;:!?)\]}'"]+$/);
      if (trailMatch) {
        trail = trailMatch[0];
        url = raw.slice(0, -trail.length);
      }
      if (!url || !isSafeHttpUrl(url)) return raw;
      return linkAnchor(url, url) + trail;
    });

    // 5b. Tables - stash full blocks (header + separator + body rows)
    //     and rebuild them as real <table> markup. Runs after the inline
    //     passes above, so cells already carry <strong>/<code>/<a> HTML.
    //     Detects both raw GFM tables (| a | b |) and the aligned plain
    //     text tables the server sanitizer emits (a | b / --- | ---).
    function splitTableRow(line) {
      let s = line.trim();
      if (s.charAt(0) === '|') s = s.substring(1);
      if (s.charAt(s.length - 1) === '|') s = s.substring(0, s.length - 1);
      return s.split('|').map(function (c) { return c.trim(); });
    }
    function buildTableHtml(block) {
      const header = splitTableRow(block[0]);
      const sep = splitTableRow(block[1] || '');
      const aligns = header.map(function (_, k) {
        const c = sep[k] || '';
        if (/:-:/.test(c)) return 'center';
        if (/:-$/.test(c)) return 'right';
        return 'left';
      });
      let html = '<div class="csai-table-wrap"><table class="csai-table"><thead><tr>';
      html += header.map(function (c, k) {
        return '<th style="text-align:' + aligns[k] + '">' + c + '</th>';
      }).join('');
      html += '</tr></thead><tbody>';
      for (let r = 2; r < block.length; r++) {
        const cells = splitTableRow(block[r]);
        html += '<tr>' + header.map(function (_, k) {
          return '<td style="text-align:' + aligns[k] + '">' + (cells[k] !== undefined ? cells[k] : '') + '</td>';
        }).join('') + '</tr>';
      }
      return html + '</tbody></table></div>';
    }
    (function () {
      const lines = text.split('\n');
      const out = [];
      const hasPipe = (l) => l.indexOf('|') !== -1;
      for (let i = 0; i < lines.length; i++) {
        const header = lines[i].trim();
        const sep = i + 1 < lines.length ? lines[i + 1].trim() : '';
        const sepOk = /^[\s:|-]+$/.test(sep) && sep.indexOf('-') !== -1 &&
          (sep.indexOf('|') !== -1 || /^\|/.test(header) || /\|$/.test(header));
        if (hasPipe(header) && sepOk) {
          let end = i + 2;
          while (end < lines.length && hasPipe(lines[end].trim())) end++;
          tables.push(buildTableHtml(lines.slice(i, end)));
          out.push('__TABLE_BLOCK_' + (tables.length - 1) + '__');
          i = end - 1;
          continue;
        }
        out.push(lines[i]);
      }
      text = out.join('\n');
    })();

    // 6. Lists (Regex)
    // Unordered
    text = text.replace(/^\s*-\s+(.*)$/gm, '<li class="ul-item">$1</li>');
    // Wrap UL groups
    text = text.replace(/((?:<li class="ul-item">.*<\/li>\n?)+)/g, '<ul class="csai-ul">$1</ul>');

    // Ordered
    text = text.replace(/^\s*\d+\.\s+(.*)$/gm, '<li class="ol-item">$1</li>');
    // Wrap OL groups
    text = text.replace(/((?:<li class="ol-item">.*<\/li>\n?)+)/g, '<ol class="csai-ol">$1</ol>');

    // 7. Newlines to <br>, but be careful around lists
    // We already wrapped lists in <ul>...</ul>, so we replace \n that are NOT inside tags? 
    // Simplify: replace \n with <br>, but remove <br> after </ul> or </ol> or </pre>
    text = text.replace(/\n/g, '<br>');
    text = text.replace(/(<\/ul>|<\/ol>|<\/pre>|<pre>)<br>/g, '$1');

    // 8. Restore Tables (before link/code placeholders so anything
    //    stashed inside cells gets processed too), then Markdown links.
    text = text.replace(/__TABLE_BLOCK_(\d+)__/g, function (match, id) {
      return typeof tables[id] !== 'undefined' ? tables[id] : match;
    });
    text = text.replace(/<br>(<div class="csai-table-wrap">)/g, '$1');
    text = text.replace(/(<\/table><\/div>)<br>/g, '$1');
    text = text.replace(/__MD_LINK_(\d+)__/g, function (match, id) {
      return typeof mdLinks[id] !== 'undefined' ? mdLinks[id] : match;
    });

    // 9. Restore Code Blocks
    text = text.replace(/__CODE_BLOCK_(\d+)__/g, function (match, id) {
      let code = placeholders[id];
      // Attacker-controlled text may contain a placeholder without any real
      // code block - keep it as-is instead of crashing on undefined.
      if (typeof code !== 'string') return match;
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
    scrollToBottom(messagesEl);
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
    // A new visitor message opens a fresh conversation: lift the closing
    // latch so the idle offer/close cycle may run again later.
    conversationClosed = false;
    const sendBtn = document.getElementById('csai-send');
    sendBtn.disabled = true;

    // Add user message
    addMessage('user', message);

    // Show typing
    showTyping();

    try {
      const payload = {
        message: message,
        widgetId: config.widgetId,
        // Bounded wire history: a short window (server keeps only the
        // last 10 for the LLM) with a per-item cap as defense in depth.
        history: chatHistory.slice(-12).map(m => ({
          role: m.role,
          content: (m.content || '').slice(0, 10000)
        })),
        sessionId: sessionId,
        // Where the visitor is chatting from (chat history detail + lead
        // email). The browser's Referer header is origin-only cross-origin,
        // so the full URL must come from the client.
        pageUrl: window.location.href.slice(0, 500),
        referrerUrl: (document.referrer || '').slice(0, 500)
      };

      // Pre-chat form details ride the first message, once.
      if (pendingLeadForm) payload.leadForm = pendingLeadForm;

      const response = await fetch(config.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();
      if (pendingLeadForm) pendingLeadForm = null;

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
    const clearBtn = document.getElementById('csai-clear');
    const sendBtn = document.getElementById('csai-send');
    const inputEl = document.getElementById('csai-input');
    const emojiBtn = document.getElementById('csai-emoji-btn');
    const messagesEl = document.getElementById('csai-messages');

    toggleBtn.addEventListener('click', toggleChat);
    closeBtn.addEventListener('click', toggleChat);

    if (clearBtn) {
      clearBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        window.CSAI_forgetChat();
      });
    }

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

    // Reset timer on scroll (programmatic auto-scroll is flagged and ignored)
    messagesEl.addEventListener('scroll', () => {
      if (Date.now() < autoScrollUntil) return;
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

    // Fetch authoritative config (also tells us whether the widget is
    // active on the server). Do not render when the widget is disabled.
    const userConfig = window.CSAIConfig || {};

    if (userConfig.widgetId && userConfig.widgetId !== 'default') {
      const cfgStatus = await fetchConfig(userConfig.widgetId);
      if (cfgStatus === 'disabled') {
        console.warn('CSAI Widget: widget is disabled or not available on this domain');
        return; // Don't initialize widget
      }
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
    initPreChatForm();
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
