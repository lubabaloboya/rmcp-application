docker compose up -d --build
# RMCP System

Full-stack application: Angular frontend, Laravel API, SQL Server, Redis, Nginx, MailHog, CloudBeaver.

## Prerequisites
- Docker & Docker Compose
- Node.js 18+ and npm (for Angular build)
- Git
- (Cloud) Open firewall ports: 80, 443, 4200, 8000, 8025, 8978
- (Optional) Domain name for SSL

## Quick Start (Local or Cloud)
1. Clone the repository:
   ```sh
   git clone https://github.com/lubabaloboya/rmcp-application.git
   cd rmcp-application
   ```
2. Edit `web/src/environments/environment.prod.ts` and set your API URL (see DEPLOYMENT_GUIDE.md).
3. Build Angular frontend:
   ```sh
   cd web
   npm install
   npm run build -- --configuration production
   cd ..
   ```
4. Prepare Laravel API:
   ```sh
   cd api
   cp .env.example .env   # Or copy your working .env
   docker compose exec api php artisan key:generate
   docker compose exec api php artisan jwt:secret
   cd ..
   ```
5. Start all services:
   ```sh
   docker compose up -d --build
   ```

## Access URLs
- Frontend: http://localhost:4200/ or http://<your-domain-or-ip>:4200/
- API: http://localhost:8000/ or http://<your-domain-or-ip>/api/v1
- MailHog: http://localhost:8025/
- CloudBeaver: http://localhost:8978/

## For Cloud/Production Deployments
See [DEPLOYMENT_GUIDE.md](DEPLOYMENT_GUIDE.md) for full step-by-step instructions, including SSL setup and troubleshooting.

## 6. Default Local Accounts

Common seeded users:
- Super Admin: admin@rmcp.local / Admin@12345
- Compliance Officer: officer@rmcp.local / Officer@12345

Other roles may also be present depending on seed state.

## 7. Verify Services

Check container state:

docker compose ps

Check API health quickly:

curl http://localhost:8080/api/v1/auth/me

Expected behavior without token: unauthorized response (this confirms API route is reachable).

## 8. Day-to-Day Commands

Stop services:

docker compose down

Restart services:

docker compose restart

View logs:

docker compose logs --tail=200 api
docker compose logs --tail=200 nginx
docker compose logs --tail=200 web

Run backend tests:

docker compose exec api php artisan test

## 9. Frontend Changes Workflow

The web container serves static built Angular files from web/dist.

After frontend code changes:

1. Build frontend from the web folder:
   npm run build
2. Restart web container from project root:
   docker compose restart web
3. Hard refresh browser (Ctrl+F5).

## 10. Database and Seed Notes

On API container startup, the entrypoint automatically:
- waits for SQL Server
- runs migrations
- runs seeders

If you need to run manually:

docker compose exec api php artisan migrate --force
docker compose exec api php artisan db:seed --force

## 11. Common Troubleshooting

### API returns 502 from localhost:8080

Cause: Nginx may still point to a stale API container target after recreate.

Fix:

docker compose restart nginx

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
  docker compose exec api php artisan db:seed --force

## 12. Additional Project Documentation

- User guide: docs/USER_GUIDE.md
- Developer guide: docs/DEVELOPER_GUIDE.md
