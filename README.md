<p align="center">
  <img src="mr-booking/assets/img/sticker.gif" alt="MR Booking" width="160" />
</p>

# MR Booking

[فارسی](README.fa.md)

Professional appointment and booking management for WordPress — Jalali/Gregorian calendar, services, staff, working hours, holidays, SMS, email, deposits (ZarinPal / wallet), OTP customer login, and accounting.

**Version:** 2.5.2  
**Author:** [Reza Ansarirad](https://rezaansarirad.ir)  
**Requirements:** WordPress 5.8+ · PHP 8.0+

Full release notes: [CHANGELOG.md](CHANGELOG.md)

---

## Screenshots

### Admin panel

|                                                                               |                                                                                |
| ----------------------------------------------------------------------------- | ------------------------------------------------------------------------------ |
| **Dashboard** — stats, today’s bookings, recent activity                      | **Appointments** — filters, status pills, approve/reject                       |
| ![Dashboard](mr-booking/assets/img/Screenshots/dashboard.png)                            | ![Appointments](mr-booking/assets/img/Screenshots/appointments.png)                       |
| **Phone booking** — manual reservation from admin                             | **Customers** — search, birthdays, wallet, CSV export                          |
| ![Phone booking](mr-booking/assets/img/Screenshots/phone-booking.png)                    | ![Customers](mr-booking/assets/img/Screenshots/customers.png)                             |
| **Services** — card grid with quick actions                                   | **New service** — duration presets & price toggle                              |
| ![Services](mr-booking/assets/img/Screenshots/services-list.png)                         | ![Service editor](mr-booking/assets/img/Screenshots/service-editor.png)                   |
| **Staff** — team list with assigned services                                  | **Staff editor** — profile & service picker cards                              |
| ![Staff](mr-booking/assets/img/Screenshots/staff-list.png)                               | ![Staff editor](mr-booking/assets/img/Screenshots/staff-editor.png)                       |
| **Working hours** — per-day intervals                                         | **Blocked hours** — lunch & recurring breaks                                   |
| ![Staff hours](mr-booking/assets/img/Screenshots/staff-hours.png)                        | ![Blocked hours](mr-booking/assets/img/Screenshots/blocked-hours.png)                     |
| **Holidays & special dates**                                                  | **Reports** — date range & popular services                                    |
| ![Holidays](mr-booking/assets/img/Screenshots/holidays.png)                              | ![Reports](mr-booking/assets/img/Screenshots/reports.png)                                 |
| **Notification templates** — variables & confirm toggle                       | **Email / SMS templates** — per-event editors                                  |
| ![Notification templates](mr-booking/assets/img/Screenshots/notifications-templates.png) | ![Email and SMS templates](mr-booking/assets/img/Screenshots/notifications-email-sms.png) |
| **WordPress admin menu** (pending badge)                                      | **Live booking toast** in admin                                                |
| ![Admin menu](mr-booking/assets/img/Screenshots/admin-menu.png)                          | ![Live notification](mr-booking/assets/img/Screenshots/admin-live-notification.png)       |

### Settings

|                                                                                      |                                                                                    |
| ------------------------------------------------------------------------------------ | ---------------------------------------------------------------------------------- |
| **General** — business, hours mode, form options                                     | **Settings tabs** — sidebar navigation                                             |
| ![Settings general](mr-booking/assets/img/Screenshots/settings-general.png)                     | ![Settings tabs](mr-booking/assets/img/Screenshots/settings-tabs.png)                         |
| **Calendar** — Jalali / Gregorian / both                                             | **Booking rules** — slots & requirements                                           |
| ![Settings calendar](mr-booking/assets/img/Screenshots/settings-calendar.png)                   | ![Settings booking rules](mr-booking/assets/img/Screenshots/settings-booking-rules.png)       |
| **Appearance** — dark / light themes                                                 | **Color palette** — buttons & loading spinner                                      |
| ![Appearance themes](mr-booking/assets/img/Screenshots/settings-appearance-themes.png)          | ![Appearance buttons](mr-booking/assets/img/Screenshots/settings-appearance-buttons.png)      |
| **Service card colors**                                                              | **Calendar day colors**                                                            |
| ![Service card colors](mr-booking/assets/img/Screenshots/settings-appearance-service-cards.png) | ![Calendar colors](mr-booking/assets/img/Screenshots/settings-appearance-calendar-colors.png) |
| **Editable texts** — steps & buttons                                                 | **Placeholders** — form field hints                                                |
| ![Text labels](mr-booking/assets/img/Screenshots/settings-texts-labels.png)                     | ![Placeholders](mr-booking/assets/img/Screenshots/settings-texts-placeholders.png)            |
| **Dashboard widget** options                                                         | **SMS** provider & API keys                                                        |
| ![Dashboard widget settings](mr-booking/assets/img/Screenshots/settings-dashboard-widget.png)   | ![SMS settings](mr-booking/assets/img/Screenshots/settings-sms.png)                           |
| **Email** sender & recipients                                                        | **Premium** — white-label on public form                                           |
| ![Email settings](mr-booking/assets/img/Screenshots/settings-email.png)                         | ![Premium settings](mr-booking/assets/img/Screenshots/settings-premium.png)                   |
| **GitHub** — open-source project link                                                |                                                                                    |
| ![GitHub tab](mr-booking/assets/img/Screenshots/settings-github.png)                            |                                                                                    |

### Public booking form

|                                                                      |                                                                      |
| -------------------------------------------------------------------- | -------------------------------------------------------------------- |
| **Step 1** — personal info & booking for                             | **Step 2** — staff & service selection                               |
| ![Booking step 1](mr-booking/assets/img/Screenshots/frontend-step-personal.png) | ![Booking step 2](mr-booking/assets/img/Screenshots/frontend-step-services.png) |
| **Step 3** — Jalali calendar                                         | **Step 4** — time slots                                              |
| ![Booking step 3](mr-booking/assets/img/Screenshots/frontend-step-date.png)     | ![Booking step 4](mr-booking/assets/img/Screenshots/frontend-step-time.png)     |
| **Success** — confirmation receipt                                   | **Birth date picker** — iOS-style wheel with desktop arrows          |
| ![Booking success](mr-booking/assets/img/Screenshots/frontend-success.png)      | ![Birth date picker](mr-booking/assets/img/Screenshots/birth-date-picker.png)   |

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
[mr_booking embed="account"]
```

`embed="account"` renders the wizard without header/auth chip (used inside the customer dashboard).

### Customer account (OTP)

Create a page with:

```
[mr_booking_account]
```

Then set that page under **Settings → Account**. Customers log in with SMS OTP, see bookings, cancel within the policy window, manage wallet, and (optionally) book from inside the panel.

Login mode: **off / optional / required** on the public form. Enable **«رزرو نوبت داخل پنل مشتری»** to embed the booking wizard in the account dashboard.

### Elementor

If Elementor is installed:

1. Edit the page with Elementor.
2. Open the **Booking Form** widget category (or search for “booking”).
3. Drag the **Booking Form** widget onto the page.
4. **Content** tab: pre-select service and/or staff.
5. **Style** tab: colors, typography, buttons, service cards, inputs.

Form colors can also be managed globally from **Settings → Appearance**.

---

## Deposits, payments & terms

All deposit/payment features are gated by **Settings → Payment → «نمایش و دریافت پیش‌پرداخت»**. With it off, the booking form behaves as before.

- **Per-service deposit** — field in the service editor; quick-edit on cards; defaults to **50% of price** (rounded to 1,000) when empty (`mr_booking_default_deposit_ratio` filter).
- **Payment step (step 6)** — when deposits are on and total deposit > 0: tip (optional), **Pay with wallet** and/or **ZarinPal** online payment.
- **ZarinPal** (v4 REST, IRT) — merchant ID, sandbox, shared callback for deposits and wallet top-ups.
- **Terms & conditions** — consent link opens a modal; required checkbox (editable copy in Payment settings).

Refunds: when a paid booking is cancelled/rejected/deleted, deposit + tip is credited to the wallet once (filter `mr_booking_refund_deposit`).

---

## Customer wallet

- Ledger table `mr_wallet_transactions` (balance = SUM).
- Customer panel → **Wallet** tab: balance, history, top-up form (amount chips + formatted input; min amount in Payment settings).
- Admin → **Customers**: balance column + inline credit/debit on the list; full panel on the customer detail page.
- Wallet balance is also shown on appointments, dashboard tables, accounting, and phone/walk-in search results.
- Appointments list has a **Payment** column (status, method badge, paid amount).

---

## Walk-in & phone booking

### Phone booking

**MR Booking → Phone booking** — search customer, pick service/staff/slot, set status. Same slot engine as the public form.

### Walk-in

**MR Booking → Walk-in** (`mr_booking_walkin`) — for customers already on site. Create booking with `source = walkin` (start = now). Does **not** block online slots. Optional editable per-service amounts; no customer SMS by default (`mr_booking_notify_walkin`).

---

## Accounting

**MR Booking → Accounting** (`mr_booking_accounting`):

- Date presets (Jalali-aware when admin calendar is Jalali), custom range, group by day/month
- Filters: service, staff, source (online / phone / walk-in), status
- KPI cards, breakdowns, ledger, CSV export
- Revenue from amounts stored on each booking at creation time

---

## Appointments & slot management

From **Appointments** you can:

- **Approve** / **Reject** / **Cancel** / **Delete** (slots freed when applicable)
- **Edit the customer profile** from appointment details
- See **wallet balance** and **payment method** on the list
- Keep list **filters and sort** after refresh or tab changes

Pending bookings show a **badge count** on the Appointments menu. Lists default to **registration time** (newest first).

---

## Notifications

### Customer notifications

| Event         | When                                       |
| ------------- | ------------------------------------------ |
| **Created**   | Right after a booking is submitted         |
| **Confirmed** | After admin approves                       |
| **Cancelled** | Rejected, cancelled, or deleted (upcoming) |
| **Reminder**  | Before appointment time                    |
| **Birthday**  | Customer birthday SMS (if configured)      |

Configure templates under **Notifications**. Empty templates fall back to defaults. All sends are logged in `notification_logs`.

### Admin notifications

New bookings notify admins by email (and SMS if enabled). Recipients: **Settings → Email** / **SMS**.

### OTP delivery (Kavenegar)

For reliable OTP codes, set **Settings → SMS → «نام الگوی کد ورود»** to a Kavenegar Verify/Lookup template with `%token%`. Without a template name, plain `sms/send` is used. Failed OTP attempts are logged (code masked).

---

## User roles & access

| Role                                         | Access                                                    |
| -------------------------------------------- | --------------------------------------------------------- |
| **Receptionist** (`mr_booking_receptionist`) | Appointments, phone booking, walk-in, customers, calendar |
| **Booking manager** (`mr_booking_manager`)   | All plugin sections including **Settings**, accounting    |

Booking-only users see only the MR Booking menu. Fine-grained `mr_booking_*` capabilities can be customized with **User Role Editor**. See **Settings → Access**.

---

## Live admin alerts

On any MR Booking admin page: poll for new bookings, toast + optional sound, click through to appointments. Interval under **Settings → Dashboard** (0 = off).

---

## Appearance & color themes

**Settings → Appearance** — Dark/Light presets, brand/button/service-card/calendar colors, loading spinner.

**Settings → Account → «رنگ‌بندی پنل مشتری»**:

- **Admin green** (default) — matches the admin panel
- **Match booking form** — uses the form colour preset

The account dashboard (and embedded wizard) follows the chosen scheme via `--mrb-*` CSS variables.

---

## Dashboard widget

Enable the WP dashboard activity widget under **Settings → Dashboard**. The MR Booking dashboard lists today’s bookings and recent registrations (newest first).

---

## Admin menu

- Dashboard
- Appointments (pending badge, payment column, wallet on customer cells)
- Phone booking
- Walk-in
- Calendar (day / week / month)
- Customers (wallet column, credit/debit, CSV, birthdays)
- Services (quick price / deposit edit, thousands separators)
- Staff
- Working hours
- Holidays and special dates
- Notifications
- Reports
- Accounting
- Settings (general, calendar, rules, appearance, texts, **payment**, **account**, SMS, email, access)

---

## Calendar

Jalali only / Gregorian only / both. Dates are stored as Gregorian and converted for display.

---

## SMS

Built-in providers:

- **Kavenegar** — connection test, credit in settings & admin bar, OTP Verify/Lookup template
- Melipayamak
- SMS.ir

```php
add_filter( 'mr_booking_sms_providers', function( $providers ) {
    // $providers['myprovider'] = new My_Provider();
    return $providers;
});
```

---

## REST API

Base URL: `/wp-json/mr-booking/v1/`

| Endpoint                   | Method | Auth           | Description                        |
| -------------------------- | ------ | -------------- | ---------------------------------- |
| `/services`                | GET    | Public         | List services                      |
| `/staff`                   | GET    | Public         | List staff                         |
| `/availability/month`      | GET    | Public         | Monthly availability               |
| `/availability/slots`      | GET    | Public         | Slots for a day                    |
| `/book`                    | POST   | Public (nonce) | Create booking                     |
| `/settings/public`         | GET    | Public         | Public frontend settings           |
| `/payment/callback`        | GET    | Public         | ZarinPal return (deposit / top-up) |
| `/auth/request-otp`        | POST   | Public\*       | Request OTP (\*login enabled)      |
| `/auth/verify-otp`         | POST   | Public\*       | Verify OTP                         |
| `/auth/complete-profile`   | POST   | Public\*       | Finish registration                |
| `/auth/logout`             | POST   | Logged in      | Logout                             |
| `/me`                      | GET    | Customer       | Current customer                   |
| `/me/bookings`             | GET    | Customer       | My bookings                        |
| `/me/bookings/{id}/cancel` | POST   | Customer       | Cancel booking                     |
| `/me/wallet`               | GET    | Customer       | Balance + ledger                   |
| `/me/wallet/topup`         | POST   | Customer       | Start wallet top-up payment        |
| `/admin/customers/search`  | GET    | Admin          | Search customers                   |
| `/admin/book`              | POST   | Admin          | Phone booking                      |
| `/admin/walkin`            | POST   | Admin          | Walk-in booking                    |

---

## Important hooks

```php
do_action( 'mr_booking_booking_created', $booking_id, $payload );
do_action( 'mr_booking_booking_status_changed', $booking_id, $new_status, $old_status );
do_action( 'mr_booking_booking_deleted', $booking_id, $booking_object );
do_action( 'mr_booking_deposit_paid', $booking_id, $method, $amount );
do_action( 'mr_booking_wallet_changed', $txn_id, $customer_id, $amount, $type );
do_action( 'mr_booking_sms_sent', $to, $message, $result );
do_action( 'mr_booking_email_sent', $to, $subject, $sent );

apply_filters( 'mr_booking_brand_name', 'Reza Ansarirad' );
apply_filters( 'mr_booking_brand_url', 'https://rezaansarirad.ir' );
apply_filters( 'mr_booking_default_deposit_ratio', 0.5 );
apply_filters( 'mr_booking_refund_deposit', true, $booking, $reason );
```

---

## Database tables

Prefix: `{wp_prefix}mr_`

- `services` (includes `deposit`)
- `staff`
- `staff_services`
- `customers`
- `bookings` (deposit / tip / payment fields)
- `booking_services`
- `working_hours`
- `holidays`
- `special_dates`
- `notification_logs`
- `wallet_transactions`

DB schema version: **1.3.0**

---

## Notification template variables

`{customer_name}` `{customer_phone}` `{service_name}` `{booking_date}` `{booking_time}` `{staff_name}` `{booking_id}` `{business_name}`

---

## Privacy

The plugin integrates with WordPress personal data export and erasure tools.

---

## Development & release zip

Do **not** upload the `.git` folder to WordPress.

```bash
cd plugin
rsync -a --exclude='.git' --exclude='.DS_Store' --exclude='__MACOSX' --exclude='*.zip' \
  mr-booking/ /tmp/mr-booking-release/mr-booking/
cd /tmp/mr-booking-release && zip -r mr-booking.zip mr-booking
```

See `.distignore` for distribution excludes.

---

## Changelog (recent)

See [CHANGELOG.md](CHANGELOG.md) for full detail.

### 2.5.x

- **2.5.2** — Mobile spacing for the embedded booking wizard and account dashboard
- **2.5.1** — Account colour scheme: admin green (default) or match booking-form preset
- **2.5.0** — Wallet balance everywhere in admin; payment method column on appointments

### 2.4.x

- **2.4.3** — Fix wallet panel on Customers detail page
- **2.4.2** — Account dashboard colours from `--mrb-*` settings
- **2.4.1** — Form polish: profile initial, wider wizard, 50% deposit default, terms modal
- **2.4.0** — Account dashboard redesign, embedded booking tab, always-visible wallet top-up

### 2.3.x – 2.0.0

- **2.3.1** — Kavenegar OTP Verify API, RTL phone `bdi`, account page design
- **2.3.0** — Wallet top-up via ZarinPal
- **2.2.0** — Deposits, payment step, ZarinPal, wallet, terms
- **2.1.0** — Quick-edit price; thousands separators on money fields
- **2.0.0** — Branding, accounting, walk-in bookings, CSV export fix
