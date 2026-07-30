(function () {
  'use strict';

  var secondToggle = document.getElementById('mrb-second-period');
  var secondHours = document.getElementById('mrb-second-hours');
  if (secondToggle && secondHours) {
    var sync = function () {
      secondHours.style.display = secondToggle.checked ? '' : 'none';
    };
    secondToggle.addEventListener('change', sync);
    sync();
  }

  function syncEmpty(card) {
    var chips = card.querySelector('.mrb-setup__chips');
    var empty = card.querySelector('.mrb-setup__chips-empty');
    if (!chips || !empty) return;
    empty.classList.toggle('is-hidden', chips.children.length > 0);
  }

  function syncChecklistOptions(card) {
    var chips = card.querySelector('.mrb-setup__chips');
    if (!chips) return;
    var used = {};
    chips.querySelectorAll('[data-id]').forEach(function (chip) {
      used[chip.getAttribute('data-id')] = true;
    });
    card.querySelectorAll('.mrb-setup__service-check').forEach(function (input) {
      var id = input.value;
      var taken = !!used[id];
      input.disabled = taken;
      if (taken) input.checked = false;
      var label = input.closest('.mrb-setup__service-option');
      if (label) label.classList.toggle('is-used', taken);
    });
  }

  function addChip(card, id, name) {
    var chips = card.querySelector('.mrb-setup__chips');
    if (!chips || !id) return false;
    if (chips.querySelector('[data-id="' + id + '"]')) return false;

    var inputName = chips.getAttribute('data-input-name') || '';
    var chip = document.createElement('span');
    chip.className = 'mrb-setup__chip';
    chip.setAttribute('data-id', String(id));
    chip.appendChild(document.createTextNode(name + ' '));

    var remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'mrb-setup__chip-remove';
    remove.setAttribute('aria-label', 'حذف');
    remove.innerHTML = '&times;';
    chip.appendChild(remove);

    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.name = inputName;
    hidden.value = String(id);
    chip.appendChild(hidden);

    chips.appendChild(chip);
    return true;
  }

  function addChecks(card, checks) {
    var added = 0;
    checks.forEach(function (input) {
      if (!input || input.disabled || !input.value) return;
      var name = input.getAttribute('data-name') || input.value;
      if (addChip(card, input.value, name)) {
        added += 1;
        input.checked = false;
      }
    });
    if (added) {
      syncEmpty(card);
      syncChecklistOptions(card);
    }
    return added;
  }

  function bindCard(card) {
    var addBtn = card.querySelector('.mrb-setup__add-service-btn');
    var addAllBtn = card.querySelector('.mrb-setup__add-all-btn');
    if (!addBtn || addBtn.dataset.bound === '1') return;
    addBtn.dataset.bound = '1';

    addBtn.addEventListener('click', function () {
      var selected = card.querySelectorAll('.mrb-setup__service-check:checked:not(:disabled)');
      addChecks(card, Array.prototype.slice.call(selected));
    });

    if (addAllBtn) {
      addAllBtn.addEventListener('click', function () {
        var available = card.querySelectorAll('.mrb-setup__service-check:not(:disabled)');
        addChecks(card, Array.prototype.slice.call(available));
      });
    }

    card.addEventListener('click', function (e) {
      var btn = e.target.closest('.mrb-setup__chip-remove');
      if (!btn) return;
      var chip = btn.closest('.mrb-setup__chip');
      if (chip) chip.remove();
      syncEmpty(card);
      syncChecklistOptions(card);
    });

    syncEmpty(card);
    syncChecklistOptions(card);
  }

  document.querySelectorAll('.mrb-setup__staff-card').forEach(bindCard);

  var addStaff = document.getElementById('mrb-add-staff');
  var staffRows = document.getElementById('mrb-staff-rows');
  var staffTpl = document.getElementById('mrb-staff-row-template');
  if (addStaff && staffRows && staffTpl) {
    addStaff.addEventListener('click', function () {
      var next = Number(staffRows.getAttribute('data-next-index') || '0') + 1;
      staffRows.setAttribute('data-next-index', String(next));
      var html = staffTpl.innerHTML.replace(/__INDEX__/g, String(next));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var card = wrap.firstElementChild;
      if (card) {
        staffRows.appendChild(card);
        bindCard(card);
      }
    });
  }

  var addService = document.getElementById('mrb-add-service');
  var serviceRows = document.getElementById('mrb-service-rows');
  var serviceTpl = document.getElementById('mrb-service-row-template');
  var showPrices = document.getElementById('mrb-show-prices');

  function syncPriceFields() {
    var on = showPrices && showPrices.checked;
    document.querySelectorAll('.mrb-price-field-wrap').forEach(function (el) {
      el.classList.toggle('is-hidden', !on);
    });
  }

  function renumberServiceCards() {
    if (!serviceRows) return;
    var cards = serviceRows.querySelectorAll('.mrb-setup__service-card');
    cards.forEach(function (card, i) {
      var n = i + 1;
      card.setAttribute('data-index', String(n));
      var title = card.querySelector('.mrb-setup__service-card__title');
      if (title) title.textContent = 'خدمت ' + n;
      var remove = card.querySelector('.mrb-remove-service');
      if (remove) remove.classList.toggle('is-hidden', cards.length <= 1);
    });
    serviceRows.setAttribute('data-next-index', String(cards.length));
  }

  if (showPrices) {
    showPrices.addEventListener('change', syncPriceFields);
    syncPriceFields();
  }

  if (addService && serviceRows && serviceTpl) {
    addService.addEventListener('click', function () {
      var next = Number(serviceRows.getAttribute('data-next-index') || '0') + 1;
      serviceRows.setAttribute('data-next-index', String(next));
      var html = serviceTpl.innerHTML.replace(/__INDEX__/g, String(next));
      var wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      var card = wrap.firstElementChild;
      if (!card) return;
      if (showPrices && showPrices.checked) {
        var priceWrap = card.querySelector('.mrb-price-field-wrap');
        if (priceWrap) priceWrap.classList.remove('is-hidden');
      }
      serviceRows.appendChild(card);
      renumberServiceCards();
    });

    serviceRows.addEventListener('click', function (e) {
      var btn = e.target.closest('.mrb-remove-service');
      if (!btn) return;
      var card = btn.closest('.mrb-setup__service-card');
      if (!card || serviceRows.querySelectorAll('.mrb-setup__service-card').length <= 1) return;
      card.remove();
      renumberServiceCards();
    });

    renumberServiceCards();
  }
})();
