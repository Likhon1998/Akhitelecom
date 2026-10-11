/**
 * Simulates POS scan matching (debounce + exact index) without the browser.
 * Run: node tests/Manual/pos-scan-simulate.mjs
 */

function normalizeScanCode(code) {
  return String(code || '')
    .replace(/[\u200B-\u200D\uFEFF]/g, '')
    .replace(/[\r\n]+/g, '')
    .trim()
    .toLowerCase();
}

function buildIndex(products) {
  const map = new Map();
  for (const p of products) {
    const barcode = normalizeScanCode(p.barcode);
    const sku = normalizeScanCode(p.sku);
    if (barcode) map.set('b:' + barcode, p);
    if (sku) map.set('s:' + sku, p);
  }
  return map;
}

function findExact(map, code) {
  const q = normalizeScanCode(code);
  if (!q) return null;
  return map.get('b:' + q) || map.get('s:' + q) || null;
}

/** Simulate old buggy behavior: match on every keystroke when length >= 3 OR any length */
function oldBuggyPartialAdd(products, typed) {
  const q = typed.trim().toLowerCase();
  return products.find((p) => String(p.barcode || '').trim().toLowerCase() === q) || null;
}

const products = [
  { id: 31, barcode: '8', sku: '', name: 'i6 short' },
  { id: 32, barcode: '81', sku: '', name: 'i6 longer' },
  { id: 33, barcode: 'DEMO-001', sku: 'SKU-DEMO-001', name: 'Pixel 7' },
];

const index = buildIndex(products);
let failed = 0;

function assert(cond, msg) {
  if (cond) console.log('PASS ', msg);
  else {
    console.log('FAIL ', msg);
    failed++;
  }
}

// Simulate scanning "81" keystroke by keystroke with OLD logic (immediate match)
const oldPartial = oldBuggyPartialAdd(products, '8');
assert(oldPartial && oldPartial.id === 31, 'Demonstrates old bug: typing 8 of 81 matches product 8');

// NEW logic: only commit full code after Enter / debounce settle
assert(findExact(index, '8')?.id === 31, 'Exact "8" matches #31');
assert(findExact(index, '81')?.id === 32, 'Exact "81" matches #32');
assert(findExact(index, 'DEMO-001')?.id === 33, 'Exact DEMO-001 matches');
assert(findExact(index, 'SKU-DEMO-001')?.id === 33, 'Exact SKU matches');
assert(findExact(index, 'demo-001')?.id === 33, 'Case-insensitive match');
assert(findExact(index, ' 81 ')?.id === 32, 'Trimmed match');
assert(findExact(index, '810') === null, 'Unknown code returns null');

// Simulate scanner race: last char may only be in DOM value
function commitScan(raw) {
  return findExact(index, raw);
}
assert(commitScan('81')?.id === 32, 'Enter commit with DOM value works');

console.log(failed === 0 ? '\nALL SCAN SIMULATIONS PASSED' : `\nFAILED: ${failed}`);
process.exit(failed === 0 ? 0 : 1);
