# Docker — Guía de uso

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) 24+
- [Docker Compose](https://docs.docker.com/compose/install/) v2+

---

## Inicio rápido

### 1. Clonar el repositorio

```bash
git clone <repo-url>
cd registration_ia_laravel
```

### 2. Configurar variables de entorno

```bash
cp .env.example .env
```

Editar `.env` y ajustar al menos:

```env
APP_KEY=          # se genera automáticamente al iniciar si está vacío
APP_URL=http://localhost:8080
APP_ENV=production
APP_DEBUG=false
```

> El contenedor genera la `APP_KEY` automáticamente si no se provee.

### 3. Construir y levantar

```bash
docker compose up -d --build
```

La aplicación queda disponible en **http://localhost:8080** (el contenedor escucha internamente en el puerto 8080 como usuario `www-data`, sin necesitar privilegios root).

---

## Estructura de archivos Docker

```
├── Dockerfile                        # Imagen multi-stage (Node + PHP 8.3 FPM + Nginx)
├── docker-compose.yml                # Orquestación local
└── docker/
    ├── nginx/nginx.conf              # Configuración de Nginx
    ├── supervisor/supervisord.conf   # Supervisor (PHP-FPM + Nginx)
    └── entrypoint.sh                 # Bootstrap: migraciones, caché, permisos
```

---

## Comandos frecuentes

| Acción | Comando |
|---|---|
| Levantar en background | `docker compose up -d` |
| Ver logs en tiempo real | `docker compose logs -f app` |
| Detener contenedores | `docker compose down` |
| Reconstruir imagen | `docker compose up -d --build` |
| Abrir shell en el contenedor | `docker compose exec app sh` |
| Correr Artisan | `docker compose exec app php artisan <comando>` |
| Correr tests | `docker compose exec app php artisan test --compact` |

---

## Base de datos

Por defecto se usa **SQLite**. El archivo `database/database.sqlite` se crea automáticamente dentro del contenedor y se monta como volumen local en `./database/`.

### Cambiar a MySQL

1. Levantar un servicio MySQL en `docker-compose.yml`:

```yaml
services:
  app:
    # ... config existente ...
    environment:
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: 3306
      DB_DATABASE: registration_ia
      DB_USERNAME: laravel
      DB_PASSWORD: secret
    depends_on:
      db:
        condition: service_healthy

  db:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: registration_ia
      MYSQL_USER: laravel
      MYSQL_PASSWORD: secret
      MYSQL_ROOT_PASSWORD: rootsecret
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 5s
      retries: 10

volumes:
  db_data:
```

2. Reconstruir: `docker compose up -d --build`

---

## Volúmenes persistentes

| Volumen | Contenido |
|---|---|
| `./database` | Archivo SQLite |
| `app_storage` | Archivos subidos (`storage/app`) |
| `app_logs` | Logs de Laravel (`storage/logs`) |

---

## Producción

Para un entorno de producción asegurarse de:

- Pasar `APP_KEY` como variable de entorno segura (no generarla en runtime)
- Usar `APP_DEBUG=false` y `APP_ENV=production`
- Configurar HTTPS mediante un reverse proxy (ej. Traefik o Nginx externo)
- No montar el código fuente como volumen; usar la imagen construida

---

## Solución de problemas

**Error: `Unable to locate file in Vite manifest`**

```bash
# La imagen no tiene los assets compilados; reconstruir:
docker compose up -d --build
```

**Error de permisos en storage**

```bash
docker compose exec app chmod -R 775 storage bootstrap/cache
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
```

**Ver logs de Nginx o PHP-FPM**

```bash
docker compose exec app cat /var/log/supervisor/nginx.err.log
docker compose exec app cat /var/log/supervisor/php-fpm.err.log
```
