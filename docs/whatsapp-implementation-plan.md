# Cekat SaaS WhatsApp Gateway Integration Plan

## Goal Description
Integrate the new self-hosted "Baileys + Express" WhatsApp gateway into the existing Cekat SaaS application. This requires decoupling `WhatsAppManager` from `FonnteService` and introducing a driver-based architecture to support the new gateway alongside (or replacing) Fonnte.

## User Review Required
> [!IMPORTANT]
> This change involves refactoring `WhatsAppManager.php`. While we will attempt to maintain backward compatibility, it changes the core messaging logic.
> **Deployment Note:** The Node.js gateway must be running and accessible by the Laravel app (e.g., `http://localhost:3000`).

## Proposed Changes

### 1. New Service Integration
#### [NEW] `app/Services/WhatsApp/BaileysService.php`
- Implements the messaging interface for the custom gateway.
- Methods: `sendMessage`, `getQR`, `getDeviceStatus`, `disconnectDevice`.
- Connects to the Node.js gateway via HTTP.

### 2. Service Refactoring
#### [MODIFY] `app/Services/WhatsApp/WhatsAppManager.php`
- Remove hardcoded `new FonnteService()`.
- Add logic to select driver based on configuration (`config('whatsapp.driver')` or `.env`).
- Example:
  ```php
  // function __construct()
  $driver = config('whatsapp.driver', 'fonnte');
  $this->service = $driver === 'baileys' ? new BaileysService() : new FonnteService();
  ```

### 3. Webhook Compatibility
#### [MODIFY] `app/Http/Controllers/Api/WhatsAppWebhookController.php`
- Ensure it can handle the payload sent by the custom gateway.
- If the gateway mimics Fonnte's payload, no changes might be needed here (preferred).

## Verification Plan

### Automated Tests
- None existing for WhatsApp logic.
- We will add standard PHPUnit tests for `BaileysService` to mock HTTP requests to the gateway.

### Manual Verification
1.  **Start Gateway**: Run `node index.js` in the gateway folder.
2.  **Configure Laravel**: Set `WHATSAPP_DRIVER=baileys` and `WHATSAPP_GATEWAY_URL=http://localhost:3000` in `.env`.
3.  **Connect Device**: Go to Cekat Dashboard > WhatsApp > Add Device. Scan the QR code served by the new gateway.
4.  **Send Message**: Use the "Test Message" feature (if available) or trigger an auto-reply.
5.  **Receive Message**: Send a message to the bot and verify the webhook triggers the AI response.
