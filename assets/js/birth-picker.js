/**
 * Shared iOS-style birth date wheel picker.
 */
(function (global) {
  'use strict';

  const toFa = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const pad = (n) => String(n).padStart(2, '0');

  const defaultJalaliMonths = {
    1: 'فروردین',
    2: 'اردیبهشت',
    3: 'خرداد',
    4: 'تیر',
    5: 'مرداد',
    6: 'شهریور',
    7: 'مهر',
    8: 'آبان',
    9: 'آذر',
    10: 'دی',
    11: 'بهمن',
    12: 'اسفند',
  };

  function g2j(gy, gm, gd) {
    const g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    const gy2 = gm > 2 ? gy + 1 : gy;
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
    let jm;
    let jd;
    if (days < 186) {
      jm = 1 + Math.floor(days / 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + Math.floor((days - 186) / 30);
      jd = 1 + ((days - 186) % 30);
    }
    return [jy, jm, jd];
  }

  function j2g(jy, jm, jd) {
    jy += 1595;
    let days =
      -355668 +
      365 * jy +
      Math.floor(jy / 33) * 8 +
      Math.floor(((jy % 33) + 3) / 4) +
      jd +
      (jm < 7 ? (jm - 1) * 31 : (jm - 7) * 30 + 186);
    let gy = 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      gy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    let gd = days + 1;
    const sal_a = [
      0,
      31,
      (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0 ? 29 : 28,
      31,
      30,
      31,
      30,
      31,
      31,
      30,
      31,
      30,
      31,
    ];
    let gm = 1;
    for (; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
    return [gy, gm, gd];
  }

  function jalaliDaysInMonth(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    const [gy, gm, gd] = j2g(jy, 12, 30);
    const back = g2j(gy, gm, gd);
    return back[0] === jy && back[1] === 12 && back[2] === 30 ? 30 : 29;
  }

  function gregDaysInMonth(y, m) {
    return new Date(y, m, 0).getDate();
  }

  /**
   * @param {object} options
   * @returns {{setValue: Function, getValue: Function, clear: Function}|null}
   */
  function init(options) {
    options = options || {};
    const prefix = options.prefix || 'mrb-birth';
    const scope = options.root || document;
    const calendarMode = options.calendarMode || 'jalali';
    const isGreg = calendarMode === 'gregorian';
    const jalaliMonths = options.months || defaultJalaliMonths;
    const placeholder = options.placeholder || 'انتخاب تاریخ تولد';
    const onChange = typeof options.onChange === 'function' ? options.onChange : null;

    const trigger = scope.querySelector('#' + prefix + '-trigger');
    const picker = scope.querySelector('#' + prefix + '-picker');
    const display = scope.querySelector('#' + prefix + '-display');
    const hidden = scope.querySelector('#' + prefix + '-date');
    if (!trigger || !picker || !display || !hidden) return null;

    const yearEl = picker.querySelector('[data-wheel="year"]');
    const monthEl = picker.querySelector('[data-wheel="month"]');
    const dayEl = picker.querySelector('[data-wheel="day"]');
    const doneBtn = picker.querySelector('[data-wheel-done]');
    if (!yearEl || !monthEl || !dayEl || !doneBtn) return null;

    let pickerHome = picker.parentElement;

    const gregMonths = [
      'January',
      'February',
      'March',
      'April',
      'May',
      'June',
      'July',
      'August',
      'September',
      'October',
      'November',
      'December',
    ];

    const now = new Date();
    let curY;
    let curM;
    let curD;
    if (isGreg) {
      curY = now.getFullYear() - 25;
      curM = now.getMonth() + 1;
      curD = Math.min(now.getDate(), 28);
    } else {
      const j = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
      curY = j[0] - 25;
      curM = j[1];
      curD = Math.min(j[2], 28);
    }

    function daysIn(y, m) {
      return isGreg ? gregDaysInMonth(y, m) : jalaliDaysInMonth(y, m);
    }

    function wheelCenter(scroller) {
      return scroller.scrollTop + scroller.clientHeight / 2;
    }

    function nearestItem(scroller) {
      const center = wheelCenter(scroller);
      let best = null;
      let bestDist = Infinity;
      scroller.querySelectorAll('.mrb-wheel__item').forEach((node) => {
        const mid = node.offsetTop + node.offsetHeight / 2;
        const dist = Math.abs(mid - center);
        if (dist < bestDist) {
          bestDist = dist;
          best = node;
        }
      });
      return best;
    }

    function scrollItemToCenter(scroller, item, smooth) {
      if (!item) return;
      const top = item.offsetTop - (scroller.clientHeight - item.offsetHeight) / 2;
      scroller.scrollTo({ top: Math.max(0, top), behavior: smooth ? 'smooth' : 'auto' });
    }

    function buildCol(scroller, items, formatter) {
      scroller.innerHTML = '';
      const padEl = document.createElement('div');
      padEl.className = 'mrb-wheel__pad';
      scroller.appendChild(padEl.cloneNode(true));
      items.forEach((val, idx) => {
        const div = document.createElement('div');
        div.className = 'mrb-wheel__item';
        div.dataset.value = String(val);
        div.dataset.index = String(idx);
        div.textContent = formatter(val);
        scroller.appendChild(div);
      });
      scroller.appendChild(padEl.cloneNode(true));
    }

    function scrollToValue(scroller, value, smooth) {
      const item = scroller.querySelector('.mrb-wheel__item[data-value="' + value + '"]');
      scrollItemToCenter(scroller, item, smooth);
    }

    function selectedValue(scroller) {
      const item = nearestItem(scroller);
      return item ? item.dataset.value : null;
    }

    function syncActive(scroller) {
      const active = nearestItem(scroller);
      scroller.querySelectorAll('.mrb-wheel__item').forEach((node) => {
        node.classList.toggle('is-active', node === active);
      });
    }

    function formatFromParts(y, m, d) {
      if (isGreg) {
        return {
          label: pad(d) + ' / ' + pad(m) + ' / ' + y,
          store: y + '-' + pad(m) + '-' + pad(d),
        };
      }
      return {
        label: toFa(pad(d)) + ' ' + (jalaliMonths[m] || String(m)) + ' ' + toFa(y),
        store: y + '/' + pad(m) + '/' + pad(d),
      };
    }

    function applyParts(y, m, d) {
      const maxDay = daysIn(y, m);
      if (d > maxDay) d = maxDay;
      curY = y;
      curM = m;
      curD = d;
      const formatted = formatFromParts(y, m, d);
      display.textContent = formatted.label;
      display.classList.remove('is-placeholder');
      hidden.value = formatted.store;
      if (onChange) onChange(formatted.store);
    }

    function rebuildDays(keepDay) {
      const maxDay = daysIn(curY, curM);
      if (keepDay > maxDay) curD = maxDay;
      const days = [];
      for (let d = 1; d <= maxDay; d++) days.push(d);
      buildCol(dayEl, days, (d) => toFa(pad(d)));
      scrollToValue(dayEl, curD, false);
      syncActive(dayEl);
    }

    function yearRange() {
      if (isGreg) {
        const max = now.getFullYear();
        const min = max - 100;
        const years = [];
        for (let y = max; y >= min; y--) years.push(y);
        return years;
      }
      const jNow = g2j(now.getFullYear(), now.getMonth() + 1, now.getDate());
      const max = jNow[0];
      const min = max - 100;
      const years = [];
      for (let y = max; y >= min; y--) years.push(y);
      return years;
    }

    function openPicker() {
      const years = yearRange();
      buildCol(yearEl, years, (y) => toFa(y));
      const months = [];
      for (let m = 1; m <= 12; m++) months.push(m);
      buildCol(monthEl, months, (m) => (isGreg ? gregMonths[m - 1] : jalaliMonths[m] || String(m)));
      rebuildDays(curD);
      scrollToValue(yearEl, curY, false);
      scrollToValue(monthEl, curM, false);
      syncActive(yearEl);
      syncActive(monthEl);

      if (picker.parentElement !== document.body) {
        document.body.appendChild(picker);
      }

      picker.hidden = false;
      picker.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('mrb-wheel-open');
      requestAnimationFrame(() => picker.classList.add('is-open'));
    }

    function closePicker() {
      picker.classList.remove('is-open');
      document.documentElement.classList.remove('mrb-wheel-open');
      setTimeout(() => {
        picker.hidden = true;
        picker.setAttribute('aria-hidden', 'true');
        if (pickerHome && picker.parentElement === document.body) {
          pickerHome.appendChild(picker);
        }
      }, 280);
    }

    function snap(scroller) {
      const item = nearestItem(scroller);
      scrollItemToCenter(scroller, item, true);
      syncActive(scroller);
      return item ? item.dataset.value : null;
    }

    let yearTimer;
    let monthTimer;
    let dayTimer;

    yearEl.addEventListener(
      'scroll',
      () => {
        syncActive(yearEl);
        clearTimeout(yearTimer);
        yearTimer = setTimeout(() => {
          const v = snap(yearEl);
          if (v) {
            curY = Number(v);
            rebuildDays(curD);
          }
        }, 80);
      },
      { passive: true }
    );

    monthEl.addEventListener(
      'scroll',
      () => {
        syncActive(monthEl);
        clearTimeout(monthTimer);
        monthTimer = setTimeout(() => {
          const v = snap(monthEl);
          if (v) {
            curM = Number(v);
            rebuildDays(curD);
          }
        }, 80);
      },
      { passive: true }
    );

    dayEl.addEventListener(
      'scroll',
      () => {
        syncActive(dayEl);
        clearTimeout(dayTimer);
        dayTimer = setTimeout(() => {
          const v = snap(dayEl);
          if (v) curD = Number(v);
        }, 80);
      },
      { passive: true }
    );

    trigger.addEventListener('click', openPicker);
    picker.querySelectorAll('[data-wheel-close]').forEach((btn) => {
      btn.addEventListener('click', closePicker);
    });

    doneBtn.addEventListener('click', () => {
      curY = Number(selectedValue(yearEl) || curY);
      curM = Number(selectedValue(monthEl) || curM);
      curD = Number(selectedValue(dayEl) || curD);
      applyParts(curY, curM, curD);
      closePicker();
    });

    function setValue(raw) {
      if (!raw) {
        clear();
        return;
      }
      raw = String(raw).trim();
      const gMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
      const jMatch = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
      if (gMatch) {
        const gy = Number(gMatch[1]);
        const gm = Number(gMatch[2]);
        const gd = Number(gMatch[3]);
        if (isGreg) {
          applyParts(gy, gm, gd);
        } else {
          const j = g2j(gy, gm, gd);
          applyParts(j[0], j[1], j[2]);
        }
        return;
      }
      if (jMatch && !isGreg) {
        applyParts(Number(jMatch[1]), Number(jMatch[2]), Number(jMatch[3]));
        return;
      }
      clear();
    }

    function clear() {
      display.textContent = placeholder;
      display.classList.add('is-placeholder');
      hidden.value = '';
      if (onChange) onChange('');
    }

    function getValue() {
      return hidden.value.trim();
    }

    return { setValue, getValue, clear };
  }

  global.mrbBirthPicker = { init };
})(window);
