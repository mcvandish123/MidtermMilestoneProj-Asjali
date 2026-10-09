/**
 * Browser-side validation for every form marked data-validate.
 * Rules come from the same HTML attributes the PHP Validator limits are printed into:
 * required, minlength, maxlength, pattern, type="email", min, max,
 * plus data-rule="password" and data-match="otherField".
 * The server checks everything again — this only gives faster feedback.
 */
(() => {
  'use strict';

  const EMAIL = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function labelOf(field) {
    return field.dataset.label || 'This field';
  }

  function errorElementFor(field) {
    const id = field.dataset.errorId || `error-${field.name}`;
    return document.getElementById(id);
  }

  /** Returns an error message, or '' when the field is valid. */
  function checkField(field) {
    const raw = field.value;
    const value = field.type === 'password' ? raw : raw.trim();
    const label = labelOf(field);
    const length = [...value].length;

    if (field.required && value === '') {
      return field.tagName === 'SELECT' ? `Choose a ${label.toLowerCase()}.` : `${label} is required.`;
    }
    if (value === '') return '';

    const min = field.getAttribute('minlength');
    if (min && length < Number(min)) return `${label} must be at least ${min} characters.`;

    const max = field.getAttribute('maxlength');
    if (max && length > Number(max)) return `${label} must be ${max} characters or fewer.`;

    if (field.type === 'email' && !EMAIL.test(value)) return 'Enter a valid email address.';

    const pattern = field.getAttribute('pattern');
    if (pattern && !new RegExp(pattern).test(value)) {
      return field.dataset.patternMessage || `${label} has an invalid format.`;
    }

    if (field.type === 'number') {
      if (!/^\d+$/.test(value)) return `${label} must be a whole number.`;
      const n = Number(value);
      const lo = field.getAttribute('min');
      const hi = field.getAttribute('max');
      if ((lo && n < Number(lo)) || (hi && n > Number(hi))) {
        return `${label} must be between ${lo} and ${hi}.`;
      }
    }

    if (field.dataset.rule === 'password' && !(/[A-Za-z]/.test(value) && /\d/.test(value))) {
      return 'Password needs at least one letter and one number.';
    }

    if (field.dataset.match) {
      const other = field.form?.elements[field.dataset.match];
      if (other && other.value !== raw) return 'Passwords do not match.';
    }

    return '';
  }

  /** Ingredient rows: at least one ingredient, and no quantity without a name. */
  function checkIngredients(form) {
    const list = form.querySelector('[data-ingredient-list]');
    if (!list) return '';
    const rows = [...list.querySelectorAll('.ingredient-row')];
    let filled = 0;
    for (let i = 0; i < rows.length; i++) {
      const qty = rows[i].querySelector('.ingredient-qty').value.trim();
      const name = rows[i].querySelector('.ingredient-name').value.trim();
      if (qty && !name) return `Ingredient #${i + 1} needs a name.`;
      if (name) filled++;
    }
    return filled === 0 ? 'Add at least one ingredient.' : '';
  }

  function show(field, message) {
    const errorEl = errorElementFor(field);
    if (errorEl) errorEl.textContent = message;
    field.setAttribute('aria-invalid', message ? 'true' : 'false');
  }

  function showIngredients(form, message) {
    const errorEl = form.querySelector('#error-ingredients');
    if (errorEl) errorEl.textContent = message;
    form.querySelector('[data-ingredients]')?.classList.toggle('has-error', Boolean(message));
  }

  function fieldsOf(form) {
    // Skip hidden inputs and the ingredient arrays (checked as a group).
    return [...form.elements].filter((el) =>
      el.name && !el.name.endsWith('[]') && el.type !== 'hidden' &&
      ['INPUT', 'TEXTAREA', 'SELECT'].includes(el.tagName)
    );
  }

  document.querySelectorAll('form[data-validate]').forEach((form) => {
    fieldsOf(form).forEach((field) => {
      field.addEventListener('blur', () => {
        if (field.value !== '' || field.getAttribute('aria-invalid') === 'true') {
          show(field, checkField(field));
        }
      });
      field.addEventListener('input', () => {
        if (field.getAttribute('aria-invalid') === 'true') show(field, checkField(field));
        // Re-check the confirmation when the password changes.
        if (field.name === 'password') {
          const confirm = form.elements.password_confirm;
          if (confirm && confirm.value) show(confirm, checkField(confirm));
        }
      });
    });

    form.addEventListener('input', (e) => {
      if (e.target.matches('.ingredient-qty, .ingredient-name') &&
          form.querySelector('[data-ingredients].has-error')) {
        showIngredients(form, checkIngredients(form));
      }
    });

    form.addEventListener('submit', (e) => {
      let firstInvalid = null;

      fieldsOf(form).forEach((field) => {
        const message = checkField(field);
        show(field, message);
        if (message && !firstInvalid) firstInvalid = field;
      });

      const ingredientMessage = checkIngredients(form);
      showIngredients(form, ingredientMessage);
      if (ingredientMessage && !firstInvalid) {
        firstInvalid = form.querySelector('.ingredient-name');
      }

      if (firstInvalid) {
        e.preventDefault();
        e.stopImmediatePropagation(); // don't show a delete confirm for an invalid form
        firstInvalid.focus();
        firstInvalid.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return;
      }

      // Prevent double submits.
      const submit = form.querySelector('[type="submit"]');
      if (submit) {
        submit.disabled = true;
        setTimeout(() => { submit.disabled = false; }, 4000);
      }
    });
  });
})();
