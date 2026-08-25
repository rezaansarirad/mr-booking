<?php
/**
 * Walk-in booking admin template.
 *
 * @package MRBooking
 * @var array $settings
 * @var list<object> $services
 * @var list<object> $staff
 * @var array<string, string> $statuses
 */

defined( 'ABSPATH' ) || exit;

$has_staff = ! empty( $staff );
?>
<div class="wrap mrb-admin mrb-walkin-page" dir="rtl">
	<header class="mrb-admin__header">
		<div>
			<p class="mrb-admin__eyebrow"><?php echo \MRBooking\Helpers::brand_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?></p>
			<h1><?php esc_html_e( 'مراجعه حضوری', 'mr-booking' ); ?></h1>
			<p class="mrb-phone-book-page__lead">
				<?php esc_html_e( 'برای مشتری‌ای که همین حالا در محل است. تاریخ و ساعت لازم نیست؛ این ثبت در لیست نوبت‌ها و حسابداری می‌نشیند اما هیچ ساعتی را در تقویم آنلاین اشغال نمی‌کند.', 'mr-booking' ); ?>
			</p>
		</div>
		<div class="mrb-header-actions">
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-appointments' ) ); ?>">
				<?php esc_html_e( 'لیست نوبت‌ها', 'mr-booking' ); ?>
			</a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-phone' ) ); ?>">
				<?php esc_html_e( 'رزرو تلفنی', 'mr-booking' ); ?>
			</a>
		</div>
	</header>

	<div id="mrb-walkin-app" class="mrb-phone-book mrb-walkin">
		<div class="mrb-phone-book__layout mrb-walkin__layout">
			<section class="mrb-panel mrb-phone-book__panel">
				<h2><?php esc_html_e( 'مشتری', 'mr-booking' ); ?></h2>

				<label class="mrb-field mrb-phone-book__search-wrap">
					<span class="mrb-field__label"><?php esc_html_e( 'جستجوی مشتری قبلی', 'mr-booking' ); ?></span>
					<input type="search" id="mrb-walkin-search" autocomplete="off" placeholder="<?php esc_attr_e( 'نام، نام خانوادگی یا شماره موبایل...', 'mr-booking' ); ?>" role="combobox" aria-expanded="false" aria-controls="mrb-walkin-results" aria-autocomplete="list" />
					<small class="mrb-field__hint"><?php esc_html_e( 'حداقل ۲ حرف بنویسید یا فیلدهای زیر را برای مشتری جدید پر کنید.', 'mr-booking' ); ?></small>
					<div id="mrb-walkin-results" class="mrb-autocomplete" role="listbox" hidden></div>
				</label>

				<input type="hidden" id="mrb-walkin-customer-id" value="" />

				<p class="mrb-walkin__customer-mode" id="mrb-walkin-customer-mode" aria-live="polite">
					<span class="mrb-badge"><?php esc_html_e( 'مشتری جدید', 'mr-booking' ); ?></span>
				</p>

				<div class="mrb-settings__grid mrb-phone-book__customer-fields">
					<label class="mrb-field" data-field="first_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام', 'mr-booking' ); ?> *</span>
						<input type="text" id="mrb-walkin-first-name" autocomplete="off" required aria-required="true" enterkeyhint="next" />
						<span class="mrb-field__error" id="mrb-err-first_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="last_name">
						<span class="mrb-field__label"><?php esc_html_e( 'نام خانوادگی', 'mr-booking' ); ?> *</span>
						<input type="text" id="mrb-walkin-last-name" autocomplete="off" required aria-required="true" enterkeyhint="next" />
						<span class="mrb-field__error" id="mrb-err-last_name" hidden></span>
					</label>
					<label class="mrb-field" data-field="phone">
						<span class="mrb-field__label"><?php esc_html_e( 'موبایل', 'mr-booking' ); ?> *</span>
						<input type="tel" id="mrb-walkin-phone" inputmode="numeric" placeholder="09xxxxxxxxx" required aria-required="true" enterkeyhint="next" aria-describedby="mrb-walkin-phone-hint" />
						<small class="mrb-field__hint" id="mrb-walkin-phone-hint"><?php esc_html_e( 'پروفایل مشتری با شماره موبایل شناخته می‌شود؛ مراجعات بعدی به همین پروفایل اضافه می‌شود.', 'mr-booking' ); ?></small>
						<span class="mrb-field__error" id="mrb-err-phone" hidden></span>
					</label>
					<label class="mrb-field" data-field="email">
						<span class="mrb-field__label"><?php esc_html_e( 'ایمیل', 'mr-booking' ); ?> <small><?php esc_html_e( '(اختیاری)', 'mr-booking' ); ?></small></span>
						<input type="email" id="mrb-walkin-email" autocomplete="off" enterkeyhint="next" />
						<span class="mrb-field__error" id="mrb-err-email" hidden></span>
					</label>
				</div>

				<?php if ( $has_staff ) : ?>
					<label class="mrb-field" style="margin-top:12px">
						<span class="mrb-field__label"><?php esc_html_e( 'پرسنل', 'mr-booking' ); ?> <small><?php esc_html_e( '(اختیاری)', 'mr-booking' ); ?></small></span>
						<select id="mrb-walkin-staff">
							<option value=""><?php esc_html_e( '— بدون پرسنل —', 'mr-booking' ); ?></option>
							<?php foreach ( $staff as $member ) : ?>
								<option value="<?php echo esc_attr( (string) $member->id ); ?>">
									<?php echo esc_html( \MRBooking\Staff\Staff_Repository::display_name( $member ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				<?php endif; ?>

				<label class="mrb-field" style="margin-top:12px">
					<span class="mrb-field__label"><?php esc_html_e( 'یادداشت داخلی', 'mr-booking' ); ?></span>
					<textarea id="mrb-walkin-notes" rows="2" placeholder="<?php esc_attr_e( 'مثلاً: پرداخت نقدی، درخواست خاص...', 'mr-booking' ); ?>"></textarea>
				</label>

				<label class="mrb-field">
					<span class="mrb-field__label"><?php esc_html_e( 'وضعیت', 'mr-booking' ); ?></span>
					<select id="mrb-walkin-status">
						<?php foreach ( $statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'completed' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<small class="mrb-field__hint"><?php esc_html_e( 'فقط «تأیید شده» و «انجام شده» در درآمد حسابداری محاسبه می‌شوند.', 'mr-booking' ); ?></small>
				</label>
			</section>

			<section class="mrb-panel mrb-phone-book__panel mrb-walkin__services-panel">
				<h2><?php esc_html_e( 'خدمت و مبلغ', 'mr-booking' ); ?></h2>

				<?php if ( empty( $services ) ) : ?>
					<p class="mrb-empty"><?php esc_html_e( 'ابتدا حداقل یک خدمت فعال تعریف کنید.', 'mr-booking' ); ?></p>
				<?php else : ?>
					<p class="mrb-field__hint mrb-walkin__services-hint">
						<?php
						echo esc_html(
							! empty( $settings['enable_multi_service'] )
								? __( 'یک یا چند خدمت را انتخاب کنید. مبلغ پیش‌فرض از تنظیمات خدمت می‌آید و برای همین مراجعه قابل تغییر است.', 'mr-booking' )
								: __( 'یک خدمت را انتخاب کنید. مبلغ پیش‌فرض از تنظیمات خدمت می‌آید و برای همین مراجعه قابل تغییر است.', 'mr-booking' )
						);
						?>
					</p>
					<div class="mrb-walkin__services" data-field="services" role="group" aria-labelledby="mrb-walkin-services-label">
						<span id="mrb-walkin-services-label" class="screen-reader-text"><?php esc_html_e( 'انتخاب خدمت', 'mr-booking' ); ?></span>
						<?php foreach ( $services as $svc ) : ?>
							<?php
							$svc_price  = \MRBooking\Services\Service_Repository::has_price( $svc ) ? (float) $svc->price : 0.0;
							$svc_id     = (int) $svc->id;
							$price_id   = 'mrb-walkin-price-' . $svc_id;
							$check_id   = 'mrb-walkin-svc-' . $svc_id;
							?>
							<div class="mrb-walkin-svc" data-service-id="<?php echo esc_attr( (string) $svc_id ); ?>" style="--mrb-service-accent: <?php echo esc_attr( (string) ( $svc->color ?: '#0f766e' ) ); ?>">
								<label class="mrb-walkin-svc__pick" for="<?php echo esc_attr( $check_id ); ?>">
									<input type="checkbox" class="mrb-walkin-svc__check" id="<?php echo esc_attr( $check_id ); ?>" value="<?php echo esc_attr( (string) $svc_id ); ?>" data-default-price="<?php echo esc_attr( (string) $svc_price ); ?>" />
									<span class="mrb-walkin-svc__name"><?php echo esc_html( $svc->name ); ?></span>
									<span class="mrb-walkin-svc__meta"><?php echo esc_html( \MRBooking\Helpers::format_duration( (int) $svc->duration ) ); ?></span>
								</label>
								<label class="mrb-walkin-svc__price" for="<?php echo esc_attr( $price_id ); ?>">
									<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: service name */ __( 'مبلغ %s', 'mr-booking' ), $svc->name ) ); ?></span>
									<input type="text" class="mrb-walkin-svc__amount" id="<?php echo esc_attr( $price_id ); ?>" inputmode="numeric" dir="ltr" data-mrb-money placeholder="0" value="<?php echo esc_attr( \MRBooking\Helpers::format_money_input( $svc_price ) ); ?>" disabled aria-describedby="mrb-err-services" />
									<em><?php esc_html_e( 'تومان', 'mr-booking' ); ?></em>
								</label>
							</div>
						<?php endforeach; ?>
					</div>
					<span class="mrb-field__error mrb-field__error--block" id="mrb-err-services" hidden></span>

					<div class="mrb-walkin__total" id="mrb-walkin-total" aria-live="polite">
						<span><?php esc_html_e( 'جمع مبلغ', 'mr-booking' ); ?></span>
						<strong data-total>—</strong>
					</div>
				<?php endif; ?>

				<p class="mrb-phone-book__error" id="mrb-walkin-error" role="alert" hidden></p>

				<div class="mrb-walkin__success" id="mrb-walkin-success" role="status" hidden>
					<div class="mrb-walkin__success-icon" aria-hidden="true">✓</div>
					<h3 id="mrb-walkin-success-title"></h3>
					<dl id="mrb-walkin-success-body"></dl>
					<div class="mrb-walkin__success-actions">
						<a class="button" id="mrb-walkin-view-link" href="#"><?php esc_html_e( 'مشاهده نوبت', 'mr-booking' ); ?></a>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mr-booking-accounting&preset=today' ) ); ?>"><?php esc_html_e( 'حسابداری امروز', 'mr-booking' ); ?></a>
						<button type="button" class="button button-primary" id="mrb-walkin-another"><?php esc_html_e( 'ثبت مراجعه بعدی', 'mr-booking' ); ?></button>
					</div>
				</div>

				<button type="button" class="button button-primary button-hero mrb-walkin__submit" id="mrb-walkin-submit" <?php disabled( empty( $services ) ); ?>>
					<?php esc_html_e( 'ثبت مراجعه حضوری', 'mr-booking' ); ?>
				</button>
			</section>
		</div>
	</div>
</div>
