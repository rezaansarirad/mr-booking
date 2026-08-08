<?php
/**
 * Database installer and schema.
 *
 * @package MRBooking
 */

declare(strict_types=1);

namespace MRBooking\Database;

use MRBooking\Helpers;

defined( 'ABSPATH' ) || exit;

final class Installer {

	public static function activate(): void {
		self::create_tables();
		self::seed_defaults();
		\MRBooking\Roles\Roles::register();
		Helpers::ensure_caps();
		update_option( 'mr_booking_db_version', MR_BOOKING_DB_VERSION );

		// First-run setup wizard — only for fresh installs that never finished setup.
		$setup_done = get_option( \MRBooking\Admin\Setup_Wizard::OPTION_COMPLETE, null );
		if ( null === $setup_done ) {
			\MRBooking\Admin\Setup_Wizard::mark_needed();
		}

		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( 'mr_booking_send_reminders' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'mr_booking_send_reminders' );
		}
		flush_rewrite_rules();
	}

	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix . 'mr_';

		$sql = array();

		$sql[] = "CREATE TABLE {$prefix}services (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			description TEXT NULL,
			duration INT UNSIGNED NOT NULL DEFAULT 30,
			has_price TINYINT(1) NOT NULL DEFAULT 0,
			price DECIMAL(12,2) NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			image_id BIGINT UNSIGNED NULL,
			color VARCHAR(20) NULL,
			sort_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY sort_order (sort_order)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}staff (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			phone VARCHAR(20) NULL,
			email VARCHAR(191) NULL,
			image_id BIGINT UNSIGNED NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			working_hours LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}staff_services (
			staff_id BIGINT UNSIGNED NOT NULL,
			service_id BIGINT UNSIGNED NOT NULL,
			PRIMARY KEY  (staff_id, service_id),
			KEY service_id (service_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}customers (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			first_name VARCHAR(100) NOT NULL,
			last_name VARCHAR(100) NOT NULL,
			phone VARCHAR(20) NOT NULL,
			email VARCHAR(191) NULL,
			birth_date DATE NULL,
			user_id BIGINT UNSIGNED NULL,
			notes TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY phone (phone),
			KEY email (email),
			KEY birth_date (birth_date),
			KEY user_id (user_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}bookings (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_code VARCHAR(32) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			staff_id BIGINT UNSIGNED NULL,
			booking_for VARCHAR(20) NOT NULL DEFAULT 'myself',
			booking_for_name VARCHAR(191) NULL,
			start_datetime DATETIME NOT NULL,
			end_datetime DATETIME NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			total_price DECIMAL(12,2) NOT NULL DEFAULT 0,
			total_duration INT UNSIGNED NOT NULL DEFAULT 0,
			notes TEXT NULL,
			source VARCHAR(50) NOT NULL DEFAULT 'frontend',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY booking_code (booking_code),
			KEY customer_id (customer_id),
			KEY staff_id (staff_id),
			KEY status (status),
			KEY start_datetime (start_datetime),
			KEY end_datetime (end_datetime)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}booking_services (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NOT NULL,
			service_id BIGINT UNSIGNED NOT NULL,
			duration INT UNSIGNED NOT NULL,
			price DECIMAL(12,2) NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY service_id (service_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}working_hours (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			day_of_week TINYINT UNSIGNED NOT NULL,
			start_time TIME NOT NULL,
			end_time TIME NOT NULL,
			is_closed TINYINT(1) NOT NULL DEFAULT 0,
			staff_id BIGINT UNSIGNED NULL,
			PRIMARY KEY  (id),
			KEY day_of_week (day_of_week),
			KEY staff_id (staff_id)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}holidays (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			holiday_date DATE NOT NULL,
			title VARCHAR(191) NOT NULL,
			is_official TINYINT(1) NOT NULL DEFAULT 0,
			is_closed TINYINT(1) NOT NULL DEFAULT 1,
			PRIMARY KEY  (id),
			UNIQUE KEY holiday_date (holiday_date)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}special_dates (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			special_date DATE NOT NULL,
			type VARCHAR(20) NOT NULL DEFAULT 'closed',
			reason VARCHAR(255) NULL,
			note TEXT NULL,
			periods LONGTEXT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY special_date (special_date),
			KEY type (type)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}notification_logs (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			booking_id BIGINT UNSIGNED NULL,
			customer_id BIGINT UNSIGNED NULL,
			channel VARCHAR(20) NOT NULL,
			recipient VARCHAR(191) NOT NULL,
			subject VARCHAR(255) NULL,
			body TEXT NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			provider_response TEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY booking_id (booking_id),
			KEY customer_id (customer_id),
			KEY channel (channel),
			KEY status (status)
		) $charset;";

		$sql[] = "CREATE TABLE {$prefix}staff_time_blocks (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			staff_id BIGINT UNSIGNED NOT NULL,
			day_of_week TINYINT UNSIGNED NOT NULL,
			start_time TIME NOT NULL,
			end_time TIME NOT NULL,
			label VARCHAR(191) NULL,
			PRIMARY KEY  (id),
			KEY staff_id (staff_id),
			KEY day_of_week (day_of_week)
		) $charset;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		self::migrate_columns();
	}

	/**
	 * Add missing columns for existing installs.
	 */
	public static function migrate_columns(): void {
		global $wpdb;
		$table = Helpers::table( 'services' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$col = $wpdb->get_results( "SHOW COLUMNS FROM {$table} LIKE 'has_price'" );
		if ( empty( $col ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN has_price TINYINT(1) NOT NULL DEFAULT 0 AFTER duration" );
			// Mark services that already have a price amount.
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$table} SET has_price = 1 WHERE price > 0" );
		}

		$staff_table = Helpers::table( 'staff' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$break_col = $wpdb->get_results( "SHOW COLUMNS FROM {$staff_table} LIKE 'break_minutes'" );
		if ( empty( $break_col ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "ALTER TABLE {$staff_table} ADD COLUMN break_minutes INT UNSIGNED NOT NULL DEFAULT 0 AFTER status" );
		}

		$blocks_table = Helpers::table( 'staff_time_blocks' );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$blocks_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $blocks_table ) );
		if ( $blocks_table !== $blocks_exists ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$charset = $wpdb->get_charset_collate();
			dbDelta(
				"CREATE TABLE {$blocks_table} (
					id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
					staff_id BIGINT UNSIGNED NOT NULL,
					day_of_week TINYINT UNSIGNED NOT NULL,
					start_time TIME NOT NULL,
					end_time TIME NOT NULL,
					label VARCHAR(191) NULL,
					PRIMARY KEY  (id),
					KEY staff_id (staff_id),
					KEY day_of_week (day_of_week)
				) {$charset};"
			);
		}
	}

	private static function seed_defaults(): void {
		global $wpdb;

		$hours_table = Helpers::table( 'working_hours' );
		$count       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$hours_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL

		if ( 0 === $count ) {
			$now_days = array( 6, 0, 1, 2, 3 ); // Sat–Wed
			foreach ( $now_days as $day ) {
				$wpdb->insert(
					$hours_table,
					array(
						'day_of_week' => $day,
						'start_time'  => '09:00:00',
						'end_time'    => '13:00:00',
						'is_closed'   => 0,
					)
				);
				$wpdb->insert(
					$hours_table,
					array(
						'day_of_week' => $day,
						'start_time'  => '16:00:00',
						'end_time'    => '21:00:00',
						'is_closed'   => 0,
					)
				);
			}

			// Thursday.
			$wpdb->insert(
				$hours_table,
				array(
					'day_of_week' => 4,
					'start_time'  => '09:00:00',
					'end_time'    => '18:00:00',
					'is_closed'   => 0,
				)
			);

			// Friday closed.
			$wpdb->insert(
				$hours_table,
				array(
					'day_of_week' => 5,
					'start_time'  => '00:00:00',
					'end_time'    => '00:00:00',
					'is_closed'   => 1,
				)
			);
		}

		if ( ! get_option( 'mr_booking_settings' ) ) {
			update_option( 'mr_booking_settings', \MRBooking\Settings\Settings::defaults() );
		}

		self::seed_sample_services();
		self::seed_persian_holidays();
	}

	private static function seed_sample_services(): void {
		global $wpdb;

		$table = Helpers::table( 'services' );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore

		if ( $count > 0 ) {
			return;
		}

		$now      = current_time( 'mysql' );
		$samples  = array(
			array( 'سشوار', 15, 0 ),
			array( 'اصلاح سر', 30, 0 ),
			array( 'اصلاح ریش', 15, 0 ),
			array( 'دور مو', 15, 0 ),
			array( 'بالای سر', 20, 0 ),
			array( 'سشوار و ریش', 30, 0 ),
			array( 'دور مو و بالای سر', 30, 0 ),
			array( 'اصلاح کامل', 45, 0 ),
			array( 'پاکسازی', 90, 0 ),
			array( 'ماساژ', 90, 0 ),
			array( 'پکیج دامادی', 120, 0 ),
			array( 'میکاپ', 60, 0 ),
		);

		$order = 0;
		foreach ( $samples as $sample ) {
			$wpdb->insert(
				$table,
				array(
					'name'        => $sample[0],
					'description' => '',
					'duration'    => $sample[1],
					'price'       => $sample[2],
					'status'      => 'active',
					'sort_order'  => $order++,
					'created_at'  => $now,
					'updated_at'  => $now,
				),
				array( '%s', '%s', '%d', '%f', '%s', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Seed a few known Persian holidays for the current Jalali year (approximate Gregorian mapping).
	 */
	private static function seed_persian_holidays(): void {
		global $wpdb;

		$table = Helpers::table( 'holidays' );
		$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore

		if ( $count > 0 ) {
			return;
		}

		$year     = (int) gmdate( 'Y' );
		$holidays = array(
			array( "{$year}-03-20", 'نوروز' ),
			array( "{$year}-03-21", 'نوروز' ),
			array( "{$year}-03-22", 'نوروز' ),
			array( "{$year}-03-23", 'نوروز' ),
			array( "{$year}-04-01", 'روز جمهوری اسلامی' ),
			array( "{$year}-04-02", 'روز طبیعت' ),
		);

		foreach ( $holidays as $h ) {
			$wpdb->insert(
				$table,
				array(
					'holiday_date' => $h[0],
					'title'        => $h[1],
					'is_official'  => 1,
					'is_closed'    => 1,
				),
				array( '%s', '%s', '%d', '%d' )
			);
		}
	}
}
