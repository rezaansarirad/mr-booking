/**
 * MR Booking — OTP login widget.
 *
 * window.mrbOtpLogin.mount(rootEl, {
 *   restUrl, nonce, i18n,
 *   onLoggedIn(customer, nonce) — called after a session is created,
 *   onNonce(nonce)              — nonce refresh hook,
 * })
 */
(function (global) {
  'use strict';

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

  function normalizePhone(raw) {
    let phone = toEnDigits(raw).replace(/\D/g, '');
    if (phone.startsWith('98') && phone.length === 12) phone = '0' + phone.slice(2);
    if (phone.startsWith('9') && phone.length === 10) phone = '0' + phone;
    return phone;
  }

  function maskPhone(phone) {
    return toFa(phone.slice(0, 4) + '•••' + phone.slice(-4));
  }

  function mount(root, opts) {
    if (!root || root.dataset.mrbOtpMounted) return null;
    root.dataset.mrbOtpMounted = '1';

    const i18n = opts.i18n || {};
    let nonce = opts.nonce || '';
    let busy = false;
    let phone = '';
    let token = '';
    let resendTimer = null;

    const steps = {
      phone: root.querySelector('[data-otp-step="phone"]'),
      code: root.querySelector('[data-otp-step="code"]'),
      profile: root.querySelector('[data-otp-step="profile"]'),
    };
    const inputs = {
      phone: root.querySelector('[name="otp_phone"]'),
      code: root.querySelector('[name="otp_code"]'),
      first: root.querySelector('[name="otp_first_name"]'),
      last: root.querySelector('[name="otp_last_name"]'),
    };
    const globalError = root.querySelector('[data-otp-error="global"]');
    const sentTo = root.querySelector('[data-otp-sent-to]');
    const resendBtn = root.querySelector('[data-otp-action="resend"]');
    const resendLabel = root.querySelector('[data-otp-resend-label]');
    const resendText = resendLabel ? resendLabel.textContent : '';

    async function api(path, body) {
      const url = String(opts.restUrl || '').replace(/\/$/, '') + path;
      const res = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        body: JSON.stringify(body || {}),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok || data.ok === false) {
        const err = new Error(data.error || data.message || i18n.error || 'Error');
        err.data = data;
        throw err;
      }
      return data;
    }

    function setNonce(n) {
      if (!n) return;
      nonce = n;
      if (typeof opts.onNonce === 'function') opts.onNonce(n);
    }

    function fieldError(name, message) {
      const wrap = root.querySelector('[data-field="otp_' + name + '"]');
      const err = root.querySelector('[data-otp-error="' + name + '"]');
      const input = wrap ? wrap.querySelector('input') : null;
      if (wrap) wrap.classList.toggle('is-invalid', !!message);
      if (err) {
        err.hidden = !message;
        err.textContent = message || '';
      }
      if (input) {
        if (message) input.setAttribute('aria-invalid', 'true');
        else input.removeAttribute('aria-invalid');
      }
    }

    function showGlobal(message) {
      if (!globalError) return;
      globalError.hidden = !message;
      globalError.textContent = message || '';
    }

    function clearErrors() {
      ['phone', 'code', 'first_name', 'last_name'].forEach((n) => fieldError(n, ''));
      showGlobal('');
    }

    function setBusy(on, step) {
      busy = on;
      const form = steps[step];
      const btn = form ? form.querySelector('.mrb-otp__submit') : null;
      if (btn) {
        btn.disabled = on;
        btn.classList.toggle('is-loading', on);
        btn.setAttribute('aria-busy', on ? 'true' : 'false');
      }
    }

    function goto(step) {
      root.dataset.step = step;
      Object.keys(steps).forEach((k) => {
        if (steps[k]) steps[k].hidden = k !== step;
      });
      clearErrors();
      const focusTarget =
        step === 'phone' ? inputs.phone : step === 'code' ? inputs.code : inputs.first;
      if (focusTarget) {
        requestAnimationFrame(() => focusTarget.focus({ preventScroll: true }));
      }
    }

    function startResendCountdown(seconds) {
      clearInterval(resendTimer);
      let left = Math.max(0, Number(seconds) || 0);
      const tick = () => {
        if (!resendBtn) return;
        if (left <= 0) {
          resendBtn.disabled = false;
          if (resendLabel) resendLabel.textContent = resendText;
          clearInterval(resendTimer);
          return;
        }
        resendBtn.disabled = true;
        if (resendLabel) {
          resendLabel.textContent = (i18n.resendIn || 'ارسال مجدد تا %s ثانیه').replace('%s', toFa(left));
        }
        left -= 1;
      };
      tick();
      resendTimer = setInterval(tick, 1000);
    }

    async function requestCode(isResend) {
      if (busy) return;
      clearErrors();
      const value = normalizePhone(inputs.phone.value);
      inputs.phone.value = value;
      if (!/^09\d{9}$/.test(value)) {
        fieldError('phone', i18n.invalidPhone || '');
        inputs.phone.focus();
        return;
      }
      phone = value;
      setBusy(true, isResend ? 'code' : 'phone');
      try {
        const data = await api('/auth/request-otp', { phone });
        if (sentTo) {
          sentTo.textContent = '';
          const parts = (i18n.sentTo || 'کد به %s پیامک شد.').split('%s');
          sentTo.append(document.createTextNode(parts[0] || ''));
          const num = document.createElement('bdi');
          num.dir = 'ltr';
          num.className = 'mrb-phone';
          num.textContent = maskPhone(phone);
          sentTo.append(num, document.createTextNode(parts[1] || ''));
        }
        if (!isResend) goto('code');
        inputs.code.value = '';
        startResendCountdown(data.resend_after || 60);
        if (isResend) showGlobal('');
      } catch (e) {
        const retry = e.data && e.data.retry_after;
        if (retry && isResend) {
          startResendCountdown(retry);
        }
        if (isResend) showGlobal(e.message);
        else fieldError('phone', e.message);
      } finally {
        setBusy(false, isResend ? 'code' : 'phone');
      }
    }

    function finish(data) {
      setNonce(data.nonce);
      if (typeof opts.onLoggedIn === 'function') opts.onLoggedIn(data.customer || null, data.nonce);
    }

    async function verifyCode() {
      if (busy) return;
      clearErrors();
      const code = toEnDigits(inputs.code.value).replace(/\D/g, '');
      inputs.code.value = code;
      if (code.length < 4) {
        fieldError('code', i18n.invalidCode || '');
        inputs.code.focus();
        return;
      }
      setBusy(true, 'code');
      try {
        const data = await api('/auth/verify-otp', { phone, code });
        if (data.needs_profile) {
          token = data.token || '';
          if (data.customer) {
            inputs.first.value = data.customer.first_name || '';
            inputs.last.value = data.customer.last_name || '';
          }
          goto('profile');
          return;
        }
        finish(data);
      } catch (e) {
        fieldError('code', e.message);
        inputs.code.select();
      } finally {
        setBusy(false, 'code');
      }
    }

    async function completeProfile() {
      if (busy) return;
      clearErrors();
      const first = inputs.first.value.trim();
      const last = inputs.last.value.trim();
      let ok = true;
      if (first.length < 2) {
        fieldError('first_name', i18n.invalidName || '');
        ok = false;
      }
      if (last.length < 2) {
        fieldError('last_name', i18n.invalidName || '');
        ok = false;
      }
      if (!ok) {
        (first.length < 2 ? inputs.first : inputs.last).focus();
        return;
      }
      setBusy(true, 'profile');
      try {
        const data = await api('/auth/complete-profile', {
          phone,
          token,
          first_name: first,
          last_name: last,
        });
        finish(data);
      } catch (e) {
        showGlobal(e.message);
        if (e.data && /403/.test(String(e.data.status || ''))) goto('phone');
      } finally {
        setBusy(false, 'profile');
      }
    }

    // Wire up.
    steps.phone.addEventListener('submit', (e) => {
      e.preventDefault();
      requestCode(false);
    });
    steps.code.addEventListener('submit', (e) => {
      e.preventDefault();
      verifyCode();
    });
    steps.profile.addEventListener('submit', (e) => {
      e.preventDefault();
      completeProfile();
    });
    if (resendBtn) resendBtn.addEventListener('click', () => requestCode(true));
    const changeBtn = root.querySelector('[data-otp-action="change-phone"]');
    if (changeBtn) {
      changeBtn.addEventListener('click', () => {
        clearInterval(resendTimer);
        goto('phone');
      });
    }

    inputs.phone.addEventListener('input', () => {
      const cursor = inputs.phone.selectionStart;
      const before = inputs.phone.value;
      inputs.phone.value = toEnDigits(before).replace(/[^\d]/g, '').slice(0, 11);
      if (cursor != null) {
        const pos = Math.max(0, cursor - (before.length - inputs.phone.value.length));
        inputs.phone.setSelectionRange(pos, pos);
      }
      fieldError('phone', '');
    });
    inputs.code.addEventListener('input', () => {
      inputs.code.value = toEnDigits(inputs.code.value).replace(/\D/g, '').slice(0, 6);
      fieldError('code', '');
      if (inputs.code.value.length === 5) verifyCode();
    });
    [inputs.first, inputs.last].forEach((inp) => {
      inp.addEventListener('input', () => fieldError(inp === inputs.first ? 'first_name' : 'last_name', ''));
    });

    return {
      reset: () => {
        clearInterval(resendTimer);
        phone = '';
        token = '';
        inputs.phone.value = '';
        inputs.code.value = '';
        goto('phone');
      },
      prefillPhone: (value) => {
        inputs.phone.value = normalizePhone(value || '');
      },
      setNonce,
      focus: () => inputs.phone.focus(),
    };
  }

  global.mrbOtpLogin = { mount, normalizePhone };
})(window);
