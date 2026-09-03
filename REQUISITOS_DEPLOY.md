# Requisitos para deploy en servidor local (LAN)

Basado en el stack real del proyecto: Laravel 12, Filament 4, PHP ^8.2 (Dockerfile usa 8.4),
PostgreSQL, Nginx, Vite/Tailwind 4. Pensado para uso interno de oficina (pocos usuarios
concurrentes, sin exposición a internet).

## 1. Software

### Opción A — Docker (recomendada, ya está en el repo)

El repo trae `docker-compose.yaml` con 3 servicios: `app` (PHP-FPM 8.4), `web` (Nginx) y
`db` (PostgreSQL 13). Solo se necesita en el servidor:

- **Docker Engine** 24+ y **Docker Compose** v2
- Sistema operativo Linux (Debian/Ubuntu Server LTS recomendado; también funciona en
  cualquier distro con Docker)

Nada más se instala en el host — todo corre dentro de los contenedores.

### Opción B — Instalación nativa (sin Docker)

- **PHP 8.2+** (el `composer.json` pide `^8.2`; el proyecto se probó con 8.4)
- Extensiones PHP: `pdo_pgsql`, `gd`, `zip`, `intl`, `opcache`, `mbstring`, `bcmath`, `xml`,
  `ctype`, `fileinfo`, `tokenizer` (la mayoría vienen por defecto en PHP; solo `pdo_pgsql`,
  `intl`, `gd`, `zip` suelen requerir instalación explícita)
- **PostgreSQL 13+**
- **Nginx** (config ya provista en `nginx/nginx.conf`) o Apache
- **Composer 2**
- **Node.js 18+ y npm** — solo necesario para compilar assets (`npm install && npm run
  build`); no se necesita en runtime, el build genera `public/build/` como archivos
  estáticos servidos por Nginx

### No se necesita (aunque figura en `.env.example`)

- **Redis** — no se usa. `SESSION_DRIVER`, `CACHE_STORE` y `QUEUE_CONNECTION` están
  configurados en `database`, así que Postgres ya cubre esas necesidades.

## 2. Hardware (para uso LAN interno, ~5–20 usuarios concurrentes)

Es un CRM/CRUD administrativo (gestión de proveedores y movimientos/cuotas), no una
aplicación de alto tráfico. Los requisitos son modestos:

| Recurso | Mínimo | Recomendado |
|---|---|---|
| CPU | 2 vCPU | 4 vCPU |
| RAM | 2 GB | 4 GB |
| Disco | 20 GB | 40 GB SSD |
| Red | Ethernet 100 Mbps | Gigabit LAN |

Notas:
- Con Docker corriendo los 3 contenedores en la misma máquina, 4 GB de RAM da margen
  cómodo (Postgres + PHP-FPM + Nginx + Xdebug si no se quita, ver abajo).
- Disco: si van a crecer los volúmenes de importación (Excel/CSV de proveedores) o quieren
  retener backups de la base localmente, sumar espacio en consecuencia. La app en sí y la
  DB son livianas al día de hoy (44 proveedores de fixture).
- No hay requisitos especiales de red: al ser solo LAN interna, no hace falta certificado
  TLS ni balanceador.

## 3. Antes de pasar esto a producción — ajustes puntuales del repo

Cosas que noté en el código que conviene resolver antes del deploy real (no son parte de
"requisitos" pero afectan cómo lo levantás):

1. **Xdebug está instalado y habilitado en el `Dockerfile`** (`pecl install xdebug` +
   `docker-php-ext-enable xdebug`). Es una herramienta de desarrollo que agrega overhead
   en cada request; para un deploy "real" conviene sacarlo de la imagen o condicionarlo
   por build-arg/entorno.
2. **Credenciales hardcodeadas en `docker-compose.yaml`** (`POSTGRES_PASSWORD: 123456`).
   Pasarlas a variables de entorno / `.env` antes de levantar en el servidor destino.
3. **`APP_ENV=local` y `APP_DEBUG=true`** en `.env.example` — para el servidor de LAN
   conviene `APP_ENV=production` (o similar) y `APP_DEBUG=false`, para no exponer stack
   traces en pantalla.
4. **Assets sin compilar**: `public/build/` no existe todavía en el repo (está en
   `.gitignore`). Hay que correr `npm install && npm run build` como parte del proceso de
   deploy antes de servir la app.
5. **Migraciones/seed**: correr `php artisan migrate --force` (y el seeder si quieren los
   datos de fixture) al levantar por primera vez.
