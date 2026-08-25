/**
 * Admin phone booking — same availability API as frontend.
 */
(function () {
  'use strict';

  const cfg = window.mrPhoneBooking || {};
  const root = document.getElementById('mrb-phone-book-app');
  if (!root || !cfg.restUrl) return;

  const state = {
    services: [],
    staff: [],
    selectedServices: [],
    staffId: null,
    days: [],
    date: null,
    time: null,
    calYear: null,
    calMonth: null,
    submitting: false,
    customerId: null,
  };

  let birthPicker = null;

  const el = {
    search: document.getElementById('mrb-customer-search'),
    results: document.getElementById('mrb-customer-results'),
    customerId: document.getElementById('mrb-customer-id'),
    firstName: document.getElementById('mrb-first-name'),
    lastName: document.getElementById('mrb-last-name'),
    phone: document.getElementById('mrb-phone'),
    email: document.getElementById('mrb-email'),
    staff: document.getElementById('mrb-staff-id'),
    notes: document.getElementById('mrb-notes'),
    status: document.getElementById('mrb-booking-status'),
    calTitle: document.getElementById('mrb-cal-title'),
    calendar: document.getElementById('mrb-calendar'),
    weekdays: document.getElementById('mrb-weekdays'),
    calHint: document.getElementById('mrb-cal-hint'),
    slots: document.getElementById('mrb-slots'),
    slotsHint: document.getElementById('mrb-slots-hint'),
    error: document.getElementById('mrb-phone-error'),
    success: document.getElementById('mrb-phone-success'),
    submit: document.getElementById('mrb-phone-submit'),
    servicesWrap: root.querySelector('[data-field="services"]'),
    dateWrap: root.querySelector('[data-field="date"]'),
    timeWrap: root.querySelector('[data-field="time"]'),
    birthTrigger: document.getElementById('mrb-phone-birth-trigger'),
  };

  const multiService = Number(cfg.settings.enable_multi_service) === 1;
  let searchTimer;

  const toFa = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);

  function formatClock(t) {
    if (!t) return '';
    const hourFormat = String((cfg.settings && cfg.settings.hour_format) || '12');
    if (hourFormat !== '12') {
      return toFa(t);
    }
    const parts = String(t).split(':');
    const h = parseInt(parts[0], 10);
    const m = parts[1] ? String(parts[1]).padStart(2, '0') : '00';
    if (Number.isNaN(h)) {
      return toFa(t);
    }
    const am = h < 12;
    let h12 = h % 12;
    if (h12 === 0) h12 = 12;
    const suffix = am ? (cfg.i18n && cfg.i18n.am) || 'ق.ظ' : (cfg.i18n && cfg.i18n.pm) || 'ب.ظ';
    return toFa(String(h12) + ':' + m) + ' ' + suffix;
  }

  function g2j(gy, gm, gd) {
    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    let gy2 = gm > 2 ? gy + 1 : gy;
    let days =
      355666 +
      365 * gy +
      Math.floor((gy2 + 3) / 4) -
      Math.floor((gy2 + 99) / 100) +
      Math.floor((gy2 + 399) / 400) +
      gd +
      g_d_m[gm - 1];
    let jy = -1595 + 33 * Math.floor(days / 12053);
    days %= 12053;
    jy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      jy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    let jm, jd;
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }
    return [jy, jm, jd];
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

  function fail(msg) {
    el.error.hidden = false;
    el.error.textContent = msg;
    el.success.hidden = true;
  }

  function clearMessages() {
    el.error.hidden = true;
    el.success.hidden = true;
  }

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

  function normalizePhone(raw) {
    let phone = toEnDigits(raw).replace(/\D/g, '');
    if (phone.startsWith('98') && phone.length === 12) phone = '0' + phone.slice(2);
    if (phone.startsWith('9') && phone.length === 10) phone = '0' + phone;
    return phone;
  }

  function normalizePhoneField(input) {
    if (!input) return '';
    const normalized = normalizePhone(input.value);
    if (normalized) input.value = normalized;
    return normalized;
  }

  function isValidPhone(value) {
    return /^09\d{9}$/.test(normalizePhone(value));
  }

  function isValidEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
  }

  function fieldErrorEl(name) {
    return root.querySelector('#mrb-err-' + name);
  }

  function fieldWrap(name) {
    return root.querySelector('[data-field="' + name + '"]');
  }

  function fieldInput(name) {
    if (name === 'first_name') return el.firstName;
    if (name === 'last_name') return el.lastName;
    if (name === 'phone') return el.phone;
    if (name === 'email') return el.email;
    if (name === 'birth_date') return el.birthTrigger;
    return null;
  }

  function setFieldError(name, message) {
    const wrap = fieldWrap(name);
    const err = fieldErrorEl(name);
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
    if (name === 'services' && el.servicesWrap) el.servicesWrap.classList.add('is-invalid');
    if (name === 'date' && el.calendar) el.calendar.classList.add('is-invalid');
    if (name === 'time' && el.slots) el.slots.classList.add('is-invalid');
  }

  function clearFieldError(name) {
    const wrap = fieldWrap(name);
    const err = fieldErrorEl(name);
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
    if (name === 'services' && el.servicesWrap) el.servicesWrap.classList.remove('is-invalid');
    if (name === 'date' && el.calendar) el.calendar.classList.remove('is-invalid');
    if (name === 'time' && el.slots) el.slots.classList.remove('is-invalid');
  }

  function clearAllErrors() {
    root.querySelectorAll('.mrb-field.is-invalid, .mrb__birth-trigger.is-invalid, .is-invalid').forEach((node) => {
      node.classList.remove('is-invalid');
    });
    root.querySelectorAll('.mrb-field__error').forEach((node) => {
      node.hidden = true;
      node.textContent = '';
    });
    root.querySelectorAll('[aria-invalid="true"]').forEach((node) => {
      node.removeAttribute('aria-invalid');
    });
    if (el.servicesWrap) el.servicesWrap.classList.remove('is-invalid');
    if (el.calendar) el.calendar.classList.remove('is-invalid');
    if (el.slots) el.slots.classList.remove('is-invalid');
  }

  function validatePhoneField(showEmpty) {
    const value = normalizePhoneField(el.phone);
    if (!value) {
      if (showEmpty) {
        setFieldError('phone', cfg.i18n.required);
        return false;
      }
      clearFieldError('phone');
      return false;
    }
    if (!isValidPhone(value)) {
      setFieldError('phone', cfg.i18n.invalidPhone);
      return false;
    }
    clearFieldError('phone');
    return true;
  }

  function validateNamedField(name, showEmpty) {
    const input = fieldInput(name);
    if (!input) return true;

    if (name === 'phone') return validatePhoneField(showEmpty);

    const value = name === 'birth_date'
      ? (birthPicker ? birthPicker.getValue() : '')
      : String(input.value || '').trim();

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

    if (name === 'birth_date') {
      clearFieldError(name);
      return true;
    }

    return true;
  }

  function focusFirstInvalid() {
    const target =
      root.querySelector('.mrb-field.is-invalid input, .mrb-field.is-invalid .mrb__birth-trigger') ||
      root.querySelector('.mrb-phone-book__services.is-invalid') ||
      root.querySelector('.mrb__calendar.is-invalid') ||
      root.querySelector('.mrb__slots.is-invalid');
    if (target && target.scrollIntoView) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      if (typeof target.focus === 'function') target.focus({ preventScroll: true });
    }
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
        const err = fieldErrorEl(name);
        markFail((err && err.textContent) || cfg.i18n.required);
      }
    });

    if (!getSelectedServices().length) {
      setFieldError('services', cfg.i18n.selectService);
      markFail(cfg.i18n.selectService);
    }

    if (!state.date) {
      setFieldError('date', cfg.i18n.selectDate);
      markFail(cfg.i18n.selectDate);
    }

    if (!state.time) {
      setFieldError('time', cfg.i18n.selectTime);
      markFail(cfg.i18n.selectTime);
    }

    if (!ok) {
      fail(firstMsg || cfg.i18n.fixErrors);
      focusFirstInvalid();
      return false;
    }

    return true;
  }

  function initCalendarCursor() {
    const now = new Date();
    if (cfg.settings.calendar_mode === 'gregorian') {
      state.calYear = now.getFullYear();
      state.calMonth = now.getMonth() + 1;
    } else {
      const [jy, jm] = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
      state.calYear = jy;
      state.calMonth = jm;
    }
  }

  function renderWeekdays() {
    el.weekdays.innerHTML = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'].map((l) => `<span>${l}</span>`).join('');
  }

  function getSelectedServices() {
    return Array.from(root.querySelectorAll('.mrb-service-check:checked')).map((i) => Number(i.value));
  }

  async function refreshAvailability() {
    state.selectedServices = getSelectedServices();
    state.staffId = el.staff && el.staff.value ? Number(el.staff.value) : null;
    state.date = null;
    state.time = null;
    if (state.selectedServices.length) {
      await loadMonth();
    } else {
      el.calendar.innerHTML = '';
      el.slots.innerHTML = '';
      el.slotsHint.textContent = cfg.i18n.selectService;
    }
  }

  async function loadMonth() {
    if (!state.selectedServices.length) return;
    el.calHint.textContent = cfg.i18n.loading;
    const params = new URLSearchParams({ year: state.calYear, month: state.calMonth });
    params.set('calendar_mode', cfg.settings.calendar_mode || 'jalali');
    state.selectedServices.forEach((id) => params.append('service_ids[]', id));
    if (state.staffId) params.set('staff_id', state.staffId);
    const data = await api('/availability/month?' + params.toString());
    state.days = data.days || [];
    renderCalendar();
    el.calHint.textContent = '';
  }

  function renderCalendar() {
    const mode = cfg.settings.calendar_mode;
    const monthName =
      mode === 'gregorian'
        ? new Date(state.calYear, state.calMonth - 1, 1).toLocaleString('en', { month: 'long' })
        : (cfg.months && cfg.months[state.calMonth]) || state.calMonth;
    el.calTitle.innerHTML = `<strong>${toFa(monthName)} ${toFa(state.calYear)}</strong>`;

    const firstDate = state.days[0]?.date;
    el.calendar.innerHTML = '';
    if (!firstDate) return;

    const first = new Date(firstDate + 'T12:00:00');
    const offset = (first.getDay() + 1) % 7;
    for (let i = 0; i < offset; i++) {
      const empty = document.createElement('div');
      empty.className = 'mrb__day is-empty';
      el.calendar.appendChild(empty);
    }

    state.days.forEach((day) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mrb__day';
      const mainNum = mode === 'gregorian' ? day.g_day : day.j_day;
      let sub = mode === 'both' ? `<small>${toFa(day.g_day)}</small>` : '';
      btn.innerHTML = `<span>${toFa(mainNum)}</span>${sub}`;

      const today = cfg.settings.today || '';
      const isToday = !!(day.today || (today && day.date === today));
      const isPast = !!day.past;
      const allowSameDay = Number(cfg.settings.allow_same_day) === 1;
      const blockedSameDay = !allowSameDay && today && day.date === today;
      const canSelect =
        day.selectable !== undefined ? !!day.selectable : !isPast && !blockedSameDay && !day.closed && !day.beyond;

      if (isToday) {
        btn.classList.add('is-today');
        btn.innerHTML += `<em class="mrb__day-today">${(cfg.i18n && cfg.i18n.todayLabel) || 'امروز'}</em>`;
      }

      if (day.holiday) {
        btn.classList.add('is-holiday');
        btn.title = day.holiday_title || cfg.texts.holiday;
      }

      if (!canSelect) {
        btn.classList.add('is-disabled');
        btn.disabled = true;
        if (isPast) btn.classList.add('is-past');
      } else if (day.fully_booked) {
        btn.classList.add('is-full');
      } else {
        btn.classList.add('is-available');
      }

      if (state.date === day.date && !btn.disabled) btn.classList.add('is-selected');

      btn.addEventListener('click', async () => {
        if (btn.disabled) return;
        state.date = day.date;
        state.time = null;
        el.calendar.querySelectorAll('.mrb__day').forEach((d) => d.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        el.calHint.textContent = day.dual ? day.dual.replace('\n', ' · ') : day.date;
        clearFieldError('date');
        clearMessages();
        await loadSlots();
      });

      el.calendar.appendChild(btn);
    });
  }

  async function loadSlots() {
    if (!state.date) return;
    el.slots.innerHTML = '';
    el.slotsHint.textContent = cfg.i18n.loading;
    const params = new URLSearchParams({ date: state.date });
    state.selectedServices.forEach((id) => params.append('service_ids[]', id));
    if (state.staffId) params.set('staff_id', state.staffId);
    const data = await api('/availability/slots?' + params.toString());
    const detail = data.detail && data.detail.length ? data.detail : (data.slots || []).map((t) => ({ time: t, status: 'available' }));
    const hasAvailable = detail.some((s) => s.status === 'available');
    el.slotsHint.textContent = hasAvailable ? '' : data.message || cfg.texts.no_slots;

    detail.forEach((slot) => {
      const t = slot.time;
      const isAvailable = slot.status === 'available';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mrb__slot';
      if (slot.status === 'past') btn.classList.add('is-past');
      if (slot.status === 'booked') btn.classList.add('is-booked');
      if (!isAvailable) btn.classList.add('is-disabled');
      if (state.time === t && isAvailable) btn.classList.add('is-selected');
      btn.textContent = formatClock(t);
      btn.disabled = !isAvailable;
      if (isAvailable) {
        btn.addEventListener('click', () => {
          state.time = t;
          el.slots.querySelectorAll('.mrb__slot').forEach((b) => b.classList.remove('is-selected'));
          btn.classList.add('is-selected');
          clearFieldError('time');
          clearMessages();
        });
      }
      el.slots.appendChild(btn);
    });
  }

  function fillCustomer(c) {
    state.customerId = c.id;
    el.customerId.value = String(c.id);
    el.firstName.value = c.first_name || '';
    el.lastName.value = c.last_name || '';
    el.phone.value = c.phone || '';
    el.email.value = c.email || '';
    if (birthPicker) birthPicker.setValue(c.birth_date || '');
    el.results.hidden = true;
    el.search.value = c.label || '';
    clearAllErrors();
    clearMessages();
  }

  function clearCustomerSelection() {
    state.customerId = null;
    el.customerId.value = '';
  }

  async function searchCustomers(q) {
    if (q.length < 2) {
      el.results.hidden = true;
      el.results.innerHTML = '';
      return;
    }
    const data = await api('/admin/customers/search?q=' + encodeURIComponent(q));
    const list = data.customers || [];
    if (!list.length) {
      el.results.innerHTML = `<p class="mrb-autocomplete__empty">${cfg.i18n.noCustomer}</p>`;
      el.results.hidden = false;
      return;
    }
    el.results.innerHTML = list
      .map(
        (c) =>
          `<button type="button" class="mrb-autocomplete__item" data-id="${c.id}">${escapeHtml(c.label)}</button>`
      )
      .join('');
    el.results.hidden = false;
    el.results.querySelectorAll('.mrb-autocomplete__item').forEach((btn) => {
      btn.addEventListener('click', () => {
        const found = list.find((x) => Number(x.id) === Number(btn.dataset.id));
        if (found) fillCustomer(found);
      });
    });
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function submitBooking() {
    if (state.submitting) return;
    clearMessages();
    if (!validateForm()) return;

    const payload = {
      customer_id: state.customerId || 0,
      first_name: el.firstName.value.trim(),
      last_name: el.lastName.value.trim(),
      phone: el.phone.value.trim(),
      email: el.email.value.trim(),
      birth_date: birthPicker ? birthPicker.getValue() : '',
      booking_for: 'myself',
      service_ids: getSelectedServices(),
      staff_id: el.staff && el.staff.value ? Number(el.staff.value) : 0,
      date: state.date,
      time: state.time,
      notes: el.notes.value.trim(),
      status: el.status.value,
    };

    state.submitting = true;
    el.submit.disabled = true;
    el.submit.textContent = cfg.i18n.submitting;

    try {
      const data = await api('/admin/book', { method: 'POST', body: JSON.stringify(payload) });
      const b = data.booking || {};
      el.success.hidden = false;
      el.success.innerHTML = `<strong>${escapeHtml(data.message || '')}</strong><br />${cfg.i18n.bookingCode}: <code>${escapeHtml(b.code || '')}</code> — ${escapeHtml(b.date_label || '')} ${toFa(b.time || '')}`;
      state.date = null;
      state.time = null;
      await refreshAvailability();
    } catch (e) {
      fail(e.message || cfg.i18n.error);
    } finally {
      state.submitting = false;
      el.submit.disabled = false;
      el.submit.textContent = cfg.i18n.submit;
    }
  }

  root.querySelectorAll('.mrb-service-check').forEach((input) => {
    input.addEventListener('change', () => {
      clearFieldError('services');
      if (!multiService && input.checked) {
        root.querySelectorAll('.mrb-service-check').forEach((other) => {
          if (other !== input) other.checked = false;
        });
      }
      refreshAvailability().catch((e) => fail(e.message));
    });
  });

  if (el.staff) {
    el.staff.addEventListener('change', () => {
      refreshAvailability().catch((e) => fail(e.message));
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
    document.addEventListener('click', (e) => {
      if (!el.search.contains(e.target) && !el.results.contains(e.target)) {
        el.results.hidden = true;
      }
    });
  }

  [el.firstName, el.lastName, el.phone, el.email].forEach((field) => {
    if (!field) return;
    field.addEventListener('input', () => {
      clearCustomerSelection();
      const name = field.id === 'mrb-first-name' ? 'first_name'
        : field.id === 'mrb-last-name' ? 'last_name'
        : field.id === 'mrb-phone' ? 'phone'
        : field.id === 'mrb-email' ? 'email'
        : '';
      if (name) clearFieldError(name);
    });
    field.addEventListener('blur', () => {
      const name = field.id === 'mrb-first-name' ? 'first_name'
        : field.id === 'mrb-last-name' ? 'last_name'
        : field.id === 'mrb-phone' ? 'phone'
        : field.id === 'mrb-email' ? 'email'
        : '';
      if (name) validateNamedField(name, true);
    });
  });

  if (window.mrbBirthPicker) {
    birthPicker = window.mrbBirthPicker.init({
      root: document,
      prefix: 'mrb-phone-birth',
      calendarMode: cfg.settings.calendar_mode,
      months: cfg.months,
      placeholder: cfg.i18n.selectBirth || 'انتخاب تاریخ تولد',
      onChange: () => {
        clearCustomerSelection();
        clearFieldError('birth_date');
      },
    });
  }

  root.querySelector('#mrb-prev-month').addEventListener('click', () => {
    state.calMonth--;
    if (state.calMonth < 1) {
      state.calMonth = 12;
      state.calYear--;
    }
    loadMonth().catch((e) => fail(e.message));
  });

  root.querySelector('#mrb-next-month').addEventListener('click', () => {
    state.calMonth++;
    if (state.calMonth > 12) {
      state.calMonth = 1;
      state.calYear++;
    }
    loadMonth().catch((e) => fail(e.message));
  });

  el.submit.addEventListener('click', () => submitBooking());

  initCalendarCursor();
  renderWeekdays();
})();
