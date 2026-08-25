# MR Booking 2.1.0 — customer accounts (OTP login)

## Admin setting
Settings → **حساب کاربری** (new tab):
- Login mode: **غیرفعال** (default — nothing changes) / **اختیاری** / **الزامی**. Switchable at any time.
- Account page (select the page that holds `[mr_booking_account]`).
- Customer cancel window in minutes (default 30) + "allow cancellation" toggle.
- OTP SMS text (`{otp_code}`, `{business_name}`, `{minutes}`). Requires SMS to be enabled.

## How it works
- **OTP**: 5-digit code via the configured SMS provider. Stored as an HMAC in a transient (5-min TTL);
  5 wrong attempts invalidate it; 60 s resend cooldown; max 5 codes/phone/hour and 20/IP/hour.
  The API never reveals whether a phone is registered.
- **Identity**: the existing customer row (keyed by phone) is linked to a WordPress user with the new
  role `mr_booking_customer` (no capabilities; wp-admin and the admin bar are blocked, login redirects to
  the account page). Existing customers are linked on first login; new phones complete name after OTP.
- **Booking form**: logged-in customers are pre-filled and the phone is locked. In *optional* mode a
  "ورود با کد پیامکی" prompt sits above the fields; in *required* mode the fields are hidden until login.
  The server always takes phone + customer id from the session, never from the request.
- **Account page** `[mr_booking_account]`: upcoming/past bookings with status, staff, price (if public);
  cancel when status is pending/confirmed and ≥ N minutes remain; profile editor (name, DOB, email —
  phone read-only). Admin can still change a customer's phone from the customers page.

## REST (namespace mr-booking/v1)
`POST /auth/request-otp`, `POST /auth/verify-otp`, `POST /auth/complete-profile`, `POST /auth/logout`,
`GET|POST /me`, `GET /me/bookings`, `POST /me/bookings/{id}/cancel`.

## Hooks
`mr_booking_otp_send` (short-circuit delivery), `mr_booking_otp_client_ip`, `mr_booking_account_url`,
`mr_booking_customer_cancelled`, `mr_booking_notify_walkin`, `mr_booking_accounting_revenue_statuses`.

## Files added
includes/Auth/Customer_Auth.php, includes/Auth/OTP_Service.php, includes/API/Account_Controller.php,
includes/Frontend/Account_Shortcode.php, templates/frontend/account.php, templates/partials/otp-login.php,
assets/js/otp-login.js, assets/js/account.js

## Files modified
mr-booking.php, includes/Plugin.php, includes/Settings/Settings.php, includes/Admin/Pages/Settings_Page.php,
includes/Bookings/Booking_Service.php, includes/Customers/Customer_Repository.php, includes/Roles/Roles.php,
includes/Frontend/Assets.php, templates/admin/settings.php, templates/admin/customers.php,
templates/frontend/booking-form.php, assets/js/frontend.js, assets/css/frontend.css, assets/css/admin.css,
uninstall.php
