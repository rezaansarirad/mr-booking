<p align="center">
  <img src="assets/img/sticker.gif" alt="MR Booking" width="160" />
</p>

# MR Booking

[فارسی](README.fa.md)

Professional appointment and booking management for WordPress — Jalali/Gregorian calendar, services, staff, working hours, holidays, SMS, and email notifications.

**Author:** Reza Ansarirad  
**Requirements:** WordPress 5.8+ · PHP 8.0+

---

## Installation

1. Copy the `mr-booking` folder into `wp-content/plugins/`.
2. Activate **MR Booking** from the WordPress admin.
3. Database tables and default settings are created automatically.
4. The **setup wizard** opens and walks you through:
   - Business name
   - Calendar mode (Jalali / Gregorian / both)
   - Working hours and closed days
   - Staff members
   - Active services
   - Booking rules and colors

If you skip the wizard, you can finish setup later from **MR Booking → Setup** (until completed) or **Settings**.

---

## Showing the booking form to visitors

### Shortcode

Create a **Page** in WordPress (e.g. “Book an appointment”) and add:

```
[mr_booking]
```

Optional attributes:

```
[mr_booking service="12"]
[mr_booking staff="5"]
[mr_booking theme="default"]
```

### Elementor

If Elementor is installed:

1. Edit the page with Elementor.
2. Open the **MR Booking** widget category (or search for “booking”).
3. Drag the **MR Booking Form** widget onto the page.
4. **Content** tab: pre-select service and/or staff.
5. **Style** tab:
   - Text, title, background, card, and border colors
   - Global, title, label, and step typography
   - Primary and back button colors and fonts
   - Input fields and card border radius

---

## Dashboard widget

From **Settings → Dashboard**, enable the activity summary widget on the WordPress dashboard and choose which stats to show (today’s bookings, customers, pending approvals, and more).

You can also use **Configure** on the widget itself for quick changes.

---

## Admin menu

- Dashboard
- Appointments
- Calendar (day / week / month)
- Customers (CSV export, birthday filters, SMS/email)
- Services
- Staff
- Working hours (multiple periods per day)
- Holidays and special dates
- Notifications (SMS/email templates)
- Reports
- Settings (general, calendar, rules, appearance, texts, SMS, email)

---

## Calendar

In settings you can choose:

- Jalali only
- Gregorian only
- Both at once

Dates are stored in the database as Gregorian and converted only for display.

---

## SMS

Built-in provider abstraction for:

- Kavenegar
- Melipayamak
- SMS.ir

Add a custom provider with a filter:

```php
add_filter( 'mr_booking_sms_providers', function( $providers ) {
    // $providers['myprovider'] = new My_Provider();
    return $providers;
});
```

---

## REST API

Base URL: `/wp-json/mr-booking/v1/`

| Endpoint              | Method | Description                     |
| --------------------- | ------ | ------------------------------- |
| `/services`           | GET    | List services                   |
| `/staff`              | GET    | List staff                      |
| `/availability/month` | GET    | Monthly availability            |
| `/availability/slots` | GET    | Slots for a day                 |
| `/book`               | POST   | Create booking (nonce required) |
| `/settings/public`    | GET    | Public frontend settings        |

---

## Important hooks

```php
do_action( 'mr_booking_booking_created', $booking_id, $payload );
do_action( 'mr_booking_booking_status_changed', $booking_id, $status );
do_action( 'mr_booking_sms_sent', $to, $message, $result );
do_action( 'mr_booking_email_sent', $to, $subject, $sent );
```

---

## Database tables

Prefix: `{wp_prefix}mr_`

- `services`
- `staff`
- `staff_services`
- `customers`
- `bookings`
- `booking_services`
- `working_hours`
- `holidays`
- `special_dates`
- `notification_logs`

---

## Notification template variables

`{customer_name}` `{customer_phone}` `{service_name}` `{booking_date}` `{booking_time}` `{staff_name}` `{booking_id}` `{business_name}`

---

## Privacy

The plugin integrates with WordPress personal data export and erasure tools.
