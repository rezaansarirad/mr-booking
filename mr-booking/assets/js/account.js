/**
 * MR Booking — customer account page.
 */
(function () {
  'use strict';

  const cfg = window.mrBookingAccount || {};
  const root = document.getElementById('mr-booking-account');
  if (!root || !cfg.restUrl || !window.mrbOtpLogin) return;

  const i18n = cfg.i18n || {};
  const toFa = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

  const el = {
    login: root.querySelector('#mrb-account-login'),
    dash: root.querySelector('#mrb-account-dash'),
    name: root.querySelector('[data-account-name]'),
    phone: root.querySelector('[data-account-phone]'),
    logout: root.querySelector('#mrb-account-logout'),
    tabs: root.querySelectorAll('.mrb-account__tab'),
    panels: root.querySelectorAll('.mrb-account__panel'),
    status: root.querySelector('#mrb-account-bookings-status'),
    bookingsError: root.querySelector('#mrb-account-bookings-error'),
    upcomingWrap: root.querySelector('#mrb-account-upcoming'),
    pastWrap: root.querySelector('#mrb-account-past'),
    upcomingList: root.querySelector('[data-list="upcoming"]'),
    pastList: root.querySelector('[data-list="past"]'),
    empty: root.querySelector('#mrb-account-empty'),
    profile: root.querySelector('#mrb-account-profile'),
    profileError: root.querySelector('#mrb-account-profile-error'),
    profileSaved: root.querySelector('#mrb-account-profile-saved'),
    save: root.querySelector('#mrb-account-save'),
  };

  let birthPicker = null;
  let reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function api(path, options = {}) {
    const url = cfg.restUrl.replace(/\/$/, '') + path;
    const res = await fetch(url, {
      ...options,
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce,
        ...(options.headers || {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      const err = new Error(data.error || data.message || i18n.error || 'Error');
      err.status = res.status;
      throw err;
    }
    return data;
  }

  /* ─── Tabs ─── */

  /* ─── Wallet ─── */

  let walletLoaded = false;

  async function loadWallet(force) {
    const panel = root.querySelector('[data-panel="wallet"]');
    if (!panel || (walletLoaded && !force)) return;
    const status = panel.querySelector('#mrb-account-wallet-status');
    const error = panel.querySelector('#mrb-account-wallet-error');
    const list = panel.querySelector('[data-list="wallet"]');
    const empty = panel.querySelector('#mrb-account-wallet-empty');
    const balance = panel.querySelector('[data-wallet-balance]');
    if (status) status.textContent = i18n.loading || '';
    if (error) error.hidden = true;
    try {
      const data = await api('/me/wallet');
      walletLoaded = true;
      if (balance) balance.textContent = data.balance_label || '';
      const statWallet = root.querySelector('[data-stat-wallet]');
      if (statWallet) statWallet.textContent = data.balance_label || '';
      const rows = data.transactions || [];
      list.innerHTML = rows
        .map(
          (t) =>
            '<li class="mrb-wallet-item mrb-wallet-item--' + escapeHtml(t.type) + '">' +
            '<div class="mrb-wallet-item__main"><strong>' + escapeHtml(t.type_label) + '</strong>' +
            (t.note ? '<span>' + escapeHtml(t.note) + '</span>' : '') +
            (t.booking ? '<span dir="ltr">' + escapeHtml(t.booking) + '</span>' : '') +
            '<small>' + escapeHtml(t.date) + '</small></div>' +
            '<span class="mrb-wallet-item__amount ' + (Number(t.amount) < 0 ? 'is-negative' : 'is-positive') + '">' +
            (Number(t.amount) < 0 ? '−' : '+') + escapeHtml(t.label) + '</span></li>'
        )
        .join('');
      if (empty) empty.hidden = rows.length > 0;
      if (status) status.textContent = '';
    } catch (e) {
      if (status) status.textContent = '';
      if (error) {
        error.hidden = false;
        error.textContent = e.message || i18n.error || '';
      }
    }
  }

  root.querySelectorAll('[data-goto-tab]').forEach((btn) => {
    btn.addEventListener('click', () => activateTab(btn.dataset.gotoTab, true));
  });

  // Embedded booking form finished → refresh lists and balance.
  root.addEventListener('mrb:booking-created', () => {
    walletLoaded = false;
    loadBookings();
    if (root.querySelector('[data-stat-wallet]')) loadWallet(true);
  });

  /* Top-up */

  const topup = {
    form: root.querySelector('#mrb-topup'),
    amount: root.querySelector('#mrb-topup-amount'),
    submit: root.querySelector('#mrb-topup-submit'),
    err: root.querySelector('#mrb-err-topup'),
    saved: root.querySelector('#mrb-account-wallet-saved'),
  };

  function parseMoney(raw) {
    const d = String(raw || '').replace(/[۰-۹]/g, (c) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(c)).replace(/[^\d]/g, '');
    return d ? Number(d) : 0;
  }

  function formatMoneyInput(raw) {
    const d = String(parseMoney(raw) || '');
    return d && d !== '0' ? d.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
  }

  if (topup.form && topup.amount) {
    topup.amount.addEventListener('input', () => {
      const next = formatMoneyInput(topup.amount.value);
      if (next !== topup.amount.value) topup.amount.value = next;
      root.querySelectorAll('.mrb-topup__chip').forEach((c) => {
        const on = Number(c.dataset.amount) === parseMoney(topup.amount.value);
        c.classList.toggle('is-active', on);
        c.setAttribute('aria-pressed', on ? 'true' : 'false');
      });
      if (topup.err) topup.err.hidden = true;
      topup.amount.removeAttribute('aria-invalid');
    });
    root.querySelectorAll('.mrb-topup__chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        topup.amount.value = formatMoneyInput(chip.dataset.amount);
        root.querySelectorAll('.mrb-topup__chip').forEach((c) => {
          c.classList.toggle('is-active', c === chip);
          c.setAttribute('aria-pressed', c === chip ? 'true' : 'false');
        });
        if (topup.err) topup.err.hidden = true;
        topup.amount.focus();
      });
    });
    topup.form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const amount = parseMoney(topup.amount.value);
      const min = Number((cfg.wallet && cfg.wallet.topup_min) || 0);
      if (!amount || amount < min) {
        if (topup.err) {
          topup.err.hidden = false;
          topup.err.textContent = (i18n.topupMin || 'حداقل مبلغ %s است.').replace('%s', (cfg.wallet && cfg.wallet.topup_min_label) || String(min));
        }
        topup.amount.setAttribute('aria-invalid', 'true');
        topup.amount.focus();
        return;
      }
      const label = topup.submit.querySelector('.mrb__btn__label') || topup.submit;
      const original = label.textContent;
      topup.submit.disabled = true;
      topup.submit.classList.add('is-loading');
      label.textContent = i18n.redirecting || '';
      try {
        const returnUrl = window.location.href.split('#')[0].replace(/([?&])mrb_wallet=[^&]*/g, '$1').replace(/[?&]$/, '');
        const data = await api('/me/wallet/topup', { method: 'POST', body: JSON.stringify({ amount, return_url: returnUrl }) });
        if (data.redirect) {
          window.location.assign(data.redirect);
          return;
        }
        throw new Error(i18n.error || '');
      } catch (err) {
        if (topup.err) {
          topup.err.hidden = false;
          topup.err.textContent = err.message || i18n.error || '';
        }
        topup.submit.disabled = false;
        topup.submit.classList.remove('is-loading');
        label.textContent = original;
      }
    });
  }

  function handleWalletReturn() {
    const result = root.dataset.walletResult || '';
    if (!result) return;
    const msg =
      result === 'success' ? i18n.topupSuccess : result === 'cancelled' ? i18n.topupCancelled : i18n.topupFailed;
    const target = result === 'success' ? topup.saved : root.querySelector('#mrb-account-wallet-error');
    if (target) {
      target.hidden = false;
      target.textContent = msg || '';
    }
    if (window.history && window.history.replaceState) {
      window.history.replaceState(null, '', window.location.href.replace(/([?&])mrb_wallet=[^&]*/g, '$1').replace(/[?&]$/, ''));
    }
    root.dataset.walletResult = '';
    return result;
  }

  function activateTab(name, focus) {
    if (name === 'wallet') loadWallet(false);
    el.tabs.forEach((tab) => {
      const on = tab.dataset.tab === name;
      tab.classList.toggle('is-active', on);
      tab.setAttribute('aria-selected', on ? 'true' : 'false');
      tab.tabIndex = on ? 0 : -1;
      if (on && focus) tab.focus();
    });
    el.panels.forEach((panel) => {
      const on = panel.dataset.panel === name;
      panel.classList.toggle('is-active', on);
      panel.hidden = !on;
    });
  }

  el.tabs.forEach((tab, idx) => {
    tab.addEventListener('click', () => activateTab(tab.dataset.tab, false));
    tab.addEventListener('keydown', (e) => {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      e.preventDefault();
      const dir = e.key === 'ArrowRight' ? -1 : 1; // RTL: right arrow moves to the previous tab
      const next = el.tabs[(idx + dir + el.tabs.length) % el.tabs.length];
      activateTab(next.dataset.tab, true);
    });
  });

  /* ─── Session ─── */

  function showDashboard(customer) {
    walletLoaded = false;
    if (customer) {
      const avatar = root.querySelector('[data-account-avatar]');
      if (avatar) avatar.textContent = String(customer.first_name || '').trim().charAt(0);
      const first = root.querySelector('[data-account-first]');
      if (first) first.textContent = String(customer.first_name || '').trim();
      el.name.textContent = ((customer.first_name || '') + ' ' + (customer.last_name || '')).trim();
      el.phone.textContent = toFa(customer.phone || '');
      fillProfile(customer);
    }
    el.login.hidden = true;
    el.dash.hidden = false;
    root.dataset.loggedIn = '1';
    const walletReturn = root.dataset.walletResult ? handleWalletReturn() : '';
    activateTab(walletReturn && root.querySelector('[data-tab="wallet"]') ? 'wallet' : 'bookings', false);
    loadBookings();
    if (root.querySelector('[data-stat-wallet]')) loadWallet(false);
  }

  function showLogin() {
    el.dash.hidden = true;
    el.login.hidden = false;
    root.dataset.loggedIn = '0';
  }

  const otp = window.mrbOtpLogin.mount(root.querySelector('[data-mrb-otp]'), {
    restUrl: cfg.restUrl,
    nonce: cfg.nonce,
    i18n: i18n,
    onNonce: (n) => {
      cfg.nonce = n;
    },
    onLoggedIn: (customer) => {
      // The embedded booking form was rendered for a guest (nonce, prefill, wallet balance);
      // a reload gives it the logged-in state instead of duplicating that logic here.
      if (root.querySelector('#mr-booking-app')) {
        window.location.reload();
        return;
      }
      showDashboard(customer);
      root.scrollIntoView({ behavior: reducedMotion ? 'auto' : 'smooth', block: 'start' });
    },
  });

  if (el.logout) {
    el.logout.addEventListener('click', async () => {
      el.logout.disabled = true;
      try {
        const data = await api('/auth/logout', { method: 'POST' });
        if (data.nonce) {
          cfg.nonce = data.nonce;
          if (otp) otp.setNonce(data.nonce);
        }
      } catch (e) {
        // Session may already be gone; fall through to the login view.
      }
      el.logout.disabled = false;
      if (otp) otp.reset();
      if (root.querySelector('#mr-booking-app')) {
        window.location.reload();
        return;
      }
      showLogin();
    });
  }

  /* ─── Bookings ─── */

  function bookingItem(b, isUpcoming) {
    const li = document.createElement('li');
    li.className = 'mrb-account__item mrb-account__item--' + b.status;
    li.dataset.id = b.id;

    const meta = [];
    if (b.staff) meta.push(escapeHtml(b.staff));
    if (b.price_label) meta.push(escapeHtml(b.price_label));
    if (b.paid_label) meta.push('<span class="mrb-account__paid">' + escapeHtml((b.payment_label ? b.payment_label + ' ' : '') + b.paid_label) + '</span>');
    if (b.duration) meta.push(toFa(b.duration) + ' ' + escapeHtml(i18n.minutes || 'دقیقه'));

    li.innerHTML =
      '<div class="mrb-account__item-main">' +
      '<div class="mrb-account__item-when"><strong>' + escapeHtml(b.date_label) + '</strong><span>' + escapeHtml(b.time_label) + (b.end_label ? ' – ' + escapeHtml(b.end_label) : '') + '</span></div>' +
      '<div class="mrb-account__item-what"><strong>' + escapeHtml(b.services || '—') + '</strong>' + (meta.length ? '<span>' + meta.join(' · ') + '</span>' : '') + '</div>' +
      '</div>' +
      '<div class="mrb-account__item-side">' +
      '<span class="mrb-badge mrb-badge--' + escapeHtml(b.status) + '">' + escapeHtml(b.status_label) + '</span>' +
      '<code class="mrb-account__code">' + escapeHtml(b.code) + '</code>' +
      '</div>' +
      '<div class="mrb-account__item-actions" data-actions></div>';

    const actions = li.querySelector('[data-actions]');
    if (isUpcoming) {
      if (b.can_cancel) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mrb__btn mrb__btn--ghost mrb__btn--small';
        btn.textContent = i18n.cancel || 'لغو نوبت';
        btn.addEventListener('click', () => askCancel(li, b));
        actions.appendChild(btn);
      } else if (b.cancel_reason && b.status !== 'cancelled') {
        const note = document.createElement('span');
        note.className = 'mrb-account__note';
        note.textContent = b.cancel_reason;
        actions.appendChild(note);
      }
    }
    return li;
  }

  function askCancel(li, b) {
    const actions = li.querySelector('[data-actions]');
    actions.innerHTML =
      '<span class="mrb-account__confirm-text">' + escapeHtml(i18n.confirmCancel || 'این نوبت لغو شود؟') + '</span>' +
      '<button type="button" class="mrb__btn mrb__btn--danger mrb__btn--small" data-yes>' + escapeHtml(i18n.yesCancel || 'بله، لغو کن') + '</button>' +
      '<button type="button" class="mrb__btn mrb__btn--ghost mrb__btn--small" data-no>' + escapeHtml(i18n.keep || 'نه، نگه دار') + '</button>';
    const yes = actions.querySelector('[data-yes]');
    const no = actions.querySelector('[data-no]');
    no.focus();
    no.addEventListener('click', () => {
      actions.innerHTML = '';
      const again = bookingItem(b, true).querySelector('[data-actions]');
      actions.replaceWith(again);
    });
    yes.addEventListener('click', async () => {
      yes.disabled = true;
      no.disabled = true;
      yes.classList.add('is-loading');
      try {
        await api('/me/bookings/' + b.id + '/cancel', { method: 'POST' });
        walletLoaded = false;
        el.status.textContent = i18n.cancelled || 'نوبت لغو شد.';
        el.status.hidden = false;
        await loadBookings();
      } catch (e) {
        actions.innerHTML = '<span class="mrb-account__note mrb-account__note--error">' + escapeHtml(e.message) + '</span>';
      }
    });
  }

  async function loadBookings() {
    el.bookingsError.hidden = true;
    el.status.hidden = false;
    el.status.textContent = i18n.loading || '';
    try {
      const data = await api('/me/bookings');
      renderBookings(data);
    } catch (e) {
      el.status.hidden = true;
      if (e.status === 401 || e.status === 403) {
        showLogin();
        return;
      }
      el.bookingsError.hidden = false;
      el.bookingsError.textContent = e.message;
    }
  }

  function renderBookings(data) {
    const upcoming = data.upcoming || [];
    const past = data.past || [];
    const statUp = root.querySelector('[data-stat-upcoming]');
    if (statUp) statUp.textContent = toFa(upcoming.filter((b) => b.status !== 'cancelled' && b.status !== 'rejected').length);
    el.upcomingList.innerHTML = '';
    el.pastList.innerHTML = '';
    upcoming.forEach((b) => el.upcomingList.appendChild(bookingItem(b, true)));
    past.forEach((b) => el.pastList.appendChild(bookingItem(b, false)));
    el.upcomingWrap.hidden = upcoming.length === 0;
    el.pastWrap.hidden = past.length === 0;
    el.empty.hidden = upcoming.length + past.length > 0;
    if (el.status.textContent === (i18n.loading || '')) {
      el.status.hidden = true;
      el.status.textContent = '';
    }
    if (upcoming.length && data.cancel_min_minutes > 0) {
      el.status.hidden = false;
      el.status.textContent = (i18n.cancelPolicy || '').replace('%s', toFa(data.cancel_min_minutes));
    }
  }

  /* ─── Profile ─── */

  function fillProfile(c) {
    if (!el.profile) return;
    el.profile.first_name.value = c.first_name || '';
    el.profile.last_name.value = c.last_name || '';
    el.profile.phone.value = c.phone || '';
    if (el.profile.email) el.profile.email.value = c.email || '';
    if (birthPicker) birthPicker.setValue(c.birth_date || '');
  }

  function profileFieldError(name, message) {
    const wrap = el.profile.querySelector('[data-field="' + name + '"]');
    const err = el.profile.querySelector('#mrb-err-' + name);
    if (wrap) wrap.classList.toggle('is-invalid', !!message);
    if (err) {
      err.hidden = !message;
      err.textContent = message || '';
    }
  }

  if (window.mrbBirthPicker) {
    birthPicker = window.mrbBirthPicker.init({
      root: root,
      prefix: 'mrb-acc-birth',
      calendarMode: cfg.calendarMode,
      months: cfg.months,
      placeholder: i18n.selectBirth || 'انتخاب تاریخ تولد',
      onChange: () => profileFieldError('birth_date', ''),
    });
    const seed = root.querySelector('#mrb-acc-birth-date');
    if (birthPicker && seed && seed.value) birthPicker.setValue(seed.value);
  }

  if (el.profile) {
    el.profile.addEventListener('submit', async (e) => {
      e.preventDefault();
      el.profileError.hidden = true;
      el.profileSaved.hidden = true;
      ['first_name', 'last_name', 'email'].forEach((n) => profileFieldError(n, ''));

      const first = el.profile.first_name.value.trim();
      const last = el.profile.last_name.value.trim();
      const email = el.profile.email ? el.profile.email.value.trim() : '';
      let ok = true;
      if (first.length < 2) {
        profileFieldError('first_name', i18n.invalidName);
        ok = false;
      }
      if (last.length < 2) {
        profileFieldError('last_name', i18n.invalidName);
        ok = false;
      }
      if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        profileFieldError('email', i18n.invalidEmail);
        ok = false;
      }
      if (!ok) return;

      el.save.disabled = true;
      el.save.classList.add('is-loading');
      try {
        const data = await api('/me', {
          method: 'POST',
          body: JSON.stringify({
            first_name: first,
            last_name: last,
            email: email,
            birth_date: birthPicker ? birthPicker.getValue() : '',
          }),
        });
        if (data.customer) {
          el.name.textContent = (data.customer.first_name + ' ' + data.customer.last_name).trim();
        }
        el.profileSaved.hidden = false;
        el.profileSaved.textContent = data.message || i18n.saved || '';
      } catch (err) {
        el.profileError.hidden = false;
        el.profileError.textContent = err.message;
      } finally {
        el.save.disabled = false;
        el.save.classList.remove('is-loading');
      }
    });
  }

  /* ─── Init ─── */

  if (root.dataset.loggedIn === '1') {
    const walletReturn = root.dataset.walletResult ? handleWalletReturn() : '';
    if (walletReturn && root.querySelector('[data-tab="wallet"]')) activateTab('wallet', false);
    else if (root.dataset.bookingResult && root.querySelector('[data-tab="book"]')) activateTab('book', false);
    loadBookings();
    if (root.querySelector('[data-stat-wallet]')) loadWallet(false);
  } else if (otp) {
    otp.focus();
  }
})();
