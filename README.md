# RMCP System - Local Developer Setup

This guide helps a developer install and run RMCP on a local laptop.

## Quick Start (5 Commands)

Run these from the project root:

1. `git clone https://github.com/lubabaloboya/rmcp-application.git`
2. `cd rmcp-application`
3. `docker compose up -d --build`
4. `docker compose ps`
5. `docker compose logs --tail=100 api`

Then open:
- Frontend: http://localhost:4200/
- API: http://localhost:8080/

## 1. What You Are Running

RMCP is a Docker-based full-stack application with:
- Laravel API service
- Angular web application
- Microsoft SQL Server
- Redis (cache, session, queue)
- Nginx reverse proxies
- MailHog (email testing)
- CloudBeaver (database UI)

## 2. Prerequisites

Install these first:
- Docker Desktop (with Docker Compose)
- Git
- Node.js 20+ and npm (only needed if you will rebuild frontend assets)

Windows note:
- Ensure Docker Desktop is running before starting the stack.

## 3. Clone the Repository

1. Clone your repository.
2. Open the project folder in terminal:
   C:\xampp\htdocs\rmcp-system

## 4. Start the Application

From the project root, run:

docker compose up -d --build

This starts all required containers and builds images where needed.

## 5. Access URLs

After startup:
- Frontend app: http://localhost:4200/
- Backend API via Nginx: http://localhost:8080/
- MailHog UI: http://localhost:8025/
- CloudBeaver UI: http://localhost:8978/

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
