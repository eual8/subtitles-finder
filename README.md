
### Полезные команды


```
composer update --ignore-platform-reqs

```

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

## MCP Search (для LLM)

Сервер MCP: `POST /mcp/search`

Рекомендуемый порядок вызовов для модели:
1. `search_fragments` — выполнить поиск по всем плейлистам/видео (`query`, `matchPhrase`, `page`).
2. `read_fragment_window` — читать контекст выбранного `fragmentId`.
3. Для перехода по контексту использовать `navigation.nextId` / `navigation.prevId`.

MCP prompts для модели:
1. `search_workflow` — базовый workflow поиска и чтения контекста.
2. `evidence_answer` — шаблон ответа только по найденным фрагментам с цитированием.
3. `insufficient_data_policy` — поведение при нехватке данных и конфликтующих фрагментах.

Поисковый сценарий повторяет поведение админки:
- страница поиска: `/admin/search`
- чтение найденного контекста: `/admin/fragments/{id}/read`
