pip install --upgrade yt-dlp
php -d memory_limit=4096M artisan scout:flush "App\Models\Fragment"
php -d memory_limit=4096M artisan scout:import "App\Models\Fragment"
