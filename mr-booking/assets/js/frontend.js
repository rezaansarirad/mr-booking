/**
 * MR Booking frontend wizard.
 */
(function () {
  'use strict';

  const cfg = window.mrBooking || {};
  const root = document.getElementById('mr-booking-app');
  if (!root || !cfg.restUrl) return;

  const state = {
    step: 1,
    services: [],
    allServices: [],
    staff: [],
    selectedServices: [],
    staffId: Number(root.dataset.staff || 0) || null,
    days: [],
    slots: [],
    slotsDetail: [],
    date: null,
    time: null,
    calYear: null,
    calMonth: null,
    submitting: false,
    nextLoading: false,
    result: null,
  };

  const preService = Number(root.dataset.service || 0);
  const staffRequired = Number(cfg.settings.require_staff) === 1;
  const depositMode = Number(cfg.settings.show_deposit) === 1 && !!root.querySelector('[data-panel="6"]');
  const LAST_STEP = depositMode ? 6 : 5;

  const el = {
    panels: root.querySelectorAll('.mrb__panel'),
    steps: root.querySelectorAll('.mrb__step'),
    next: root.querySelector('#mrb-next'),
    prev: root.querySelector('#mrb-prev'),
    services: root.querySelector('#mrb-services'),
    servicesEmpty: root.querySelector('#mrb-services-empty'),
    staffWrap: root.querySelector('#mrb-staff-wrap'),
    staffSelect: root.querySelector('#mrb-staff-select'),
    staffTitle: root.querySelector('#mrb-staff-title'),
    staffHint: root.querySelector('#mrb-staff-hint'),
    calTitle: root.querySelector('#mrb-cal-title'),
    calendar: root.querySelector('#mrb-calendar'),
    calendarScroll: root.querySelector('#mrb-calendar-scroll'),
    weekdays: root.querySelector('#mrb-weekdays'),
    calHint: root.querySelector('#mrb-cal-hint'),
    selectedDate: root.querySelector('#mrb-selected-date'),
    selectedDateTime: root.querySelector('#mrb-selected-date-time'),
    editDate: root.querySelector('#mrb-edit-date'),
    slots: root.querySelector('#mrb-slots'),
    slotsHint: root.querySelector('#mrb-slots-hint'),
    summary: root.querySelector('#mrb-summary'),
    error: root.querySelector('#mrb-error'),
    success: root.querySelector('#mrb-success'),
    successTitle: root.querySelector('#mrb-success-title'),
    successBody: root.querySelector('#mrb-success-body'),
    forName: root.querySelector('.mrb-for-name'),
    terms: root.querySelector('#mrb-terms-accept'),
    payLines: root.querySelector('#mrb-pay-lines'),
    payTotal: root.querySelector('#mrb-pay-total'),
    tip: root.querySelector('#mrb-tip'),
    payMethods: root.querySelector('#mrb-pay-methods'),
    walletOption: root.querySelector('#mrb-pay-wallet-option'),
    walletHint: root.querySelector('[data-wallet-hint]'),
  };

  function formatMoney(n) {
    const v = Math.max(0, Math.round(Number(n) || 0));
    return toFa(v.toLocaleString('en-US')) + ' تومان';
  }

  function parseMoney(raw) {
    const digits = toEnDigits(raw).replace(/[^\d]/g, '');
    return digits ? Number(digits) : 0;
  }

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

  async function api(path, options = {}) {
    const url = cfg.restUrl.replace(/\/$/, '') + path;
    const res = await fetch(url, {
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': cfg.nonce,
        ...(options.headers || {}),
      },
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      const err = new Error(data.error || cfg.i18n.error || 'Error');
      err.data = data;
      throw err;
    }
    return data;
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

  function setupPersonal() {
    const emailInput = root.querySelector('[name="email"]');
    const emailReq = root.querySelector('.mrb-req--email');
    if (cfg.settings.require_email) {
      if (emailInput) {
        emailInput.required = true;
        emailInput.setAttribute('aria-required', 'true');
      }
      if (emailReq) emailReq.hidden = false;
    } else if (emailInput) {
      emailInput.required = false;
      emailInput.removeAttribute('aria-required');
    }

    if (cfg.settings.require_birth_date) {
      const birth = root.querySelector('#mrb-birth-date');
      const birthReq = root.querySelector('.mrb-req--birth');
      if (birth) birth.required = true;
      if (birthReq) birthReq.hidden = false;
    }

    const forNameInput = root.querySelector('[name="booking_for_name"]');
    root.querySelectorAll('input[name="booking_for"]').forEach((r) => {
      r.addEventListener('change', () => {
        const v = root.querySelector('input[name="booking_for"]:checked').value;
        const needName = v !== 'myself';
        el.forName.classList.toggle('is-hidden', !needName);
        if (forNameInput) {
          forNameInput.required = needName;
          if (needName) {
            forNameInput.setAttribute('aria-required', 'true');
          } else {
            forNameInput.removeAttribute('aria-required');
            clearFieldError('booking_for_name');
          }
        }
      });
    });

    const phoneInput = root.querySelector('[name="phone"]');
    if (phoneInput) {
      phoneInput.addEventListener('input', () => {
        const cursor = phoneInput.selectionStart;
        const before = phoneInput.value;
        phoneInput.value = toEnDigits(before).replace(/[^\d]/g, '').slice(0, 11);
        if (document.activeElement === phoneInput && cursor != null) {
          const delta = before.length - phoneInput.value.length;
          const pos = Math.max(0, cursor - delta);
          phoneInput.setSelectionRange(pos, pos);
        }
        phoneInput.setCustomValidity('');
        if (phoneInput.classList.contains('is-invalid')) {
          validatePhoneField(phoneInput, false);
        }
      });
      phoneInput.addEventListener('blur', () => {
        normalizePhoneField(phoneInput);
        validatePhoneField(phoneInput, true);
      });
    }

    root.querySelectorAll('.mrb__fields input[name]').forEach((input) => {
      if (input.name === 'phone' || input.type === 'hidden' || input.type === 'radio') return;
      input.addEventListener('blur', () => validateNamedField(input.name, true));
      input.addEventListener('input', () => {
        if (input.classList.contains('is-invalid') || input.getAttribute('aria-invalid') === 'true') {
          validateNamedField(input.name, false);
        }
      });
    });

    setupBirthPicker();
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

  function setFieldError(name, message) {
    const wrap = fieldWrap(name);
    const err = fieldErrorEl(name);
    const input =
      root.querySelector('[name="' + name + '"]') ||
      (name === 'birth_date' ? root.querySelector('#mrb-birth-trigger') : null);

    if (wrap) wrap.classList.add('is-invalid');
    if (err) {
      err.hidden = false;
      err.textContent = message;
    }
    if (input) {
      input.classList.add('is-invalid');
      input.setAttribute('aria-invalid', 'true');
      if (typeof input.setCustomValidity === 'function' && name !== 'birth_date') {
        input.setCustomValidity(message);
      }
    }
    if (name === 'birth_date') {
      const trigger = root.querySelector('#mrb-birth-trigger');
      if (trigger) {
        trigger.classList.add('is-invalid');
        trigger.setAttribute('aria-invalid', 'true');
      }
    }
  }

  function clearFieldError(name) {
    const wrap = fieldWrap(name);
    const err = fieldErrorEl(name);
    const input = root.querySelector('[name="' + name + '"]');
    if (wrap) wrap.classList.remove('is-invalid');
    if (err) {
      err.hidden = true;
      err.textContent = '';
    }
    if (input) {
      input.classList.remove('is-invalid');
      input.removeAttribute('aria-invalid');
      if (typeof input.setCustomValidity === 'function') input.setCustomValidity('');
    }
    if (name === 'birth_date') {
      const trigger = root.querySelector('#mrb-birth-trigger');
      if (trigger) {
        trigger.classList.remove('is-invalid');
        trigger.removeAttribute('aria-invalid');
      }
    }
  }

  function clearAllErrors() {
    root.querySelectorAll('.mrb-field.is-invalid, .mrb__birth-trigger.is-invalid, .is-invalid').forEach((n) => {
      n.classList.remove('is-invalid');
    });
    root.querySelectorAll('.mrb-field__error').forEach((n) => {
      n.hidden = true;
      n.textContent = '';
    });
    root.querySelectorAll('[aria-invalid="true"]').forEach((n) => n.removeAttribute('aria-invalid'));
    root.querySelectorAll('input').forEach((n) => {
      if (typeof n.setCustomValidity === 'function') n.setCustomValidity('');
    });
    el.error.hidden = true;
    el.error.textContent = '';
  }

  function validatePhoneField(input, showEmpty) {
    const value = normalizePhoneField(input);
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
    const input = root.querySelector('[name="' + name + '"]');
    if (!input) return true;

    if (name === 'phone') return validatePhoneField(input, showEmpty);

    const value = String(input.value || '').trim();

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
        if (cfg.settings.require_email && showEmpty) {
          setFieldError(name, cfg.i18n.required);
          return false;
        }
        clearFieldError(name);
        return !cfg.settings.require_email;
      }
      if (!isValidEmail(value)) {
        setFieldError(name, cfg.i18n.invalidEmail);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    if (name === 'booking_for_name') {
      const forVal = root.querySelector('input[name="booking_for"]:checked');
      const need = forVal && forVal.value !== 'myself';
      if (!need) {
        clearFieldError(name);
        return true;
      }
      if (!value) {
        if (showEmpty) setFieldError(name, cfg.i18n.forNameRequired);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    if (name === 'birth_date') {
      if (cfg.settings.require_birth_date && !value) {
        if (showEmpty) setFieldError(name, cfg.i18n.selectBirth);
        return false;
      }
      clearFieldError(name);
      return true;
    }

    return true;
  }

  function focusFirstInvalid() {
    const target =
      root.querySelector('.mrb-field.is-invalid input:not([type="hidden"])') ||
      root.querySelector('.mrb__birth-trigger.is-invalid') ||
      root.querySelector('.mrb-field__error:not([hidden])');
    if (target && typeof target.focus === 'function') {
      target.focus({ preventScroll: false });
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }

  function validateStep(step) {
    clearAllErrors();
    let ok = true;
    let firstMsg = '';

    const markFail = (msg) => {
      ok = false;
      if (!firstMsg) firstMsg = msg;
    };

    if (step === 1) {
      if (auth.mode === 'required' && !auth.loggedIn) {
        if (authEl.login) authEl.login.hidden = false;
        if (authOtp) authOtp.focus();
        return fail(cfg.i18n.loginRequired);
      }
      ['first_name', 'last_name', 'phone', 'email', 'birth_date', 'booking_for_name'].forEach((name) => {
        if (!validateNamedField(name, true)) {
          const err = fieldErrorEl(name);
          markFail((err && err.textContent) || cfg.i18n.required);
        }
      });
    }

    if (step === 2) {
      if (!state.selectedServices.length) {
        const err = fieldErrorEl('services');
        if (err) {
          err.hidden = false;
          err.textContent = cfg.i18n.selectService;
        }
        el.services.classList.add('is-invalid');
        markFail(cfg.i18n.selectService);
      }
      if (staffRequired && !state.staffId && state.staff.length) {
        const err = fieldErrorEl('staff');
        if (err) {
          err.hidden = false;
          err.textContent = cfg.i18n.selectStaff;
        }
        if (el.staffSelect) {
          el.staffSelect.classList.add('is-invalid');
          el.staffSelect.setAttribute('aria-invalid', 'true');
        }
        markFail(cfg.i18n.selectStaff);
      }
    }

    if (step === 3) {
      if (!state.date) {
        const err = fieldErrorEl('date');
        if (err) {
          err.hidden = false;
          err.textContent = cfg.i18n.selectDate;
        }
        el.calendar.classList.add('is-invalid');
        markFail(cfg.i18n.selectDate);
      } else {
        const today = (cfg.settings && cfg.settings.today) || '';
        if (today && state.date < today) {
          const err = fieldErrorEl('date');
          if (err) {
            err.hidden = false;
            err.textContent = cfg.i18n.pastDate;
          }
          markFail(cfg.i18n.pastDate);
        } else {
          const day = state.days.find((d) => d.date === state.date);
          const allowSameDay = cfg.settings && Number(cfg.settings.allow_same_day) === 1;
          const blockedSameDay = !allowSameDay && today && state.date === today;
          const canSelect =
            day &&
            ( day.selectable !== undefined
              ? !!day.selectable
              : !day.past && !day.closed && !day.beyond && !blockedSameDay );
          if (!canSelect) {
            const err = fieldErrorEl('date');
            if (err) {
              err.hidden = false;
              err.textContent =
                day && day.no_future_slots
                  ? cfg.i18n.noFutureSlots || cfg.i18n.selectDate
                  : blockedSameDay
                    ? cfg.i18n.sameDayDisabled || cfg.i18n.selectDate
                    : cfg.i18n.pastDate || cfg.i18n.selectDate;
            }
            markFail(cfg.i18n.pastDate || cfg.i18n.selectDate);
          }
        }
      }
    }

    if (step === 5 && el.terms && Number(cfg.settings.require_terms) === 1 && !el.terms.checked) {
      const err = fieldErrorEl('terms');
      if (err) {
        err.hidden = false;
        err.textContent = cfg.i18n.acceptTerms;
      }
      const wrap = fieldWrap('terms');
      if (wrap) wrap.classList.add('is-invalid');
      el.terms.setAttribute('aria-invalid', 'true');
      markFail(cfg.i18n.acceptTerms);
    }

    if (step === 6) {
      if (depositTotal() > 0 && !selectedPaymentMethod()) {
        const err = fieldErrorEl('payment');
        if (err) {
          err.hidden = false;
          err.textContent = cfg.i18n.selectPayment;
        }
        if (el.payMethods) el.payMethods.classList.add('is-invalid');
        markFail(cfg.i18n.selectPayment);
      }
    }

    if (step === 4) {
      const selectedSlot = state.slotsDetail.find((s) => s.time === state.time);
      if (!state.time || !selectedSlot || selectedSlot.status !== 'available') {
        const err = fieldErrorEl('time');
        if (err) {
          err.hidden = false;
          err.textContent = cfg.i18n.selectTime;
        }
        el.slots.classList.add('is-invalid');
        markFail(cfg.i18n.selectTime);
      }
    }

    if (!ok) {
      fail(firstMsg || cfg.i18n.fixErrors);
      focusFirstInvalid();
      return false;
    }
    return true;
  }

  function fail(msg) {
    el.error.hidden = false;
    el.error.textContent = msg;
    return false;
  }

  let birthPicker = null;

  function setupBirthPicker() {
    if (!window.mrbBirthPicker) return;
    birthPicker = window.mrbBirthPicker.init({
      root: root,
      prefix: 'mrb-birth',
      calendarMode: cfg.settings.calendar_mode,
      months: cfg.months,
      placeholder: (cfg.texts && cfg.texts.ph_birth_date) || cfg.i18n.selectBirth || 'انتخاب تاریخ تولد',
      onChange: () => clearFieldError('birth_date'),
    });
  }

  function staffHasAssignments() {
    return state.staff.some((s) => Array.isArray(s.service_ids) && s.service_ids.length > 0);
  }

  function visibleServices() {
    if (!state.staffId) {
      return state.allServices.slice();
    }
    const member = state.staff.find((s) => Number(s.id) === Number(state.staffId));
    if (!member) return state.allServices.slice();
    const ids = Array.isArray(member.service_ids) ? member.service_ids.map(Number) : [];
    if (!ids.length) {
      return staffHasAssignments() ? [] : state.allServices.slice();
    }
    return state.allServices.filter((s) => ids.includes(Number(s.id)));
  }

  function pruneSelectedServices() {
    const allowed = new Set(state.services.map((s) => Number(s.id)));
    state.selectedServices = state.selectedServices.filter((id) => allowed.has(Number(id)));
  }

  function renderServices() {
    state.services = visibleServices();
    pruneSelectedServices();
    el.services.innerHTML = '';

    if (el.servicesEmpty) {
      el.servicesEmpty.hidden = state.services.length > 0;
    }

    state.services.forEach((s) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mrb__service';
      btn.setAttribute('role', 'listitem');
      btn.dataset.id = s.id;
      if (s.color) btn.style.setProperty('--mrb-service-accent', s.color);
      if (state.selectedServices.map(Number).includes(Number(s.id))) {
        btn.classList.add('is-selected');
      }
      const priceHtml =
        !depositMode && Number(cfg.settings.show_prices) === 1 && Number(s.has_price) === 1 && s.price_label
          ? `<em class="mrb__service-price">${escapeHtml(s.price_label)}</em>`
          : '';
      const depositHtml = depositMode
        ? `<em class="mrb__service-deposit">${escapeHtml(cfg.texts.deposit_label)}: ${
            Number(s.deposit) > 0 ? escapeHtml(s.deposit_label) : escapeHtml(cfg.i18n.noDeposit)
          }</em>`
        : '';
      btn.innerHTML = `
        <span class="mrb__service-check" aria-hidden="true"></span>
        <div class="mrb__service-main">
          <strong>${escapeHtml(s.name)}</strong>
          ${s.description ? `<p>${escapeHtml(s.description)}</p>` : ''}
        </div>
        <div class="mrb__service-meta">
          <span class="mrb__duration-pill">${escapeHtml(s.duration_label || '')}</span>
          ${priceHtml}
          ${depositHtml}
        </div>
      `;
      btn.addEventListener('click', () => toggleService(Number(s.id), btn));
      el.services.appendChild(btn);
    });
  }

  async function loadServices() {
    const data = await api('/services');
    state.allServices = data.services || [];
    if (preService && !state.selectedServices.length) {
      const exists = state.allServices.some((s) => Number(s.id) === preService);
      if (exists) state.selectedServices = [preService];
    }
    renderServices();
  }

  async function onStaffChange() {
    const val = el.staffSelect ? el.staffSelect.value : '';
    state.staffId = val ? Number(val) : null;
    clearFieldError('staff');
    if (el.staffSelect) {
      el.staffSelect.classList.remove('is-invalid');
      el.staffSelect.removeAttribute('aria-invalid');
    }
    el.error.hidden = true;
    await renderServices();
    if (state.step === 3) {
      await loadMonth();
    }
  }

  async function loadStaff() {
    if (!el.staffWrap || !el.staffSelect) return;

    const data = await api('/staff');
    state.staff = data.staff || [];

    if (!state.staff.length) {
      el.staffWrap.classList.add('is-hidden');
      renderServices();
      return;
    }

    el.staffWrap.classList.remove('is-hidden');
    if (el.staffTitle) {
      el.staffTitle.textContent = staffRequired
        ? cfg.i18n.staffLabelRequired || 'انتخاب پرسنل'
        : cfg.i18n.staffLabelOptional || 'انتخاب پرسنل (اختیاری)';
    }
    if (el.staffHint) {
      el.staffHint.textContent = staffRequired
        ? cfg.i18n.staffHintRequired || 'ابتدا پرسنل را انتخاب کنید؛ سپس خدمات همان پرسنل نمایش داده می‌شود.'
        : cfg.i18n.staffHintOptional || 'پرسنل را انتخاب کنید تا خدمات مرتبط فیلتر شود، یا «همه پرسنل» را بگذارید.';
    }

    el.staffSelect.innerHTML = '';

    if (staffRequired) {
      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = (cfg.texts && cfg.texts.ph_staff) || cfg.i18n.staffPlaceholder || 'انتخاب پرسنل…';
      placeholder.disabled = true;
      placeholder.hidden = true;
      placeholder.selected = !state.staffId;
      el.staffSelect.appendChild(placeholder);
    } else {
      const any = document.createElement('option');
      any.value = '';
      any.textContent = cfg.i18n.staffAny || 'همه پرسنل';
      el.staffSelect.appendChild(any);
    }

    state.staff.forEach((s) => {
      const opt = document.createElement('option');
      opt.value = String(s.id);
      opt.textContent = s.name;
      el.staffSelect.appendChild(opt);
    });

    if (staffRequired && !state.staffId && state.staff.length === 1) {
      state.staffId = Number(state.staff[0].id);
    }

    if (state.staffId && state.staff.some((s) => Number(s.id) === Number(state.staffId))) {
      el.staffSelect.value = String(state.staffId);
    } else if (!staffRequired) {
      el.staffSelect.value = '';
      state.staffId = null;
    }

    if (staffRequired && state.staffId) {
      clearFieldError('staff');
    }

    renderServices();
  }

  function toggleService(id, btn) {
    id = Number(id);
    if (cfg.settings.enable_multi_service) {
      if (state.selectedServices.map(Number).includes(id)) {
        state.selectedServices = state.selectedServices.map(Number).filter((x) => x !== id);
        btn.classList.remove('is-selected');
      } else {
        state.selectedServices = state.selectedServices.map(Number).concat(id);
        btn.classList.add('is-selected');
      }
    } else {
      state.selectedServices = [id];
      el.services.querySelectorAll('.mrb__service').forEach((b) => b.classList.remove('is-selected'));
      btn.classList.add('is-selected');
    }
    if (state.selectedServices.length) {
      clearFieldError('services');
      el.services.classList.remove('is-invalid');
      el.error.hidden = true;
    }
  }

  async function initStepTwo() {
    await loadServices();
    await loadStaff();
  }

  function canSelectDay(day) {
    if (!day) return false;
    const today = (cfg.settings && cfg.settings.today) || '';
    const allowSameDay = cfg.settings && Number(cfg.settings.allow_same_day) === 1;
    const blockedSameDay = !allowSameDay && today && day.date === today;
    return day.selectable !== undefined
      ? !!day.selectable
      : !day.past && !blockedSameDay && !day.closed && !day.beyond;
  }

  function ensureDefaultDateSelection() {
    if (state.date) {
      return;
    }
    const today = (cfg.settings && cfg.settings.today) || '';
    if (!today) {
      return;
    }
    const todayDay = state.days.find((d) => d.date === today);
    if (todayDay && canSelectDay(todayDay)) {
      state.date = today;
    }
  }

  function formatSelectedDateLabel(day) {
    if (!day) return '';
    const mode = cfg.settings.calendar_mode;
    if (mode === 'both') {
      return String(day.dual || day.date).replace('\n', ' · ');
    }
    if (mode === 'gregorian') {
      return day.date;
    }
    const months = cfg.months || {};
    const monthName = months[day.j_month] || '';
    return [day.weekday_fa || '', toFa(day.j_day), monthName, toFa(day.j_year)].filter(Boolean).join(' ');
  }

  function updateSelectedDateUI(dayObj) {
    const day = dayObj || state.days.find((d) => d.date === state.date);
    const bars = [el.selectedDate, el.selectedDateTime].filter(Boolean);

    if (!day || !state.date) {
      bars.forEach((bar) => {
        bar.hidden = true;
      });
      return;
    }

    const label = formatSelectedDateLabel(day);
    bars.forEach((bar) => {
      bar.hidden = false;
      const textEl = bar.querySelector('[data-selected-date-text]');
      if (textEl) {
        textEl.textContent = label;
      }
    });
  }

  function selectedDayObject() {
    return state.days.find((d) => d.date === state.date) || null;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  async function loadMonth() {
    el.calHint.textContent = cfg.i18n.loading;
    const params = new URLSearchParams({
      year: state.calYear,
      month: state.calMonth,
    });
    state.selectedServices.forEach((id) => params.append('service_ids[]', id));
    if (state.staffId) params.set('staff_id', state.staffId);

    const data = await api('/availability/month?' + params.toString());
    state.days = data.days || [];
    renderCalendar();
    el.calHint.textContent = '';
  }

  function renderWeekdays() {
    // Persian week starts Saturday.
    const labels = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];
    el.weekdays.innerHTML = labels.map((l) => `<span>${l}</span>`).join('');
  }

  function renderCalendar() {
    const mode = cfg.settings.calendar_mode;
    const monthName =
      mode === 'gregorian'
        ? new Date(state.calYear, state.calMonth - 1, 1).toLocaleString('en', { month: 'long' })
        : (cfg.months && cfg.months[state.calMonth]) || state.calMonth;

    el.calTitle.innerHTML =
      mode === 'both'
        ? `<strong>${toFa(monthName)} ${toFa(state.calYear)}</strong>`
        : `<strong>${toFa(monthName)} ${toFa(state.calYear)}</strong>`;

    // Build grid aligned to Saturday start.
    let firstDate = state.days[0]?.date;
    if (!firstDate) {
      el.calendar.innerHTML = '';
      return;
    }

    ensureDefaultDateSelection();

    const today = (cfg.settings && cfg.settings.today) || '';
    const allowSameDay = cfg.settings && Number(cfg.settings.allow_same_day) === 1;

    const first = new Date(firstDate + 'T12:00:00');
    const dow = first.getDay(); // 0 Sun
    const offset = (dow + 1) % 7; // Sat=0

    el.calendar.innerHTML = '';
    for (let i = 0; i < offset; i++) {
      const empty = document.createElement('div');
      empty.className = 'mrb__day is-empty';
      el.calendar.appendChild(empty);
    }

    state.days.forEach((day) => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mrb__day';
      btn.dataset.date = day.date;

      let mainNum = mode === 'gregorian' ? day.g_day : day.j_day;
      let sub = '';
      if (mode === 'both') {
        mainNum = day.j_day;
        sub = `<small>${toFa(day.g_day)}</small>`;
      }

      btn.innerHTML = `<span>${toFa(mainNum)}</span>${sub}`;

      const isToday = !!(day.today || (today && day.date === today));
      const isPast = !!day.past;
      const canSelect = canSelectDay(day);

      if (isToday) {
        btn.classList.add('is-today');
        btn.innerHTML += `<em class="mrb__day-today">${escapeHtml((cfg.i18n && cfg.i18n.todayLabel) || 'امروز')}</em>`;
      }

      if (day.holiday) {
        btn.classList.add('is-holiday');
        btn.title = day.holiday_title || cfg.texts.holiday;
      }
      if (isToday && !btn.title) {
        btn.title = (cfg.i18n && cfg.i18n.todayLabel) || 'امروز';
      }
      if (!canSelect) {
        btn.classList.add('is-disabled');
        if (isPast) btn.classList.add('is-past');
        btn.disabled = true;
        if (isPast) btn.title = (cfg.i18n && cfg.i18n.pastDate) || '';
        else if (!allowSameDay && today && day.date === today) btn.title = (cfg.i18n && cfg.i18n.sameDayDisabled) || '';
        else if (day.closed_reason === 'holiday') btn.title = day.holiday_title || (cfg.texts && cfg.texts.holiday) || '';
        else if (day.closed) btn.title = (cfg.texts && cfg.texts.closed_day) || '';
        else if (day.no_future_slots) btn.title = (cfg.i18n && cfg.i18n.noFutureSlots) || '';
      } else if (day.no_future_slots) {
        btn.classList.add('is-no-slots');
        btn.title = (cfg.i18n && cfg.i18n.noFutureSlots) || '';
      } else if (day.fully_booked) {
        btn.classList.add('is-full');
        btn.title = cfg.texts.fully_booked;
      } else if (day.available) {
        btn.classList.add('is-available');
      } else {
        btn.classList.add('is-available');
      }

      if (state.date === day.date && !btn.disabled) btn.classList.add('is-selected');

      btn.addEventListener('click', () => {
        if (btn.disabled) return;
        state.date = day.date;
        state.time = null;
        el.calendar.querySelectorAll('.mrb__day').forEach((d) => d.classList.remove('is-selected'));
        btn.classList.add('is-selected');
        clearFieldError('date');
        el.calendar.classList.remove('is-invalid');
        el.error.hidden = true;
        updateSelectedDateUI(day);
        if (el.calHint) {
          el.calHint.textContent = cfg.i18n.selectedDateHint || 'برای ادامه، «بعدی» را بزنید یا تاریخ دیگری انتخاب کنید.';
        }
        if (el.calendarScroll) {
          el.calendarScroll.scrollTop = 0;
        }
      });

      el.calendar.appendChild(btn);
    });

    // Drop stale selection if it became past/disabled.
    if (state.date) {
      const selected = state.days.find((d) => d.date === state.date);
      if (!canSelectDay(selected)) {
        state.date = null;
        state.time = null;
        updateSelectedDateUI(null);
      }
    }

    if (!state.date) {
      ensureDefaultDateSelection();
    }

    if (state.date) {
      updateSelectedDateUI(selectedDayObject());
      el.calendar.querySelectorAll('.mrb__day').forEach((btn) => {
        const match = btn.dataset.date === state.date && !btn.disabled;
        btn.classList.toggle('is-selected', match);
      });
    }
  }

  async function loadSlots() {
    el.slots.innerHTML = '';
    el.slotsHint.textContent = cfg.i18n.loading;
    const params = new URLSearchParams({ date: state.date });
    state.selectedServices.forEach((id) => params.append('service_ids[]', id));
    if (state.staffId) params.set('staff_id', state.staffId);
    const data = await api('/availability/slots?' + params.toString());
    state.slots = data.slots || [];
    state.slotsDetail = data.detail && data.detail.length ? data.detail : state.slots.map((t) => ({ time: t, status: 'available' }));
    const detail = state.slotsDetail;
    const hasAvailable = detail.some((s) => s.status === 'available');
    el.slotsHint.textContent = hasAvailable ? '' : data.message || cfg.texts.no_slots;

    if (state.time && !detail.some((s) => s.time === state.time && s.status === 'available')) {
      state.time = null;
    }

    detail.forEach((slot) => {
      const t = slot.time;
      const isPast = slot.status === 'past';
      const isBooked = slot.status === 'booked';
      const isAvailable = slot.status === 'available';
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mrb__slot';
      if (isPast) btn.classList.add('is-past');
      if (isBooked) btn.classList.add('is-booked');
      if (!isAvailable) btn.classList.add('is-disabled');
      if (state.time === t && isAvailable) btn.classList.add('is-selected');
      btn.textContent = formatClock(t);
      btn.disabled = !isAvailable;
      if (isPast) btn.title = (cfg.i18n && cfg.i18n.pastTime) || '';
      else if (isBooked) btn.title = (cfg.i18n && cfg.i18n.bookedTime) || '';
      if (isAvailable) {
        btn.addEventListener('click', () => {
          state.time = t;
          el.slots.querySelectorAll('.mrb__slot').forEach((b) => b.classList.remove('is-selected'));
          btn.classList.add('is-selected');
          clearFieldError('time');
          el.slots.classList.remove('is-invalid');
          el.error.hidden = true;
        });
      }
      el.slots.appendChild(btn);
    });
  }

  function selectedServiceObjects() {
    return state.services.filter((s) => state.selectedServices.includes(s.id));
  }

  function renderSummary() {
    const svcs = selectedServiceObjects();
    const day = selectedDayObject();
    const name =
      root.querySelector('[name="first_name"]').value +
      ' ' +
      root.querySelector('[name="last_name"]').value;
    const forVal = root.querySelector('input[name="booking_for"]:checked').value;
    const forLabel =
      forVal === 'child'
        ? cfg.texts.booking_for_child
        : forVal === 'other'
          ? cfg.texts.booking_for_other
          : cfg.texts.booking_for_myself;

    const staffMember = state.staffId
      ? state.staff.find((s) => Number(s.id) === Number(state.staffId))
      : null;

    el.summary.innerHTML = `
      <dl>
        <div><dt>مشتری</dt><dd>${escapeHtml(name)}</dd></div>
        <div><dt>موبایل</dt><dd>${escapeHtml(root.querySelector('[name="phone"]').value)}</dd></div>
        <div><dt>رزرو برای</dt><dd>${escapeHtml(forLabel)}</dd></div>
        ${staffMember ? `<div><dt>پرسنل</dt><dd>${escapeHtml(staffMember.name)}</dd></div>` : ''}
        <div><dt>خدمات</dt><dd>${escapeHtml(svcs.map((s) => s.name).join('، '))}</dd></div>
        <div><dt>تاریخ</dt><dd>${escapeHtml(day ? formatSelectedDateLabel(day) : state.date || '')}</dd></div>
        <div><dt>ساعت</dt><dd>${formatClock(state.time || '')}</dd></div>
        <div><dt>مدت</dt><dd>${toFa(svcs.reduce((a, s) => a + s.duration, 0))} دقیقه</dd></div>
        ${
          depositMode
            ? `<div><dt>${escapeHtml(cfg.texts.deposit_label)}</dt><dd>${
                depositTotal() > 0 ? escapeHtml(formatMoney(depositTotal())) : escapeHtml(cfg.i18n.noDeposit)
              }</dd></div>`
            : ''
        }
        ${
          !depositMode && Number(cfg.settings.show_prices) === 1
            ? (() => {
                const priced = svcs.filter((s) => Number(s.has_price) === 1 && s.price_label);
                if (!priced.length) return '';
                return `<div><dt>مبلغ</dt><dd>${escapeHtml(
                  priced.map((s) => s.price_label).join(' + ')
                )}</dd></div>`;
              })()
            : ''
        }
      </dl>
    `;
  }

  function nextButtonLabel(step) {
    if (step === LAST_STEP) {
      return depositMode && depositTotal() > 0 ? cfg.i18n.btnPay : cfg.texts.btn_submit;
    }
    if (step === 5 && depositMode && depositTotal() > 0) return cfg.i18n.btnToPayment;
    return cfg.texts.btn_next;
  }

  /* ─── Payment step ─── */

  function depositTotal() {
    if (!depositMode) return 0;
    return selectedServiceObjects().reduce((sum, s) => sum + (Number(s.deposit) || 0), 0);
  }

  function tipAmount() {
    return el.tip ? parseMoney(el.tip.value) : 0;
  }

  function selectedPaymentMethod() {
    const r = root.querySelector('input[name="payment_method"]:checked');
    return r ? r.value : '';
  }

  function renderPayment() {
    if (!depositMode || !el.payLines) return;
    const svcs = selectedServiceObjects();
    el.payLines.innerHTML = `
      <dl>
        ${svcs
          .map(
            (s) =>
              `<div><dt>${escapeHtml(s.name)}</dt><dd>${
                Number(s.deposit) > 0 ? escapeHtml(s.deposit_label) : escapeHtml(cfg.i18n.noDeposit)
              }</dd></div>`
          )
          .join('')}
        <div class="mrb-pay__line--strong"><dt>${escapeHtml(cfg.texts.deposit_label)}</dt><dd>${escapeHtml(formatMoney(depositTotal()))}</dd></div>
      </dl>`;
    updatePayTotal();
  }

  function updatePayTotal() {
    if (!el.payTotal) return;
    const total = depositTotal() + tipAmount();
    el.payTotal.querySelector('[data-pay-total]').textContent = formatMoney(total);
    updateWalletOption();
    const labelEl = el.next.querySelector('.mrb__btn__label');
    if (labelEl && state.step === LAST_STEP && !state.nextLoading) labelEl.textContent = nextButtonLabel(state.step);
  }

  function updateWalletOption() {
    if (!el.walletOption) return;
    const input = el.walletOption.querySelector('input');
    const total = depositTotal() + tipAmount();
    const wallet = cfg.wallet;
    if (!auth.loggedIn || !wallet) {
      el.walletOption.classList.add('is-disabled');
      input.disabled = true;
      if (input.checked) input.checked = false;
      if (el.walletHint) el.walletHint.textContent = cfg.i18n.walletLogin;
      return;
    }
    const enough = Number(wallet.balance) + 0.001 >= total;
    el.walletOption.classList.toggle('is-disabled', !enough);
    input.disabled = !enough;
    if (!enough && input.checked) input.checked = false;
    if (el.walletHint) {
      el.walletHint.textContent = cfg.i18n.walletBalance + ': ' + (wallet.balance_label || '') + (enough ? '' : ' — ' + cfg.i18n.walletShort);
    }
  }

  if (el.tip) {
    el.tip.addEventListener('input', () => {
      const before = el.tip.value;
      const digits = toEnDigits(before).replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
      const next = digits ? digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';
      if (next !== before) el.tip.value = next;
      updatePayTotal();
    });
  }

  root.querySelectorAll('input[name="payment_method"]').forEach((r) => {
    r.addEventListener('change', () => {
      clearFieldError('payment');
      if (el.payMethods) el.payMethods.classList.remove('is-invalid');
      el.error.hidden = true;
    });
  });

  /* Terms dialog */
  (function setupTermsDialog() {
    const dialog = root.querySelector('#mrb-terms-dialog');
    const opener = root.querySelector('#mrb-terms-open');
    if (!dialog || !opener || typeof dialog.showModal !== 'function') return;
    let returnTo = null;
    const open = (e) => {
      e.preventDefault();
      returnTo = e.currentTarget;
      if (!dialog.open) dialog.showModal();
      const body = dialog.querySelector('.mrb-terms-dialog__body');
      if (body) body.scrollTop = 0;
    };
    opener.addEventListener('click', open);
    dialog.querySelectorAll('[data-terms-close]').forEach((b) => b.addEventListener('click', () => dialog.close()));
    const accept = dialog.querySelector('[data-terms-accept]');
    if (accept) {
      accept.addEventListener('click', () => {
        if (el.terms) {
          el.terms.checked = true;
          el.terms.dispatchEvent(new Event('change', { bubbles: true }));
        }
        dialog.close();
      });
    }
    dialog.addEventListener('click', (e) => {
      if (e.target === dialog) dialog.close();
    });
    dialog.addEventListener('close', () => {
      if (returnTo && typeof returnTo.focus === 'function') returnTo.focus();
    });
  })();

  if (el.terms) {
    el.terms.addEventListener('change', () => {
      if (el.terms.checked) {
        clearFieldError('terms');
        el.terms.removeAttribute('aria-invalid');
        el.error.hidden = true;
      }
    });
  }

  function setNextLoading(loading, labelOverride) {
    state.nextLoading = loading;
    el.next.disabled = loading;
    el.next.classList.toggle('is-loading', loading);
    el.next.setAttribute('aria-busy', loading ? 'true' : 'false');
    if (el.prev && state.step > 1) {
      el.prev.disabled = loading;
    }
    const labelEl = el.next.querySelector('.mrb__btn__label') || el.next;
    labelEl.textContent = loading
      ? labelOverride || cfg.i18n.loading
      : nextButtonLabel(state.step);
  }

  function setStep(step) {
    state.step = step;
    el.panels.forEach((p) => {
      const n = p.dataset.panel;
      p.classList.toggle('is-active', String(n) === String(step));
    });
    el.steps.forEach((s) => {
      const n = Number(s.dataset.step);
      s.classList.toggle('is-active', n === step);
      s.classList.toggle('is-done', n < step);
    });
    el.prev.style.visibility = step === 1 ? 'hidden' : 'visible';
    if (el.prev) {
      el.prev.disabled = false;
    }
    el.next.hidden = step === 1 && auth.mode === 'required' && !auth.loggedIn;
    const labelEl = el.next.querySelector('.mrb__btn__label');
    if (labelEl) {
      labelEl.textContent = nextButtonLabel(step);
    } else {
      el.next.textContent = nextButtonLabel(step);
    }
    clearAllErrors();
    root.querySelector('.mrb__footer').hidden = false;
    if (step === 3 || step === 4) {
      updateSelectedDateUI(selectedDayObject());
    }
  }

  async function goNext() {
    if (!validateStep(state.step)) return;
    if (state.nextLoading || state.submitting) return;

    if (state.step === LAST_STEP || (state.step === 5 && depositMode && depositTotal() <= 0)) {
      await submitBooking();
      return;
    }

    setNextLoading(true);
    try {
      const next = state.step + 1;
      if (next === 6) {
        renderPayment();
      }
      if (next === 3) {
        initCalendarCursor();
        renderWeekdays();
        await loadMonth();
      }
      if (next === 4) {
        await loadSlots();
      }
      if (next === 5) {
        renderSummary();
      }
      setStep(next);
    } finally {
      setNextLoading(false);
    }
  }

  function goPrev() {
    if (state.step <= 1) return;
    setStep(state.step - 1);
  }

  async function submitBooking() {
    if (state.submitting) return;
    state.submitting = true;
    setNextLoading(true, cfg.i18n.submitting);

    try {
      const payload = {
        first_name: root.querySelector('[name="first_name"]').value.trim(),
        last_name: root.querySelector('[name="last_name"]').value.trim(),
        phone: root.querySelector('[name="phone"]').value.trim(),
        email: root.querySelector('[name="email"]').value.trim(),
        birth_date: root.querySelector('[name="birth_date"]').value.trim(),
        booking_for: root.querySelector('input[name="booking_for"]:checked').value,
        booking_for_name: root.querySelector('[name="booking_for_name"]').value.trim(),
        service_ids: state.selectedServices,
        staff_id: state.staffId || 0,
        date: state.date,
        time: state.time,
        terms_accepted: el.terms ? (el.terms.checked ? 1 : 0) : 1,
      };

      if (depositMode && depositTotal() > 0) {
        payload.payment_method = selectedPaymentMethod();
        payload.tip_amount = tipAmount();
        payload.return_url = window.location.href.split('#')[0].replace(/([?&])mrb_(payment|code)=[^&]*/g, '$1').replace(/[?&]$/, '');
      }

      const data = await api('/book', {
        method: 'POST',
        body: JSON.stringify(payload),
      });

      if (data.redirect) {
        setNextLoading(true, cfg.i18n.redirecting);
        window.location.assign(data.redirect);
        return;
      }

      state.result = data.booking;
      showSuccess(data);
    } catch (e) {
      fail(e.message || cfg.i18n.error);
      setNextLoading(false);
      state.submitting = false;
      if (e.data && e.data.code === 'login_required') {
        auth.loggedIn = false;
        applyAuthState();
        setStep(1);
        fail(e.message || cfg.i18n.loginRequired);
      }
    }
  }

  function showSuccess(data) {
    state.submitting = false;
    setNextLoading(false);
    try {
      root.dispatchEvent(new CustomEvent('mrb:booking-created', { bubbles: true, detail: data || {} }));
    } catch (e) {
      /* older browsers */
    }
    el.panels.forEach((p) => p.classList.remove('is-active'));
    el.success.hidden = false;
    el.success.classList.add('is-active');
    root.classList.add('is-success');
    root.querySelector('.mrb__footer').hidden = true;
    el.successTitle.textContent = data.message || cfg.texts.success;
    const b = data.booking || {};
    el.successBody.innerHTML = `
      <dl>
        <div><dt>کد رزرو</dt><dd><strong>${escapeHtml(b.code || '')}</strong></dd></div>
        <div><dt>مشتری</dt><dd>${escapeHtml(b.customer || '')}</dd></div>
        <div><dt>تاریخ</dt><dd>${escapeHtml((b.date_label || '').replace('\n', ' · '))}</dd></div>
        <div><dt>ساعت</dt><dd>${toFa(b.time || '')}</dd></div>
        <div><dt>خدمات</dt><dd>${escapeHtml((b.services || []).map((s) => s.name).join('، '))}</dd></div>
        ${b.paid_label ? `<div><dt>${escapeHtml(cfg.i18n.paid)}</dt><dd><strong>${escapeHtml(b.paid_label)}</strong></dd></div>` : ''}
      </dl>
    `;
  }

  /* ─── Return from the payment gateway ─── */

  function handlePaymentReturn() {
    const result = root.dataset.paymentResult || '';
    if (!result) return false;
    const code = root.dataset.paymentCode || '';
    if (result === 'success') {
      showSuccess({ message: cfg.i18n.paySuccess, booking: { code: code } });
      el.successBody.innerHTML = `<dl><div><dt>کد رزرو</dt><dd><strong>${escapeHtml(code)}</strong></dd></div></dl>`;
    } else {
      const msg =
        result === 'cancelled' ? cfg.i18n.payCancelled : result === 'invalid' ? cfg.i18n.payInvalid : cfg.i18n.payFailed;
      fail(msg);
      el.error.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    if (window.history && window.history.replaceState) {
      const clean = window.location.href.replace(/([?&])mrb_(payment|code)=[^&]*/g, '$1').replace(/[?&]$/, '');
      window.history.replaceState(null, '', clean);
    }
    return result === 'success';
  }

  function shiftMonth(delta) {
    state.calMonth += delta;
    if (state.calMonth > 12) {
      state.calMonth = 1;
      state.calYear++;
    }
    if (state.calMonth < 1) {
      state.calMonth = 12;
      state.calYear--;
    }
    loadMonth().catch((e) => fail(e.message));
  }

  /* ─── Customer account (OTP) integration ─── */

  const auth = {
    mode: (cfg.auth && cfg.auth.mode) || 'off',
    loggedIn: !!(cfg.auth && cfg.auth.logged_in),
    customer: (cfg.auth && cfg.auth.customer) || null,
  };
  const authEl = {
    wrap: root.querySelector('#mrb-auth'),
    chip: root.querySelector('#mrb-auth-chip'),
    chipName: root.querySelector('[data-auth-name]'),
    chipPhone: root.querySelector('[data-auth-phone]'),
    prompt: root.querySelector('#mrb-auth-prompt'),
    open: root.querySelector('#mrb-auth-open'),
    login: root.querySelector('#mrb-auth-login'),
    guest: root.querySelector('#mrb-auth-guest'),
    logout: root.querySelector('#mrb-auth-logout'),
    fields: root.querySelector('.mrb__fields'),
    phoneHint: root.querySelector('#mrb-hint-phone'),
  };
  let authOtp = null;
  let phoneHintDefault = authEl.phoneHint ? authEl.phoneHint.textContent : '';

  function prefillFromCustomer(c) {
    if (!c) return;
    const set = (name, value) => {
      const input = root.querySelector('[name="' + name + '"]');
      if (input && !input.value) input.value = value || '';
    };
    set('first_name', c.first_name);
    set('last_name', c.last_name);
    set('email', c.email);
    const phone = root.querySelector('[name="phone"]');
    if (phone) {
      phone.value = c.phone || '';
      phone.readOnly = true;
      phone.setAttribute('aria-readonly', 'true');
      phone.classList.add('is-locked');
      if (authEl.phoneHint) authEl.phoneHint.textContent = cfg.i18n.phoneLocked;
    }
    if (birthPicker && c.birth_date && !birthPicker.getValue()) birthPicker.setValue(c.birth_date);
    ['first_name', 'last_name', 'phone', 'email', 'birth_date'].forEach(clearFieldError);
  }

  function unlockPhone() {
    const phone = root.querySelector('[name="phone"]');
    if (phone) {
      phone.readOnly = false;
      phone.removeAttribute('aria-readonly');
      phone.classList.remove('is-locked');
      if (authEl.phoneHint) authEl.phoneHint.textContent = phoneHintDefault;
    }
  }

  function applyAuthState() {
    if (!authEl.wrap || auth.mode === 'off') return;
    const isRequired = auth.mode === 'required';

    if (auth.loggedIn && auth.customer) {
      authEl.chip.hidden = false;
      authEl.chipName.textContent = ((auth.customer.first_name || '') + ' ' + (auth.customer.last_name || '')).trim();
      const avatarEl = root.querySelector('[data-auth-avatar]');
      if (avatarEl) avatarEl.textContent = String(auth.customer.first_name || '').trim().charAt(0);
      authEl.chipPhone.textContent = toFa(auth.customer.phone || '');
      authEl.prompt.hidden = true;
      authEl.login.hidden = true;
      authEl.fields.hidden = false;
      el.next.hidden = false;
      prefillFromCustomer(auth.customer);
      return;
    }

    authEl.chip.hidden = true;
    unlockPhone();
    if (isRequired) {
      authEl.prompt.hidden = true;
      authEl.login.hidden = false;
      authEl.fields.hidden = true;
      el.next.hidden = true;
    } else {
      authEl.prompt.hidden = false;
      authEl.login.hidden = true;
      authEl.fields.hidden = false;
      el.next.hidden = false;
      if (authEl.open) authEl.open.setAttribute('aria-expanded', 'false');
    }
  }

  function onCustomerLoggedIn(customer, nonce) {
    if (nonce) cfg.nonce = nonce;
    auth.loggedIn = true;
    auth.customer = customer;
    applyAuthState();
    el.error.hidden = true;
    if (el.calHint) el.calHint.textContent = '';
    const first = root.querySelector('[name="first_name"]');
    const live = root.querySelector('#mrb-error');
    // Announce the prefill without treating it as an error.
    if (live) {
      live.hidden = false;
      live.classList.add('mrb__error--success');
      live.textContent = cfg.i18n.welcomeBack;
      setTimeout(() => {
        live.hidden = true;
        live.classList.remove('mrb__error--success');
        live.textContent = '';
      }, 4000);
    }
    if (first) first.focus({ preventScroll: true });
  }

  function setupAuth() {
    if (!authEl.wrap || auth.mode === 'off') return;

    const otpRoot = authEl.wrap.querySelector('[data-mrb-otp]');
    if (otpRoot && window.mrbOtpLogin) {
      authOtp = window.mrbOtpLogin.mount(otpRoot, {
        restUrl: cfg.restUrl,
        nonce: cfg.nonce,
        i18n: cfg.i18n,
        onNonce: (n) => {
          cfg.nonce = n;
        },
        onLoggedIn: onCustomerLoggedIn,
      });
    }

    if (authEl.open) {
      authEl.open.addEventListener('click', () => {
        const opening = authEl.login.hidden;
        authEl.login.hidden = !opening;
        authEl.open.setAttribute('aria-expanded', opening ? 'true' : 'false');
        if (opening && authOtp) {
          const typed = root.querySelector('[name="phone"]');
          if (typed && typed.value) authOtp.prefillPhone(typed.value);
          authOtp.focus();
        }
      });
    }

    if (authEl.guest) {
      authEl.guest.addEventListener('click', () => {
        authEl.login.hidden = true;
        if (authEl.open) authEl.open.setAttribute('aria-expanded', 'false');
        const first = root.querySelector('[name="first_name"]');
        if (first) first.focus();
      });
    }

    if (authEl.logout) {
      authEl.logout.addEventListener('click', async () => {
        authEl.logout.disabled = true;
        try {
          const data = await api('/auth/logout', { method: 'POST' });
          if (data.nonce) cfg.nonce = data.nonce;
        } catch (e) {
          // ignore — treat as logged out
        }
        authEl.logout.disabled = false;
        auth.loggedIn = false;
        auth.customer = null;
        if (authOtp) authOtp.reset();
        applyAuthState();
      });
    }

    applyAuthState();
  }

  // Init
  setupPersonal();
  setupAuth();
  setStep(1);
  handlePaymentReturn();
  initStepTwo().catch((e) => fail(e.message));

  el.next.addEventListener('click', () => goNext().catch((e) => fail(e.message)));
  el.prev.addEventListener('click', goPrev);
  if (el.staffSelect) {
    el.staffSelect.addEventListener('change', () => {
      onStaffChange().catch((e) => fail(e.message));
    });
  }
  root.querySelector('#mrb-prev-month').addEventListener('click', () => shiftMonth(-1));
  root.querySelector('#mrb-next-month').addEventListener('click', () => shiftMonth(1));

  if (el.editDate) {
    el.editDate.addEventListener('click', () => {
      if (el.calendarScroll) {
        el.calendarScroll.scrollTop = 0;
      }
      if (el.selectedDate) {
        el.selectedDate.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      }
    });
  }

  root.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter') return;
    const tag = (e.target && e.target.tagName) || '';
    if (tag === 'TEXTAREA' || tag === 'BUTTON') return;
    if (e.target.closest('[data-mrb-otp]')) return; // OTP forms handle Enter themselves.
    if (!e.target.closest('.mrb__fields')) return;
    e.preventDefault();
    goNext().catch((err) => fail(err.message));
  });
})();
