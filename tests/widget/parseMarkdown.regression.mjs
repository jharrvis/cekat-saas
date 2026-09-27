// Regression test for public/widget/widget.js :: parseMarkdown
// Plan ref: plan/business-workflow-ui-ux-robustness-plan.md (widget rendering section)
// Run: node tests/widget/parseMarkdown.regression.mjs
//
// The test extracts the REAL parseMarkdown function from public/widget/widget.js
// (not a copy) and asserts the plan's acceptance cases:
//  - plain URL reply: https://www.floodbar.id/order
//  - markdown link reply: [halaman order Floodbar](https://www.floodbar.id/order)
//  - numbered list containing both markdown links and plain URLs
//  - trailing punctuation after URLs
//  - no output contains %3Ca%20href=, &lt;a href, or nested anchor tags
//  - all generated links include target="_blank" and rel="noopener noreferrer"

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';
import assert from 'node:assert/strict';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..');
const raw = readFileSync(path.join(root, 'public', 'widget', 'widget.js'), 'utf8');
const src = raw.replace(/\r\n/g, '\n');

const startMarker = '  function parseMarkdown(text) {';
const start = src.indexOf(startMarker);
assert.ok(start !== -1, 'parseMarkdown not found in public/widget/widget.js');

// NOTE: the end of the function is located via the closing brace at the same
// 2-space indent. If parseMarkdown is ever restructured so this marker no
// longer matches, the guards below fail loudly instead of silently testing
// the wrong code: the extracted source must parse AND must contain the
// link-hardening markers (__MD_LINK_ placeholder + isSafeHttpUrl).
const endMarker = '\n  }\n';
const end = src.indexOf(endMarker, start);
assert.ok(end !== -1, 'end of parseMarkdown not found (function structure changed?)');

const fnSource = src.slice(start, end + '\n  }'.length);
assert.ok(fnSource.includes('__MD_LINK_'), 'extracted source missing __MD_LINK_ placeholder logic');
assert.ok(fnSource.includes('isSafeHttpUrl'), 'extracted source missing isSafeHttpUrl validation');
assert.ok(fnSource.includes('noopener'), 'extracted source missing rel="noopener noreferrer"');
// Evaluate the extracted declaration and grab the function reference
// (throws SyntaxError immediately if the slice is not valid JS).
const parseMarkdown = new Function(`${fnSource}; return parseMarkdown;`)();

const countAnchors = (html) => (html.match(/<a[\s>]/g) || []).length;
const assertClean = (html, label) => {
  assert.ok(!html.includes('%3Ca%20href='), `${label}: contains encoded anchor fragment`);
  assert.ok(!html.includes('&lt;a href'), `${label}: contains escaped anchor`);
  assert.ok(!/<a[^>]*<a[\s>]/.test(html), `${label}: contains nested anchor tags`);
};

let passed = 0;
const check = (name, fn) => { fn(); passed += 1; console.log(`ok - ${name}`); };

// 1. Plain URL renders once as a valid anchor
check('plain URL reply', () => {
  const out = parseMarkdown('Silakan order di https://www.floodbar.id/order ya kak');
  assert.equal(countAnchors(out), 1);
  assert.ok(out.includes('<a href="https://www.floodbar.id/order" target="_blank" rel="noopener noreferrer" class="csai-link">'));
  assertClean(out, 'plain URL');
});

// 2. Markdown link renders once, no double-processing of the href
check('markdown link reply', () => {
  const out = parseMarkdown('Silakan buka [halaman order Floodbar](https://www.floodbar.id/order)');
  assert.equal(countAnchors(out), 1);
  assert.ok(out.includes('>halaman order Floodbar</a>'));
  assert.ok(out.includes('href="https://www.floodbar.id/order"'));
  assertClean(out, 'markdown link');
});

// 3. Numbered list containing both markdown links and plain URLs
check('numbered list with mixed links', () => {
  const input = 'Berikut opsinya:\n1. Buka [halaman order Floodbar](https://www.floodbar.id/order)\n2. Atau langsung ke https://www.floodbar.id/order untuk checkout';
  const out = parseMarkdown(input);
  assert.equal(countAnchors(out), 2);
  assertClean(out, 'numbered list');
});

// 4. Trailing punctuation stays outside the anchor
check('trailing punctuation', () => {
  const out = parseMarkdown('Cek https://www.floodbar.id/order, lalu https://www.floodbar.id/order.');
  assert.equal(countAnchors(out), 2);
  assert.ok(!out.includes('href="https://www.floodbar.id/order,"'));
  assert.ok(!out.includes('href="https://www.floodbar.id/order."'));
  assert.ok(out.includes('</a>, lalu'));
  assert.ok(out.includes('</a>.'));
  assertClean(out, 'trailing punctuation');
});

// 5. Plain URL with closing paren (e.g. inside sentence) is trimmed
check('trailing paren', () => {
  const out = parseMarkdown('info lengkap (lihat https://www.floodbar.id/order)');
  assert.equal(countAnchors(out), 1);
  assert.ok(out.includes('href="https://www.floodbar.id/order"'));
  assert.ok(out.includes('</a>)'));
  assertClean(out, 'trailing paren');
});

// 6. XSS payload stays escaped, code blocks untouched
check('xss + code block safety', () => {
  const out = parseMarkdown('<script>alert(1)</script> dan ```https://www.floodbar.id/order```');
  assert.ok(!out.includes('<script>'));
  assert.ok(out.includes('&lt;script&gt;'));
  assert.ok(out.includes('<pre><code>https://www.floodbar.id/order</code></pre>'));
});

// 7. Markdown link with quote in URL is NOT linkified (attribute-breakout attempt)
check('markdown link quote injection left as text', () => {
  const out = parseMarkdown('coba [klik](https://x.com/"onmouseover="alert(1)) ini');
  assert.equal(countAnchors(out), 0);
  assert.ok(!out.includes('onmouseover="alert(1)"'));
  assertClean(out, 'quote injection');
});

// 8. Plain URL with embedded quote is NOT linkified
check('plain URL quote injection left as text', () => {
  const out = parseMarkdown('lihat https://x.com/a"b{c}d dan lanjut');
  assert.equal(countAnchors(out), 0);
  assertClean(out, 'plain quote injection');
});

// 9. Every generated anchor carries target + rel
check('anchors always have target and rel', () => {
  const out = parseMarkdown('[a](https://a.id/x) dan https://b.id/y');
  const opens = out.match(/<a[^>]*>/g) || [];
  assert.equal(opens.length, 2);
  for (const tag of opens) {
    assert.ok(tag.includes('target="_blank"'), `missing target: ${tag}`);
    assert.ok(tag.includes('rel="noopener noreferrer"'), `missing rel: ${tag}`);
  }
});

console.log(`\nAll ${passed} widget parseMarkdown regression checks passed.`);
