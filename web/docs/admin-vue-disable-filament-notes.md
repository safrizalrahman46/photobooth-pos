# Admin Vue Plan - Disable Filament and Inventory Tracking

## Context
- Target product direction: admin panel runs on Vue only.
- Filament should be disabled as runtime admin UI.
- Existing Vue logic that already works should be kept.
- Main focus now: close bug gaps, especially authorization and physical add-on stock tracking.

## Current Snapshot
- Admin routes are mounted under `/admin/*` and rendered by Vue dashboard shell.
- Filament package and code still exist in repository, but default runtime driver is Vue.
- Most backend logic has been moved to `Admin*Controller` and `Admin*Service` classes.
- Main unresolved gap is parity on authorization and inventory flow.

## Architecture Decision
1. Vue is the only active admin UI.
2. Filament should be treated as non-active and prepared for hard-disable after safety checks.
3. Backend authorization becomes source of truth (not sidebar visibility).
4. Add-ons split into:
   - `Add-ons` module for master data.
   - `Stock` module for inventory operations and movement history.

## Authorization Partition (Filament-style parity for Vue routes)

### booking.view
- `GET /admin`
- `GET /admin/bookings`
- `GET /admin/dashboard-data`
- `GET /admin/bookings/{booking}/transfer-proof`

### booking.manage
- `POST /admin/bookings`
- `PUT /admin/bookings/{booking}`
- `DELETE /admin/bookings/{booking}`
- `POST /admin/bookings/{booking}/confirm`
- `POST /admin/bookings/{booking}/decline`

### queue.view
- `GET /admin/queue-tickets`
- `GET /admin/queue-data`

### queue.manage
- `POST /admin/queue/call-next`
- `POST /admin/queue/check-in`
- `POST /admin/queue/walk-in`
- `PATCH /admin/queue/{queueTicket}/status`

### transaction.view
- `GET /admin/transactions`
- `GET /admin/payments`
- `GET /admin/payments-data`

### payment.manage
- `POST /admin/payments/{transaction}/store`
- `POST /admin/bookings/{booking}/confirm-payment`

### catalog.manage
- `GET /admin/packages`
- `GET /admin/packages-data`
- `POST /admin/packages`
- `PUT /admin/packages/{package}`
- `DELETE /admin/packages/{package}`
- `GET /admin/add-ons`
- `GET /admin/add-ons-data`
- `POST /admin/add-ons`
- `PUT /admin/add-ons/{addOn}`
- `DELETE /admin/add-ons/{addOn}`
- `POST /admin/add-ons/{addOn}/stock-movement`
- `GET /admin/design-catalogs`
- `GET /admin/designs-data`
- `POST /admin/designs`
- `PUT /admin/designs/{designCatalog}`
- `DELETE /admin/designs/{designCatalog}`

### settings.manage
- `GET /admin/settings`
- `GET /admin/settings-data`
- `PUT /admin/settings/default-branch`
- `POST /admin/settings/branches`
- `PUT /admin/settings/branches/{branch}`
- `DELETE /admin/settings/branches/{branch}`
- `GET /admin/branches`
- `GET /admin/branches-data`
- `POST /admin/branches`
- `PUT /admin/branches/{branch}`
- `DELETE /admin/branches/{branch}`
- `GET /admin/time-slots`
- `GET /admin/time-slots-data`
- `POST /admin/time-slots`
- `PUT /admin/time-slots/{timeSlot}`
- `DELETE /admin/time-slots/{timeSlot}`
- `POST /admin/time-slots/generate`
- `POST /admin/time-slots/bulk-bookable`
- `GET /admin/blackout-dates`
- `GET /admin/blackout-dates-data`
- `POST /admin/blackout-dates`
- `PUT /admin/blackout-dates/{blackoutDate}`
- `DELETE /admin/blackout-dates/{blackoutDate}`
- `GET /admin/printer-settings`
- `GET /admin/printer-settings-data`
- `POST /admin/printer-settings`
- `PUT /admin/printer-settings/{printerSetting}`
- `DELETE /admin/printer-settings/{printerSetting}`
- `PATCH /admin/printer-settings/{printerSetting}/default`
- `GET /admin/app-settings`
- `GET /admin/app-settings-data`
- `PUT /admin/app-settings/{group}`

### report.view
- `GET /admin/reports`
- `GET /admin/dashboard-report`

### user.manage
- `GET /admin/users`
- `GET /admin/users-data`
- `POST /admin/users`
- `PUT /admin/users/{user}`
- `DELETE /admin/users/{user}`

## High Priority Bug Gaps
1. Most `/admin/*` endpoints still depend on `auth` only, not granular `can:*`.
2. `QueueCallNextRequest::authorize()` currently allows all authenticated users.
3. Admin login flow still needs explicit `is_active` guard in Vue auth flow.
4. User module policy is not yet consistent between read and write paths.
5. Physical add-on stock is not enforced when booking uses add-ons.
6. Public booking payload still carries add-on price from client and must not be trusted.

## Inventory Problem Statement (Add-ons)
- Users are confused because stock in/out is mixed into Add-ons master page.
- Current UI has stock movement action, but no dedicated stock ledger module.
- Physical add-ons (keychain, paper, props, etc.) need operational tracking that is clear and auditable.

## Agreed UX Direction: Add Sidebar `Stock`
- Keep `Add-ons` for master catalog only.
- Add new `Stock` module for inventory operation and movement history.
- Suggested menu position: near Add-ons, above Designs.

## Stock Module - Implementation Scope

### 1) Navigation and module wiring
- Add nav item id: `stock` in admin UI config.
- Add route page: `GET /admin/stock` using same Vue dashboard shell.
- Extend Vue module switch in `AdminDashboardApp.vue` with `StockPage`.

### 2) Stock API endpoints
- `GET /admin/stock-data`
  - returns physical add-on rows, low-stock summary, movement ledger (paginated/filterable).
- `POST /admin/stock/{addOn}/movement`
  - can alias existing movement action or reuse current add-on stock movement endpoint.

### 3) Stock page UI
- Create `resources/js/admin/pages/StockPage.vue`.
- Minimum features:
  - summary cards: physical items, low stock, out of stock.
  - table of physical add-ons with current stock and threshold status.
  - stock in/out form with notes.
  - movement history table with filters (date, item, type, actor).

### 4) Inventory hardening in booking flow
- Add server-side guard for physical add-ons:
  - validate qty against `available_stock`.
  - use row lock (`lockForUpdate`) during critical updates.
- Define stock deduction point (recommended): when booking payment is confirmed.
- Record automatic stock movement with reference to booking code.

### 5) Price and payload integrity
- Do not trust add-on `price` from client payload.
- Resolve add-on price from DB by add-on id and active package scope.

## Planned File Touch List

### Backend
- `routes/web.php`
- `app/Http/Controllers/Web/AdminDashboardController.php`
- `app/Http/Controllers/Web/AdminStockController.php` (new)
- `app/Services/AdminStockService.php` (new)
- `app/Services/AdminBookingManagementService.php`
- `app/Services/BookingService.php`
- `app/Http/Requests/QueueCallNextRequest.php`
- `app/Http/Controllers/Web/AdminAuthController.php`
- `app/Services/AppSettingService.php`

### Frontend
- `resources/js/admin/AdminDashboardApp.vue`
- `resources/js/admin/pages/StockPage.vue` (new)
- `resources/js/admin/pages/AddOnsPage.vue` (quick-link/label adjustment only, optional)
- `resources/js/admin/pages/BookingsPage.vue` (stock-aware add-on picker hints)
- `resources/js/booking/BookingApp.vue` (stock-aware addon UX, optional)

## Filament Disable Strategy

### Phase 1 (safe hardening first)
1. Apply full permission partition in Vue routes/controllers.
2. Fix high-risk request authorization gaps.
3. Add inventory safety guards.
4. Add tests for role-based 403/200 and stock rules.

### Phase 2 (functional parity done)
1. Confirm all admin workflows run only on Vue modules.
2. Keep any useful Vue enhancements that exceed old Filament behavior.

### Phase 3 (hard-disable Filament)
1. Stop registering Filament provider in runtime flow.
2. Remove stale Filament-specific toggles/middleware as needed.
3. Remove Filament dependencies when no runtime reference remains.

## Verification Checklist
- Authorization matrix enforced for each admin endpoint.
- Inactive users cannot log in to admin.
- Queue call-next is restricted by permission.
- Physical add-ons cannot oversell.
- Stock movement history is visible and auditable.
- Public add-on total cannot be tampered from client payload.
- Admin navigation and module route for `stock` work on desktop and mobile.

## Notes
- Backend authorization remains the final gate.
- Sidebar/menu visibility is UX only and must not replace backend policy.
- Keep migration incremental to avoid breaking production behavior.
