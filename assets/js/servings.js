/**
 * OWN FEATURE — Servings scaler.
 * On a recipe page with a servings count, − / + rescales every ingredient
 * quantity that starts with a number ("1 1/2 cups", "1/2 kilo", "0.5 L", "2-3 pcs").
 * Text-only quantities like "to taste" stay as they are.
 */
(() => {
  'use strict';

  const panel = document.querySelector('[data-scaler]');
  if (!panel) return;

  const base = Number(panel.dataset.baseServings);
  const output = panel.querySelector('[data-scale-output]');
  if (!base || !output) return;

  const MIN = 1;
  const MAX = 50;
  let servings = base;

  // number formats: "1 1/2", "1/2", "1.5", "2"
  const NUM = String.raw`(\d+\s+\d+\/\d+|\d+\/\d+|\d+(?:\.\d+)?)`;
  const LEADING = new RegExp(`^\\s*${NUM}(?:\\s*-\\s*${NUM})?(.*)$`);

  function parseNumber(text) {
    const t = text.trim();
    if (t.includes(' ')) {
      const [whole, frac] = t.split(/\s+/);
      return Number(whole) + parseNumber(frac);
    }
    if (t.includes('/')) {
      const [a, b] = t.split('/').map(Number);
      return b ? a / b : NaN;
    }
    return Number(t);
  }

  /** Show friendly kitchen fractions (¼, ⅓, ½, ⅔, ¾) instead of long decimals. */
  function formatNumber(value) {
    const fractions = [[0, ''], [0.25, '1/4'], [1 / 3, '1/3'], [0.5, '1/2'], [2 / 3, '2/3'], [0.75, '3/4'], [1, '']];
    let whole = Math.floor(value);
    const rest = value - whole;
    let best = fractions[0];
    for (const f of fractions) {
      if (Math.abs(rest - f[0]) < Math.abs(rest - best[0])) best = f;
    }
    if (Math.abs(rest - best[0]) > 0.06) {
      return String(Math.round(value * 10) / 10); // not close to a nice fraction
    }
    if (best[0] === 1) whole += 1;
    if (!best[1]) return String(whole);
    return whole ? `${whole} ${best[1]}` : best[1];
  }

  function scaleQuantity(original, factor) {
    const match = original.match(LEADING);
    if (!match) return original;
    const [, first, second, rest] = match;
    const a = parseNumber(first);
    if (Number.isNaN(a)) return original;
    let scaled = formatNumber(a * factor);
    if (second) scaled += `–${formatNumber(parseNumber(second) * factor)}`;
    return scaled + rest;
  }

  function render() {
    const factor = servings / base;
    panel.querySelectorAll('.qty[data-qty]').forEach((el) => {
      el.textContent = factor === 1 ? el.dataset.qty : scaleQuantity(el.dataset.qty, factor);
      el.classList.toggle('is-scaled', factor !== 1);
    });
    output.textContent = `${servings} serving${servings === 1 ? '' : 's'}`;
    panel.querySelector('[data-scale-step="-1"]').disabled = servings <= MIN;
    panel.querySelector('[data-scale-step="1"]').disabled = servings >= MAX;
  }

  panel.addEventListener('click', (e) => {
    const step = e.target.closest('[data-scale-step]');
    if (!step) return;
    servings = Math.min(MAX, Math.max(MIN, servings + Number(step.dataset.scaleStep)));
    render();
  });

  render();
})();
