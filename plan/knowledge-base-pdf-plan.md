# Knowledge Base + PDF Upload Implementation Plan

## 🎯 Overview

Implementasi fitur Knowledge Base yang memungkinkan user upload berbagai file (PDF, DOCX, TXT) untuk training AI chatbot.

**Estimasi:** 3-5 hari

---

## 📦 Features

1. **File Upload**
   - PDF, DOCX, TXT support
   - Max file size: 10MB (free), 25MB (paid)
   - Max files: 5 (free), 20 (paid)
   
2. **Text Extraction**
   - Parse PDF/DOCX ke plain text
   - Chunking untuk context window
   
3. **Storage**
   - File disimpan di local/S3
   - Text extracted di database
   
4. **Integration**
   - Include dalam AI prompt context
   - Search/RAG capability (future)

---

## 🗄️ Database Schema

### New Table: `knowledge_documents`

```sql
CREATE TABLE knowledge_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    knowledge_base_id BIGINT UNSIGNED NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    storage_path VARCHAR(500) NOT NULL,
    file_type ENUM('pdf', 'docx', 'txt', 'url') NOT NULL,
    file_size INT UNSIGNED NOT NULL, -- bytes
    extracted_text LONGTEXT,
    chunk_count INT UNSIGNED DEFAULT 0,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    processing_started_at TIMESTAMP NULL,
    processing_completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (knowledge_base_id) REFERENCES knowledge_bases(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_file_type (file_type)
);
```

### New Table: `document_chunks` (untuk RAG di masa depan)

```sql
CREATE TABLE document_chunks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    knowledge_document_id BIGINT UNSIGNED NOT NULL,
    chunk_index INT UNSIGNED NOT NULL,
    content TEXT NOT NULL,
    token_count INT UNSIGNED DEFAULT 0,
    embedding BLOB NULL, -- untuk vector search nanti
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (knowledge_document_id) REFERENCES knowledge_documents(id) ON DELETE CASCADE,
    INDEX idx_document_chunk (knowledge_document_id, chunk_index)
);
```

### Update `knowledge_bases` table

Tambah kolom:
- `max_documents` INT DEFAULT 5
- `max_file_size` INT DEFAULT 10485760 (10MB)
- `total_storage_used` BIGINT DEFAULT 0

---

## 📁 File Structure

```
app/
├── Models/
│   ├── KnowledgeDocument.php      # NEW
│   └── DocumentChunk.php          # NEW
├── Services/
│   └── DocumentParser/
│       ├── DocumentParserInterface.php
│       ├── PdfParser.php           # NEW
│       ├── DocxParser.php          # NEW
│       ├── TxtParser.php           # NEW
│       └── DocumentParserFactory.php
├── Jobs/
│   └── ProcessDocumentJob.php      # NEW (Queue)
├── Http/Controllers/
│   └── KnowledgeDocumentController.php  # NEW
├── Livewire/
│   └── DocumentUploader.php        # NEW
resources/views/
├── livewire/
│   └── document-uploader.blade.php # NEW
├── chatbots/tabs/
│   └── documents.blade.php         # NEW
```

---

## 🔧 Implementation Steps

### Phase 1: Database & Models (Day 1)

1. Create migrations
2. Create Eloquent models
3. Update KnowledgeBase model relationships

### Phase 2: File Parsers (Day 1-2)

1. Install parser packages:
   ```bash
   composer require smalot/pdfparser
   composer require phpoffice/phpword
   ```

2. Create parser classes
3. Create factory pattern for parsers

### Phase 3: Upload Interface (Day 2)

1. Create Livewire component for file upload
2. Drag & drop UI
3. Upload progress indicator
4. File list with status

### Phase 4: Queue Processing (Day 2-3)

1. Create ProcessDocumentJob
2. Extract text in background
3. Update status & error handling
4. Email notification on completion (optional)

### Phase 5: AI Integration (Day 3)

1. Update AI prompt builder to include document content
2. Implement smart chunking for context limits
3. Prioritize relevant chunks (keyword matching for now)

### Phase 6: Testing & Polish (Day 4-5)

1. Test various PDF formats
2. Handle edge cases (scanned PDFs, corrupted files)
3. Add quota enforcement
4. Performance optimization

---

## 📝 Detailed Specs

### DocumentParserInterface

```php
interface DocumentParserInterface
{
    public function parse(string $filePath): ParsedDocument;
    public function supports(string $fileType): bool;
}

class ParsedDocument
{
    public string $text;
    public array $metadata;
    public int $pageCount;
    public int $wordCount;
}
```

### Chunking Strategy

```php
// Split text into ~1000 token chunks with overlap
const CHUNK_SIZE = 1000;      // tokens
const CHUNK_OVERLAP = 100;    // tokens

function chunkText(string $text): array {
    // 1. Split by paragraphs first
    // 2. Combine until chunk size reached
    // 3. Include overlap from previous chunk
    // 4. Return array of chunks with metadata
}
```

### AI Context Building

```php
function buildContext(KnowledgeBase $kb, string $userMessage): string {
    $context = "";
    
    // 1. Include FAQs first (highest priority)
    foreach ($kb->faqs as $faq) {
        $context .= "Q: {$faq->question}\nA: {$faq->answer}\n\n";
    }
    
    // 2. Include document chunks (most relevant first)
    $relevantChunks = $this->findRelevantChunks($kb, $userMessage);
    foreach ($relevantChunks as $chunk) {
        if (strlen($context) > MAX_CONTEXT_LENGTH) break;
        $context .= "---\n{$chunk->content}\n";
    }
    
    return $context;
}
```

---

## 🎨 UI Design

### Document Tab in Chatbot Editor

```
┌─────────────────────────────────────────────────────────┐
│ 📁 Dokumen Pendukung                    [+ Upload File] │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────────────────────────────────────────┐    │
│  │  📄 Drag & drop files here                      │    │
│  │                                                 │    │
│  │     PDF, DOCX, TXT (Max 10MB)                   │    │
│  │                                                 │    │
│  │            [Browse Files]                       │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  Uploaded Documents (3/5)                               │
│  ──────────────────────────────────────────────────    │
│  📕 product-catalog.pdf          2.4MB  ✅ Ready        │
│  📘 faq-lengkap.docx            156KB  ✅ Ready        │
│  📄 cara-order.txt               12KB  🔄 Processing   │
│                                                         │
│  💡 Tips: Upload katalog, SOP, FAQ, atau dokumen        │
│     lain yang sering ditanyakan pelanggan.              │
└─────────────────────────────────────────────────────────┘
```

---

## ✅ Checklist

### Phase 1: Database
- [ ] Create migration for `knowledge_documents`
- [ ] Create migration for `document_chunks`  
- [ ] Update `knowledge_bases` migration
- [ ] Create KnowledgeDocument model
- [ ] Create DocumentChunk model
- [ ] Update KnowledgeBase model relationships

### Phase 2: Parsers
- [ ] Install composer packages
- [ ] Create DocumentParserInterface
- [ ] Create PdfParser
- [ ] Create DocxParser
- [ ] Create TxtParser
- [ ] Create DocumentParserFactory

### Phase 3: Upload UI
- [ ] Create Livewire DocumentUploader component
- [ ] Create upload blade view
- [ ] Add documents tab to chatbot editor
- [ ] Progress bar & status indicators

### Phase 4: Processing
- [ ] Create ProcessDocumentJob
- [ ] Queue configuration
- [ ] Error handling & retry logic
- [ ] Status updates via Livewire

### Phase 5: AI Integration
- [ ] Update prompt builder
- [ ] Implement chunking
- [ ] Smart context selection
- [ ] Test with real documents

### Phase 6: Polish
- [ ] Quota enforcement (file count & size)
- [ ] Delete functionality
- [ ] Download original file
- [ ] Preview extracted text

---

**Created:** January 18, 2026  
**Priority:** HIGH  
**Status:** Planning
