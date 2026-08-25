# RMCP System

Full-stack application: Angular frontend, Laravel API, SQL Server, Redis, Nginx, MailHog, CloudBeaver.

## Prerequisites
- Docker & Docker Compose
- Node.js 18+ and npm (for Angular build)
- Git
- (Cloud) Open firewall ports: 80, 443, 4200, 8080, 8025, 8978
- (Optional) Domain name for SSL

## Quick Start (Local or Cloud)
1. Clone the repository:
   ```sh
   git clone https://github.com/lubabaloboya/rmcp-application.git
   cd rmcp-application
   ```
2. Create a Docker env file and set a strong SQL Server SA password:
   ```sh
   cp .env.docker.example .env
   # Edit .env and set MSSQL_SA_PASSWORD to a strong value
   ```
3. Edit `web/src/environments/environment.prod.ts` and set your API URL (see `docs/DEPLOYMENT_GUIDE.md`).
4. Build Angular frontend:
   ```sh
   cd web
   npm install
   npm run build -- --configuration production
   cd ..
   ```
5. Prepare Laravel API:
   ```sh
   cd api
   cp .env.example .env   # Or copy your working .env
   docker compose exec api php artisan key:generate
   docker compose exec api php artisan jwt:secret
   cd ..
   ```
6. Start all services:
   ```sh
   docker compose up -d --build
   ```

## Access URLs
- Frontend: http://localhost:4200/ or http://<your-domain-or-ip>:4200/
- API: http://localhost:8080/api/v1 or http://<your-domain-or-ip>:8080/api/v1
- MailHog: http://localhost:8025/
- CloudBeaver: http://localhost:8978/

## For Cloud/Production Deployments
See the [Deployment guide](docs/DEPLOYMENT_GUIDE.md) for full step-by-step instructions, including SSL setup and troubleshooting.

## 6. Default Local Accounts

Common seeded users:
- Super Admin: admin@rmcp.local / Admin@12345
- Compliance Officer: officer@rmcp.local / Officer@12345

Other roles may also be present depending on seed state.

## 7. Verify Services

Check container state:

```sh
docker compose ps
```

Check API health quickly:

```sh
curl http://localhost:8080/api/v1/auth/me
```

Expected behavior without token: unauthorized response (this confirms API route is reachable).

## 8. Day-to-Day Commands

Stop services:

```sh
docker compose down
```

Restart services:

```sh
docker compose restart
```

View logs:

```sh
docker compose logs --tail=200 api
docker compose logs --tail=200 nginx
docker compose logs --tail=200 web
```

Run backend tests:

```sh
docker compose exec api php artisan test
```

## 9. Frontend Changes Workflow

The web container serves static built Angular files from web/dist.

After frontend code changes:

1. Build frontend from the web folder:
   ```sh
   npm run build
   ```
2. Restart web container from project root:
   ```sh
   docker compose restart web
   ```
3. Hard refresh browser (Ctrl+F5).

## 10. Database and Seed Notes

On API container startup, the entrypoint automatically:
- waits for SQL Server
- runs migrations
- runs seeders

If you need to run manually:

```sh
docker compose exec api php artisan migrate --force
docker compose exec api php artisan db:seed --force
```

## 11. Common Troubleshooting

### Docker Compose fails with missing MSSQL_SA_PASSWORD

Cause: `docker-compose.yml` now requires `MSSQL_SA_PASSWORD` and no longer ships insecure fallback defaults.

Fix:

```sh
cp .env.docker.example .env
# Edit .env and set MSSQL_SA_PASSWORD
docker compose up -d --build
```

### Login requests get 429 Too Many Requests

Cause: auth endpoints are rate-limited for brute-force protection.

Fix:
- Wait for the throttle window to reset
- Reduce rapid retries from clients/scripts
- Adjust values in `api/.env` only if operationally required:
   - `AUTH_RATE_LIMIT_PER_MINUTE`
   - `API_RATE_LIMIT_PER_MINUTE`
   - `BULK_IMPORT_RATE_LIMIT_PER_MINUTE`

### API returns 502 from localhost:8080

Cause: Nginx may still point to a stale API container target after recreate.

Fix:

```sh
docker compose restart nginx
```

### Frontend still shows old UI

Cause: stale browser cache or old dist assets.

Fix:
- Run npm run build in web folder
- Run docker compose restart web
- Hard refresh browser

### Login fails for seeded users

Fix:
- Ensure stack is healthy with docker compose ps
- Rerun seeds:
   ```sh
   docker compose exec api php artisan db:seed --force
   ```

## 12. Additional Project Documentation

- [User guide](docs/USER_GUIDE.md)
- [Developer guide](docs/DEVELOPER_GUIDE.md)
