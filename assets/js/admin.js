(function ($) {
  'use strict';

  function toFa(n) {
    return String(n).replace(/\d/g, function (d) {
      return '۰۱۲۳۴۵۶۷۸۹'[d];
    });
  }

  function formatDuration(minutes) {
    var i18n = (window.mrBookingAdmin && mrBookingAdmin.i18n) || {};
    minutes = parseInt(minutes, 10) || 0;
    if (minutes < 60) {
      return toFa(minutes) + ' ' + (i18n.minute || 'دقیقه');
    }
    var h = Math.floor(minutes / 60);
    var m = minutes % 60;
    if (!m) {
      return toFa(h) + ' ' + (i18n.hour || 'ساعت');
    }
    var tpl = i18n.hourAndMinute || '%1$s ساعت و %2$s دقیقه';
    return tpl.replace('%1$s', toFa(h)).replace('%2$s', toFa(m));
  }

  function syncDurationPreview() {
    var input = document.getElementById('mrb-service-duration');
    var preview = document.getElementById('mrb-duration-preview');
    if (!input || !preview) return;
    var mins = parseInt(input.value, 10) || 0;
    var label = formatDuration(mins);
    var tpl = (window.mrBookingAdmin && mrBookingAdmin.i18n.durationPreview) || 'این خدمت %s از وقت مشتری را می‌گیرد.';
    preview.innerHTML = tpl.replace('%s', '<strong>' + label + '</strong>');

    document.querySelectorAll('.mrb-duration-chip').forEach(function (chip) {
      chip.classList.toggle('is-active', parseInt(chip.dataset.duration, 10) === mins);
    });
  }

  $(document).on('click', '.mrb-duration-chip', function () {
    var mins = $(this).data('duration');
    $('#mrb-service-duration').val(mins);
    syncDurationPreview();
  });

  $(document).on('input change', '#mrb-service-duration', syncDurationPreview);
  syncDurationPreview();

  function syncPriceToggle() {
    var toggle = document.getElementById('mrb-has-price');
    var box = document.getElementById('mrb-price-amount');
    if (!toggle || !box) return;
    box.classList.toggle('is-hidden', !toggle.checked);
  }

  $(document).on('change', '#mrb-has-price', syncPriceToggle);
  syncPriceToggle();

  $(document).on('click', '.mrb-add-period', function () {
    var day = $(this).data('day');
    var prefix = $(this).closest('form').find('[name^="days["]').first().attr('name');
    var field = prefix ? 'days' : 'days';
    var row =
      '<div class="mrb-period-row">' +
      '<label class="mrb-period-field"><span>شروع</span>' +
      '<input type="time" name="' + field + '[' + day + '][start][]" value="09:00" /></label>' +
      '<span class="mrb-period-sep" aria-hidden="true">→</span>' +
      '<label class="mrb-period-field"><span>پایان</span>' +
      '<input type="time" name="' + field + '[' + day + '][end][]" value="13:00" /></label>' +
      '<button type="button" class="button mrb-remove-period" aria-label="حذف بازه">' +
      '<span class="dashicons dashicons-no-alt"></span></button>' +
      '</div>';
    $(this).before(row);
  });

  $(document).on('click', '.mrb-remove-period', function () {
    var $periods = $(this).closest('.mrb-periods');
    var $rows = $periods.find('.mrb-period-row');
    if ($rows.length <= 1) return;
    $(this).closest('.mrb-period-row').remove();
  });

  function syncDayClosed($checkbox) {
    var $block = $checkbox.closest('.mrb-day-block');
    if (!$block.length) return;
    var closed = $checkbox.prop('checked');
    $block.toggleClass('is-closed', closed).toggleClass('is-open', !closed);
  }

  $(document).on('change', '.mrb-day-closed', function () {
    syncDayClosed($(this));
  });

  $('.mrb-day-closed').each(function () {
    syncDayClosed($(this));
  });

  function escAttr(val) {
    return String(val || '')
      .replace(/&/g, '&amp;')
      .replace(/"/g, '&quot;')
      .replace(/</g, '&lt;');
  }

  function blockRowHtml(day, start, end, label) {
    return (
      '<div class="mrb-block-row">' +
      '<label class="mrb-period-field"><span>از</span>' +
      '<input type="time" name="blocks[' + day + '][start][]" value="' + escAttr(start) + '" /></label>' +
      '<span class="mrb-period-sep" aria-hidden="true">→</span>' +
      '<label class="mrb-period-field"><span>تا</span>' +
      '<input type="time" name="blocks[' + day + '][end][]" value="' + escAttr(end) + '" /></label>' +
      '<label class="mrb-period-field mrb-period-field--label"><span>عنوان</span>' +
      '<input type="text" name="blocks[' + day + '][label][]" value="' + escAttr(label) + '" placeholder="ناهار" /></label>' +
      '<button type="button" class="button mrb-remove-block" aria-label="حذف">' +
      '<span class="dashicons dashicons-no-alt"></span></button>' +
      '</div>'
    );
  }

  function readBlocksFromDay($daySection) {
    var blocks = [];
    $daySection.find('.mrb-block-row').each(function () {
      var $row = $(this);
      blocks.push({
        start: $row.find('input[name*="[start]"]').val() || '',
        end: $row.find('input[name*="[end]"]').val() || '',
        label: $row.find('input[name*="[label]"]').val() || '',
      });
    });
    return blocks;
  }

  function dayHasCompleteBlock(blocks) {
    return blocks.some(function (block) {
      return block.start && block.end;
    });
  }

  function setBlocksForDay($daySection, blocks) {
    var day = $daySection.data('day');
    var $container = $daySection.find('.mrb-block-rows');
    var $addBtn = $container.find('.mrb-add-block');

    $container.find('.mrb-block-row').remove();

    if (!blocks.length) {
      blocks = [{ start: '', end: '', label: '' }];
    }

    blocks.forEach(function (block) {
      $addBtn.before(blockRowHtml(day, block.start, block.end, block.label));
    });
  }

  function findSourceDaySection($week, preferredDay) {
    if (preferredDay !== undefined && preferredDay !== null && preferredDay !== '') {
      var $preferred = $week.find('.mrb-block-day[data-day="' + preferredDay + '"]');
      if ($preferred.length && dayHasCompleteBlock(readBlocksFromDay($preferred))) {
        return $preferred;
      }
    }

    var $found = null;
    $week.find('.mrb-block-day').each(function () {
      if ($found) {
        return;
      }
      var $day = $(this);
      if (dayHasCompleteBlock(readBlocksFromDay($day))) {
        $found = $day;
      }
    });
    return $found;
  }

  function applyBlocksToAllDays($trigger, preferredDay) {
    var i18n = (window.mrBookingAdmin && mrBookingAdmin.i18n) || {};
    var $week = $trigger.closest('.mrb-staff-schedule-box').find('.mrb-blocks-week');
    if (!$week.length) {
      return;
    }

    var $source = findSourceDaySection($week, preferredDay);
    if (!$source || !$source.length) {
      window.alert(i18n.applyBlocksNoSource || 'لطفاً ابتدا بازه زمانی (از و تا) را در حداقل یک روز وارد کنید.');
      return;
    }

    var blocks = readBlocksFromDay($source);
    $week.find('.mrb-block-day').each(function () {
      if ($(this).is($source)) {
        return;
      }
      setBlocksForDay($(this), blocks);
    });

    var $globalBtn = $week.siblings('.mrb-blocks-toolbar').find('.mrb-apply-blocks-all');
    $globalBtn.addClass('is-applied');
    window.setTimeout(function () {
      $globalBtn.removeClass('is-applied');
    }, 1800);
  }

  $(document).on('click', '.mrb-apply-blocks-all', function () {
    applyBlocksToAllDays($(this));
  });

  $(document).on('click', '.mrb-apply-blocks-day', function () {
    applyBlocksToAllDays($(this), $(this).data('day'));
  });

  $(document).on('click', '.mrb-add-block', function () {
    var day = $(this).data('day');
    $(this).before(blockRowHtml(day, '', '', ''));
  });

  $(document).on('click', '.mrb-remove-block', function () {
    var $rows = $(this).closest('.mrb-block-rows').find('.mrb-block-row');
    if ($rows.length <= 1) {
      $(this).closest('.mrb-block-row').find('input').val('');
      return;
    }
    $(this).closest('.mrb-block-row').remove();
  });

  function syncSpecialHours() {
    var type = document.getElementById('mrb-special-type');
    var box = document.getElementById('mrb-special-hours');
    if (!type || !box) return;
    box.classList.toggle('is-hidden', type.value !== 'special');
  }

  $(document).on('change', '#mrb-special-type', syncSpecialHours);
  syncSpecialHours();

  function initStaffNewDialog() {
    var dialog = document.getElementById('mrb-staff-new-dialog');
    var openBtn = document.getElementById('mrb-staff-new-open');
    if (!dialog || !openBtn || typeof dialog.showModal !== 'function') {
      return;
    }

    function openDialog() {
      dialog.showModal();
    }

    function closeDialog() {
      if (dialog.open) {
        dialog.close();
      }
    }

    function resetDialogForm() {
      var form = dialog.querySelector('form');
      if (!form) {
        return;
      }
      form.reset();
      $(dialog).find('.mrb-day-closed').each(function () {
        syncDayClosed($(this));
      });
    }

    openBtn.addEventListener('click', openDialog);

    dialog.querySelectorAll('[data-close-dialog]').forEach(function (btn) {
      btn.addEventListener('click', closeDialog);
    });

    dialog.addEventListener('click', function (event) {
      if (event.target === dialog) {
        closeDialog();
      }
    });

    dialog.addEventListener('close', resetDialogForm);

    var page = document.querySelector('.mrb-staff-page[data-open-staff-dialog="1"]');
    if (page) {
      openDialog();
      if (window.history && window.history.replaceState) {
        var url = new URL(window.location.href);
        url.searchParams.delete('new');
        window.history.replaceState({}, '', url.toString());
      }
    }
  }

  initStaffNewDialog();

	$(document).on('click', '.mrb-notif-var', function () {
		var $btn = $(this);
		var token = $btn.data('copy');
		if (!token || !navigator.clipboard) {
			return;
		}
		navigator.clipboard.writeText(token).then(function () {
			$btn.addClass('is-copied');
			setTimeout(function () {
				$btn.removeClass('is-copied');
			}, 1200);
		});
	});

	$(document).on('submit', '.mrb-appt-delete-form', function (e) {
		var msg = this.getAttribute('data-confirm');
		if (msg && !window.confirm(msg)) {
			e.preventDefault();
		}
	});
})(jQuery);
