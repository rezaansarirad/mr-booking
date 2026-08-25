<?php
/**
 * Notification orchestration.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Notifications;

use MRBooking\Bookings\Booking_Repository;
use MRBooking\Helpers;
use MRBooking\Notifications\Email\Email_Sender;
use MRBooking\Notifications\SMS\SMS_Manager;
use MRBooking\Settings\Settings;
use MRBooking\Staff\Staff_Repository;

defined( 'ABSPATH' ) || exit;

final class Notification_Service {

	/** @var array<string, true> */
	private static array $sent_in_request = array();

	/** @var array<string, mixed>|null */
	private static ?array $last_notify_result = null;

	public function hooks(): void {
		add_action( 'mr_booking_booking_created', array( $this, 'on_created' ), 10, 1 );
		add_action( 'mr_booking_booking_status_changed', array( $this, 'on_status' ), 10, 3 );
	}

	public function on_created( int $booking_id ): void {
		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			return;
		}

		/**
		 * Walk-ins are entered by staff for a customer standing at the desk, so
		 * neither the admin alert nor the customer receipt adds value by default.
		 *
		 * @param bool   $notify  Whether to send notifications for this walk-in.
		 * @param object $booking Booking row.
		 */
		if ( Booking_Repository::is_walkin( $booking ) && ! apply_filters( 'mr_booking_notify_walkin', false, $booking ) ) {
			return;
		}

		// Always alert admin / registered recipients about new bookings.
		$this->notify_admins_new_booking( $booking );

		// Customer: pending receipt vs instant confirmation.
		if ( 'confirmed' === (string) $booking->status ) {
			$this->notify_customer( $booking_id, 'confirmed' );
		} else {
			$this->notify_customer( $booking_id, 'created' );
		}
	}

	public function on_status( int $booking_id, string $status, string $old_status = '' ): void {
		$map = array(
			'confirmed' => 'confirmed',
			'cancelled' => 'cancelled',
			'rejected'  => 'cancelled',
		);
		if ( ! isset( $map[ $status ] ) ) {
			return;
		}

		$event = $map[ $status ];
		if ( 'confirmed' === $event && 'confirmed' === $old_status ) {
			return;
		}

		$this->notify_customer( $booking_id, $event );
	}

	/**
	 * Backward-compatible wrapper.
	 */
	public function notify( int $booking_id, string $event ): void {
		if ( 'created' === $event ) {
			$booking = Booking_Repository::find( $booking_id );
			if ( $booking ) {
				$this->notify_admins_new_booking( $booking );
			}
		}
		$this->notify_customer( $booking_id, $event );
	}

	public function notify_customer( int $booking_id, string $event ): void {
		self::notify_customer_result( $booking_id, $event );
	}

	/**
	 * @return array{email: array{attempted: bool, sent: bool, skip_reason: string|null}, sms: array{attempted: bool, sent: bool, skip_reason: string|null}}
	 */
	public static function notify_customer_result( int $booking_id, string $event ): array {
		$result = array(
			'email' => array(
				'attempted'   => false,
				'sent'        => false,
				'skip_reason' => null,
			),
			'sms'   => array(
				'attempted'   => false,
				'sent'        => false,
				'skip_reason' => null,
			),
		);

		$dedupe_key = $booking_id . ':' . $event;
		if ( isset( self::$sent_in_request[ $dedupe_key ] ) ) {
			return self::$last_notify_result ?? $result;
		}
		self::$sent_in_request[ $dedupe_key ] = true;

		$booking = Booking_Repository::find( $booking_id );
		if ( ! $booking ) {
			$result['email']['skip_reason'] = 'booking_not_found';
			$result['sms']['skip_reason']   = 'booking_not_found';
			return $result;
		}

		$settings = Settings::get();
		$defaults = Settings::defaults();

		if ( 'confirmed' === $event && empty( $settings['notify_customer_on_confirm'] ) ) {
			$result['email']['skip_reason'] = 'notify_on_confirm_disabled';
			$result['sms']['skip_reason']   = 'notify_on_confirm_disabled';
			return $result;
		}

		$vars = self::vars_for_booking( $booking );

		$sms_key = 'tpl_sms_' . $event;
		$sms_tpl = trim( (string) ( $settings[ $sms_key ] ?? '' ) );
		if ( '' === $sms_tpl ) {
			$sms_tpl = trim( (string) ( $defaults[ $sms_key ] ?? '' ) );
		}
		if ( ! empty( $settings['sms_enabled'] ) && $sms_tpl && ! empty( $booking->phone ) && Helpers::is_valid_mobile( (string) $booking->phone ) ) {
			$result['sms']['attempted'] = true;
			$msg                        = Helpers::replace_vars( $sms_tpl, $vars );
			$sms_result                 = SMS_Manager::send( (string) $booking->phone, $msg );
			$result['sms']['sent']      = ! empty( $sms_result['ok'] );
			if ( ! $result['sms']['sent'] ) {
				$result['sms']['skip_reason'] = (string) ( $sms_result['error'] ?? 'sms_failed' );
			}
			self::log( $booking_id, (int) $booking->customer_id, 'sms', (string) $booking->phone, '', $msg, $sms_result );
		} elseif ( empty( $settings['sms_enabled'] ) ) {
			$result['sms']['skip_reason'] = 'sms_disabled';
		} elseif ( ! $sms_tpl ) {
			$result['sms']['skip_reason'] = 'sms_template_empty';
		} elseif ( empty( $booking->phone ) || ! Helpers::is_valid_mobile( (string) $booking->phone ) ) {
			$result['sms']['skip_reason'] = 'phone_missing';
		}

		$email_subj_key = 'tpl_email_' . $event . '_subject';
		$email_body_key = 'tpl_email_' . $event . '_body';
		$email          = sanitize_email( (string) ( $booking->email ?? '' ) );
		$subject_tpl    = trim( (string) ( $settings[ $email_subj_key ] ?? '' ) );
		$body_tpl       = trim( (string) ( $settings[ $email_body_key ] ?? '' ) );
		if ( '' === $subject_tpl ) {
			$subject_tpl = trim( (string) ( $defaults[ $email_subj_key ] ?? '' ) );
		}
		if ( '' === $body_tpl ) {
			$body_tpl = trim( (string) ( $defaults[ $email_body_key ] ?? '' ) );
		}

		if ( empty( $settings['email_enabled'] ) ) {
			$result['email']['skip_reason'] = 'email_disabled';
		} elseif ( ! $email || ! is_email( $email ) ) {
			$result['email']['skip_reason'] = 'customer_email_missing';
		} elseif ( ! $subject_tpl || ! $body_tpl ) {
			$result['email']['skip_reason'] = 'email_template_empty';
		} else {
			$result['email']['attempted'] = true;
			$subject                      = Helpers::replace_vars( $subject_tpl, $vars );
			$body                         = Helpers::replace_vars( $body_tpl, $vars );
			$sent                         = Email_Sender::send( $email, $subject, $body );
			$result['email']['sent']      = $sent;
			if ( ! $sent ) {
				$result['email']['skip_reason'] = 'wp_mail_failed';
			}
			self::log(
				$booking_id,
				(int) $booking->customer_id,
				'email',
				$email,
				$subject,
				$body,
				array( 'ok' => $sent )
			);
		}

		self::$last_notify_result = $result;

		return $result;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public static function pull_last_notify_result(): ?array {
		$result                   = self::$last_notify_result;
		self::$last_notify_result = null;

		return $result;
	}

	public function notify_admins_new_booking( object $booking ): void {
		$vars     = self::vars_for_booking( $booking, 'admin' );
		$settings = Settings::get();
		$link     = admin_url( 'admin.php?page=mr-booking-appointments&view=' . (int) $booking->id );
		$pending  = admin_url( 'admin.php?page=mr-booking-appointments&status=pending' );
		$ymd      = substr( (string) $booking->start_datetime, 0, 10 );
		$holiday  = \MRBooking\Holidays\Holiday_Repository::find_by_date( $ymd );
		$holiday_note = '';
		if ( $holiday && ! empty( $holiday->is_official ) ) {
			$holiday_note = '<p style="margin:12px 0;padding:10px 12px;border-radius:8px;background:#fef3c7;color:#92400e">'
				. '<strong>' . esc_html__( 'توجه: این نوبت در روز تعطیل رسمی ثبت شده است.', 'mr-booking' ) . '</strong>'
				. ( ! empty( $holiday->title ) ? ' ' . esc_html( (string) $holiday->title ) : '' )
				. '</p>';
		}

		$subject = sprintf(
			/* translators: %s: booking code */
			__( 'رزرو جدید در انتظار بررسی — %s', 'mr-booking' ),
			$vars['booking_id']
		);

		$body = '<div dir="rtl" style="font-family:Tahoma,sans-serif;line-height:1.8">'
			. $holiday_note
			. '<p><strong>' . esc_html__( 'یک رزرو جدید ثبت شد', 'mr-booking' ) . '</strong></p>'
			. '<ul>'
			. '<li>' . esc_html__( 'مشتری:', 'mr-booking' ) . ' ' . esc_html( $vars['customer_name'] ) . '</li>'
			. '<li>' . esc_html__( 'موبایل:', 'mr-booking' ) . ' ' . esc_html( $vars['customer_phone'] ) . '</li>'
			. '<li>' . esc_html__( 'خدمت:', 'mr-booking' ) . ' ' . esc_html( $vars['service_name'] ) . '</li>'
			. '<li>' . esc_html__( 'پرسنل:', 'mr-booking' ) . ' ' . esc_html( $vars['staff_name'] ?: '—' ) . '</li>'
			. '<li>' . esc_html__( 'تاریخ:', 'mr-booking' ) . ' ' . esc_html( $vars['booking_date'] ) . '</li>'
			. '<li>' . esc_html__( 'ساعت:', 'mr-booking' ) . ' ' . esc_html( $vars['booking_time'] ) . '</li>'
			. '<li>' . esc_html__( 'کد:', 'mr-booking' ) . ' ' . esc_html( $vars['booking_id'] ) . '</li>'
			. '<li>' . esc_html__( 'وضعیت:', 'mr-booking' ) . ' ' . esc_html( (string) ( $booking->status ?? '' ) ) . '</li>'
			. '</ul>'
			. '<p><a href="' . esc_url( $link ) . '">' . esc_html__( 'مشاهده و تأیید/رد رزرو', 'mr-booking' ) . '</a></p>'
			. '<p><a href="' . esc_url( $pending ) . '">' . esc_html__( 'لیست در انتظار تأیید', 'mr-booking' ) . '</a></p>'
			. '</div>';

		$sms = sprintf(
			'رزرو جدید %s | %s | %s %s | %s | بررسی: %s',
			$vars['booking_id'],
			$vars['customer_name'],
			$vars['booking_date'],
			$vars['booking_time'],
			$vars['service_name'],
			$pending
		);

		foreach ( self::admin_emails( $settings ) as $email ) {
			$sent = Email_Sender::send( $email, $subject, $body );
			self::log( (int) $booking->id, null, 'email', $email, $subject, $body, array( 'ok' => $sent ) );
		}

		if ( ! empty( $settings['sms_enabled'] ) ) {
			foreach ( self::admin_phones( $settings ) as $phone ) {
				$result = SMS_Manager::send( $phone, $sms );
				self::log( (int) $booking->id, null, 'sms', $phone, '', $sms, $result );
			}
		}
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return list<string>
	 */
	public static function admin_emails( array $settings ): array {
		$raw = trim( (string) ( $settings['email_admin'] ?? '' ) . "\n" . (string) ( $settings['notify_emails'] ?? '' ) );
		$parts = preg_split( '/[\s,;]+/', $raw ) ?: array();
		$out   = array();
		foreach ( $parts as $email ) {
			$email = sanitize_email( $email );
			if ( $email && is_email( $email ) ) {
				$out[] = $email;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @param array<string, mixed> $settings
	 * @return list<string>
	 */
	public static function admin_phones( array $settings ): array {
		$raw   = trim( (string) ( $settings['sms_admin_phone'] ?? '' ) . "\n" . (string) ( $settings['notify_phones'] ?? '' ) );
		$parts = preg_split( '/[\s,;]+/', $raw ) ?: array();
		$out   = array();
		foreach ( $parts as $phone ) {
			$phone = Helpers::sanitize_mobile( $phone );
			if ( Helpers::is_valid_mobile( $phone ) ) {
				$out[] = $phone;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/**
	 * @return array<string, string>
	 */
	public static function vars_for_booking( object $booking, string $context = 'customer' ): array {
		$services = Booking_Repository::services( (int) $booking->id );
		$names    = array();
		foreach ( $services as $s ) {
			$names[] = (string) $s->name;
		}

		$staff_name = '';
		if ( ! empty( $booking->staff_id ) ) {
			$staff = Staff_Repository::find( (int) $booking->staff_id );
			if ( $staff ) {
				$staff_name = Staff_Repository::display_name( $staff );
			}
		}

		$date = substr( (string) $booking->start_datetime, 0, 10 );

		if ( 'admin' === $context ) {
			$date_label = Helpers::format_admin_dual_date( $date );
			$time       = Helpers::format_admin_time( (string) $booking->start_datetime );
		} else {
			$date_label = Helpers::format_customer_date( $date );
			$time       = Helpers::format_customer_time( (string) $booking->start_datetime );
		}

		return array(
			'customer_name'  => trim( ( $booking->first_name ?? '' ) . ' ' . ( $booking->last_name ?? '' ) ),
			'customer_phone' => (string) ( $booking->phone ?? '' ),
			'service_name'   => implode( '، ', $names ),
			'booking_date'   => $date_label,
			'booking_time'   => $time,
			'staff_name'     => $staff_name,
			'booking_id'     => (string) $booking->booking_code,
			'business_name'  => (string) Settings::get_value( 'business_name', get_bloginfo( 'name' ) ),
		);
	}

	/**
	 * @param array<string, mixed> $result
	 */
	public static function log(
		?int $booking_id,
		?int $customer_id,
		string $channel,
		string $recipient,
		string $subject,
		string $body,
		array $result
	): void {
		global $wpdb;
		$wpdb->insert(
			Helpers::table( 'notification_logs' ),
			array(
				'booking_id'        => $booking_id,
				'customer_id'       => $customer_id,
				'channel'           => $channel,
				'recipient'         => $recipient,
				'subject'           => $subject,
				'body'              => $body,
				'status'            => ! empty( $result['ok'] ) ? 'sent' : 'failed',
				'provider_response' => wp_json_encode( $result ),
				'created_at'        => current_time( 'mysql' ),
			)
		);
	}

	/**
	 * Cron: send reminders.
	 */
	public static function send_reminders(): void {
		$settings = Settings::get();
		if ( empty( $settings['reminder_enabled'] ) ) {
			return;
		}

		$hours = max( 1, (int) $settings['reminder_hours_before'] );
		$from  = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) );
		$to    = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $hours * HOUR_IN_SECONDS ) );

		global $wpdb;
		$b    = Helpers::table( 'bookings' );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id FROM {$b} WHERE status IN ('pending','confirmed') AND start_datetime BETWEEN %s AND %s", // phpcs:ignore
				$from,
				$to
			)
		);

		$svc = new self();
		foreach ( $rows ?: array() as $row ) {
			$key = 'mr_booking_reminded_' . $row->id;
			if ( get_transient( $key ) ) {
				continue;
			}
			$svc->notify_customer( (int) $row->id, 'reminder' );
			set_transient( $key, 1, DAY_IN_SECONDS );
		}
	}

	/**
	 * Send custom message to customer.
	 */
	public static function send_custom( int $customer_id, string $channel, string $message, string $subject = '' ): array {
		$customer = \MRBooking\Customers\Customer_Repository::find( $customer_id );
		if ( ! $customer ) {
			return array( 'ok' => false, 'error' => 'Customer not found' );
		}

		$vars = array(
			'customer_name'  => trim( $customer->first_name . ' ' . $customer->last_name ),
			'customer_phone' => (string) $customer->phone,
			'business_name'  => (string) Settings::get_value( 'business_name', get_bloginfo( 'name' ) ),
			'service_name'   => '',
			'booking_date'   => '',
			'booking_time'   => '',
			'staff_name'     => '',
			'booking_id'     => '',
		);

		$message = Helpers::replace_vars( $message, $vars );
		$subject = Helpers::replace_vars( $subject, $vars );

		if ( 'sms' === $channel ) {
			$result = SMS_Manager::send( (string) $customer->phone, $message );
			self::log( null, $customer_id, 'sms', (string) $customer->phone, '', $message, $result );
			return $result;
		}

		if ( 'email' === $channel && $customer->email ) {
			$sent = Email_Sender::send( (string) $customer->email, $subject ?: __( 'پیام از کسب‌وکار', 'mr-booking' ), wpautop( esc_html( $message ) ) );
			self::log( null, $customer_id, 'email', (string) $customer->email, $subject, $message, array( 'ok' => $sent ) );
			return array( 'ok' => $sent );
		}

		return array( 'ok' => false, 'error' => 'Invalid channel' );
	}
}
