// Regression test for public/widget/widget.js :: inactivity / auto-close state machine
// Plan ref: plan/widget-inactivity-auto-close-summary-plan.md
// Run: node tests/widget/inactivityClose.regression.mjs
//
// Static guards for the bugs that made the idle offer repeat forever and
// the widget never close itself:
//  - duplicate toggleChat declaration (the simple one shadowed the
//    timer-aware one)
//  - closeOfferSent flipped back to false by the offer's own addMessage
//  - programmatic auto-scroll treated as user activity (killed the
//    close-offer/minimize timers)
// Plus the new auto-close flow: close endpoint, grace minimize, summary.

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const raw = readFileSync(path.join(root, 'public', 'widget', 'widget.js'), 'utf8');
const src = raw.replace(/\r\n/g, '\n');

function extractFunction(name) {
  const marker = `function ${name}(`;
  const start = src.indexOf(marker);
  assert.ok(start !== -1, `${name} not found in public/widget/widget.js`);
  const endMarker = '\n  }\n';
  const end = src.indexOf(endMarker, start);
  assert.ok(end !== -1, `end of ${name} not found (function structure changed?)`);
  return src.slice(start, end + '\n  }'.length);
}

let passed = 0;
const check = (name, fn) => { fn(); passed += 1; console.log(`ok - ${name}`); };

check('single toggleChat declaration (no shadowing duplicate)', () => {
  const matches = src.match(/function toggleChat\(/g) || [];
  assert.equal(matches.length, 1, `expected 1 toggleChat, found ${matches.length}`);
});

check('showCloseOffer sets closeOfferSent AFTER addMessage', () => {
  const fn = extractFunction('showCloseOffer');
  const addIdx = fn.indexOf('addMessage(');
  const flagIdx = fn.indexOf('closeOfferSent = true');
  assert.ok(addIdx !== -1, 'showCloseOffer no longer adds the offer message');
  assert.ok(flagIdx !== -1, 'showCloseOffer no longer sets closeOfferSent');
  assert.ok(flagIdx > addIdx, 'closeOfferSent must be set after addMessage (addMessage resets activity state)');
});

check('showCloseOffer skips empty conversations', () => {
  const fn = extractFunction('showCloseOffer');
  assert.ok(fn.includes("m.role === 'user'"), 'showCloseOffer must require a real conversation');
});

check('showCloseOffer arms closeConversation on timeout', () => {
  const fn = extractFunction('showCloseOffer');
  assert.ok(fn.includes('closeConversation()'), 'offer timeout must end the conversation, not just minimize');
  assert.ok(fn.includes('closeOfferTimer = setTimeout'), 'closeOfferTimer must be armed');
});

check('programmatic scroll is flagged and ignored by the scroll listener', () => {
  assert.ok(src.includes('autoScrollUntil'), 'autoScrollUntil flag missing');
  const scrollListener = src.slice(src.indexOf("addEventListener('scroll'"));
  assert.ok(
    scrollListener.slice(0, 300).includes('autoScrollUntil'),
    'scroll listener must ignore flagged programmatic scrolls'
  );
  // Only the helper itself may assign scrollTop directly.
  const rawAssigns = src.match(/\.scrollTop = /g) || [];
  assert.equal(rawAssigns.length, 1, `expected 1 raw scrollTop assignment (in scrollToBottom), found ${rawAssigns.length}`);
});

check('resetInactivityTimer clears the pending auto-minimize timer', () => {
  const fn = extractFunction('resetInactivityTimer');
  assert.ok(fn.includes('minimizeTimer'), 'resetInactivityTimer must cancel the grace minimize');
  assert.ok(fn.includes('removeOfferBubble'), 'resetInactivityTimer must remove a stale offer bubble');
});

check('closeConversation ends session, shows summary, rotates session id', () => {
  const fn = extractFunction('closeConversation');
  assert.ok(fn.includes('closeSessionOnServer()'), 'must call the close endpoint');
  assert.ok(fn.includes("sessionId = generateSessionId()"), 'must rotate sessionId so the next message starts fresh');
  assert.ok(fn.includes('minimizeTimer = setTimeout'), 'must arm the grace auto-minimize');
  assert.ok(fn.includes('config.closeGraceTimeout'), 'grace delay must come from config');
});

check('close endpoint wired to /widget/session/close', () => {
  const fn = extractFunction('closeSessionOnServer');
  assert.ok(fn.includes("config.configUrl + 'session/close'"), 'wrong close endpoint path');
  assert.ok(fn.includes("method: 'POST'"), 'close must be a POST');
});

check('manual endChat also closes the server session', () => {
  const endChat = src.indexOf('CSAI_endChat = function');
  assert.ok(endChat !== -1, 'CSAI_endChat missing');
  const body = src.slice(endChat, endChat + 400);
  assert.ok(body.includes('closeSessionOnServer()'), 'CSAI_endChat must end the session server-side');
  assert.ok(body.includes('clearHistory()'), 'CSAI_endChat must still wipe local history');
});

check('default config exposes closeGraceTimeout', () => {
  const start = src.indexOf('const defaultConfig');
  assert.ok(start !== -1, 'defaultConfig not found');
  const body = src.slice(start, src.indexOf('};', start));
  assert.ok(/closeGraceTimeout:\s*\d+/.test(body), 'closeGraceTimeout missing from defaultConfig');
});

// Loop guard: the closing message's own addMessage used to re-arm the
// idle cycle, so offer -> close -> offer repeated forever (with 403s
// after the session-id rotation). One closing per conversation, only a
// new visitor message starts a fresh cycle.

check('conversationClosed latch is declared', () => {
  assert.ok(/let conversationClosed = false/.test(src), 'conversationClosed latch missing');
});

check('showCloseOffer refuses while closed or a reply is loading', () => {
  const fn = extractFunction('showCloseOffer');
  assert.ok(fn.includes('conversationClosed'), 'showCloseOffer must respect the closing latch');
  assert.ok(fn.includes('isLoading'), 'showCloseOffer must not close while the chat reply is still in flight (unsigned session id -> 403)');
});

check('resetInactivityTimer stops the idle cycle after a closing', () => {
  const fn = extractFunction('resetInactivityTimer');
  assert.ok(/if \(conversationClosed\) return;/.test(fn), 'resetInactivityTimer must not re-arm the offer after the conversation ended');
});

check('closeConversation latches before awaiting the server', () => {
  const fn = extractFunction('closeConversation');
  const latchIdx = fn.indexOf('conversationClosed = true');
  const awaitIdx = fn.indexOf('await closeSessionOnServer');
  assert.ok(latchIdx !== -1, 'closeConversation must set the latch');
  assert.ok(awaitIdx !== -1, 'closeConversation must await the close endpoint');
  assert.ok(latchIdx < awaitIdx, 'latch must be set before the await so a racing reset cannot start another cycle');
});

check('a new visitor message lifts the closing latch', () => {
  const fn = extractFunction('sendMessage');
  assert.ok(/conversationClosed = false/.test(fn), 'sendMessage must re-enable the idle cycle for the fresh conversation');
});

check('manual endChat latches too', () => {
  const endChat = src.indexOf('CSAI_endChat = function');
  const body = src.slice(endChat, endChat + 500);
  assert.ok(body.includes('conversationClosed = true'), 'CSAI_endChat must latch so no offer cycle restarts on a wiped history');
});

console.log(`\nAll ${passed} widget inactivity/auto-close regression checks passed.`);
