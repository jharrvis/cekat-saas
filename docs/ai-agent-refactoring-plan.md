# AI Agent Architecture Refactoring Plan

## 🎯 Overview

Refactoring arsitektur untuk memisahkan **AI Agent** (otak/pengetahuan) dari **Widget** (tampilan/channel), dengan fitur **Agent Handoff** untuk routing antar agent.

**Estimasi Total:** 5-7 hari

---

## 🏗️ Architecture Overview

### Current Architecture (Monolithic)
```
User ─── Widget ─── Knowledge Base
           │
           ├── Tampilan
           ├── AI Settings
           └── Training Data
```

### New Architecture (Modular)
```
                    ┌─────────────────┐
                    │     User        │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
        ┌─────▼─────┐  ┌─────▼─────┐  ┌─────▼─────┐
        │ AI Agent  │  │ AI Agent  │  │ AI Agent  │
        │ Marketing │  │ Technical │  │   Sales   │
        └─────┬─────┘  └─────┬─────┘  └─────┬─────┘
              │              │              │
              │   ◄──Handoff──►             │
              │              │              │
        ┌─────▼─────────────▼──────────────▼─────┐
        │              Widget(s)                  │
        │  ┌───────┐ ┌───────┐ ┌───────┐        │
        │  │ Web   │ │WhatsApp│ │Landing│        │
        │  │Widget │ │Channel │ │ Page  │        │
        │  └───────┘ └───────┘ └───────┘        │
        └────────────────────────────────────────┘
```

---

## 🔄 Agent Handoff Flow

### Concept
```
User: "Bagaimana cara setting teknis produk X?"

┌─────────────────┐
│ Marketing Agent │ ◄── Detects technical question
└────────┬────────┘
         │ "Untuk pertanyaan teknis, saya akan
         │  hubungkan Anda dengan tim teknis..."
         ▼
┌─────────────────┐
│ Technical Agent │ ◄── Takes over conversation
└────────┬────────┘
         │ "Halo! Saya dari tim teknis. Untuk
         │  setting produk X, berikut langkahnya..."
         ▼
     [Continues...]
```

### Implementation Options

#### Option A: Automatic Routing (AI-based)
- AI menganalisis pertanyaan
- Jika di luar scope, otomatis handoff
- Lebih seamless, tapi butuh prompt engineering

#### Option B: Keyword/Intent Trigger
- Define triggers: "masalah teknis", "tidak bisa", "error"
- Lebih predictable, easier to debug

#### Option C: Explicit Command
- User: "/teknis" atau "bicara dengan tim teknis"
- Most control, but requires user knowledge

**Recommendation:** Start with **Option B** (Keyword), enhance with **Option A** later.

---

## 📊 Database Schema

### New Table: `ai_agents`

```sql
CREATE TABLE ai_agents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    
    -- Basic Info
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    avatar_url VARCHAR(500),
    
    -- AI Configuration
    ai_model VARCHAR(100) DEFAULT 'google/gemini-2.0-flash-001',
    ai_temperature DECIMAL(2,1) DEFAULT 0.7,
    system_prompt TEXT,
    personality ENUM('professional', 'friendly', 'casual', 'formal') DEFAULT 'friendly',
    
    -- Behavior Settings
    max_tokens INT DEFAULT 500,
    language VARCHAR(10) DEFAULT 'id',
    fallback_message TEXT,
    
    -- Handoff Configuration
    can_handoff BOOLEAN DEFAULT FALSE,
    handoff_triggers JSON, -- ["masalah teknis", "error", "tidak bisa"]
    handoff_message TEXT,  -- "Saya akan hubungkan dengan tim teknis..."
    
    -- Usage Stats
    messages_used INT UNSIGNED DEFAULT 0,
    conversations_count INT UNSIGNED DEFAULT 0,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active (user_id, is_active)
);
```

### New Table: `agent_handoffs`

```sql
CREATE TABLE agent_handoffs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    source_agent_id BIGINT UNSIGNED NOT NULL,
    target_agent_id BIGINT UNSIGNED NOT NULL,
    
    -- Trigger Configuration
    trigger_type ENUM('keyword', 'intent', 'explicit', 'fallback') DEFAULT 'keyword',
    trigger_keywords JSON,      -- ["teknis", "error", "setup"]
    trigger_intents JSON,       -- ["technical_support", "billing"]
    priority INT DEFAULT 0,     -- Higher = checked first
    
    -- Handoff Behavior
    handoff_message TEXT,       -- Custom message when handing off
    return_enabled BOOLEAN DEFAULT TRUE,  -- Can return to original agent
    notify_user BOOLEAN DEFAULT TRUE,     -- Tell user about handoff
    
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (source_agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE,
    FOREIGN KEY (target_agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE,
    UNIQUE KEY unique_handoff (source_agent_id, target_agent_id)
);
```

### Modified Table: `widgets`

```sql
-- Add new columns
ALTER TABLE widgets ADD COLUMN ai_agent_id BIGINT UNSIGNED AFTER user_id;
ALTER TABLE widgets ADD COLUMN channel_type ENUM('web', 'whatsapp', 'telegram', 'embed') DEFAULT 'web';

-- Widget now only stores display settings
-- AI settings moved to ai_agents table
```

### Modified Table: `knowledge_bases`

```sql
-- Change relationship from widget to ai_agent
ALTER TABLE knowledge_bases DROP FOREIGN KEY knowledge_bases_widget_id_foreign;
ALTER TABLE knowledge_bases CHANGE widget_id ai_agent_id BIGINT UNSIGNED;
ALTER TABLE knowledge_bases ADD FOREIGN KEY (ai_agent_id) REFERENCES ai_agents(id) ON DELETE CASCADE;
```

### Modified Table: `chat_sessions`

```sql
-- Track which agent is currently handling
ALTER TABLE chat_sessions ADD COLUMN current_agent_id BIGINT UNSIGNED AFTER widget_id;
ALTER TABLE chat_sessions ADD COLUMN agent_history JSON; -- Track handoff history
```

### Modified Table: `chat_messages`

```sql
-- Track which agent sent the message
ALTER TABLE chat_messages ADD COLUMN ai_agent_id BIGINT UNSIGNED AFTER chat_session_id;
ALTER TABLE chat_messages ADD COLUMN is_handoff_message BOOLEAN DEFAULT FALSE;
```

---

## 📁 File Structure

```
app/
├── Models/
│   ├── AiAgent.php                 # NEW
│   ├── AgentHandoff.php            # NEW
│   ├── Widget.php                  # MODIFIED
│   ├── KnowledgeBase.php           # MODIFIED
│   ├── ChatSession.php             # MODIFIED
│   └── ChatMessage.php             # MODIFIED
│
├── Services/
│   ├── Agent/
│   │   ├── AgentService.php        # NEW - Core agent logic
│   │   ├── HandoffService.php      # NEW - Handoff routing
│   │   └── AgentContextBuilder.php # NEW - Build context for agent
│   └── AI/
│       └── OpenRouterService.php   # MODIFIED
│
├── Http/Controllers/
│   ├── AiAgentController.php       # NEW
│   └── Api/ChatController.php      # MODIFIED
│
├── Livewire/
│   ├── AgentManager.php            # NEW
│   ├── AgentEditor.php             # NEW
│   ├── HandoffConfigurator.php     # NEW
│   └── WidgetManager.php           # MODIFIED
│
resources/views/
├── agents/
│   ├── index.blade.php             # NEW - List agents
│   ├── create.blade.php            # NEW
│   └── edit.blade.php              # NEW
├── livewire/
│   ├── agent-manager.blade.php     # NEW
│   ├── agent-editor.blade.php      # NEW
│   └── handoff-configurator.blade.php  # NEW
└── layouts/partials/
    └── sidebar.blade.php           # MODIFIED - Add Agents menu
```

---

## 🔧 Implementation Phases

### Phase 1: Database Migration (Day 1)
- [ ] Create `ai_agents` table migration
- [ ] Create `agent_handoffs` table migration
- [ ] Modify existing tables migrations
- [ ] Create data migration script (widgets → ai_agents)

### Phase 2: Models & Relationships (Day 1-2)
- [ ] Create AiAgent model
- [ ] Create AgentHandoff model
- [ ] Update Widget model
- [ ] Update KnowledgeBase model
- [ ] Update ChatSession model
- [ ] Update ChatMessage model
- [ ] Create model factories for testing

### Phase 3: Agent Management UI (Day 2-3)
- [ ] Create agent list page
- [ ] Create agent editor Livewire component
- [ ] Add AI settings (model, temperature, prompt)
- [ ] Add personality selector
- [ ] Update sidebar navigation

### Phase 4: Widget-Agent Linking (Day 3)
- [ ] Update widget creation to select agent
- [ ] Create agent selector component
- [ ] Allow changing agent on existing widget
- [ ] Handle widgets without agents (backward compat)

### Phase 5: Chat Integration (Day 3-4)
- [ ] Update ChatController to use agents
- [ ] Modify prompt building for agent context
- [ ] Track agent in chat messages
- [ ] Update chat history display

### Phase 6: Handoff System (Day 4-5)
- [ ] Create HandoffService
- [ ] Implement keyword detection
- [ ] Add handoff message injection
- [ ] Track handoff history in session
- [ ] Create handoff configuration UI

### Phase 7: Testing & Migration (Day 5-6)
- [ ] Migrate existing data
- [ ] Test all chat flows
- [ ] Test handoff scenarios
- [ ] Performance testing
- [ ] Fix edge cases

### Phase 8: Knowledge Base Update (Day 6-7)
- [ ] Move knowledge base to agent context
- [ ] Update FAQ management
- [ ] Implement PDF upload (from previous plan)
- [ ] Test with real documents

---

## 🎨 UI Design

### Agent List Page

```
┌─────────────────────────────────────────────────────────────┐
│ 🤖 AI Agents                                   [+ New Agent] │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 🧑‍💼 Customer Service Agent              [Edit] [···] │  │
│  │ Model: gemini-2.0-flash  │  Widgets: 3  │  Active ✓  │  │
│  │ 1,240 messages  •  Last used: 2 hours ago            │  │
│  │                                                      │  │
│  │ Handoffs to: Technical Support, Sales                │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ 🔧 Technical Support Agent              [Edit] [···] │  │
│  │ Model: claude-3-sonnet  │  Widgets: 1  │  Active ✓   │  │
│  │ 456 messages  •  Last used: 5 hours ago              │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Agent Editor

```
┌─────────────────────────────────────────────────────────────┐
│ ← Back                        Customer Service Agent        │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  [General] [Knowledge] [Handoffs] [Settings]                │
│  ─────────────────────────────────────────────────────────  │
│                                                             │
│  📝 Basic Information                                       │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Name: [Customer Service Agent              ]        │   │
│  │                                                     │   │
│  │ Description:                                        │   │
│  │ [Menjawab pertanyaan umum pelanggan tentang produk  │   │
│  │  dan layanan perusahaan.                         ]  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  🤖 AI Configuration                                        │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Model: [Google Gemini 2.0 Flash        ▼]           │   │
│  │                                                     │   │
│  │ Personality: ○ Professional ● Friendly ○ Casual    │   │
│  │                                                     │   │
│  │ Temperature: [0.7] ─────●──────                     │   │
│  │              Creative ◄────► Focused                │   │
│  │                                                     │   │
│  │ System Prompt:                                      │   │
│  │ [Kamu adalah asisten customer service yang ramah.   │   │
│  │  Jawab pertanyaan dengan singkat dan jelas.      ]  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│                                          [Save Changes]     │
└─────────────────────────────────────────────────────────────┘
```

### Handoff Configuration Tab

```
┌─────────────────────────────────────────────────────────────┐
│ 🔄 Handoff Configuration                                    │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ☑ Enable Handoff to Other Agents                           │
│                                                             │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 📌 Handoff Rule #1                          [Delete] │   │
│  │                                                     │   │
│  │ When user mentions:                                 │   │
│  │ [teknis, error, tidak bisa, setup, install    ]     │   │
│  │                                                     │   │
│  │ Route to: [Technical Support Agent    ▼]            │   │
│  │                                                     │   │
│  │ Handoff message:                                    │   │
│  │ [Sepertinya Anda membutuhkan bantuan teknis. Saya   │   │
│  │  akan menghubungkan dengan tim teknis kami...    ]  │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                             │
│  [+ Add Another Handoff Rule]                               │
│                                                             │
│  💡 Tip: Handoff memungkinkan agent spesialis menjawab      │
│     pertanyaan sesuai bidangnya.                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔀 Chat Flow with Handoff

### Sequence Diagram

```
User          Widget        ChatController    MarketingAgent    TechAgent
  │              │                │                 │               │
  │─── "Halo" ──►│                │                 │               │
  │              │─── process ───►│                 │               │
  │              │                │── getResponse ─►│               │
  │              │                │◄── "Halo! Ada  ─┤               │
  │              │                │     yang bisa   │               │
  │◄─────────────┤◄───────────────┤     dibantu?"   │               │
  │              │                │                 │               │
  │─ "Ada error  │                │                 │               │
  │   saat setup"►                │                 │               │
  │              │─── process ───►│                 │               │
  │              │                │── getResponse ─►│               │
  │              │                │                 │─ check ──────►│
  │              │                │                 │  handoff      │
  │              │                │                 │  triggers     │
  │              │                │                 │◄─ MATCH! ─────┤
  │              │                │                 │               │
  │              │                │◄─ handoff msg ──┤               │
  │              │                │   + switch      │               │
  │              │                │                 │               │
  │              │                │─── getResponse ─────────────────►
  │              │                │◄── "Halo, saya dari tim teknis.  │
  │◄─────────────┤◄───────────────┤     Untuk error setup..."       │
  │              │                │                 │               │
```

### Code Example

```php
// HandoffService.php
class HandoffService
{
    public function checkHandoff(AiAgent $currentAgent, string $message): ?AgentHandoff
    {
        $handoffs = $currentAgent->handoffs()->active()->orderBy('priority', 'desc')->get();
        
        foreach ($handoffs as $handoff) {
            if ($this->matchesTriggers($handoff, $message)) {
                return $handoff;
            }
        }
        
        return null;
    }
    
    private function matchesTriggers(AgentHandoff $handoff, string $message): bool
    {
        $message = strtolower($message);
        $keywords = $handoff->trigger_keywords ?? [];
        
        foreach ($keywords as $keyword) {
            if (str_contains($message, strtolower($keyword))) {
                return true;
            }
        }
        
        return false;
    }
    
    public function executeHandoff(
        ChatSession $session, 
        AgentHandoff $handoff, 
        string $originalMessage
    ): array {
        // 1. Update session to new agent
        $previousAgent = $session->current_agent_id;
        $session->update([
            'current_agent_id' => $handoff->target_agent_id,
            'agent_history' => array_merge(
                $session->agent_history ?? [],
                [['from' => $previousAgent, 'to' => $handoff->target_agent_id, 'at' => now()]]
            )
        ]);
        
        // 2. Generate handoff message
        $handoffMessage = $handoff->handoff_message ?? 
            "Saya akan menghubungkan Anda dengan tim yang lebih tepat...";
        
        // 3. Get response from new agent
        $newAgent = $handoff->targetAgent;
        $response = $this->agentService->getResponse($newAgent, $originalMessage);
        
        return [
            'handoff_occurred' => true,
            'handoff_message' => $handoffMessage,
            'new_agent' => $newAgent->name,
            'response' => $response,
        ];
    }
}
```

---

## ⚠️ Complexity Assessment

### Is Handoff Too Complex?

| Aspect | Complexity | Notes |
|--------|------------|-------|
| Database Schema | Medium | Extra tables, but straightforward |
| Backend Logic | Medium-High | Handoff routing needs careful testing |
| UI | Medium | Additional configuration screens |
| User Understanding | Low-Medium | Need good UX to make it intuitive |
| Maintenance | Medium | More moving parts |

### Recommendation: **Phased Approach**

**Phase A (Must-have):** Agent-Widget separation ✅
- This alone is very valuable
- Simpler to implement
- Immediate benefit

**Phase B (Nice-to-have):** Agent Handoff
- Can be added later
- More advanced feature
- Consider as "Enterprise" feature

---

## ✅ Implementation Checklist

### Week 1: Core Refactoring
- [ ] Phase 1: Database migrations
- [ ] Phase 2: Models & relationships
- [ ] Phase 3: Agent management UI
- [ ] Phase 4: Widget-Agent linking
- [ ] Phase 5: Chat integration

### Week 2: Advanced Features
- [ ] Phase 6: Handoff system
- [ ] Phase 7: Testing & data migration
- [ ] Phase 8: Knowledge base update

---

## 📋 Migration Strategy

### Data Migration Script

```php
// MigrateWidgetsToAgents.php
public function up()
{
    // 1. Create agents from existing widgets
    Widget::with('knowledgeBase')->chunk(100, function ($widgets) {
        foreach ($widgets as $widget) {
            $agent = AiAgent::create([
                'user_id' => $widget->user_id,
                'name' => $widget->name . ' Agent',
                'slug' => $widget->slug . '-agent',
                'ai_model' => $widget->settings['ai_model'] ?? 'google/gemini-2.0-flash-001',
                'system_prompt' => $widget->settings['system_prompt'] ?? null,
            ]);
            
            // 2. Link widget to new agent
            $widget->update(['ai_agent_id' => $agent->id]);
            
            // 3. Move knowledge base
            if ($widget->knowledgeBase) {
                $widget->knowledgeBase->update(['ai_agent_id' => $agent->id]);
            }
        }
    });
}
```

---

**Created:** January 18, 2026  
**Priority:** HIGH  
**Status:** Planning  
**Dependencies:** None (foundation change)
