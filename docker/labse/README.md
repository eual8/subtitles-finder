# ukr-paraphrase-multilingual-mpnet-base Embeddings Service

A FastAPI service for the `lang-uk/ukr-paraphrase-multilingual-mpnet-base` sentence-transformers model
fine-tuned for Ukrainian and multilingual paraphrase/sentence similarity tasks. The service
provides 768-dimensional text embeddings via a simple HTTP API.

## Features

- 🌍 Supports multiple languages (model fine-tuned for Ukrainian)
- 📊 768-dimensional embeddings
- 🚀 FastAPI with automatic API documentation
- 🐳 Docker containerized (integrated with docker-compose)
- ⚡ Batch processing support (up to 100 texts per request)

## API Endpoints

### Health Check
```
GET /health
```

### Generate Embeddings
```
POST /embeddings
```

Request body:
```json
{
  "texts": ["Hello world", "Привіт світ", "Тестовий текст"],
  "normalize": true
}
```

Response:
```json
{
  "embeddings": [[...], [...], [...]],
  "dimensions": 768,
  "count": 3
}
```

## Usage Examples

### Python
```python
import requests

# Generate embeddings
response = requests.post('http://localhost:8080/embeddings',
    json={"texts": ["Hello world", "Test text"]})
embeddings = response.json()['embeddings']
```

### cURL
```bash
# Generate embeddings
curl -X POST http://localhost:8080/embeddings \
  -H "Content-Type: application/json" \
  -d '{"texts": ["Hello world"]}'
```

## API Documentation

Interactive API documentation is available at:
- Swagger UI: http://localhost:8080/docs

## Building and Running

The service is integrated with docker-compose. To build and run:

```bash
# Build and start all services
docker-compose up -d --build

# View logs (service name may vary; check docker-compose.yml)
docker-compose logs -f sentence-embeddings

# Stop the service
docker-compose down
```

## Performance Notes

- First request will be slower as the model and tokenizer are loaded into memory (model size depends on HF weights).
- Subsequent requests will be faster.
- The container may require ~1-2 GB of memory for model weights plus additional RAM for batching.

## Model Information

- Model: lang-uk/ukr-paraphrase-multilingual-mpnet-base
- Origin: Fine-tuned from `sentence-transformers/paraphrase-multilingual-mpnet-base-v2`
- License: Apache-2.0
- Embedding dimensions: 768
- Use case: Ukrainian-focused paraphrase detection, multilingual sentence similarity, clustering and semantic search

## Notes

- The FastAPI app (`docker/labse/app.py`) exposes endpoints `/` (health/status), `/health` and `/embeddings`.
- The app restricts batch size to a maximum of 100 texts per request to avoid excessive memory use.
- If you change the service name in `docker-compose.yml`, update the `docker-compose logs` command above accordingly.

## Citing

If you use the model in research, please cite the authors and the paper linked from the model card on Hugging Face.
