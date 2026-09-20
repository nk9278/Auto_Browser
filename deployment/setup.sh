#!/bin/bash

# Update System
sudo apt-get update && sudo apt-get upgrade -y

# Install PHP and extensions
sudo apt-get install -y php8.3-cli php8.3-fpm php8.3-mysql php8.3-curl php8.3-xml php8.3-mbstring

# Install MariaDB (MySQL)
sudo apt-get install -y mariadb-server

# Install Nginx
sudo apt-get install -y nginx

# Install Python & Venv
sudo apt-get install -y python3 python3-pip python3-venv

# Setup Python Virtual Environment
cd /var/www/automation || exit
python3 -m venv venv
source venv/bin/activate
pip install -r requirements.txt
playwright install --with-deps chromium

# Configure permissions
sudo chown -R www-data:www-data /var/www/automation/dashboard
sudo chmod -R 775 /var/www/automation/storage

echo "Deployment setup complete. Please configure Nginx and .env file next."
