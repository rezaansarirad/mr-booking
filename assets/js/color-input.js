(function () {
  'use strict';

  function normalizeHex(raw) {
    if (!raw) return null;
    var val = String(raw).trim().replace(/\s/g, '');
    if (val.charAt(0) !== '#') {
      val = '#' + val;
    }
    if (/^#[0-9a-fA-F]{3}$/.test(val)) {
      val =
        '#' +
        val.charAt(1) +
        val.charAt(1) +
        val.charAt(2) +
        val.charAt(2) +
        val.charAt(3) +
        val.charAt(3);
    }
    if (/^#[0-9a-fA-F]{6}$/.test(val)) {
      return val.toLowerCase();
    }
    return null;
  }

  function syncSwatch(control) {
    var picker = control.querySelector('.mrb-color-control__picker');
    if (!picker) return;
    var card = control.closest('.mrb-palette-card');
    if (!card) return;
    var swatch = card.querySelector('.mrb-palette-card__swatch');
    if (swatch) {
      swatch.style.backgroundColor = picker.value;
    }
  }

  function syncFromPicker(control) {
    var picker = control.querySelector('.mrb-color-control__picker');
    var hex = control.querySelector('.mrb-color-control__hex');
    if (!picker || !hex) return;
    hex.value = picker.value.toLowerCase();
    syncSwatch(control);
  }

  function syncFromHex(control) {
    var picker = control.querySelector('.mrb-color-control__picker');
    var hex = control.querySelector('.mrb-color-control__hex');
    if (!picker || !hex) return;
    var normalized = normalizeHex(hex.value);
    if (normalized) {
      picker.value = normalized;
      hex.value = normalized;
      hex.classList.remove('is-invalid');
      syncSwatch(control);
      return;
    }
    hex.classList.add('is-invalid');
  }

  function initControl(control) {
    if (control.dataset.mrbColorReady) return;
    control.dataset.mrbColorReady = '1';

    var picker = control.querySelector('.mrb-color-control__picker');
    var hex = control.querySelector('.mrb-color-control__hex');
    if (!picker || !hex) return;

    syncFromPicker(control);

    picker.addEventListener('input', function () {
      syncFromPicker(control);
      hex.classList.remove('is-invalid');
    });

    hex.addEventListener('input', function () {
      syncFromHex(control);
    });

    hex.addEventListener('blur', function () {
      var normalized = normalizeHex(hex.value);
      if (normalized) {
        picker.value = normalized;
        hex.value = normalized;
        hex.classList.remove('is-invalid');
        return;
      }
      syncFromPicker(control);
      hex.classList.remove('is-invalid');
    });

    hex.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        hex.blur();
      }
    });
  }

  function initAll(root) {
    (root || document).querySelectorAll('[data-mrb-color-control]').forEach(initControl);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initAll(document);
    });
  } else {
    initAll(document);
  }

  window.mrbInitColorInputs = initAll;
})();
