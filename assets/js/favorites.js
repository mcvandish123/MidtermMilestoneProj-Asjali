/**
 * Save / unsave favorites without reloading the page (Fetch API + async/await).
 * Works for every button marked data-favorite on the page.
 */
(() => {
  'use strict';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
  const toast = (msg, type) => window.Kusina?.toast(msg, type);

  /** Update every favorite button and counter for this recipe on the page. */
  function setState(recipeId, favorited, count) {
    document.querySelectorAll(`[data-favorite][data-recipe-id="${recipeId}"]`).forEach((btn) => {
      btn.setAttribute('aria-pressed', String(favorited));
      btn.setAttribute('aria-label', favorited ? 'Remove from favorites' : 'Save to favorites');
      const text = btn.querySelector('.fav-text');
      if (text) text.textContent = favorited ? 'Saved' : 'Save';
    });
    if (typeof count === 'number') {
      document.querySelectorAll(`[data-fav-count="${recipeId}"]`).forEach((el) => {
        el.textContent = String(count);
      });
    }
  }

  async function sendFavorite(recipeId, action) {
    const res = await fetch('api/favorite.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-CSRF-Token': csrfToken,
      },
      body: new URLSearchParams({ recipe_id: recipeId, action }),
    });

    let data = {};
    try {
      data = await res.json();
    } catch {
      throw new Error('The server sent an unexpected response.');
    }
    if (!res.ok || !data.ok) {
      throw new Error(data.error || `Request failed (HTTP ${res.status}).`);
    }
    return data;
  }

  function removeCard(button) {
    const card = button.closest('[data-recipe-card]');
    if (!card) return;
    card.classList.add('is-leaving');
    setTimeout(() => {
      const grid = card.parentElement;
      card.remove();
      if (grid && !grid.querySelector('[data-recipe-card]')) {
        grid.remove();
        const empty = document.querySelector('[data-favorites-empty]');
        if (empty) empty.hidden = false;
      }
    }, 250);
  }

  document.addEventListener('click', async (e) => {
    const button = e.target.closest('[data-favorite]');
    if (!button || button.dataset.busy) return;

    const recipeId = button.dataset.recipeId;
    const wasSaved = button.getAttribute('aria-pressed') === 'true';

    button.dataset.busy = '1';
    setState(recipeId, !wasSaved); // optimistic update

    try {
      const data = await sendFavorite(recipeId, wasSaved ? 'remove' : 'add');
      setState(recipeId, data.favorited, data.count);
      toast(data.favorited ? 'Saved to your favorites.' : 'Removed from your favorites.', 'success');
      if (!data.favorited && button.hasAttribute('data-remove-card')) {
        removeCard(button);
      }
    } catch (err) {
      setState(recipeId, wasSaved); // undo the optimistic update
      toast(err.message, 'error');
    } finally {
      delete button.dataset.busy;
    }
  });
})();
