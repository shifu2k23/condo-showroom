# Plan: Path-Based Multi-Tenancy (Single DB) for Laravel + Livewire

## Approach
Introduce tenancy in layers to minimize churn: schema first, tenant context next, model isolation, route migration, media hardening, super-admin management, then test gates. Roll out in two deploy phases with an explicit backfill command between nullable and NOT NULL constraints.

## Scope
- In:
  - Tenant-prefixed paths: `/t/{tenant:slug}/...` for all tenant-facing public + admin pages
  - Strict tenant isolation via middleware + model scoping + route ownership checks
  - Tenant-isolated image delivery through private storage + streamed route
  - Super-admin control plane outside tenancy at `/super/tenants`
  - Backfill/bootstrapping command and Pest isolation coverage
- Out:
  - Multi-database architecture
  - Rebuilding existing modules/controllers/Livewire pages from scratch
  - Public tenant directory/listing

---

## Migration Order (Safe, Minimal-Churn Sequence)

### M01 - `create_tenants_table`
Create `database/migrations/2026_.._create_tenants_table.php`
- Columns:
  - `id`
  - `name`
  - `slug` (unique)
  - `status` (`ACTIVE|TRIAL|DISABLED`, default `TRIAL`)
  - `trial_ends_at` nullable
  - `created_by` nullable + index
  - timestamps

### M02 - `add_tenant_id_nullable_to_tenant_owned_tables`
Create `database/migrations/2026_.._add_tenant_columns_nullable.php`
- Add nullable `tenant_id` + index to:
  - `users` (nullable long-term for super-admin)
  - `categories`
  - `units`
  - `unit_images`
  - `viewing_requests`
  - `rentals`
  - `maintenance_tickets`
  - `audit_logs`
  - `analytics_snapshots`
  - `renter_sessions`
- Add `users.is_super_admin` (`boolean`, default false, index)
- Add `unit_images.public_id` (`ulid`/`char(26)`, nullable unique initially)

### M03 - `add_tenant_compound_indexes`
Create `database/migrations/2026_.._add_tenant_compound_indexes.php`
- `categories`: `(tenant_id, name)`
- `units`: `(tenant_id, category_id, status, deleted_at)`
- `unit_images`: `(tenant_id, unit_id, sort_order)`
- `viewing_requests`: `(tenant_id, unit_id, status, requested_start_at)`
- `rentals`: `(tenant_id, unit_id, status, starts_at, ends_at)`
- `maintenance_tickets`: `(tenant_id, rental_id, status, created_at)`
- `audit_logs`: `(tenant_id, unit_id, created_at)`

### M04 - Backfill (Command, not migration)
Run `php artisan tenancy:bootstrap-default`
- Create/get default tenant (`slug=default`)
- Backfill null `tenant_id` across all tenant-owned rows
- Generate `unit_images.public_id` where null
- Optional: create tenant admin for default tenant
- Idempotent + chunked updates + transaction boundaries per table

### M05 - `enforce_not_null_and_foreign_keys`
Create `database/migrations/2026_.._enforce_tenant_constraints.php`
- Set `tenant_id` NOT NULL on all tenant-owned tables except super-admin users
  - Keep `users.tenant_id` nullable (for tenantless super-admin)
- Add FKs: `tenant_id -> tenants.id`
- Set `unit_images.public_id` NOT NULL unique

### M06 - Media path hardening (if needed)
If current unit image path convention is mixed, add migration for normalization metadata only (no file move in migration):
- optional `storage_disk` column on `unit_images` (default `private`)

### M07 - Cleanup legacy indexes/constraints
Drop obsolete global-unique constraints that must become tenant-local (example: `categories.name` global unique today).
- Convert to tenant-scoped uniqueness where required:
  - `unique(tenant_id, name)` or `unique(tenant_id, slug)` depending on business rule

---

## 1) Migrations + Backfill Details (with file-level notes)

### New files
- `database/migrations/2026_.._create_tenants_table.php`
- `database/migrations/2026_.._add_tenant_columns_nullable.php`
- `database/migrations/2026_.._add_tenant_compound_indexes.php`
- `database/migrations/2026_.._enforce_tenant_constraints.php`
- `app/Console/Commands/TenancyBootstrapDefault.php`

### Existing files touched
- `database/migrations/2026_02_17_101420_create_categories_table.php` (follow-up migration to relax global unique)
- `database/migrations/2026_02_17_101432_create_units_table.php` (tenant compound index in new migration)
- `database/migrations/2026_02_17_101435_create_unit_images_table.php` (public_id + tenant index in new migration)

---

## 2) Tenant Context: `CurrentTenant` + `TenantManager` + middleware

### New files
- `app/Models/Tenant.php`
- `app/Support/Tenancy/CurrentTenant.php`
- `app/Support/Tenancy/TenantManager.php`
- `app/Http/Middleware/SetTenantFromPath.php`
- `app/Support/Tenancy/TenancyBypass.php` (flag for super/admin bypass when needed)

### Existing files touched
- `bootstrap/app.php`
  - Register middleware aliases:
    - `tenant` => `SetTenantFromPath`
    - `super-admin` => `EnsureSuperAdmin`

### Behavior
- Resolve route model `{tenant:slug}`
- Validate status (`DISABLED` blocked)
- Validate trial status (block or route to “trial expired” page)
- Bind tenant into request lifecycle (`app(CurrentTenant::class)`)
- Share tenant context to views if needed

---

## 3) Global Scoping: `BelongsToTenant` trait

### New files
- `app/Models/Concerns/BelongsToTenant.php`

### Existing files touched (apply trait)
- `app/Models/Category.php`
- `app/Models/Unit.php`
- `app/Models/UnitImage.php`
- `app/Models/ViewingRequest.php`
- `app/Models/Rental.php`
- `app/Models/MaintenanceTicket.php`
- `app/Models/AuditLog.php`
- `app/Models/AnalyticsSnapshot.php`
- `app/Models/RenterSession.php`
- `app/Models/User.php` (conditional scope: only when `tenant_id` present and not super-admin context)

### Trait rules
- Adds global scope `where tenant_id = CurrentTenant::id()`
- `creating` hook auto-sets `tenant_id` from current tenant
- Safe bypass when:
  - `app()->runningInConsole()`
  - tenancy bypass flag is enabled (super-admin route group)

---

## 4) Route Refactor to `/t/{tenant:slug}`

### Existing files touched
- `routes/web.php`
- `routes/settings.php` (tenant-aware settings paths)
- `app/Providers/FortifyServiceProvider.php` (tenant-aware login flow/redirect)

### Route structure
- Tenant group:
  - `Route::prefix('t/{tenant:slug}')->middleware(['tenant'])->group(...)`
  - Public showroom routes (home, units, renter routes)
  - Tenant admin auth routes:
    - `GET /t/{tenant}/login`
    - `POST /t/{tenant}/login`
  - Tenant admin area:
    - `/t/{tenant}/admin/...` with `auth`, `verified`, `admin`
- Super-admin group:
  - `/super/...` with `auth`, `super-admin`, and tenancy bypass
- Optional global `/login`:
  - lightweight tenant slug chooser -> redirect `/t/{slug}/login`

---

## 5) Auth Rules: tenant admin vs tenantless super-admin

### Existing files touched
- `app/Models/User.php`
- `app/Http/Middleware/IsAdmin.php` (or replace)

### New files
- `app/Http/Middleware/EnsureAdmin.php`
- `app/Http/Middleware/EnsureSuperAdmin.php`

### Rules
- Tenant admin:
  - `is_admin = true`
  - `tenant_id` must match current tenant
- Super-admin:
  - `is_super_admin = true`
  - `tenant_id = null` (tenantless)
  - can access `/super/*` only
- Enforce tenant match on all tenant admin pages and mutations

---

## 6) Tenant-Isolated Images

### New files
- `app/Http/Controllers/TenantMediaController.php`

### Existing files touched
- `routes/web.php`
- `config/filesystems.php`
- `app/Models/UnitImage.php`
- `app/Livewire/Admin/Units/Form.php` (upload path)
- `resources/views/livewire/public/unit-show.blade.php`
- `resources/views/livewire/public/showroom-index.blade.php`
- Admin unit image rendering views/components

### Storage + serving
- Store files on private disk:
  - `tenants/{tenant_id}/units/{unit_id}/{filename}`
- Route:
  - `GET /t/{tenant}/media/unit-images/{unitImage:public_id}`
- Controller:
  - load `UnitImage` by `public_id`
  - enforce tenant ownership (`unitImage.tenant_id === currentTenant.id`)
  - stream via `Storage::disk('private')->response(...)`
- UI URLs must use tenant media route (never `Storage::url()`)

---

## 7) Super-Admin Livewire UI at `/super/tenants`

### New files
- `app/Livewire/Super/Tenants/Index.php`
- `resources/views/livewire/super/tenants/index.blade.php`
- `app/Application/SuperAdmin/TenantProvisioningService.php` (or `app/Services/TenantProvisioningService.php`)

### Existing files touched
- `routes/web.php`
- `app/Models/Tenant.php`
- `app/Models/User.php`

### Features
- List tenants: name, slug, status, trial_ends_at
- Create tenant:
  - name
  - optional slug (auto-generate if blank)
  - trial duration
  - initial tenant admin (email + generated password)
- Actions:
  - disable tenant
  - extend trial
- Show copyable link:
  - `https://APP_URL/t/{slug}`

---

## 8) Command: `tenancy:bootstrap-default`

### New files
- `app/Console/Commands/TenancyBootstrapDefault.php`

### Existing files touched
- `routes/console.php` (if command registration needed)

### Command flow
- `--dry-run` support
- create default tenant if missing
- backfill each table in chunks (`chunkById`)
- create public IDs for unit images
- optional flags:
  - `--create-admin-email=...`
  - `--force`
- print summary metrics per table

---

## 9) Pest Test Plan (Isolation Critical)

### New files
- `tests/Feature/Tenancy/PublicIsolationTest.php`
- `tests/Feature/Tenancy/AdminIsolationTest.php`
- `tests/Feature/Tenancy/MediaIsolationTest.php`
- `tests/Feature/Tenancy/SuperAdminTenantProvisionTest.php`
- `database/factories/TenantFactory.php`

### Existing files touched
- Update factories to include `tenant_id`:
  - `database/factories/UserFactory.php`
  - `database/factories/CategoryFactory.php` (if exists)
  - `database/factories/UnitFactory.php` (if exists)
  - `database/factories/UnitImageFactory.php` (if exists)
  - `database/factories/RentalFactory.php`
  - `database/factories/ViewingRequestFactory.php`

### Required assertions
- Public: tenant A cannot list/view tenant B units
- Admin: tenant A admin cannot edit/delete tenant B records
- Media: tenant A cannot fetch tenant B image endpoint (404)
- Scope: categories/rentals/tickets/logs restricted by tenant
- Super-admin: create tenant works and returns valid `/t/{slug}` link + tenant admin

---

## Action Items (Atomic)
- [ ] Add tenants schema + nullable tenant columns + indexes (M01-M03).
- [ ] Implement tenant context classes and `tenant` middleware wiring.
- [ ] Introduce `BelongsToTenant` trait and apply to tenant-owned models.
- [ ] Add tenantless super-admin model/middleware strategy.
- [ ] Refactor all tenant-facing routes to `/t/{tenant:slug}`.
- [ ] Add optional global `/login` slug chooser redirect.
- [ ] Move unit image access to private storage + tenant media streaming route.
- [ ] Build `/super/tenants` Livewire CRUD/provisioning flow.
- [ ] Implement `tenancy:bootstrap-default` and run backfill.
- [ ] Add Pest isolation suites and enforce CI pass before cutover.

## Validation Gates
- Gate A (after M03): app runs with nullable tenant columns.
- Gate B (after bootstrap command): all tenant-owned rows have tenant_id.
- Gate C (after M05 + route cutover): tenant-prefixed paths only, no cross-tenant leaks, media isolation verified.
