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
    var on = toggle.checked;
    box.classList.toggle('is-hidden', !on);
    var switchUi = toggle.closest('.mrb-switch');
    if (switchUi) {
      switchUi.classList.toggle('is-on', on);
    }
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

  $(document).on('change', '.mrb-day-closed', function (e) {
    e.stopPropagation();
    syncDayClosed($(this));
  });

  $(document).on('click', '.mrb-day-closed-toggle', function (e) {
    e.stopPropagation();
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

	function setThemeActive(theme) {
		$('.mrb-theme-card').removeClass('is-active');
		$('.mrb-theme-card[data-theme="' + theme + '"]').addClass('is-active');
		$('#mrb-form-theme').val(theme);
	}

	function applyColorPreset(preset) {
		if (!preset) return;

		function normalizeHex(val) {
			if (val === null || val === undefined) return val;
			var s = String(val).trim();
			if (s.charAt(0) !== '#') s = '#' + s;
			if (/^#[0-9a-fA-F]{6}$/.test(s)) return s.toLowerCase();
			return s;
		}

		function settingField(key) {
			return document.querySelector(
				'[name="settings[' + key + ']"]'
			);
		}

		Object.keys(preset).forEach(function (key) {
			var val = preset[key];
			var field = settingField(key);
			if (!field) return;

			if (field.type === 'checkbox') {
				field.checked = !!parseInt(String(val), 10);
				return;
			}

			if (field.type === 'color') {
				val = normalizeHex(val);
			}

			field.value = val;

			if (field.type === 'color') {
				field.dispatchEvent(new Event('input', { bubbles: true }));
			}
		});

		if (window.mrbInitColorInputs) {
			window.mrbInitColorInputs(document);
		}

		document.querySelectorAll('.mrb-palette-card').forEach(function (card) {
			var picker = card.querySelector('input[type="color"]');
			var swatch = card.querySelector('.mrb-palette-card__swatch');
			if (picker && swatch) {
				swatch.style.backgroundColor = picker.value;
			}
		});
	}

	function initThemePresetSync() {
		var applied = new URL(window.location.href).searchParams.get('theme_applied');
		if (!applied) return;
		var presets = (window.mrBookingAdmin && mrBookingAdmin.colorPresets) || {};
		if (!presets[applied]) return;
		applyColorPreset(presets[applied]);
		setThemeActive(applied);
	}

	initThemePresetSync();

	function showThemePreviewToast() {
		var msg =
			(window.mrBookingAdmin &&
				mrBookingAdmin.i18n &&
				mrBookingAdmin.i18n.themePreviewApplied) ||
			'';
		if (!msg) return;
		var $toast = $('<div class="mrb-settings__toast mrb-settings__toast--inline" role="status">' +
			'<span class="dashicons dashicons-yes-alt"></span>' + msg + '</div>');
		$('.mrb-settings__theme-picker').first().append($toast);
		setTimeout(function () {
			$toast.fadeOut(200, function () {
				$(this).remove();
			});
		}, 4200);
	}

	$(document).on('click', '.mrb-theme-card__preview-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var theme = $(this).data('theme');
		var presets = (window.mrBookingAdmin && mrBookingAdmin.colorPresets) || {};
		if (!presets[theme]) return;
		applyColorPreset(presets[theme]);
		setThemeActive(theme);
		showThemePreviewToast();
	});

	$(document).on('click', '.mrb-theme-card:not(.mrb-theme-card--custom)', function (e) {
		if ($(e.target).closest('a, button').length) return;
		var theme = $(this).data('theme');
		var presets = (window.mrBookingAdmin && mrBookingAdmin.colorPresets) || {};
		if (!presets[theme]) return;
		applyColorPreset(presets[theme]);
		setThemeActive(theme);
	});

	$(document).on('input change', '.mrb-settings__palette input[name^="settings[color_"], .mrb-settings__palette input[name="settings[bg_gradient_primary_mix]"], .mrb-settings__palette input[name="settings[bg_gradient_accent_mix]"]', function () {
		if (!$('#mrb-form-theme').length) return;
		setThemeActive('custom');
	});

	/* ─── New booking notifications (admin poll) ─── */
	(function initBookingNotifications() {
		var cfg = window.mrBookingAdmin || {};
		if (!cfg.pollBookings || !cfg.ajaxUrl) return;

		var STORAGE_KEY = 'mrbLastBookingId';
		var MUTE_KEY = 'mrbBookingSoundMuted';
		var stack = null;

		function ensureStack() {
			if (stack) return stack;
			stack = document.createElement('div');
			stack.className = 'mrb-admin-notify-stack';
			stack.setAttribute('aria-live', 'polite');
			document.body.appendChild(stack);
			return stack;
		}

		function getSinceId() {
			var latest = parseInt(cfg.latestBookingId, 10) || 0;
			var stored = parseInt(sessionStorage.getItem(STORAGE_KEY) || '', 10);
			if (!stored || stored > latest) {
				sessionStorage.setItem(STORAGE_KEY, String(latest));
				return latest;
			}
			return stored;
		}

		function setSinceId(id) {
			sessionStorage.setItem(STORAGE_KEY, String(id));
		}

		function playNotifySound() {
			if (localStorage.getItem(MUTE_KEY) === '1') return;
			try {
				var AudioCtx = window.AudioContext || window.webkitAudioContext;
				if (!AudioCtx) return;
				var ctx = new AudioCtx();
				var t = ctx.currentTime;
				[987.77, 783.99].forEach(function (freq, i) {
					var osc = ctx.createOscillator();
					var gain = ctx.createGain();
					osc.type = 'sine';
					osc.frequency.setValueAtTime(freq, t + i * 0.1);
					gain.gain.setValueAtTime(0.0001, t + i * 0.1);
					gain.gain.exponentialRampToValueAtTime(0.22, t + i * 0.1 + 0.015);
					gain.gain.exponentialRampToValueAtTime(0.0001, t + i * 0.1 + 0.14);
					osc.connect(gain);
					gain.connect(ctx.destination);
					osc.start(t + i * 0.1);
					osc.stop(t + i * 0.1 + 0.15);
				});
				window.setTimeout(function () {
					ctx.close();
				}, 500);
			} catch (e) {
				/* ignore */
			}
		}

		function tpl(text, vars) {
			var out = text || '';
			Object.keys(vars || {}).forEach(function (key) {
				out = out.replace('%' + key + '$s', vars[key]);
			});
			return out;
		}

		function showBookingToast(booking) {
			var i18n = cfg.i18n || {};
			var el = document.createElement('div');
			el.className = 'mrb-admin-notify';
			el.innerHTML =
				'<div class="mrb-admin-notify__icon" aria-hidden="true"></div>' +
				'<div class="mrb-admin-notify__body">' +
				'<strong>' + (i18n.newBookingTitle || 'رزرو جدید') + '</strong>' +
				'<p>' +
				tpl(i18n.newBookingBody || '%1$s — %2$s', {
					1: booking.name || '',
					2: booking.datetime || '',
				}) +
				'</p>' +
				'<span class="mrb-admin-notify__meta">' +
				(booking.code || '') +
				' · ' +
				(booking.status_label || '') +
				'</span>' +
				'</div>' +
				'<div class="mrb-admin-notify__actions">' +
				'<a class="button button-primary button-small" href="' +
				(booking.url || cfg.appointmentsUrl) +
				'">' +
				(i18n.newBookingReload || 'مشاهده') +
				'</a>' +
				'<button type="button" class="button button-small mrb-admin-notify__dismiss">' +
				(i18n.newBookingDismiss || 'بستن') +
				'</button>' +
				'</div>';

			el.querySelector('.mrb-admin-notify__dismiss').addEventListener('click', function () {
				el.classList.add('is-hiding');
				window.setTimeout(function () {
					el.remove();
				}, 220);
			});

			ensureStack().appendChild(el);
			window.requestAnimationFrame(function () {
				el.classList.add('is-visible');
			});

			window.setTimeout(function () {
				if (el.parentNode) {
					el.classList.add('is-hiding');
					window.setTimeout(function () {
						el.remove();
					}, 220);
				}
			}, 12000);
		}

		function poll() {
			var sinceId = getSinceId();
			$.post(cfg.ajaxUrl, {
				action: 'mr_booking_admin',
				nonce: cfg.nonce,
				mr_action: 'check_new_bookings',
				since_id: sinceId,
			})
				.done(function (res) {
					if (!res || !res.success || !res.data) return;
					var data = res.data;
					var bookings = data.bookings || [];
					if (!bookings.length) {
						if (data.latest_id) {
							setSinceId(Math.max(sinceId, parseInt(data.latest_id, 10) || 0));
						}
						return;
					}

					playNotifySound();
					bookings.forEach(function (booking) {
						showBookingToast(booking);
					});

					if (data.latest_id) {
						setSinceId(parseInt(data.latest_id, 10));
					}

					document.querySelectorAll('.mrb-appt-table tbody tr, .mrb-table--recent tbody tr').forEach(function (row) {
						row.classList.remove('is-new-flash');
					});
					bookings.forEach(function (booking) {
						var row = document.querySelector('tr[data-booking-id="' + booking.id + '"]');
						if (row) row.classList.add('is-new-flash');
					});
				})
				.fail(function () {
					/* silent */
				});
		}

		getSinceId();
		window.setInterval(poll, parseInt(cfg.pollIntervalMs, 10) || 30000);
		window.setTimeout(poll, 4000);
	})();

  var serviceEditor = document.getElementById('mrb-service-editor');
  if (serviceEditor && /[?&]new=1(?:&|$)/.test(window.location.search)) {
    window.requestAnimationFrame(function () {
      serviceEditor.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  }
})(jQuery);
