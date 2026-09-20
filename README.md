# Automation Platform

## Objective
A robust, production-ready browser automation platform foundation running on a VPS, built for future expansion.

## Architecture
- **Web Dashboard:** PHP 8.2+, MySQL 8+, Tailwind CSS, Nginx, PHP-FPM
- **Automation Foundation:** Python 3.11+, Playwright, Chromium
- **Infrastructure:** Ubuntu VPS, Supervisor/systemd

## Installation & Deployment (VPS)

1. Clone the repository to your server:
   ```bash
   git clone <repo_url> /var/www/automation
   cd /var/www/automation
   ```

2. Run the deployment setup script (see `deployment/setup.sh`):
   ```bash
   sudo bash deployment/setup.sh
   ```

## Configuration

1. Copy the environment configuration:
   ```bash
   cp .env.example .env
   ```
2. Edit `.env` and fill in your database and environment settings. Ensure you use strong passwords. The `.env` file is protected and excluded via `.gitignore`.

## Database Setup

Run the migrations to create the database schema:
```bash
php database/migrate.php
```

## Running the PHP Dashboard

On a VPS, configure Nginx to serve the `dashboard/public` directory (see `deployment/nginx.conf`).
To run locally for testing:
```bash
php -S localhost:8000 -t dashboard/public
```

## Running Python Automation

1. Activate the virtual environment (created by `setup.sh`):
   ```bash
   source venv/bin/activate
   ```
2. Start the worker:
   ```bash
   python automation/main.py
   ```
*(For production, configure this as a background service using Supervisor or systemd).*

## Playwright Setup

Playwright is automatically installed via the `setup.sh` script, which installs dependencies and the Chromium browser:
```bash
pip install playwright
playwright install --with-deps chromium
```
To verify, you can run the test script.

## Testing

Run PHP tests:
```bash
php tests/php/test_db.php
php tests/php/test_auth.php
```

Run Python tests:
```bash
export BROWSER_HEADLESS=true
source venv/bin/activate
pytest tests/python/
```

## Troubleshooting

- **Database Errors:** Ensure the credentials in `.env` are correct and `mariadb-server` is running.
- **Browser Errors:** If Playwright fails to launch, ensure `BROWSER_HEADLESS=true` in environments without a graphical server, or ensure you've installed system dependencies (`playwright install --with-deps chromium`).
- **File Permissions:** Ensure the `storage/` directory is writable by both your web server user (`www-data`) and your Python worker.
