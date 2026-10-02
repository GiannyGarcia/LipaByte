document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      const message = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(message)) {
        e.preventDefault();
      }
    });
  });

  document.querySelectorAll('.alert').forEach((alert) => {
    setTimeout(() => {
      alert.style.transition = 'opacity .4s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 400);
    }, 5000);
  });

  const toggleFilters = document.getElementById('toggleFilters');
  const closeFilters = document.getElementById('closeFilters');
  const filtersPanel = document.getElementById('filters');
  const filterBackdrop = document.getElementById('filterBackdrop');

  function openFilters() {
    if (!filtersPanel || !filterBackdrop) return;
    filtersPanel.classList.add('open');
    filtersPanel.setAttribute('aria-hidden', 'false');
    filterBackdrop.hidden = false;
    document.body.style.overflow = 'hidden';
  }

  function closeFiltersPanel() {
    if (!filtersPanel || !filterBackdrop) return;
    filtersPanel.classList.remove('open');
    filtersPanel.setAttribute('aria-hidden', 'true');
    filterBackdrop.hidden = true;
    document.body.style.overflow = '';
  }

  toggleFilters?.addEventListener('click', openFilters);
  closeFilters?.addEventListener('click', closeFiltersPanel);
  filterBackdrop?.addEventListener('click', closeFiltersPanel);

  document.querySelectorAll('.gallery-thumb').forEach((thumb) => {
    thumb.addEventListener('click', () => {
      const main = document.getElementById('mainImage');
      const src = thumb.getAttribute('data-src');
      if (main && src) {
        main.src = src;
        document.querySelectorAll('.gallery-thumb').forEach((t) => t.classList.remove('active'));
        thumb.classList.add('active');
      }
    });
  });

  const searchInput = document.querySelector('.mp-search-input');
  if (searchInput) {
    searchInput.closest('form')?.addEventListener('submit', (e) => {
      if (searchInput.value.trim() === '' && !window.location.search.includes('q=')) {
        e.preventDefault();
      }
    });
  }

  const marketplaceHow = document.getElementById('marketplaceHow');
  if (marketplaceHow) {
    const steps = marketplaceHow.querySelectorAll('.marketplace-how-step');
    const detailPanel = marketplaceHow.querySelector('.marketplace-how-detail');
    const detailText = detailPanel?.querySelector('p');
    let pinned = false;

    const showDetail = (step) => {
      if (!step || !detailText || !detailPanel) return;
      steps.forEach((s) => {
        const active = s === step;
        s.classList.toggle('is-active', active);
        s.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      detailText.textContent = step.dataset.detail || '';
      detailPanel.setAttribute('aria-labelledby', step.id || '');
      detailPanel.hidden = false;
      marketplaceHow.classList.add('is-detail-visible');
    };

    const hideDetail = () => {
      pinned = false;
      marketplaceHow.classList.remove('is-detail-visible', 'is-detail-pinned');
      if (detailPanel) detailPanel.hidden = true;
      steps.forEach((s) => {
        s.classList.remove('is-active');
        s.setAttribute('aria-selected', 'false');
      });
    };

    steps.forEach((step) => {
      step.addEventListener('mouseenter', () => showDetail(step));
      step.addEventListener('focus', () => showDetail(step));
      step.addEventListener('click', (e) => {
        e.stopPropagation();
        if (pinned && step.classList.contains('is-active')) {
          hideDetail();
          return;
        }
        pinned = true;
        marketplaceHow.classList.add('is-detail-pinned');
        showDetail(step);
      });
    });

    marketplaceHow.addEventListener('mouseleave', () => {
      if (!pinned) hideDetail();
    });

    document.addEventListener('click', (e) => {
      if (!marketplaceHow.contains(e.target)) hideDetail();
    });
  }

  const rentForm = document.getElementById('rentForm');
  if (rentForm) {
    const dailyRate = parseFloat(rentForm.dataset.dailyRate || '0');
    const currency = rentForm.dataset.currency || '₱';
    const startInput = rentForm.querySelector('#start_date');
    const endInput = rentForm.querySelector('#end_date');
    const estimate = document.getElementById('rentPriceEstimate');
    const placeholder = document.getElementById('rentPricePlaceholder');
    const breakdown = document.getElementById('rentPriceBreakdown');
    const totalEl = document.getElementById('rentPriceTotal');

    const formatMoney = (amount) => `${currency}${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

    const setEmptyEstimate = () => {
      if (!estimate) return;
      estimate.classList.remove('is-calculated');
      estimate.classList.add('is-empty');
      if (placeholder) placeholder.hidden = false;
      if (breakdown) {
        breakdown.textContent = '';
        breakdown.hidden = true;
      }
      if (totalEl) {
        totalEl.textContent = '';
        totalEl.hidden = true;
      }
    };

    const updateEstimate = () => {
      if (!startInput?.value || !endInput?.value || !estimate) {
        setEmptyEstimate();
        return;
      }
      const start = new Date(startInput.value);
      const end = new Date(endInput.value);
      if (Number.isNaN(start.getTime()) || Number.isNaN(end.getTime()) || end < start) {
        setEmptyEstimate();
        return;
      }
      const msPerDay = 86400000;
      const days = Math.max(1, Math.ceil((end - start) / msPerDay) + 1);
      const total = days * dailyRate;
      estimate.classList.remove('is-empty');
      estimate.classList.add('is-calculated');
      if (placeholder) placeholder.hidden = true;
      if (breakdown) {
        breakdown.textContent = `${days} day${days === 1 ? '' : 's'} × ${formatMoney(dailyRate)}`;
        breakdown.hidden = false;
      }
      if (totalEl) {
        totalEl.textContent = formatMoney(total);
        totalEl.hidden = false;
      }
    };

    setEmptyEstimate();

    startInput?.addEventListener('change', () => {
      if (endInput && endInput.value && endInput.value < startInput.value) {
        endInput.value = startInput.value;
      }
      if (endInput) {
        endInput.min = startInput.value;
      }
      updateEstimate();
    });
    endInput?.addEventListener('change', updateEstimate);
  }

  const chatFeed = document.getElementById('chatFeed');
  const chatCompose = document.getElementById('chatCompose');
  if (chatFeed && chatCompose) {
    const requestId = chatFeed.dataset.request;
    const apiUrl = chatFeed.dataset.api;
    const pollMs = parseInt(chatFeed.dataset.poll || '3000', 10);
    const userId = parseInt(chatFeed.dataset.user || '0', 10);
    let lastId = 0;

    chatFeed.querySelectorAll('.chat-bubble[data-id]').forEach((el) => {
      const id = parseInt(el.dataset.id || '0', 10);
      if (id > lastId) lastId = id;
    });

    const scrollFeed = () => {
      chatFeed.scrollTop = chatFeed.scrollHeight;
    };
    scrollFeed();

    const escapeHtml = (value) => {
      const div = document.createElement('div');
      div.textContent = value;
      return div.innerHTML;
    };

    const appendMessage = (message) => {
      if (chatFeed.querySelector(`[data-id="${message.id}"]`)) return;
      const isMine = !message.is_system && message.sender_id === userId;
      const article = document.createElement('article');
      article.className = 'chat-bubble ' + (
        message.is_system ? 'chat-bubble-system' : (isMine ? 'chat-bubble-mine' : 'chat-bubble-theirs')
      );
      article.dataset.id = String(message.id);
      article.innerHTML =
        `<div class="chat-bubble-meta"><strong>${escapeHtml(message.sender)}</strong><span>${escapeHtml(message.time_label)}</span></div>` +
        `<div class="chat-bubble-body">${escapeHtml(message.body).replace(/\n/g, '<br>')}</div>`;
      chatFeed.appendChild(article);
      if (message.id > lastId) lastId = message.id;
      scrollFeed();
    };

    const pollChat = async () => {
      try {
        const response = await fetch(`${apiUrl}&request=${requestId}&after=${lastId}`, {
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await parseChatResponse(response);
        if (data?.success && Array.isArray(data.messages)) {
          data.messages.forEach(appendMessage);
        }
      } catch (err) {
        /* polling failed silently */
      }
    };

    setInterval(pollChat, pollMs);

    const parseChatResponse = async (response) => {
      const raw = await response.text();
      try {
        return JSON.parse(raw);
      } catch (err) {
        throw new Error(raw.trim().slice(0, 180) || 'Unexpected server response.');
      }
    };

    chatCompose.addEventListener('submit', async (event) => {
      event.preventDefault();
      const bodyInput = chatCompose.querySelector('#chatBody');
      const text = bodyInput?.value?.trim();
      if (!text) return;

      const formData = new FormData(chatCompose);
      formData.set('body', text);
      formData.set('after', String(lastId));
      formData.set('request_id', String(requestId));

      const sendBtn = chatCompose.querySelector('button[type="submit"]');
      if (sendBtn) sendBtn.disabled = true;

      try {
        const response = await fetch(`${apiUrl}&request=${requestId}`, {
          method: 'POST',
          body: formData,
          credentials: 'same-origin',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        const data = await parseChatResponse(response);
        if (data.success) {
          bodyInput.value = '';
          (data.messages || []).forEach(appendMessage);
        } else {
          window.alert(data.error || 'Could not send message.');
        }
      } catch (err) {
        window.alert(err.message || 'Could not send message. Please try again.');
      } finally {
        if (sendBtn) sendBtn.disabled = false;
      }
    });
  }
});
