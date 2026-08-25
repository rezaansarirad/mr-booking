# MR Booking — changelog

## 2.5.2 — Mobile spacing for the wizard inside the dashboard

- Fix: `.mrb--account .mrb__body { padding: 0 }` also matched the embedded wizard's body, so on mobile the
  inputs ran edge-to-edge. The reset is now scoped to the dashboard's own shell only.
- Embedded wizard: 16px side gutters, 16px between fields, 48px inputs/buttons with 12px radius, tidier
  step nav, framed «رزرو برای» group, error banner inset, sticky footer with safe-area padding, border and
  rounded bottom corners matching the card.
- Dashboard on phones: tighter outer padding and card padding, hero icon shrunk and title inset so they
  don't overlap, stat cards in a 2-column grid with the primary card full-width, sidebar identity as
  avatar + name/phone side by side, smaller logout.

## 2.5.1 — Dashboard colour scheme: admin green

- The dashboard was correctly following the *booking-form* preset (default: dark/gold). The green the
  admin panel uses was hard-coded in `admin.css` and never existed as frontend variables.
- New `Color_Presets::admin_palette()` expresses the admin panel's green palette as `--mrb-*` tokens, and
  `Assets::account_css_vars()` scopes them to `.mrb--account` and to the wizard embedded inside it (higher
  specificity than the form preset's `.mrb{}` rule, so the embedded form matches the dashboard).
- New setting Settings → حساب کاربری → «رنگ‌بندی پنل مشتری»: **سبز — همرنگ پنل مدیریت** (default) or
  **همرنگ فرم رزرو** (`account_color_scheme`).
- Fix: the form preset's inline background (gold gradient) was painting the embedded wizard inside the
  dashboard card; it is now transparent there regardless of scheme.

## 2.5.0 — Wallet balance everywhere, payment method in bookings

- **Customers list:** new «کیف پول» column with a balance chip per customer and a «تغییر موجودی» button that
  opens an inline dialog (credit / debit, formatted amount, note) — no need to open the customer first.
  Posts to the existing `mr_booking_wallet_adjust` handler (`redirect=list` returns to the list and flashes
  the row). Per-customer credit/debit on the detail page is unchanged.
- **Balance shown wherever a customer appears:** appointments list (customer cell) and detail (customer
  card, with a «تغییر موجودی» link that jumps to the wallet panel), dashboard tables, accounting ledger,
  and the phone-booking / walk-in customer search results (label now ends with «· کیف پول: …»).
  `Booking_Repository::query()` selects `wallet_balance` per row (one correlated subquery, no N+1);
  `Wallet_Repository::balances()` fetches many customers in one query.
- **Bookings show how the deposit was paid:** new «پرداخت» column in the appointments list with the payment
  status badge, method (🟣 کیف پول / 🔵 درگاه زرین‌پال, icon + label) and paid amount; the detail page already
  listed method and gateway reference.

## 2.4.3 — Fix: wallet panel on the Customers page

- The admin wallet panel (balance, credit/debit form, ledger) had been inserted into the customer-*list*
  branch of `templates/admin/customers.php`, where `$customer` is null. That triggered a PHP warning on
  line 149 and left a stray `" />` on the page. The panel now lives in the customer-detail branch, directly
  under the profile panel, and only renders when a customer is being viewed.

## 2.4.2 — Dashboard follows the plugin colour scheme

- Every colour in the account dashboard now comes from the settings-driven `--mrb-*` variables
  (Settings → ظاهر): sidebar/hero/primary stat use `--mrb-primary` → `--mrb-secondary` gradients, text
  on those surfaces uses `--mrb-btn-text`, cards use `--mrb-card`/`--mrb-border`, shadows and backdrops
  are tinted from `--mrb-primary`/`--mrb-text`, and the page wrapper inherits the configured background
  (including the optional gradient). No hard-coded hex/rgba values remain in the dashboard styles —
  switching a colour preset restyles the dashboard instantly.

## 2.4.1 — Booking form polish

- **Profile icon** in the booking form's logged-in chip now shows the customer's initial on a gradient
  disc (the CSS-drawn glyph is gone; a neutral silhouette only appears if the name is empty).
- **Wizard width / step 6:** shell max-width 720 → 880px; the step nav no longer scrolls — steps share
  the width equally on desktop (labels ellipsise if needed) so all six steps are always visible.
- **Deposit defaults to 50 % of the price** (rounded to 1,000): applied when a service is saved with an
  empty deposit, kept in sync when the price is quick-edited while the deposit was never customised, filled
  live in the editor (with a «۵۰٪ قیمت» button), and back-filled once for existing priced services that had
  a zero deposit. Ratio is filterable (`mr_booking_default_deposit_ratio`).
- **Terms & conditions** are no longer an accordion: the title inside the consent sentence is a link that
  opens a modal with the full text (close via ×, «بستن», backdrop click or Escape; «خواندم و می‌پذیرم»
  ticks the checkbox). Focus returns to the link on close.

## 2.4.0 — Account dashboard redesign, embedded booking, wallet top-up form

- **Dashboard layout** (`templates/frontend/account.php` rebuilt): right sidebar with brand block
  (business name + «پنل کاربری»), large avatar initial, name, phone, icon nav (نوبت‌های من / رزرو نوبت /
  کیف پول / پروفایل) and logout; main column with a gradient hero (badge, «سلام {name}», lead), a row of
  stat cards (upcoming bookings — highlighted, wallet balance, new booking — all tappable), and one card per
  section. Mobile: sidebar stacks on top with a horizontal nav scroller. All ids/data-attributes the JS
  depends on are unchanged; the profile form is untouched.
- **Booking inside the dashboard:** new «رزرو نوبت» tab renders the full booking wizard via
  `[mr_booking embed="account"]` (new `embed` shortcode attribute → `.mrb--embedded`: no header, auth
  chip hidden, flush shell). On success the form emits `mrb:booking-created`; the dashboard reloads the
  lists and balance. Gateway returns (`?mrb_payment=…`) open the booking tab. Login/logout on a page that
  embeds the form reloads so the wizard gets the correct session state. Toggle in Settings → حساب کاربری
  → «رزرو نوبت داخل پنل مشتری» (`account_embed_booking`, default on).
- **Wallet top-up form now always visible** when the wallet tab exists (it was gated behind the gateway
  being configured). Amount input with numeric validation and live thousands separators; chips for
  **۲۰۰٬۰۰۰ / ۵۰۰٬۰۰۰ / ۱٬۰۰۰٬۰۰۰** تومان that fill the input (`aria-pressed`, stay in sync with manual
  edits); custom amounts allowed; minimum enforced client- and server-side. If the gateway is not configured
  the button is disabled with an explanatory note.

## 2.3.1 — OTP delivery, phone display, account page design

- **OTP not arriving (Kavenegar):** codes were sent via plain `sms/send`, which the API accepts but
  operators filter for code-style messages from shared lines. New `Kavenegar_Provider::send_otp()`
  uses the **Verify / Lookup** template API when Settings → پیامک → «نام الگوی کد ورود» is filled
  (create a template with `%token%` in the Kavenegar panel, wait for approval, enter its name).
  `SMS_Manager::send_otp()` picks it automatically and falls back to plain send when no template is set.
  Every OTP attempt is now logged to `notification_logs` (subject `otp`, code masked) with the provider
  response; site managers also see the provider error inline when a send fails.
- **Phone shown reversed:** masked numbers like `0903•••4799` are two digit groups around neutral
  characters, so RTL bidi swapped them. All phone displays (OTP "sent to", account header, booking-form
  chip, booking codes) are now wrapped in `<bdi dir="ltr">` / `unicode-bidi: isolate`.
- **Account page design:** gradient welcome card with avatar initial, name, phone, quick stats
  (upcoming bookings, wallet balance — tappable to open the wallet tab), floating logout; refined tab
  pills; booking cards with a status-coloured edge and hover lift; dashed empty states; section
  titles with rules. All colours come from the existing theme variables.

## 2.3.0 — Wallet top-up via gateway

- Customer panel → «کیف پول»: new top-up form (suggested-amount chips + formatted amount field).
  `POST /me/wallet/topup` starts a ZarinPal payment; the callback verifies it and credits the wallet
  (`type = topup`, gateway ref stored in the note), then returns to the account page with
  `?mrb_wallet=success|failed|cancelled`, which opens the wallet tab and shows the result.
- Only shown when both wallet payment and the online gateway are enabled. New setting
  Settings → پرداخت → «حداقل مبلغ افزایش موجودی» (`wallet_topup_min`, default 10,000).
- `Payment_Service::gateway_request()` is now shared by deposits and top-ups; the payment transient
  carries a `type` so the single callback route serves both.
- The previously requested OTP login/registration, account dashboard (bookings, cancel until N minutes
  before — default 30), locked verified phone, pre-filled booking form for logged-in customers, and the
  admin login-mode switch (off / optional / required) were already present in the codebase and are
  unchanged.

## 2.2.0 — Deposits, payment step, ZarinPal, wallet, terms

All deposit/payment/wallet features are gated by **Settings → Payment → «نمایش و دریافت پیش‌پرداخت»**.
With it off, the booking form behaves exactly as before.

### Deposit per service
- New `deposit` column on services. Field in the service editor (under the price box) and a
  **«ویرایش پیش‌پرداخت»** quick-edit button on each card (same dialog as quick price, `field=deposit`).
- When deposits are enabled, the public form shows the deposit under each service card and in the
  summary; the main price is never sent to the browser (`/services` omits price fields in deposit mode).
- Services list shows a purple deposit badge in deposit mode.

### Payment step (step 6)
- Shown only when deposits are on and the selected services have a deposit > 0 (otherwise the
  confirm step submits directly, as before). Lists per-service deposits, an optional formatted **tip**
  field (toggle in settings), the payable total, and the payment method:
  **Pay with wallet** (logged-in customers, needs sufficient balance) / **Online payment** (ZarinPal).
  Each method has its own enable toggle.
- Deposit and tip are computed/sanitised server-side (`Booking_Service::create`); the visitor cannot
  alter the deposit. Booking rows store `deposit_amount`, `tip_amount`, `paid_amount`, `payment_status`
  (none/unpaid/paid/refunded/failed), `payment_method`, `payment_ref`, `terms_accepted`.

### ZarinPal (v4 REST, IRT)
- Settings → Payment: merchant ID, sandbox toggle, callback URL shown. `Payment_Service` requests a
  payment, redirects the visitor, verifies on return (`GET mr-booking/v1/payment/callback`) and sends
  them back to the booking page with `?mrb_payment=success|failed|cancelled|invalid`.
- Failed/cancelled payments mark the booking cancelled (slot released, no customer SMS); a booking
  whose gateway request cannot even be created is deleted so the visitor can retry.
- Online bookings are created *before* the gateway redirect (status pending / payment unpaid) so the
  slot is held during payment.

### Wallet
- New table `mr_wallet_transactions` (append-only ledger; balance = SUM). `Wallet_Repository`.
- Customer panel gets a **«کیف پول»** tab (balance + transactions) via `GET /me/wallet`.
- Admin → Customers → customer page: wallet panel with balance, ledger, and a credit/debit form
  (`admin_post_mr_booking_wallet_adjust`, capability: customers).
- **Refund on cancellation**: when a paid booking becomes cancelled/rejected (customer cancel within
  the existing cancellation policy, or admin action) or is deleted, deposit + tip is credited back to the
  wallet once (idempotent). Filter `mr_booking_refund_deposit` to change the rule.
- Appointment detail shows deposit / tip / payment status / method / gateway ref. Accounting gains a
  «پیش‌پرداخت دریافتی» KPI.

### Terms & conditions (step 5)
- Collapsible terms text + required checkbox under the booking summary. Editable in Settings → Payment
  (title, checkbox label, text; «پذیرش الزامی» toggle). Enforced client- and server-side.
  Note: the checkbox is required by default after this update.

### Other
- `Settings::init` now runs the schema upgrade on `init` (not only `admin_init`) so a frontend booking
  right after updating cannot hit missing columns.
- New files: includes/Payments/Payment_Service.php, includes/Wallet/Wallet_Repository.php.
- DB version 1.3.0.

## 2.1.0

### Quick Edit Price (Services)
- Every service card has a new **«ویرایش قیمت»** button that opens a small dialog: service name, one amount
  field, live preview in Persian digits, Enter to save. Focus returns to the button on close.
- Saving redirects back with a success notice naming the service and its new price; the card flashes briefly.
- Amount 0 / empty marks the service as «بدون قیمت»; any amount above 0 marks it as priced.
- New `admin_post_mr_booking_quick_price` handler (capability: services). Existing bookings keep the amount
  stored at their creation time.

### Thousands separators on every price field
- New `assets/js/money-input.js` (`window.mrbMoneyInput`) formats any `<input data-mrb-money>` while typing:
  digits grouped in threes with `,`, Persian/Arabic digits normalised, caret position preserved, paste handled.
- Applied to: quick-price dialog, service editor price, walk-in per-service amounts, setup-wizard service prices.
- Server side, `Helpers::parse_money()` accepts the formatted value (commas, Persian digits, spaces) and
  `Helpers::format_money_input()` renders stored prices for inputs. Services save, quick price, walk-in and
  the setup wizard all use it — a pasted "۱٬۵۰۰٬۰۰۰" or "1,500,000" is stored as 1500000.

Files added: assets/js/money-input.js
Files modified: mr-booking.php, includes/Helpers.php, includes/Admin/Admin.php, includes/Admin/Setup_Wizard.php,
includes/Admin/Pages/Services.php, includes/Roles/Capabilities.php, templates/admin/services.php,
templates/admin/setup-wizard.php, templates/admin/walkin-booking.php, assets/js/admin.js, assets/js/setup-wizard.js,
assets/js/walkin-booking.js, assets/css/admin.css

## 2.0.0

## 1. Branding
- New `Helpers::brand_name()` / `brand_url()` / `brand_link()` (filters: `mr_booking_brand_name`, `mr_booking_brand_url`).
- "MR Booking" eyebrow label on every admin page, the setup wizard, the WP dashboard widget title and the public booking-form header now read **Reza Ansarirad** and link to https://rezaansarirad.ir (new tab).
- Premium "hide branding" option copy updated; it still hides the brand link on the public form.

## 2. Bug fix — CSV export fatal error
- `includes/Export/Exporter.php` was missing `use` imports for `Customer_Repository` and `Booking_Repository`
  (PHP resolved them inside `MRBooking\Export`). Both the customers and bookings exports are fixed.

## 3. Accounting
- Per-service amount and the "show price to customer" toggle already existed
  (Services → price box; Settings → General → «نمایش قیمت به مشتری»). Unchanged.
- New menu **حسابداری** (`mr-booking-accounting`, capability `mr_booking_accounting`; granted to
  Administrator and «مدیر رزرو» automatically).
- Filters: quick presets (today / yesterday / last 7 days / this month / last month / this year — month
  presets follow the Jalali calendar when the admin calendar is Jalali), custom date range, group by
  day or month, service, staff, source (online/phone/walk-in), status (default = confirmed + completed).
- KPI cards, daily/monthly breakdown, per-service, per-source, per-staff, ledger of bookings, CSV export.
- Revenue is read from the amounts stored on each booking at creation, so changing a service's price
  later never rewrites history. Filter `mr_booking_accounting_revenue_statuses` controls counted statuses.

## 4. Walk-in bookings
- New menu **مراجعه حضوری** (`mr-booking-walkin`, capability `mr_booking_walkin`; granted to
  Administrator, «مدیر رزرو» and «منشی رزرو»).
- Search an existing customer or enter first/last name + mobile (mobile is required because the
  customers table is uniquely keyed by phone). Pick service(s); each shows its configured price in an
  editable field; running total; optional staff, note, status (default «انجام شده»).
- Stored as a normal booking with `source = 'walkin'`, start = now, end = now + service duration.
- `Booking_Repository::overlapping()` / `for_date()` exclude walk-ins, so they never block online slots.
- No customer/admin notifications for walk-ins (filter `mr_booking_notify_walkin` to enable).
- Appointments list/detail show a «حضوری» badge and a new "Amount" card with per-service lines.
- New REST route `POST mr-booking/v1/admin/walkin`.

## Files added
- includes/Accounting/Accounting_Repository.php
- includes/Admin/Pages/Accounting.php
- includes/Admin/Pages/Walkin_Booking.php
- templates/admin/accounting.php
- templates/admin/walkin-booking.php
- assets/js/walkin-booking.js

## Files modified
- mr-booking.php (version 2.0.0)
- includes/Helpers.php, includes/Admin/Admin.php, includes/Admin/Dashboard_Widget.php
- includes/API/Rest_Controller.php, includes/Bookings/Booking_Service.php, includes/Bookings/Booking_Repository.php
- includes/Export/Exporter.php, includes/Notifications/Notification_Service.php
- includes/Roles/Capabilities.php, includes/Roles/Roles.php
- templates/admin/appointments.php, templates/admin/settings.php, templates/admin/setup-wizard.php,
  all other templates/admin/*.php (eyebrow label only), templates/frontend/booking-form.php
- assets/css/admin.css, assets/css/frontend.css
