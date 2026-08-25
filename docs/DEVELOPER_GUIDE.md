# RMCP Developer Guide

This guide describes how developers run, understand, and extend RMCP.

## 1. Stack Overview

- Frontend: Angular (standalone app)
- Backend: Laravel 12 (JWT auth, role/permission middleware)
- Database: Microsoft SQL Server
- Cache/Session/Queue: Redis
- Runtime: Docker Compose (api, nginx, web, mssql, redis, mailhog, cloudbeaver)

## 2. Repository Structure

- `api/`: Laravel backend
- `web/`: Angular frontend
- `docker/`: Dockerfiles and Nginx configs
- `docker-compose.yml`: local orchestration
- `docs/`: project documentation

## 3. Local Run (Docker)

Before first start, copy Docker env template and set a strong SQL Server password:

```powershell
Copy-Item .env.docker.example .env
# Edit .env and set MSSQL_SA_PASSWORD
```

From repository root:

```powershell
docker compose up -d --build
```

Useful URLs:
- Frontend: http://localhost:4200/
- Backend (Nginx -> PHP-FPM): http://localhost:8080/
- MailHog: http://localhost:8025/
- CloudBeaver: http://localhost:8978/

Stop stack:

```powershell
docker compose down
```

## 4. Backend Startup Behavior

API container entrypoint performs:
- `.env` bootstrap from `.env.example` if needed
- `composer install` if vendor is missing
- SQL Server readiness wait and database auto-create
- `php artisan migrate --force`
- `php artisan db:seed --force`
- starts scheduler worker and php-fpm

Entry point script: `docker/php/entrypoint.sh`

## 5. Frontend Delivery Model

Frontend is served as static Angular build output by Nginx.

- Build assets path: `web/dist/rmcp-web/browser`
- Web Nginx config: `docker/web/default.conf`
- SPA fallback is enabled (`try_files ... /index.html`)
- `/login` redirects to `http://localhost:4200/`

When frontend code changes:

```powershell
Set-Location web
npm run build
Set-Location ..
docker compose restart web
```

## 6. Authentication and Authorization

### JWT Auth

- Login endpoint: `POST /api/v1/auth/login`
- Me endpoint: `GET /api/v1/auth/me`
- Logout endpoint: `POST /api/v1/auth/logout`

Auth and API abuse protections:
- `POST /api/v1/auth/login` and `POST /api/v1/auth/register` are throttled (`throttle:auth`)
- `/api/v1/*` routes are globally throttled (`throttle:api`)
- `POST /api/v1/clients/bulk` has extra throttle protection (`throttle:bulk-imports`)

Rate limit env controls:
- `API_RATE_LIMIT_PER_MINUTE`
- `AUTH_RATE_LIMIT_PER_MINUTE`
- `BULK_IMPORT_RATE_LIMIT_PER_MINUTE`

Backend controller: `api/app/Http/Controllers/Api/AuthController.php`

### Permission Model

Permissions are attached to roles, then enforced by middleware:
- Example route guard: `->middleware('permission:clients.view')`

Resource ownership is also policy-enforced for key entities:
- `ClientPolicy`
- `DocumentPolicy`
- `RmcpCasePolicy`

This adds company-scope checks on top of role permissions for client, document, and case access.

Main permission list:
- `api/config/rmcp.php`

Role seeding logic:
- `api/database/seeders/RoleSeeder.php`

## 7. API Surface (High Level)

All routes are under `/api/v1` and mostly require `auth:api`.

Main modules:
- Auth
- Dashboard
- Companies
- Clients
- Documents (+ versions/download)
- Cases
- Incidents
- Governance entities (directors/shareholders/beneficial owners)
- Tasks
- Communications
- Audit logs
- Roles
- Compliance automation (risk rules, document checklists, screenings, risk assessment)

Route file:
- `api/routes/api.php`

## 8. Data and Seeding

Core seeders:
- `RoleSeeder`
- `DocumentTypeSeeder`
- `SampleDataSeeder`
- `DatabaseSeeder` creates super admin if missing

Typical local accounts:
- `admin@rmcp.local` / `Admin@12345`
- `officer@rmcp.local` / `Officer@12345`

## 9. Redis Usage

Current local config uses Redis for:
- Cache (`CACHE_STORE=redis`)
- Session (`SESSION_DRIVER=redis`)
- Queue (`QUEUE_CONNECTION=redis`)

Compose env source:
- `docker-compose.yml` (api service)

Redis service:
- `redis:7-alpine`
- Exposed locally on `6379`

## 10. Common Developer Commands

### Backend commands

```powershell
docker compose exec api php artisan migrate
docker compose exec api php artisan db:seed
docker compose exec api php artisan test
```

### Queue check

```powershell
docker compose exec api php artisan tinker --execute="echo config('queue.default').PHP_EOL;"
```

### Logs

```powershell
docker compose logs --tail=200 api
docker compose logs --tail=200 nginx
docker compose logs --tail=200 web
```

## 11. Frontend Routing

Frontend route definitions:
- `web/src/app/app.routes.ts`

Guards used:
- `authGuard`
- `guestGuard`
- `adminGuard`
- `permissionGuard(...)`

Unknown routes redirect to login in Angular.

## 12. Troubleshooting

### API returns 502 through Nginx

If API container was recreated, Nginx may hold a stale upstream target briefly.

Fix:

```powershell
docker compose restart nginx
```

### Frontend still shows old UI

Cause is usually stale browser cache or outdated dist files.

Fix sequence:

```powershell
Set-Location web
npm run build
Set-Location ..
docker compose restart web
```

Then force browser refresh (`Ctrl+F5`).

### SQL Server readiness issues

Check:

```powershell
docker compose logs --tail=200 mssql
docker compose logs --tail=200 api
```

### Docker compose fails with missing MSSQL_SA_PASSWORD

Set the required env variable and restart:

```powershell
Copy-Item .env.docker.example .env
# Edit .env and set MSSQL_SA_PASSWORD
docker compose up -d --build
```

## 13. Contribution Notes

- Keep backend route permission checks explicit.
- Keep Angular guards aligned with backend permissions.
- Prefer minimal focused patches to avoid regressions.
- Validate after infra changes (API/web health, auth login probe, key workflows).

## 14. Compliance Automation Page Improvements

The Angular component for the Compliance Automation page (`web/src/app/pages/compliance-automation-page.component.ts` and `.html`) now includes:
- **Summary cards** for quick compliance stats (rules, clients, checklist types)
- **Filtering logic** for rules and clients (see `filteredRules`, `filteredClients`, `filterRules`, `filterClients`)
- **Modern, user-friendly UI** with tooltips, search fields, and improved feedback
- **Section headers and tooltips** for clarity

These changes improve usability for compliance officers and admins, and provide a reference for implementing similar UI/UX patterns elsewhere in the app.
