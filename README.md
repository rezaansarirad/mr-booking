<p align="center">
  <img src="assets/img/sticker.gif" alt="MR Booking" width="160" />
</p>

# MR Booking

[فارسی](README.fa.md)

Professional appointment and booking management for WordPress — Jalali/Gregorian calendar, services, staff, working hours, holidays, SMS, and email notifications.

**Version:** 1.7.3  
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
   - Booking rules and appearance colors

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
   - Service card colors (background, border, price, duration badge, selected state)
   - Input fields, placeholders, and card border radius

Form colors can also be managed globally from **Settings → Appearance**.

---

## Phone booking (admin)

Use **MR Booking → Phone booking** to create reservations manually from the admin panel — useful for phone orders or walk-ins.

- Search existing customers by name or mobile
- Pick service, staff, date, and time slot
- Enter or update customer details (name, phone, email, birth date)
- Choose booking status (pending or confirmed)

The page uses the same slot engine and REST endpoints as the public form.

---

## Appointments & slot management

From **Appointments** you can:

- **Approve** pending bookings → customer receives confirmation notification (if enabled)
- **Reject** pending bookings → slot is freed; customer is notified
- **Cancel** upcoming confirmed bookings → slot is freed; customer is notified
- **Delete** a booking permanently → slot is freed when applicable

After changing status, the admin panel shows clear feedback if the customer email/SMS was sent, skipped (no email, disabled, etc.), or failed.

Pending bookings show a **badge count** on the Appointments menu item.

Lists are sorted by **registration time** (newest first) by default.

---

## Notifications

### Customer notifications

Automatic SMS and/or email on these events:

| Event | When |
| ----- | ---- |
| **Created** | Right after a booking is submitted (pending receipt) |
| **Confirmed** | After admin approves the booking |
| **Cancelled** | When a booking is rejected, cancelled, or deleted (upcoming) |
| **Reminder** | Before appointment time (per reminder settings) |
| **Birthday** | Customer birthday SMS (if configured) |

Configure templates under **Notifications**. Toggle **Send confirmation notification to customer** to control confirm emails/SMS separately from the initial receipt.

Empty templates fall back to built-in defaults. All sends are logged in `notification_logs`.

### Admin notifications

When a new booking is created, admins receive email (and SMS if enabled) with booking details and quick links to review pending appointments. Configure recipients in **Settings → Email** and **Settings → SMS** (`notify_emails`, `notify_phones`).

### Troubleshooting email

If a customer did not receive a confirmation email, check:

1. **Notifications** → “Send confirmation notification to customer” is enabled
2. **Settings → Email** → email sending is enabled and From address is valid
3. The customer has an email on file (email is optional unless **Require email** is on)
4. Server mail / SMTP is working (use a plugin like WP Mail SMTP if needed)

After approving a booking, the admin notice explains exactly what happened.

---

## Live admin alerts

While you are on any MR Booking admin page:

- New bookings are detected automatically (polling every 30 seconds)
- A toast notification appears with customer and service info
- An optional sound plays (can be muted for the session)
- Click through to the appointments list

---

## Appearance & color themes

**Settings → Appearance** includes:

- **Theme presets** — Dark and Light templates based on brand colors (primary `#D4AF37`, secondary `#F0D875`, accent `#142A38`)
- **Brand** — Primary, secondary, accent, text, labels, inputs, placeholders
- **Buttons** — Continue/submit and back button colors (normal + hover)
- **Service cards** — Card background, border, selected state, price, duration badge, checkmark
- **Calendar** — Available, unavailable, holiday, fully booked colors
- **Custom** — Fine-tune any field after applying a preset; preview before saving

The setup wizard can set initial colors during first install.

---

## Dashboard widget

From **Settings → Dashboard**, enable the activity summary widget on the WordPress dashboard and choose which stats to show (today’s bookings, customers, pending approvals, and more).

You can also use **Configure** on the widget itself for quick changes.

The MR Booking dashboard shows today’s bookings and recent registrations sorted by newest first.

---

## Admin menu

- Dashboard
- Appointments (pending badge, approve/reject/cancel/delete)
- Phone booking
- Calendar (day / week / month)
- Customers (edit profile, CSV export, birthday filters, SMS/email)
- Services
- Staff
- Working hours (multiple periods per day)
- Holidays and special dates
- Notifications (SMS/email templates, confirm toggle)
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

| Endpoint | Method | Auth | Description |
| -------- | ------ | ---- | ----------- |
| `/services` | GET | Public | List services |
| `/staff` | GET | Public | List staff |
| `/availability/month` | GET | Public | Monthly availability |
| `/availability/slots` | GET | Public | Slots for a day |
| `/book` | POST | Public (nonce) | Create booking (frontend) |
| `/settings/public` | GET | Public | Public frontend settings |
| `/admin/customers/search` | GET | Admin | Search customers by name/phone |
| `/admin/book` | POST | Admin | Create booking from admin (phone booking) |

---

## Important hooks

```php
do_action( 'mr_booking_booking_created', $booking_id );
do_action( 'mr_booking_booking_status_changed', $booking_id, $new_status, $old_status );
do_action( 'mr_booking_booking_deleted', $booking_id, $booking_object );
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

---

## Changelog (recent)

### 1.7.x
- Customer email/SMS on confirm, reject, and cancel with admin delivery feedback
- Live admin notifications + sound for new bookings; pending count badge
- Phone booking admin page with customer search
- Customer profile editing in admin
- Dark/Light color theme presets; service card and button color groups
- Slot freed on reject/cancel/delete; admin can cancel confirmed bookings
- Appointments sorted by registration time (newest first)
- REST: `/admin/customers/search`, `/admin/book`
