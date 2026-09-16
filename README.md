# Echevia API

Backend Laravel **11.55.1** da Echevia. A identificação de espécies por foto passa por aqui e a API chama a PlantNet no servidor, para o navegador não bater no CORS (`Origin not allowed` em `127.0.0.1` vs `localhost`).

## Hospedagem (HostGator)

| | |
| --- | --- |
| Host | https://marketingcriativa.com.br/ |
| Pasta | `clientes/echevia/echevia-api` |
| Banco | `marke047_echevia` |

O document root pode apontar para `public/`. Se não for possível, o `.htaccess` na raiz encaminha para `public/`.

URL da API com o rewrite da raiz:

`https://marketingcriativa.com.br/clientes/echevia/echevia-api/api/identify`

Os `.htaccess` já usam `RewriteBase` dessa pasta. Se o document root for `public/`:

`https://marketingcriativa.com.br/clientes/echevia/echevia-api/public/api/identify`

Ajuste `APP_URL` e `VITE_API_URL` no frontend de acordo com a URL real.

Na HostGator: `composer install --no-dev`, `php artisan key:generate`, coloque `PLANTNET_API_KEY` e `APP_URL` no `.env`, e `chmod -R ug+rwx storage bootstrap/cache`.

## Setup local

PHP 8.2+ e Composer:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000
```

No `.env`:

- `PLANTNET_API_KEY` — chave da PlantNet
- `CORS_ALLOWED_ORIGINS` — origens do Vite (`http://127.0.0.1:5173`, `http://localhost:5173`) e o domínio de produção
- `DB_DATABASE=marke047_echevia` (usuário/senha do cPanel; a identificação ainda não usa o banco)

## Endpoints

| Método | Caminho | Descrição |
| --- | --- | --- |
| GET | `/up` | Health check Laravel |
| GET | `/` | Nome da API |
| POST | `/api/identify` | Multipart `image` (jpg/png, até 10 MB). Resposta: `{ "matches": [...] }` |

A chave da PlantNet não sai no frontend.
