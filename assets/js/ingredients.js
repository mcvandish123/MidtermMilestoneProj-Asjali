/**
 * Dynamic ingredient rows on the create / edit recipe form.
 * "+ Add ingredient" clones a <template>, × removes a row,
 * and pressing Enter in the last ingredient name adds a new row.
 */
(() => {
  'use strict';

  const fieldset = document.querySelector('[data-ingredients]');
  if (!fieldset) return;

  const list = fieldset.querySelector('[data-ingredient-list]');
  const template = document.getElementById('ingredient-row-template');
  const addButton = fieldset.querySelector('[data-add-ingredient]');
  const countLabel = fieldset.querySelector('[data-ingredient-count]');
  const max = Number(fieldset.dataset.max) || 50;

  function rows() {
    return [...list.querySelectorAll('.ingredient-row')];
  }

  /** Keep numbers and accessible labels in order after adding/removing. */
  function renumber() {
    const all = rows();
    all.forEach((row, i) => {
      const n = i + 1;
      row.querySelector('.ingredient-number').textContent = n;
      row.querySelector('.ingredient-qty').setAttribute('aria-label', `Quantity for ingredient ${n}`);
      row.querySelector('.ingredient-name').setAttribute('aria-label', `Ingredient ${n}`);
      row.querySelector('[data-remove-ingredient]').setAttribute('aria-label', `Remove ingredient ${n}`);
    });
    addButton.disabled = all.length >= max;
    countLabel.textContent = all.length >= max
      ? `Maximum of ${max} ingredients reached.`
      : `${all.length} of ${max} rows`;
  }

  function addRow(focus = true) {
    if (rows().length >= max) return null;
    const row = template.content.firstElementChild.cloneNode(true);
    list.append(row);
    renumber();
    if (focus) row.querySelector('.ingredient-qty').focus();
    return row;
  }

  function removeRow(row) {
    if (rows().length === 1) {
      // Keep one row; just clear it.
      row.querySelectorAll('input').forEach((input) => { input.value = ''; });
      row.querySelector('.ingredient-qty').focus();
      return;
    }
    const next = row.nextElementSibling || row.previousElementSibling;
    row.remove();
    renumber();
    next?.querySelector('.ingredient-name')?.focus();
  }

  addButton.addEventListener('click', () => addRow());

  list.addEventListener('click', (e) => {
    const button = e.target.closest('[data-remove-ingredient]');
    if (button) removeRow(button.closest('.ingredient-row'));
  });

  list.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || !e.target.matches('.ingredient-qty, .ingredient-name')) return;
    e.preventDefault(); // Enter should not submit the whole recipe
    const row = e.target.closest('.ingredient-row');
    if (e.target.matches('.ingredient-qty')) {
      row.querySelector('.ingredient-name').focus();
    } else if (row === rows().at(-1)) {
      addRow();
    } else {
      row.nextElementSibling.querySelector('.ingredient-qty').focus();
    }
  });

  renumber();
})();
