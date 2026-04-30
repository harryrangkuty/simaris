echo "========================================"
echo "         Harry Deployment Script      "
echo "========================================"

echo "[1/6] Pulling latest changes from Git..."
git pull origin main
echo "----------------------------------------"

echo "[2/6] Clearing old caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo "----------------------------------------"

echo "[3/6] Caching config..."
php artisan config:cache
echo "[4/6] Caching routes..."
php artisan route:cache
echo "[5/6] Caching views..."
php artisan view:cache
echo "----------------------------------------"

echo "[6/6] Syncing public assets to server..."
rsync -av --delete --exclude='index.php' --exclude='storage' --exclude='api' public/ ../public_html/
echo "----------------------------------------"

echo "Deployment completed successfully."
echo "========================================"