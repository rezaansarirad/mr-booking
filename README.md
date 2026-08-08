<p align="center">
  <img src="assets/img/sticker.gif" alt="MR Booking" width="160" />
</p>

# MR Booking

[فارسی](README.fa.md)

Professional appointment and booking management for WordPress — Jalali/Gregorian calendar, services, staff, working hours, holidays, SMS, and email notifications.

**Version:** 1.9.3  
**Author:** Reza Ansarirad  
**Requirements:** WordPress 5.8+ · PHP 8.0+

---

## Screenshots

### Admin panel

| | |
| --- | --- |
| **Dashboard** — stats, today’s bookings, recent activity | **Appointments** — filters, status pills, approve/reject |
| ![Dashboard](assets/img/Screenshots/dashboard.png) | ![Appointments](assets/img/Screenshots/appointments.png) |
| **Phone booking** — manual reservation from admin | **Customers** — search, birthdays, CSV export |
| ![Phone booking](assets/img/Screenshots/phone-booking.png) | ![Customers](assets/img/Screenshots/customers.png) |
| **Services** — card grid with quick actions | **New service** — duration presets & price toggle |
| ![Services](assets/img/Screenshots/services-list.png) | ![Service editor](assets/img/Screenshots/service-editor.png) |
| **Staff** — team list with assigned services | **Staff editor** — profile & service picker cards |
| ![Staff](assets/img/Screenshots/staff-list.png) | ![Staff editor](assets/img/Screenshots/staff-editor.png) |
| **Working hours** — per-day intervals | **Blocked hours** — lunch & recurring breaks |
| ![Staff hours](assets/img/Screenshots/staff-hours.png) | ![Blocked hours](assets/img/Screenshots/blocked-hours.png) |
| **Holidays & special dates** | **Reports** — date range & popular services |
| ![Holidays](assets/img/Screenshots/holidays.png) | ![Reports](assets/img/Screenshots/reports.png) |
| **Notification templates** — variables & confirm toggle | **Email / SMS templates** — per-event editors |
| ![Notification templates](assets/img/Screenshots/notifications-templates.png) | ![Email and SMS templates](assets/img/Screenshots/notifications-email-sms.png) |
| **WordPress admin menu** (pending badge) | **Live booking toast** in admin |
| ![Admin menu](assets/img/Screenshots/admin-menu.png) | ![Live notification](assets/img/Screenshots/admin-live-notification.png) |

### Settings

| | |
| --- | --- |
| **General** — business, hours mode, form options | **Settings tabs** — sidebar navigation |
| ![Settings general](assets/img/Screenshots/settings-general.png) | ![Settings tabs](assets/img/Screenshots/settings-tabs.png) |
| **Calendar** — Jalali / Gregorian / both | **Booking rules** — slots & requirements |
| ![Settings calendar](assets/img/Screenshots/settings-calendar.png) | ![Settings booking rules](assets/img/Screenshots/settings-booking-rules.png) |
| **Appearance** — dark / light themes | **Color palette** — buttons & loading spinner |
| ![Appearance themes](assets/img/Screenshots/settings-appearance-themes.png) | ![Appearance buttons](assets/img/Screenshots/settings-appearance-buttons.png) |
| **Service card colors** | **Calendar day colors** |
| ![Service card colors](assets/img/Screenshots/settings-appearance-service-cards.png) | ![Calendar colors](assets/img/Screenshots/settings-appearance-calendar-colors.png) |
| **Editable texts** — steps & buttons | **Placeholders** — form field hints |
| ![Text labels](assets/img/Screenshots/settings-texts-labels.png) | ![Placeholders](assets/img/Screenshots/settings-texts-placeholders.png) |
| **Dashboard widget** options | **SMS** provider & API keys |
| ![Dashboard widget settings](assets/img/Screenshots/settings-dashboard-widget.png) | ![SMS settings](assets/img/Screenshots/settings-sms.png) |
| **Email** sender & recipients | **Premium** — white-label on public form |
| ![Email settings](assets/img/Screenshots/settings-email.png) | ![Premium settings](assets/img/Screenshots/settings-premium.png) |
| **GitHub** — open-source project link | |
| ![GitHub tab](assets/img/Screenshots/settings-github.png) | |

### Public booking form

| | |
| --- | --- |
| **Step 1** — personal info & booking for | **Step 2** — staff & service selection |
| ![Booking step 1](assets/img/Screenshots/frontend-step-personal.png) | ![Booking step 2](assets/img/Screenshots/frontend-step-services.png) |
| **Step 3** — Jalali calendar | **Step 4** — time slots |
| ![Booking step 3](assets/img/Screenshots/frontend-step-date.png) | ![Booking step 4](assets/img/Screenshots/frontend-step-time.png) |
| **Success** — confirmation receipt | **Birth date picker** — iOS-style wheel |
| ![Booking success](assets/img/Screenshots/frontend-success.png) | ![Birth date picker](assets/img/Screenshots/birth-date-picker.png) |

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

If you skip the wizard, you can finish setup later from **Booking Form → Setup** (until completed) or **Settings**.

The admin menu title follows the WordPress site language: **Booking Form** (English) or **رزروها** (Persian).

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
2. Open the **Booking Form** widget category (or search for “booking”).
3. Drag the **Booking Form** widget onto the page.
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

After approving a booking, the admin notice explains exactly what happened (email and SMS separately).

---

## User roles & access

MR Booking registers WordPress roles for staff who only manage bookings — without full site admin access.

| Role | Access |
| ---- | ------ |
| **Receptionist** (`mr_booking_receptionist`) | Appointments, phone booking, customers, calendar |
| **Booking manager** (`mr_booking_manager`) | All plugin sections including **Settings** |

Create users under **Users → Add New** and assign a role. Booking-only users see only the MR Booking menu; other WordPress admin menus are hidden. After login they land on their first allowed section.

Fine-grained capabilities (`mr_booking_*`) can be customized with plugins like **User Role Editor**.

See **Settings → Access** in the admin panel for a quick guide.

---

## Live admin alerts

While you are on any MR Booking admin page:

- New bookings are detected automatically (configurable poll interval)
- A toast notification appears with customer and service info
- An optional sound plays (can be muted for the session)
- Click through to the appointments list

Adjust the poll interval under **Settings → Dashboard** (0 = off, 15–300 seconds).

---

## Appearance & color themes

**Settings → Appearance** includes:

- **Theme presets** — Dark and Light templates based on brand colors (primary `#D4AF37`, secondary `#F0D875`, accent `#142A38`)
- **Brand** — Primary, secondary, accent, text, labels, inputs, placeholders
- **Buttons** — Continue/submit and back button colors (normal + hover); **loading spinner color** on Continue/Submit
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
- Settings (general, calendar, rules, appearance, texts, SMS, email, **access**)

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

- **Kavenegar** — connection test, account credit in settings, credit balance in the WordPress admin bar
- Melipayamak
- SMS.ir

In **Settings → SMS**, use **Test connection** to verify the API key (Kavenegar shows account balance). Credit is cached and shown in the admin toolbar.

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

## Development & release zip

The plugin repo includes `.git/` for development. **Do not upload the git folder to WordPress** — updates will fail.

Build a clean zip (no `.git`, no `.DS_Store`):

```bash
cd plugin
rsync -a --exclude='.git' --exclude='.DS_Store' --exclude='__MACOSX' --exclude='*.zip' \
  mr-booking/ /tmp/mr-booking-release/mr-booking/
cd /tmp/mr-booking-release && zip -r mr-booking.zip mr-booking
```

See `.distignore` for files excluded from distribution.

---

## Changelog (recent)

### 1.9.x
- **1.9.3** — Booking manager role includes **Settings** menu access
- **1.9.2** — Fix duplicate import fatal error in restricted admin
- **1.9.1** — Fix missing `DASHBOARD` capability constant on activation
- **1.9.0** — WordPress user roles (Receptionist, Booking manager); per-section capabilities; trimmed admin for booking staff; SMS connection test & Kavenegar credit in admin bar; separate email/SMS feedback after approve

### 1.8.x
- **1.8.3** — Admin menu and Elementor category/widget title follow site locale (**Booking Form** / **رزروها**)
- **1.8.2** — Fixed “official holiday” badge always showing on the booking form; removed holiday labels from the public form; compact selected-date bar; calendar no longer scrolls inside the date step
- **1.8.1** — Loading spinner on Continue/Submit buttons during async steps; configurable loading color in **Settings → Appearance → Buttons**
- **1.8.0** — Redesigned staff “available services” picker (card grid with service colors) on the Staff admin page
- **1.7.9** — Services admin: “new service” form inline below the list (no separate page)
- **1.7.8** — Service price toggle/UI fixes on the new-service form
- **1.7.7** — Settings toggle switches RTL layout fix
- **1.7.6** — Booking calendar: default today selection, “today” label fix, improved selected-day styling
- **1.7.5** — Elementor widget colors default from plugin appearance settings; premium option to hide “MR Booking” branding on the public form only; GitHub tab in settings
- **1.7.4** — Admin panel dates follow WordPress locale / Parsi Date when available

### 1.7.x (earlier)
- Customer email/SMS on confirm, reject, and cancel with admin delivery feedback
- Live admin notifications + sound for new bookings; pending count badge
- Phone booking admin page with customer search
- Customer profile editing in admin
- Dark/Light color theme presets; service card and button color groups
- Slot freed on reject/cancel/delete; admin can cancel confirmed bookings
- Appointments sorted by registration time (newest first)
- REST: `/admin/customers/search`, `/admin/book`
