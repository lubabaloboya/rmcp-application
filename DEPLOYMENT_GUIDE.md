# RMCP System - Full Stack Deployment Guide (Angular + Laravel)

## Prerequisites

- Ubuntu 20.04+ VM (Google Cloud, AWS, etc.)
- sudo/root access
- Open firewall ports: 80 (HTTP), 443 (HTTPS), 4200 (Angular), 8000/8080 (API), 1433 (SQL Server), 8025 (Mailhog), 8978 (CloudBeaver)
- Domain name (optional, for SSL)

### Software
- Docker & Docker Compose
- Node.js 18+ and npm (for Angular build)
- Git

## 1. Connect to Your VM
```sh
ssh <your-username>@<your-vm-external-ip>
```

## 2. Update System & Install Prerequisites
```sh
sudo apt-get update && sudo apt-get upgrade -y
sudo apt-get install -y curl git
```

## 3. Install Docker & Docker Compose
```sh
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo apt-get install -y docker-compose-plugin
```

## 4. Install Node.js & npm (for Angular build)
```sh
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

## 5. Clone Your Project
```sh
git clone https://<TOKEN>@github.com/lubabaloboya/rmcp-application.git
cd rmcp-application
```

## 6. Configure Angular Environment
- Edit `web/src/environments/environment.prod.ts` and set your public API URL:
  ```typescript
  export const environment = {
    production: true,
    apiBaseUrl: 'http://<your-domain-or-ip>/api/v1',
  };
  ```

## 7. Build Angular App
```sh
cd web
npm install
npm run build -- --configuration production
cd ..
```

## 8. Prepare Laravel Environment
```sh
cd api
cp .env.example .env   # Or copy your working .env
docker compose exec api php artisan key:generate
docker compose exec api php artisan jwt:secret
cd ..
```

## 9. Start All Services with Docker Compose
```sh
sudo docker compose up -d --build
```

## 10. (Optional) Set Up SSL (HTTPS)
1. [Obtain a domain and point it to your VM’s IP.]
2. [Install Certbot:]
   ```sh
   sudo apt-get install -y certbot
   sudo systemctl stop nginx  # if nginx is running
   sudo certbot certonly --standalone -d <your-domain>
   ```
3. [Update nginx config to use the generated certs.]
4. [Restart nginx.]

## 11. Open Firewall Ports
- Ensure ports 80, 443, 4200, 8000, etc. are open in your cloud firewall.

## 12. Access Your App
- Angular frontend: `http://<your-domain-or-ip>:4200`
- API: `http://<your-domain-or-ip>/api/v1`
- MailHog: `http://<your-domain-or-ip>:8025/`
- CloudBeaver: `http://<your-domain-or-ip>:8978/`

## Troubleshooting
- **API connection refused:** Check API URL in Angular env, Docker Compose ports, and container health.
- **.env or APP_KEY errors:** Ensure `.env` exists in `api/`, run `php artisan key:generate` and `php artisan jwt:secret`.
- **SSL/HTTPS issues:** Confirm domain DNS, firewall, and nginx SSL config.
- **Database errors:** Check SQL Server container logs and volume permissions.

---
For more details, see README.md and docs/DEVELOPER_GUIDE.md.
- API: `http://<your-external-ip>:8080/api/v1`

---

# Important Docker & Deployment Commands

- **Check running containers:**
  ```sh
  sudo docker compose ps
  ```
- **View logs for a service:**
  ```sh
  sudo docker compose logs <service-name>
  ```
- **Restart all services:**
  ```sh
  sudo docker compose restart
  ```
- **Stop all services:**
  ```sh
  sudo docker compose down
  ```
- **Rebuild and restart after code changes:**
  ```sh
  sudo docker compose down
  sudo docker compose up -d --build
  ```
- **Check if ports are listening:**
  ```sh
  sudo ss -tuln | grep 8080
  sudo ss -tuln | grep 4200
  ```
- **Update code from GitHub:**
  ```sh
  git pull
  ```

---

# Enabling Google Network Management API

If you encounter the error:

"Network Management API has not been used in project project-93308b44-7944-46a7-845 before or it is disabled. Enable it by visiting https://console.developers.google.com/apis/api/networkmanagement.googleapis.com/overview?project=project-93308b44-7944-46a7-845 then retry. If you enabled this API recently, wait a few minutes for the action to propagate to our systems and retry."

Follow these steps:

1. Visit the link:
   https://console.developers.google.com/apis/api/networkmanagement.googleapis.com/overview?project=project-93308b44-7944-46a7-845

2. Click "Enable" to activate the Network Management API for your project.

3. Wait a few minutes for the change to propagate.

4. Retry your operation.

---

# Enabling Cloud Identity-Aware Proxy API

If you encounter the error:

"Cloud Identity-Aware Proxy API has not been used in project project-93308b44-7944-46a7-845 before or it is disabled. Enable it by visiting https://console.developers.google.com/apis/api/iap.googleapis.com/overview?project=project-93308b44-7944-46a7-845 then retry. If you enabled this API recently, wait a few minutes for the action to propagate to our systems and retry."

Follow these steps:

1. Visit the link:
   https://console.developers.google.com/apis/api/iap.googleapis.com/overview?project=project-93308b44-7944-46a7-845

2. Click "Enable" to activate the Cloud Identity-Aware Proxy API for your project.

3. Wait a few minutes for the change to propagate.

4. Retry your operation.

---

# Troubleshooting & Fixes

## 1. **App not accessible from browser**
- Check firewall rules for open ports (80, 443, 8080, 4200, etc.).
- Check `sudo docker compose ps` to ensure containers are running.
- Check `sudo ss -tuln | grep <port>` to ensure ports are listening.

## 2. **Frontend redirects to localhost**
- Edit `web/src/environments/environment.prod.ts` to use your external IP.
- Rebuild Angular app and restart Docker Compose.

## 3. **Build errors (Angular)**
- Use `*ngFor` instead of `@for` or JS for-loops in templates.
- Run `npm install` before building.

## 4. **Docker errors**
- Reinstall Docker if `docker` or `docker-compose` not found.
- Use `sudo` for all Docker commands.

## 5. **Node/npm not found**
- Install Node.js and npm as shown above.

## 6. **No space left on device / Docker errors**
- Check disk usage:
  ```sh
  df -h
  ```
- Remove unused Docker containers, images, volumes, and networks:
  ```sh
  sudo docker system prune -a --volumes
  ```
- Delete unnecessary files, including logs:
  - To delete old logs:
    ```sh
    sudo rm -rf /var/log/*.log
    sudo rm -rf /var/log/*.gz
    sudo rm -rf /var/log/*/*.log
    sudo rm -rf /var/log/*/*.gz
    ```
  - To clear Docker logs:
    ```sh
    sudo truncate -s 0 /var/lib/docker/containers/*/*.log
    ```
- If needed, increase your VM’s disk size.

---

# Tips
- Always rebuild the Angular app after changing `environment.prod.ts`.
- Use incognito mode or clear cache after redeploying frontend.
- For production, consider using a reverse proxy (Nginx) and HTTPS.

---

# Laravel .env Setup for Docker Deployment

1. Navigate to your api directory:
   ```sh
   cd /path/to/your/project/api
   ```
   (Replace `/path/to/your/project` with your actual path, e.g., `/home/youruser/rmcp-system/api` or `c:/xampp/htdocs/rmcp-system/api`)

2. If you have a `.env.example` file, copy it to `.env`:
   ```sh
   cp .env.example .env
   ```
   If you do not have a `.env.example` file, create a new `.env` file:
   ```sh
   nano .env
   ```
   Or use `vim` or another editor.

3. Add the following content to your `.env` file (edit as needed):
   ```
   APP_ENV=local
   APP_DEBUG=false
   APP_URL=http://localhost:8080

   DB_CONNECTION=sqlsrv
   DB_HOST=mssql
   DB_PORT=1433
   DB_DATABASE=rmcp
   DB_USERNAME=sa
   DB_PASSWORD=Rmcp@123456
   ```

4. Save and exit the editor (for nano: press Ctrl+O, Enter, then Ctrl+X).

5. Go back to your project root and restart your containers:
   ```sh
   cd ..
   sudo docker compose down
   sudo docker compose up -d --build
   ```

6. Check that the `.env` file is now present inside the container:
   ```sh
   sudo docker exec -it rmcp-api ls -l /var/www/api/.env
   ```

If you see the `.env` file, your Laravel app should now work!

---

**Keep this file for quick reference during deployment and troubleshooting!**

## Step-by-step: Deleting Logs Using the Console

1. Connect to your VM using SSH:
   ```sh
   ssh <your-username>@<your-vm-external-ip>
   ```

2. Check disk usage to identify full partitions:
   ```sh
   df -h
   ```

3. List log files in /var/log:
   ```sh
   ls -lh /var/log/*.log
   ls -lh /var/log/*.gz
   ls -lh /var/log/*/*.log
   ls -lh /var/log/*/*.gz
   ```

4. Delete old log files:
   ```sh
   sudo rm -rf /var/log/*.log
   sudo rm -rf /var/log/*.gz
   sudo rm -rf /var/log/*/*.log
   sudo rm -rf /var/log/*/*.gz
   ```

5. Clear Docker container logs (if using Docker):
   ```sh
   sudo truncate -s 0 /var/lib/docker/containers/*/*.log
   ```

6. Verify disk space is freed:
   ```sh
   df -h
   ```

7. (Optional) Remove unused Docker resources:
   ```sh
   sudo docker system prune -a --volumes
   ```
