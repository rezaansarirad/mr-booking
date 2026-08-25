/**
 * Admin walk-in booking — no date/time; amounts editable per service.
 */
(function () {
  'use strict';

  const cfg = window.mrWalkinBooking || {};
  const root = document.getElementById('mrb-walkin-app');
  if (!root || !cfg.restUrl) return;

  const state = {
    submitting: false,
    customerId: null,
  };

  const el = {
    search: document.getElementById('mrb-walkin-search'),
    results: document.getElementById('mrb-walkin-results'),
    customerId: document.getElementById('mrb-walkin-customer-id'),
    customerMode: document.getElementById('mrb-walkin-customer-mode'),
    firstName: document.getElementById('mrb-walkin-first-name'),
    lastName: document.getElementById('mrb-walkin-last-name'),
    phone: document.getElementById('mrb-walkin-phone'),
    email: document.getElementById('mrb-walkin-email'),
    staff: document.getElementById('mrb-walkin-staff'),
    notes: document.getElementById('mrb-walkin-notes'),
    status: document.getElementById('mrb-walkin-status'),
    servicesWrap: root.querySelector('[data-field="services"]'),
    total: document.getElementById('mrb-walkin-total'),
    error: document.getElementById('mrb-walkin-error'),
    success: document.getElementById('mrb-walkin-success'),
    successTitle: document.getElementById('mrb-walkin-success-title'),
    successBody: document.getElementById('mrb-walkin-success-body'),
    viewLink: document.getElementById('mrb-walkin-view-link'),
    another: document.getElementById('mrb-walkin-another'),
    submit: document.getElementById('mrb-walkin-submit'),
  };

  const multiService = Number(cfg.settings && cfg.settings.enable_multi_service) === 1;
  let searchTimer;

  const toFa = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

  function toEnDigits(value) {
    return String(value || '').replace(/[۰-۹٠-٩]/g, (d) => {
      const map = {
        '۰': '0', '۱': '1', '۲': '2', '۳': '3', '۴': '4',
        '۵': '5', '۶': '6', '۷': '7', '۸': '8', '۹': '9',
        '٠': '0', '١': '1', '٢': '2', '٣': '3', '٤': '4',
        '٥': '5', '٦': '6', '٧': '7', '٨': '8', '٩': '9',
      };
      return map[d] || d;
    });
  }

  function formatMoney(amount) {
    const n = Math.max(0, Math.round(Number(amount) || 0));
    if (n <= 0) return cfg.i18n.free;
    return toFa(n.toLocaleString('en-US')) + ' ' + (cfg.currency || 'تومان');
  }

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
    if (!res.ok) throw new Error(data.error || data.message || cfg.i18n.error);
    return data;
  }

  /* ─── Messages ─── */

  function fail(msg) {
    el.error.hidden = false;
    el.error.textContent = msg;
  }

  function clearMessages() {
    el.error.hidden = true;
    el.error.textContent = '';
  }

  /* ─── Validation (mirrors phone booking) ─── */

  function normalizePhone(raw) {
    let phone = toEnDigits(raw).replace(/\D/g, '');
    if (phone.startsWith('98') && phone.length === 12) phone = '0' + phone.slice(2);
    if (phone.startsWith('9') && phone.length === 10) phone = '0' + phone;
    return phone;
  }

  function isValidPhone(value) {
    return /^09\d{9}$/.test(normalizePhone(value));
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function fieldInput(name) {
    return (
      {
        first_name: el.firstName,
        last_name: el.lastName,
        phone: el.phone,
        email: el.email,
      }[name] || null
    );
  }

  function setFieldError(name, message) {
    const wrap = root.querySelector('[data-field="' + name + '"]');
    const err = root.querySelector('#mrb-err-' + name);
    const input = fieldInput(name);
    if (wrap) wrap.classList.add('is-invalid');
    if (err) {
      err.hidden = false;
      err.textContent = message;
    }
    if (input) {
      input.classList.add('is-invalid');
      input.setAttribute('aria-invalid', 'true');
    }
  }

  function clearFieldError(name) {
    const wrap = root.querySelector('[data-field="' + name + '"]');
    const err = root.querySelector('#mrb-err-' + name);
    const input = fieldInput(name);
    if (wrap) wrap.classList.remove('is-invalid');
    if (err) {
      err.hidden = true;
      err.textContent = '';
    }
    if (input) {
      input.classList.remove('is-invalid');
      input.removeAttribute('aria-invalid');
    }
  }

  function clearAllErrors() {
    root.querySelectorAll('.is-invalid').forEach((n) => n.classList.remove('is-invalid'));
    root.querySelectorAll('.mrb-field__error').forEach((n) => {
      n.hidden = true;
      n.textContent = '';
    });
    root.querySelectorAll('[aria-invalid="true"]').forEach((n) => n.removeAttribute('aria-invalid'));
  }

  function validateNamedField(name, showEmpty) {
    const input = fieldInput(name);
    if (!input) return true;
    const value = String(input.value || '').trim();

    if (name === 'phone') {
      const phone = normalizePhone(value);
      if (phone) input.value = phone;
      if (!phone) {
        if (showEmpty) setFieldError(name, cfg.i18n.required);
        else clearFieldError(name);
        return false;
      }
      if (!isValidPhone(phone)) {
        setFieldError(name, cfg.i18n.invalidPhone);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    if (name === 'first_name' || name === 'last_name') {
      if (!value) {
        if (showEmpty) setFieldError(name, cfg.i18n.required);
        return false;
      }
      if (value.length < 2) {
        setFieldError(name, cfg.i18n.invalidName);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    if (name === 'email') {
      if (!value) {
        clearFieldError(name);
        return true;
      }
      if (!isValidEmail(value)) {
        setFieldError(name, cfg.i18n.invalidEmail);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    return true;
  }

  function focusFirstInvalid() {
    const target =
      root.querySelector('.mrb-field.is-invalid input') ||
      root.querySelector('.mrb-walkin-svc.is-invalid input') ||
      root.querySelector('.mrb-walkin__services.is-invalid');
    if (target && target.scrollIntoView) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (typeof target.focus === 'function') target.focus({ preventScroll: true });
    }
  }

  /* ─── Services & amounts ─── */

  function serviceRows() {
    return Array.from(root.querySelectorAll('.mrb-walkin-svc'));
  }

  function selectedRows() {
    return serviceRows().filter((row) => row.querySelector('.mrb-walkin-svc__check').checked);
  }

  const money = window.mrbMoneyInput || null;

  function formatAmountValue(n) {
    const rounded = Math.max(0, Math.round(Number(n) || 0));
    if (!rounded) return '';
    return money ? money.format(String(rounded)) : String(rounded);
  }

  function rowAmount(row) {
    const input = row.querySelector('.mrb-walkin-svc__amount');
    const raw = toEnDigits(input.value).replace(/[^\d]/g, '');
    if (raw === '') return null;
    const n = Number(raw);
    return Number.isFinite(n) && n >= 0 ? n : NaN;
  }

  function syncRow(row) {
    const check = row.querySelector('.mrb-walkin-svc__check');
    const amount = row.querySelector('.mrb-walkin-svc__amount');
    const on = check.checked;
    row.classList.toggle('is-selected', on);
    amount.disabled = !on;
    if (on && amount.value === '') {
      amount.value = formatAmountValue(check.dataset.defaultPrice);
    }
  }

  function updateTotal() {
    if (!el.total) return;
    const rows = selectedRows();
    const out = el.total.querySelector('[data-total]');
    if (!rows.length) {
      out.textContent = '—';
      el.total.classList.remove('is-active');
      return;
    }
    let sum = 0;
    rows.forEach((row) => {
      const a = rowAmount(row);
      if (Number.isFinite(a)) sum += a;
    });
    out.textContent = formatMoney(sum);
    el.total.classList.add('is-active');
  }

  function toggleService(row, checked) {
    const check = row.querySelector('.mrb-walkin-svc__check');
    check.checked = checked;
    if (checked && !multiService) {
      serviceRows().forEach((other) => {
        if (other !== row) {
          other.querySelector('.mrb-walkin-svc__check').checked = false;
          syncRow(other);
        }
      });
    }
    syncRow(row);
    clearFieldError('services');
    if (el.servicesWrap) el.servicesWrap.classList.remove('is-invalid');
    clearMessages();
    updateTotal();
    if (checked) {
      const amount = row.querySelector('.mrb-walkin-svc__amount');
      amount.focus();
      amount.select();
    }
  }

  serviceRows().forEach((row) => {
    const check = row.querySelector('.mrb-walkin-svc__check');
    const amount = row.querySelector('.mrb-walkin-svc__amount');
    const priceLabel = row.querySelector('.mrb-walkin-svc__price');
    syncRow(row);

    check.addEventListener('change', () => toggleService(row, check.checked));

    // Clicking a (disabled) amount field selects the service and opens it for editing.
    priceLabel.addEventListener('click', (e) => {
      if (check.checked) return;
      e.preventDefault();
      toggleService(row, true);
    });

    if (money) money.attach(amount);
    amount.addEventListener('input', () => {
      row.classList.remove('is-invalid');
      amount.removeAttribute('aria-invalid');
      updateTotal();
    });
  });

  function validateServices() {
    const rows = selectedRows();
    if (!rows.length) {
      setFieldError('services', cfg.i18n.selectService);
      if (el.servicesWrap) el.servicesWrap.classList.add('is-invalid');
      return false;
    }
    let ok = true;
    rows.forEach((row) => {
      const a = rowAmount(row);
      if (Number.isNaN(a)) {
        ok = false;
        row.classList.add('is-invalid');
        row.querySelector('.mrb-walkin-svc__amount').setAttribute('aria-invalid', 'true');
      }
    });
    if (!ok) setFieldError('services', cfg.i18n.invalidPrice);
    return ok;
  }

  function validateForm() {
    clearAllErrors();
    let ok = true;
    let firstMsg = '';
    const markFail = (msg) => {
      ok = false;
      if (!firstMsg) firstMsg = msg;
    };

    ['first_name', 'last_name', 'phone', 'email'].forEach((name) => {
      if (!validateNamedField(name, true)) {
        const err = root.querySelector('#mrb-err-' + name);
        markFail((err && err.textContent) || cfg.i18n.required);
      }
    });

    if (!validateServices()) {
      const err = root.querySelector('#mrb-err-services');
      markFail((err && err.textContent) || cfg.i18n.selectService);
    }

    if (!ok) {
      fail(firstMsg || cfg.i18n.fixErrors);
      focusFirstInvalid();
      return false;
    }
    return true;
  }

  /* ─── Customer search ─── */

  function setCustomerMode(existing) {
    if (!el.customerMode) return;
    el.customerMode.innerHTML = existing
      ? '<span class="mrb-badge mrb-badge--confirmed">' + escapeHtml(cfg.i18n.existing) + '</span>'
      : '<span class="mrb-badge">' + escapeHtml(cfg.i18n.newCustomer) + '</span>';
  }

  function fillCustomer(c) {
    state.customerId = c.id;
    el.customerId.value = String(c.id);
    el.firstName.value = c.first_name || '';
    el.lastName.value = c.last_name || '';
    el.phone.value = c.phone || '';
    el.email.value = c.email || '';
    closeResults();
    el.search.value = c.label || '';
    setCustomerMode(true);
    clearAllErrors();
    clearMessages();
    const firstService = root.querySelector('.mrb-walkin-svc__check');
    if (firstService) firstService.focus();
  }

  function clearCustomerSelection() {
    if (!state.customerId) return;
    state.customerId = null;
    el.customerId.value = '';
    setCustomerMode(false);
  }

  function closeResults() {
    el.results.hidden = true;
    el.results.innerHTML = '';
    el.search.setAttribute('aria-expanded', 'false');
  }

  async function searchCustomers(q) {
    if (q.length < 2) {
      closeResults();
      return;
    }
    const data = await api('/admin/customers/search?q=' + encodeURIComponent(q));
    const list = data.customers || [];
    el.results.hidden = false;
    el.search.setAttribute('aria-expanded', 'true');
    if (!list.length) {
      el.results.innerHTML = '<p class="mrb-autocomplete__empty">' + escapeHtml(cfg.i18n.noCustomer) + '</p>';
      return;
    }
    el.results.innerHTML = list
      .map(
        (c) =>
          '<button type="button" class="mrb-autocomplete__item" role="option" data-id="' +
          Number(c.id) +
          '">' +
          escapeHtml(c.label) +
          '</button>'
      )
      .join('');
    el.results.querySelectorAll('.mrb-autocomplete__item').forEach((btn) => {
      btn.addEventListener('click', () => {
        const found = list.find((x) => Number(x.id) === Number(btn.dataset.id));
        if (found) fillCustomer(found);
      });
    });
  }

  if (el.search) {
    el.search.addEventListener('input', () => {
      clearCustomerSelection();
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        searchCustomers(el.search.value.trim()).catch(() => {});
      }, 280);
    });
    el.search.addEventListener('focus', () => {
      if (el.search.value.trim().length >= 2) searchCustomers(el.search.value.trim()).catch(() => {});
    });
    el.search.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeResults();
      if (e.key === 'ArrowDown' && !el.results.hidden) {
        const first = el.results.querySelector('.mrb-autocomplete__item');
        if (first) {
          e.preventDefault();
          first.focus();
        }
      }
    });
    el.results.addEventListener('keydown', (e) => {
      const items = Array.from(el.results.querySelectorAll('.mrb-autocomplete__item'));
      const idx = items.indexOf(document.activeElement);
      if (e.key === 'ArrowDown' && idx > -1 && items[idx + 1]) {
        e.preventDefault();
        items[idx + 1].focus();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (idx > 0) items[idx - 1].focus();
        else el.search.focus();
      } else if (e.key === 'Escape') {
        closeResults();
        el.search.focus();
      }
    });
    document.addEventListener('click', (e) => {
      if (!el.search.contains(e.target) && !el.results.contains(e.target)) closeResults();
    });
  }

  [el.firstName, el.lastName, el.phone, el.email].forEach((field) => {
    if (!field) return;
    const name = field.id.replace('mrb-walkin-', '').replace('-', '_');
    field.addEventListener('input', () => {
      clearCustomerSelection();
      if (field.classList.contains('is-invalid')) validateNamedField(name, false);
      else clearFieldError(name);
    });
    field.addEventListener('blur', () => validateNamedField(name, true));
  });

  /* ─── Submit ─── */

  function setSubmitting(on) {
    state.submitting = on;
    el.submit.disabled = on;
    el.submit.setAttribute('aria-busy', on ? 'true' : 'false');
    el.submit.classList.toggle('is-loading', on);
    el.submit.textContent = on ? cfg.i18n.submitting : cfg.i18n.submit;
  }

  async function submitWalkin() {
    if (state.submitting) return;
    clearMessages();
    if (!validateForm()) return;

    const servicePrices = {};
    const serviceIds = selectedRows().map((row) => {
      const id = Number(row.dataset.serviceId);
      servicePrices[id] = rowAmount(row) || 0;
      return id;
    });

    const payload = {
      customer_id: state.customerId || 0,
      first_name: el.firstName.value.trim(),
      last_name: el.lastName.value.trim(),
      phone: el.phone.value.trim(),
      email: el.email.value.trim(),
      service_ids: serviceIds,
      service_prices: servicePrices,
      staff_id: el.staff && el.staff.value ? Number(el.staff.value) : 0,
      notes: el.notes ? el.notes.value.trim() : '',
      status: el.status ? el.status.value : 'completed',
    };

    setSubmitting(true);
    try {
      const data = await api('/admin/walkin', { method: 'POST', body: JSON.stringify(payload) });
      showSuccess(data);
    } catch (e) {
      fail(e.message || cfg.i18n.error);
      setSubmitting(false);
    }
  }

  function showSuccess(data) {
    const b = data.booking || {};
    const services = (b.services || []).map((s) => s.name).join('، ');
    const price = typeof b.price === 'number' ? b.price : Number(b.price || 0);
    el.successTitle.textContent = data.message || '';
    el.successBody.innerHTML =
      '<div><dt>' + escapeHtml(cfg.i18n.bookingCode) + '</dt><dd><code>' + escapeHtml(b.code || '') + '</code></dd></div>' +
      '<div><dt>' + escapeHtml('مشتری') + '</dt><dd>' + escapeHtml(b.customer || '') + '</dd></div>' +
      '<div><dt>' + escapeHtml('خدمات') + '</dt><dd>' + escapeHtml(services) + '</dd></div>' +
      '<div><dt>' + escapeHtml(cfg.i18n.total) + '</dt><dd><strong>' + escapeHtml(formatMoney(price)) + '</strong></dd></div>';
    if (el.viewLink && b.id) {
      el.viewLink.href = cfg.appointmentsUrl + '&view=' + Number(b.id);
    }
    root.classList.add('is-success');
    el.success.hidden = false;
    el.submit.hidden = true;
    setSubmitting(false);
    el.success.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (el.another) el.another.focus();
  }

  function resetForm() {
    root.classList.remove('is-success');
    el.success.hidden = true;
    el.submit.hidden = false;
    state.customerId = null;
    el.customerId.value = '';
    setCustomerMode(false);
    [el.firstName, el.lastName, el.phone, el.email, el.search, el.notes].forEach((f) => {
      if (f) f.value = '';
    });
    if (el.staff) el.staff.value = '';
    if (el.status) el.status.value = 'completed';
    serviceRows().forEach((row) => {
      const check = row.querySelector('.mrb-walkin-svc__check');
      const amount = row.querySelector('.mrb-walkin-svc__amount');
      check.checked = false;
      amount.value = formatAmountValue(check.dataset.defaultPrice);
      syncRow(row);
    });
    clearAllErrors();
    clearMessages();
    updateTotal();
    if (el.search) el.search.focus();
  }

  el.submit.addEventListener('click', submitWalkin);
  if (el.another) el.another.addEventListener('click', resetForm);

  root.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const tag = (e.target && e.target.tagName) || '';
    if (tag !== 'INPUT') return;
    if (e.target.type === 'search') return;
    e.preventDefault();
    submitWalkin();
  });

  setCustomerMode(false);
  updateTotal();
})();
