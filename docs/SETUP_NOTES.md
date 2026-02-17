# Condo Showroom Setup Notes

## Setup

1. Install dependencies:
   - `composer install`
   - `npm install`
2. Create environment file:
   - `copy .env.example .env`
3. Generate app key:
   - `php artisan key:generate`
4. Run migrations and seeders:
   - `php artisan migrate --seed`
5. Build frontend assets:
   - `npm run dev` (local) or `npm run build` (production)

## Storage

- Run `php artisan storage:link`.
- Images are stored as relative paths in DB (example: `units/15/<uuid>.jpg`).
- URLs are generated through `Storage::url($path)`.

## Admin Access

- Public showroom: `/`
- Admin login: type `/login`
- Admin dashboard: `/admin`
- Only users with `is_admin = true` can access admin routes.

## Realtime Notifications

- Viewing request creation triggers admin notifications.
- Notification channels:
  - `database` always
  - `broadcast` when `BROADCAST_CONNECTION` is configured (not `null`)
- Current admin bell uses Livewire polling for near-real-time updates.

## Filesystem Switching (Public to S3)

- Default: `FILESYSTEM_DISK=public`
- To switch to S3, set:
  - `FILESYSTEM_DISK=s3`
  - `AWS_ACCESS_KEY_ID`
  - `AWS_SECRET_ACCESS_KEY`
  - `AWS_DEFAULT_REGION`
  - `AWS_BUCKET`

No code changes are required for this switch.

## Security Notes

- Do not commit `.env`.
- Do not place secrets in Blade/Livewire/JS output.
- Keep broadcast/storage credentials server-side only.
- Store relative file paths only, never raw cloud credential data.
