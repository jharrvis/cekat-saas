# 🤖 Customer Service AI SaaS - Development Plan

## 📋 Overview

**Nama Project**: Customer Service AI SaaS  
**Tujuan**: Platform SaaS untuk membuat chatbot AI customer service yang bisa di-embed ke website manapun.  
**Tech Stack**: PHP (tanpa framework), Vanilla JavaScript, OpenRouter API

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                        DASHBOARD (SaaS)                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │  Training   │  │   Model     │  │   Widget    │             │
│  │    Data     │  │  Selection  │  │  Generator  │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      BACKEND API                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │  Knowledge  │  │  OpenRouter │  │   Widget    │             │
│  │    Base     │  │   Gateway   │  │     API     │             │
│  └─────────────┘  └─────────────┘  └─────────────┘             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER WEBSITE                             │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  <script src="your-saas.com/widget/UNIQUE_KEY.js">     │   │
│  │  </script>                                              │   │
│  │                                                         │   │
│  │  💬 [Floating Chat Button]                              │   │
│  └─────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

---

## 💰 Tier Pricing

| Tier | Nama | Harga/bulan | Model | Fitur |
|------|------|-------------|-------|-------|
| 🆓 | **Starter** | Free | GPT-3.5 Turbo | 100 chat/bulan, 1 widget, branding |
| 🥈 | **Pro** | Rp 299k | GPT-4o-mini, Claude 3 Haiku | 2,000 chat/bulan, 3 widget, custom branding, **Agentic AI (Active Promo, Contextual Hook)** |
| 🥇 | **Business** | Rp 799k | GPT-4o, Claude 3.5 Sonnet | 10,000 chat/bulan, 10 widget, analytics, **Full Agentic Actions (Taking Order, Invoicing)** |
| 🏆 | **Enterprise** | Custom | Semua model | Unlimited, API access, dedicated support |

---

## 📁 Struktur Project

```
customer-service/
├── api/
│   ├── chat.php              # Chat endpoint
│   ├── config.php            # Konfigurasi (API keys, settings)
│   └── helpers.php           # Helper functions
├── data/
│   ├── knowledge-base.json   # Training data manual
│   └── widgets/              # Widget configurations per client
│       └── demo.json
├── widget/
│   ├── widget.js             # Embeddable widget script
│   └── widget.css            # Widget styles (inline dalam JS)
├── docs/
│   └── DEVELOPMENT_PLAN.md   # Dokumen ini
├── demo/
│   └── index.html            # Demo page untuk testing widget
└── README.md
```

---

## 🔧 Fitur MVP (Phase 1)

### 1. Backend API

#### `api/chat.php`
- Menerima pesan dari widget
- Load knowledge base dari JSON
- Kirim ke OpenRouter dengan context
- Return response AI

#### Knowledge Base Format (`data/knowledge-base.json`)
```json
{
  "company": {
    "name": "Nama Perusahaan",
    "description": "Deskripsi singkat perusahaan"
  },
  "persona": {
    "name": "Assistant Name",
    "role": "Customer Service",
    "tone": "friendly",
    "language": "id"
  },
  "faqs": [
    {
      "question": "Bagaimana cara order?",
      "answer": "Anda bisa order melalui..."
    }
  ],
  "products": [
    {
      "name": "Product A",
      "description": "...",
      "price": 100000
    }
  ],
  "policies": {
    "refund": "Kebijakan refund...",
    "shipping": "Info pengiriman..."
  }
}
```

### 2. JavaScript Widget

**Features:**
- Floating chat button (bottom-right)
- Expandable chat window
- Real-time chat dengan AI
- Typing indicator
- Chat history (localStorage)
- Responsive design
- Customizable via config

**Embed Code:**
```html
<script>
  window.CSAIConfig = {
    widgetId: 'demo',
    position: 'bottom-right',
    primaryColor: '#6366f1',
    greeting: 'Halo! Ada yang bisa saya bantu?'
  };
</script>
<script src="http://localhost/customer-service/widget/widget.js"></script>
```

---

## 🔌 OpenRouter Integration

### API Endpoint
`https://openrouter.ai/api/v1/chat/completions`

### Supported Models (by Tier)

| Model ID | Provider | Tier |
|----------|----------|------|
| `openai/gpt-3.5-turbo` | OpenAI | Starter |
| `openai/gpt-4o-mini` | OpenAI | Pro |
| `anthropic/claude-3-haiku` | Anthropic | Pro |
| `openai/gpt-4o` | OpenAI | Business |
| `anthropic/claude-3.5-sonnet` | Anthropic | Business |
| `google/gemini-pro-1.5` | Google | Business |

### Request Format
```php
$payload = [
    'model' => 'openai/gpt-4o-mini',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.7,
    'max_tokens' => 500
];
```

---

## 🎨 Widget UI Design

```
┌──────────────────────────────────────┐
│ 🤖 Customer Service        ─  ✕     │
├──────────────────────────────────────┤
│                                       │
│  ┌────────────────────────┐          │
│  │ Halo! Ada yang bisa    │          │
│  │ saya bantu hari ini?   │          │
│  └────────────────────────┘          │
│                                       │
│          ┌────────────────────────┐  │
│          │ Saya mau tanya soal   │  │
│          │ pengiriman            │  │
│          └────────────────────────┘  │
│                                       │
│  ┌────────────────────────┐          │
│  │ Tentu! Untuk pengiriman│          │
│  │ kami menggunakan...    │          │
│  └────────────────────────┘          │
│                                       │
├──────────────────────────────────────┤
│ ┌────────────────────────────┐  ➤   │
│ │ Ketik pesan...             │       │
│ └────────────────────────────┘       │
└──────────────────────────────────────┘

                                   ┌────┐
                                   │ 💬 │  ← Floating Button
                                   └────┘
```

---

## 🚀 Development Phases

### Phase 1: MVP (Current)
- [x] Development plan
- [ ] Backend API dengan OpenRouter
- [ ] JavaScript widget
- [ ] Manual training data (JSON)
- [ ] Demo page

### Phase 2: Dashboard
- [ ] User authentication
- [ ] Training data management UI
- [ ] Widget customization UI
- [ ] Analytics basic

### Phase 3: Multi-tenant
- [ ] Multiple widgets per user
- [ ] Tier-based feature gating
- [ ] Usage tracking & limits
- [ ] Payment integration

### Phase 4: Advanced
- [ ] Vector database (embeddings)
- [ ] Document upload & parsing
- [ ] Website scraping
- [ ] Conversation analytics
- [ ] Human handoff

---

## 💡 Ide Tambahan untuk Diferensiasi

1. **🤖 Multi-Agent System** - Sales Agent, Support Agent, Technical Agent dengan auto-routing
2. **📊 Smart Escalation** - Auto-detect when AI can't answer, notify human
3. **🌐 Multi-language** - Auto-detect bahasa customer
4. **📝 Conversation Memory** - Remember customer across sessions
5. **🎨 No-Code Flow Builder** - Visual builder untuk conversation flows
6. **⚡ Pre-built Templates** - E-commerce, SaaS, Restaurant, Real estate
7. **🔒 Compliance** - GDPR toggle, data retention policies

---

## ⚙️ Configuration

### Environment Variables (config.php)
```php
define('OPENROUTER_API_KEY', 'sk-or-xxxxx');
define('DEFAULT_MODEL', 'openai/gpt-4o-mini');
define('MAX_TOKENS', 500);
define('TEMPERATURE', 0.7);
```

---

## 📝 Catatan Pengembangan

- **Tanpa Framework**: Gunakan PHP native untuk simplicity
- **CORS**: Enable CORS untuk widget cross-origin
- **Rate Limiting**: Implementasi di Phase 2
- **Security**: Sanitize semua input, use HTTPS
- **Caching**: Cache knowledge base di memory

---

## 🚀 Laravel Migration Plan (v2.0)

### Tech Stack Upgrade
- **Backend**: Laravel 11 + Livewire 3
- **Database**: MySQL 8.0
- **Auth**: Laravel Socialite (Google SSO)
- **Cache**: Redis (Optional)
- **Widget**: Standalone Vanilla JS (tetap)

---

## 🔐 Authentication: Google SSO

### Setup
```bash
composer require laravel/socialite
```

### Config (`config/services.php`)
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI'),
],
```

### Flow
1. User clicks "Login with Google"
2. Redirect to Google OAuth
3. Google returns with user data
4. Create/update user in DB
5. Issue Laravel session

---

## 🗄️ Database Schema

```sql
-- ============================================
-- USERS & AUTH
-- ============================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    google_id VARCHAR(255) UNIQUE NULL,
    avatar VARCHAR(255) NULL,
    plan_tier ENUM('hobby', 'pro', 'business') DEFAULT 'hobby',
    plan_expires_at TIMESTAMP NULL,
    monthly_message_quota INT DEFAULT 100,
    monthly_message_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- WIDGETS (Chatbot Instances)
-- ============================================
CREATE TABLE widgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    settings JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- KNOWLEDGE BASE
-- ============================================
CREATE TABLE knowledge_bases (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id BIGINT UNSIGNED NOT NULL,
    company_name VARCHAR(255),
    company_description TEXT,
    persona_name VARCHAR(100),
    persona_tone VARCHAR(100),
    custom_instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE
);

CREATE TABLE knowledge_faqs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    knowledge_base_id BIGINT UNSIGNED NOT NULL,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (knowledge_base_id) REFERENCES knowledge_bases(id) ON DELETE CASCADE
);

CREATE TABLE knowledge_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    knowledge_base_id BIGINT UNSIGNED NOT NULL,
    filename VARCHAR(255),
    file_path VARCHAR(500),
    file_type ENUM('pdf', 'docx', 'txt', 'url') NOT NULL,
    status ENUM('pending', 'processing', 'ready', 'failed') DEFAULT 'pending',
    content_text LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (knowledge_base_id) REFERENCES knowledge_bases(id) ON DELETE CASCADE
);

-- ============================================
-- CHAT SESSIONS & MESSAGES
-- ============================================
CREATE TABLE chat_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id BIGINT UNSIGNED NOT NULL,
    visitor_uuid VARCHAR(100) NOT NULL,
    visitor_name VARCHAR(255) NULL,
    visitor_email VARCHAR(255) NULL,
    visitor_phone VARCHAR(50) NULL,
    source_url VARCHAR(500) NULL,
    user_agent TEXT NULL,
    ip_address VARCHAR(45) NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    is_converted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (widget_id) REFERENCES widgets(id) ON DELETE CASCADE,
    INDEX idx_widget_started (widget_id, started_at)
);

CREATE TABLE chat_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    role ENUM('user', 'assistant', 'system') NOT NULL,
    content TEXT NOT NULL,
    tokens_used INT DEFAULT 0,
    model_used VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_created (session_id, created_at)
);

-- ============================================
-- ANALYTICS & BILLING
-- ============================================
CREATE TABLE usage_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    widget_id BIGINT UNSIGNED NULL,
    action ENUM('chat', 'upload', 'crawl') NOT NULL,
    tokens_used INT DEFAULT 0,
    cost_usd DECIMAL(10,6) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_month (user_id, created_at)
);
```

---

## ✨ Feature Roadmap (Value Proposition)

### Growth Features (Pro Tier)
| Fitur | Deskripsi |
|-------|-----------|
| **Lead Capture** | AI minta nama/email/WA sebelum lanjut chat |
| **Human Handoff** | Tombol "Bicara dengan CS Manusia" → notif WA/Email |
| **Multi-Language** | Auto-detect bahasa visitor |
| **Chat Transcripts** | Export riwayat chat ke PDF/CSV |
| **Operating Hours** | Set jam online, pesan offline di luar jam |

### Premium Features (Business Tier)
| Fitur | Deskripsi |
|-------|-----------|
| **Agentic AI** | AI bisa buat invoice, cek stok, proses order |
| **CRM Integration** | Sync leads ke HubSpot, Zoho, Google Sheets |
| **WhatsApp Bot** | Deploy bot ke WhatsApp Business API |
| **Custom Domain** | Widget di subdomain klien |
| **Team Seats** | Multiple admin per akun |
| **API Access** | REST API untuk integrasi custom |
| **White Label** | Hapus semua branding Cekat |

### Unique Selling Points (USP)
1. **Bahasa Indonesia Native** – Persona CS yang natural
2. **Agentic AI** – Bukan cuma jawab, tapi bisa ACTION
3. **1-Click Setup** – Lebih simpel dari kompetitor
4. **Harga Lokal** – Rp 199rb vs $29/month

---

## 📅 Laravel Implementation Timeline

### Phase 1: Core (Week 1-2)
- [ ] Laravel Project Setup
- [ ] Database Migration
- [ ] Google SSO Auth
- [ ] Basic Dashboard (Livewire)
- [ ] Migrate Chat API

### Phase 2: Features (Week 3-4)
- [ ] Knowledge Base CRUD
- [ ] Widget Preview Editor
- [ ] Lead Capture Form
- [ ] Basic Analytics

### Phase 3: Growth (Week 5-6)
- [ ] Human Handoff
- [ ] Usage Billing Logic
- [ ] Stripe/Midtrans Integration
- [ ] Chat Export

### Phase 4: Premium (Future)
- [ ] Agentic AI (Function Calling)
- [ ] WhatsApp Integration
- [ ] CRM Connectors

