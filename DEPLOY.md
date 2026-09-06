# Deploy — Sistema SUOEM

Ambiente temporal en VPS DonWeb. Documento de referencia para actualizaciones y mantenimiento.

---

## Datos del entorno

| | |
|---|---|
| Servidor | `66.97.41.189` (DonWeb Cloud Server) |
| SSH | `ssh -p 5688 mauricio@66.97.41.189` |
| Ruta del proyecto | `/opt/suoem_sistema` |
| Compose de producción | `docker-compose.prod.yaml` (no versionado) |
| URL pública | `http://suoem.kaucoder.site` |
| Panel admin | `http://suoem.kaucoder.site/admin/login` |
| Repositorio | `github.com/oliveram2284/suoem_sistema` |
| Proxy | Traefik (contenedor `coolify-proxy`) |

**Contenedores:** `suoem_sistema-app-1` (PHP-FPM), `suoem_sistema-web-1` (Nginx), `suoem_sistema-db-1` (PostgreSQL 13)

El código se monta desde el host (`./:/var/www`), así que los cambios de archivos se reflejan sin reconstruir imágenes.

---

## Deploy estándar

El caso más común: cambios de código PHP, vistas, configuración de rutas.

```bash
cd /opt/suoem_sistema
sudo git pull
sudo chown -R www-data:www-data storage bootstrap/cache
sudo docker compose -f docker-compose.prod.yaml exec app php artisan config:clear
sudo docker compose -f docker-compose.prod.yaml exec app php artisan cache:clear
```

El `chown` es necesario porque `git pull` corre como root y cambia el propietario de los archivos que toca. Sin eso, PHP-FPM (que corre como `www-data`) no puede escribir en storage y la aplicación devuelve error 500.

Verificá que quedó bien abriendo el panel en el navegador.

---

## Casos que requieren pasos extra

### Cambiaron las dependencias de Composer

Si el `composer.json` o `composer.lock` se modificaron:

```bash
sudo docker run --rm -v /opt/suoem_sistema:/app -w /app composer:latest \
  install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts
sudo chown -R www-data:www-data vendor storage bootstrap/cache
```

El `--no-scripts` evita que corra `package:discover`, que falla sin entorno completo.

### Hay migraciones nuevas

```bash
sudo docker compose -f docker-compose.prod.yaml exec app php artisan migrate --force
```

Sacá un backup antes si la migración es destructiva (ver sección de backups).

### Cambió el Dockerfile o Dockerfile.nginx

```bash
sudo docker compose -f docker-compose.prod.yaml up -d --build
```

Tarda varios minutos. El paso más lento es el `chown -R` sobre `vendor/`.

### Cambiaron las variables de entorno

El `.env` vive solo en el servidor, no está en el repositorio.

```bash
sudo vim .env
sudo docker compose -f docker-compose.prod.yaml restart app
```

---

## Probar otra rama

```bash
cd /opt/suoem_sistema
sudo git fetch
sudo git checkout nombre-de-la-rama
sudo chown -R www-data:www-data storage bootstrap/cache
sudo docker compose -f docker-compose.prod.yaml exec app php artisan config:clear
```

Después aplicá los pasos extra que correspondan (composer, migraciones).

Para volver:

```bash
sudo git checkout main
```

**Dos advertencias.** La base de datos es una sola, compartida entre todas las ramas: si una rama trae migraciones incompatibles, el estado queda inconsistente para las demás. Y mientras estés en una rama de prueba, es lo que ve cualquiera que abra la URL — tenelo en cuenta si le pasaste el link al cliente.

---

## Backups

### Sacar un dump

```bash
sudo docker compose -f docker-compose.prod.yaml exec db \
  pg_dump -U suoem suoem_db > ~/backup_$(date +%F_%H%M).sql
```

### Restaurar

```bash
cat ~/backup_ARCHIVO.sql | sudo docker compose -f docker-compose.prod.yaml exec -T db \
  psql -U suoem -d suoem_db
```

Hacelo siempre antes de correr migraciones destructivas o de probar una rama con cambios de esquema.

---

## Diagnóstico

### Ver el estado de los contenedores

```bash
sudo docker compose -f docker-compose.prod.yaml ps
```

### Logs

```bash
# Nginx: accesos y errores HTTP
sudo docker compose -f docker-compose.prod.yaml logs web --tail 50

# PHP: errores de Laravel (LOG_CHANNEL=stderr va acá, no a storage/logs)
sudo docker compose -f docker-compose.prod.yaml logs app --tail 50

# PostgreSQL
sudo docker compose -f docker-compose.prod.yaml logs db --tail 50
```

### Consola de la aplicación

```bash
sudo docker compose -f docker-compose.prod.yaml exec app bash
sudo docker compose -f docker-compose.prod.yaml exec app php artisan tinker
```

### Consola de PostgreSQL

```bash
sudo docker compose -f docker-compose.prod.yaml exec db psql -U suoem -d suoem_db
```

### Reiniciar todo

```bash
sudo docker compose -f docker-compose.prod.yaml restart
```

---

## Errores frecuentes

**Error 500 después de un `git pull`**
Permisos. Corré el `chown -R www-data:www-data storage bootstrap/cache`.

**Error 403 al entrar al panel**
Filament rechaza al usuario. Verificá que `app/Models/User.php` implemente `FilamentUser` con `canAccessPanel()`. En producción, sin esa interfaz Filament bloquea el acceso por defecto.

**Cambios de código que no se reflejan**
Caché de configuración o de vistas:
```bash
sudo docker compose -f docker-compose.prod.yaml exec app php artisan config:clear
sudo docker compose -f docker-compose.prod.yaml exec app php artisan view:clear
```
Si persiste, puede ser opcache (está activo con `revalidate_freq=60`): reiniciá el contenedor `app`.

**"Port is already allocated"**
Traefik ocupa 80, 443 y 8080. El compose de producción no publica puertos a propósito — el enrutamiento va por labels de Traefik.

**El sitio no responde desde afuera**
Verificá que el contenedor `web` esté en la red `coolify` y que Traefik esté corriendo:
```bash
sudo docker ps | grep proxy
sudo docker inspect suoem_sistema-web-1 | grep -A3 coolify
```

---

## Mantenimiento del disco

El servidor tiene 20 GB. Docker acumula imágenes y capas de builds anteriores.

```bash
df -h /
sudo docker system df
sudo docker system prune -af
```

Corré el `prune` cada varios deploys con `--build`, o si el disco pasa el 80%.

---

## Pendientes

- [ ] **HTTPS**: cambiar el entrypoint de las labels de Traefik a `https` con `certresolver=letsencrypt`, y `APP_URL=https://...` en el `.env`
- [ ] **Basic auth** sobre `/admin` (middleware de Traefik)
- [ ] **Backup automático** de Postgres (cron diario)
- [ ] **Reinicio del servidor** pendiente desde la actualización del kernel
- [ ] **Cambiar la contraseña de Postgres** (`suoem2026` es débil) si esto pasa a definitivo
- [ ] **Datos de prueba**: verificar que no haya información real de afiliados antes de mostrarlo al cliente

---

## Notas del setup

Cosas que costaron encontrar y conviene no olvidar:

- **Coolify quedó instalado** pero no gestiona esta aplicación. Solo se aprovecha su Traefik como proxy. El recurso de Coolify para SUOEM se puede borrar sin afectar nada.
- **El puerto SSH es 5688**, no el 22. DonWeb lo cambia por defecto.
- **DonWeb filtra puertos**: solo 80, 443 y 5688 llegan desde afuera. El 8000 y el 8080 están bloqueados aguas arriba del `ufw`.
- **`LOG_CHANNEL=stderr`**: Laravel no escribe en `storage/logs/laravel.log`, todo va a los logs del contenedor.
- **`trustProxies(at: '*')`** en `bootstrap/app.php` es necesario porque Traefik está adelante. Sin eso, Laravel genera URLs con el esquema equivocado.
