
### Полезные команды

#### Если не идёт скачивание с ютуба, то возмножно надо обновить программу:
```
pip install --upgrade yt-dlp
```


#### Команды для удаления всех данных из индекса и добавление заново:
```
php -d memory_limit=4096M artisan scout:flush "App\Models\Fragment"
php -d memory_limit=4096M artisan scout:import "App\Models\Fragment"
```

#### Индексы создаются с помощью миграций, если надо обновить или заново запустить создание:
```
php artisan elastic:migrate:refresh
```

#### Создание индекса Typesense вручную:
```
php artisan typesense:create-index
```
Команда создаёт коллекцию `fragments` в Typesense, проверяя существование индекса. Добавьте флаг `--force`, чтобы предварительно удалить уже созданную коллекцию и пересоздать её с нуля.
