````md
# Tray Meli Ads Service (Desafio Técnico)

Serviço backend em **Laravel 12** que captura os anúncios (30) do Mercado Livre (via **Mockoon**) e expõe os dados via **API REST JSON**, com persistência no **MySQL** e processamento assíncrono via **fila (RabbitMQ)**.

Este projeto executa 100% localmente utilizando as APIs do mock (Mockoon).

---

## Sumário

- Arquitetura
- Requisitos
- Como rodar (Docker)
- Configuração (.env)
- Sincronização manual
- Sincronização automática (Scheduler)
- API REST
- Logs
- Testes
- Atendimento às regras do desafio
- Decisões técnicas (Perfil Backend Sênior)
- Estudo de caso: 10 sellers × 5.000 anúncios
- Troubleshooting

---

## Arquitetura

Fluxo principal:

1. **Meli-Auth (Mockoon)**  
   `GET /traymeli/sellers/252254392`

2. **Search (Mockoon)**  
   `GET /mercadolibre/sites/MLB/search?seller_id=...`

3. **Fila (RabbitMQ)**  
   Enfileira um job por item (30 jobs)

4. **Items (Mockoon)**  
   `GET /mercadolibre/items/:id`

5. **Persistência (MySQL)**  
   Upsert do item + status de sincronização

6. **API REST**  
   `GET /api/items`

Componentes Docker:

- app (Laravel API + comandos)
- worker (queue:work)
- mysql
- redis
- rabbitmq
- mockoon

---

## Requisitos

- Docker
- Docker Compose

---

## Como rodar (Docker)

Subir containers:

```bash
docker compose up -d --build
```

Instalar dependências (se necessário):

```bash
docker compose exec app composer install
```

Rodar migrations:

```bash
docker compose exec app php artisan migrate --force
```

Serviços disponíveis:

- API: http://localhost:8000
- Mockoon: http://localhost:3001
- RabbitMQ UI: http://localhost:15672 (guest/guest)

---

## Configuração (.env)

Principais variáveis:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=tray_meli
DB_USERNAME=root
DB_PASSWORD=root

QUEUE_CONNECTION=rabbitmq
RABBITMQ_HOST=rabbitmq
RABBITMQ_QUEUE=meli-items

CACHE_STORE=redis
REDIS_HOST=redis

MELI_SELLER_ID=252254392
MELI_BASE_URL=http://mockoon:3001
MELI_SITE=MLB
MELI_RETRIES=2
```

Rate limit opcional:

```env
MELI_RATE_LIMIT_PER_SECOND=5
MELI_THROTTLE_BLOCK_SECONDS=2
MELI_THROTTLE_KEY=meli-api
```

---

## Sincronização manual

Executar:

```bash
docker compose exec app php artisan meli:sync-items
```

Fluxo executado:

- Obtém token válido
- Busca IDs via Search
- Registra itens como `queued`
- Enfileira 30 jobs
- Worker consome e grava detalhes

Validar:

```bash
docker compose logs -f worker
```

Consultar MySQL:

```bash
docker compose exec mysql mysql -uroot -proot -Dtray_meli -e "select item_id,sync_status from items limit 5;"
```

---

## Sincronização automática (Scheduler)

Executar:

```bash
docker compose exec app php artisan schedule:work
```

Verificar:

```bash
docker compose exec app php artisan schedule:list
```

Executa `meli:sync-items` a cada 10 minutos.

---

## API REST

Endpoint:

```
GET /api/items
```

Exemplo:

```bash
curl http://localhost:8000/api/items
```

Filtros:

- seller_id
- status
- sync_status
- q (busca por título)
- per_page
- sort (ex: -fetched_at)

Retorno padrão Laravel:

```json
{
  "data": [],
  "links": {},
  "meta": {}
}
```

---

## Logs

Ver logs:

```bash
docker compose logs -f app
docker compose logs -f worker
```

O sistema registra:

- Falha de token
- Erros de API
- Status de processamento
- Execução de jobs

---

## Testes

Rodar todos:

```bash
docker compose exec app php artisan test
```

Rodar específico:

```bash
docker compose exec app php artisan test --filter=ItemsApiTest
```

Testes cobrem:

- Paginação
- Estrutura JSON
- Integridade com FK
- Regras de sincronização

---

## Atendimento às regras do desafio

1. Token obrigatório  
   Implementado via `getActiveSellerToken`.

2. Somente token válido  
   Cliente tenta até obter `inactiveToken=false`.

3. Token inválido → log e continuar  
   Registra log e retorna de forma controlada.

4. Consultar primeiro Search  
   `searchItems()` executado antes de buscar detalhes.

5. Buscar detalhes e persistir  
   Cada ID gera um Job que grava `raw` + campos principais.

6. Demonstrar via API  
   `/api/items` expõe dados com filtros e paginação.

7. Apresentar logs  
   Logs visíveis no terminal via Docker.

---

## Decisões técnicas (Perfil Backend Sênior)

### Uso de RabbitMQ

- Evita bloqueio do processo principal
- Permite escalabilidade horizontal
- Retry/backoff configurável
- Controle de concorrência

### Upsert (updateOrCreate)

- Idempotência
- Execuções repetidas não duplicam dados
- Atualização incremental

### Campos de controle

- sync_status
- last_error
- fetched_at
- synced_at

Facilitam auditoria e troubleshooting.

### Cache com Redis

- Reduz carga de leitura
- TTL curto
- Invalidação via tags após sync

### Worker configurável

Parâmetros:

- timeout
- backoff
- tries
- memory limit
- max-jobs

Evita workers presos e melhora estabilidade.

---

## Estudo de Caso: 10 sellers × 5.000 anúncios

Se o sistema crescer para:

- 10 sellers
- 5.000 anúncios cada
- Total: 50.000 anúncios

Propostas de melhoria:

### 1. Paralelização controlada

- Um job por seller
- Chunk de IDs (ex: 100 por batch)
- Limite de concorrência por seller

### 2. Bulk Upsert

- Agrupar inserts/updates em lote
- Reduz I/O no MySQL

### 3. Separação de banco

- Banco write (primário)
- Réplica read-only para API

### 4. Cache agressivo

- Cache por seller
- Cache por página
- Invalidação por versão

### 5. Rate Limiting global

- Throttle Redis
- Fila com prioridades

### 6. Monitoramento

- Métricas por seller
- Tempo médio de sync
- Taxa de erro
- Alertas em falhas de token

### 7. Escalabilidade horizontal

- Múltiplos workers
- Auto scaling baseado em tamanho da fila

---

## Troubleshooting

Token inválido:

```bash
curl http://localhost:3001/traymeli/sellers/252254392
```

Limpar cache:

```bash
docker compose exec app php artisan optimize:clear
```

Ver filas:

```bash
docker compose exec rabbitmq rabbitmqctl list_queues name messages_ready messages_unacknowledged
```

---

## Licença

Projeto de desafio técnico.
````
