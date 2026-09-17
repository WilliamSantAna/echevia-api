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
- `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` — token S3 do R2
- `R2_BUCKET=echevia-media`
- `R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com`
- `R2_URL` — URL pública do bucket (`https://pub-….r2.dev` ou domínio próprio), sem barra no final

Teste local (depois de preencher o `.env`):

```bash
php artisan r2:ping
```

## Cloudflare R2

O R2 não usa ACL por arquivo (`public-read` quebra o upload). Os arquivos ficam públicos pelo **r2.dev** (ou domínio próprio), não pela visibilidade do Flysystem.

1. Painel: [R2 Object Storage](https://dash.cloudflare.com/?to=/:account/r2/overview) → **Create bucket** → nome `echevia-media` (jurisdição default, classe Standard).
2. Abra o bucket → **Settings** → **Public Development URL** → **Enable** → digite `allow`. Copie `https://pub-….r2.dev` (sem barra no final).
3. No mesmo **Settings** → **CORS Policy** → cole o JSON abaixo.
4. Volte em **R2** → **API Tokens** → **Manage** → **Create Account API token** → permissão **Object Read & Write**, Apply to specific buckets → só `echevia-media`.
5. Copie **Access Key ID**, **Secret Access Key** e o **Account ID** (sidebar). O secret só aparece uma vez. O endpoint S3 é `https://<ACCOUNT_ID>.r2.cloudflarestorage.com`.

CORS do bucket:

```json
[
  {
    "AllowedOrigins": [
      "https://marketingcriativa.com.br",
      "http://localhost:5173",
      "http://127.0.0.1:5173"
    ],
    "AllowedMethods": ["GET", "PUT", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

O `r2.dev` é limitado e pensado para desenvolvimento. Depois dá para ligar um subdomínio na Cloudflare. As chaves ficam só no `.env` da API, nunca no frontend.

## Endpoints

| Método | Caminho | Descrição |
| --- | --- | --- |
| GET | `/up` | Health check Laravel |
| GET | `/` | Nome da API |
| POST | `/api/identify` | Multipart `image` (jpg/png, até 10 MB). Resposta: `{ "matches": [...] }` |

A chave da PlantNet não sai no frontend.
