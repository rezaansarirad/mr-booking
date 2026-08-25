/**
 * MR Booking — money input formatter.
 *
 * Attach to any <input data-mrb-money>: digits are grouped in threes with
 * commas while typing, Persian/Arabic digits are normalised, and the caret
 * stays where the user expects. Read the numeric value with
 * window.mrbMoneyInput.parse(input.value).
 */
(function (global) {
  'use strict';

  var FA = /[۰-۹]/g;
  var AR = /[٠-٩]/g;

  function toEnDigits(value) {
    return String(value == null ? '' : value)
      .replace(FA, function (d) { return String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)); })
      .replace(AR, function (d) { return String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)); });
  }

  function digitsOnly(value) {
    return toEnDigits(value).replace(/[^\d]/g, '').replace(/^0+(?=\d)/, '');
  }

  function group(digits) {
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
  }

  function format(value) {
    var digits = digitsOnly(value);
    return digits ? group(digits) : '';
  }

  function parse(value) {
    var digits = digitsOnly(value);
    return digits ? Number(digits) : 0;
  }

  function reformat(input) {
    var before = input.value;
    var caret = input.selectionStart;
    var digitsBeforeCaret = caret == null ? null : digitsOnly(before.slice(0, caret)).length;

    var next = format(before);
    if (next === before) return;
    input.value = next;

    if (digitsBeforeCaret == null || document.activeElement !== input) return;

    // Place caret after the same number of digits it was after before formatting.
    var pos = 0;
    var seen = 0;
    while (pos < next.length && seen < digitsBeforeCaret) {
      if (/\d/.test(next.charAt(pos))) seen++;
      pos++;
    }
    try {
      input.setSelectionRange(pos, pos);
    } catch (e) {
      /* unsupported input type — ignore */
    }
  }

  function attach(input) {
    if (!input || input.dataset.mrbMoneyReady === '1') return;
    input.dataset.mrbMoneyReady = '1';
    if (input.type !== 'text') input.type = 'text';
    input.setAttribute('inputmode', 'numeric');
    input.setAttribute('autocomplete', 'off');
    input.setAttribute('dir', 'ltr');
    if (!input.getAttribute('pattern')) input.setAttribute('pattern', '[0-9۰-۹,٬\\s]*');

    input.addEventListener('input', function () { reformat(input); });
    input.addEventListener('blur', function () { reformat(input); });
    input.addEventListener('paste', function () {
      // Let the paste land, then normalise.
      setTimeout(function () { reformat(input); }, 0);
    });
    reformat(input);
  }

  function init(scope) {
    (scope || document).querySelectorAll('input[data-mrb-money]').forEach(attach);
  }

  global.mrbMoneyInput = {
    attach: attach,
    init: init,
    format: format,
    parse: parse,
    toEnDigits: toEnDigits,
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { init(document); });
  } else {
    init(document);
  }
})(window);
