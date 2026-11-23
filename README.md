# SSMT — Sistema de Seguimiento de Titulación

Monorepo con dos aplicaciones independientes:

- **`backend/`** — API REST en **Laravel 13** (PHP 8.3) con autenticación Sanctum.
- **`frontend/`** — SPA en **Vue 3 + Vite** (Node 22.18+ o 24.12+).

## Requisitos

| Herramienta | Versión |
| --- | --- |
| PHP | 8.3+ (extensiones `pdo_sqlite` o `pdo_mysql`, `mbstring`, `fileinfo`) |
| Composer | 2.x |
| Node.js | ^22.18.0 o >=24.12.0 |
| npm | incluido con Node |
| MySQL/MariaDB | 8.x (opcional; por defecto se usa SQLite) |

## Estructura

```
SSMT/
├── backend/     # Laravel (API, migraciones, seeders, módulos)
├── frontend/    # Vue 3 + Vite (SPA)
└── sissg.sql    # Volcado MySQL de la base de datos existente
```

## 1. Configurar el backend

```bash
cd backend
composer install
cp .env.example .env          # En PowerShell: copy .env.example .env
php artisan key:generate
```

### Base de datos (elige una opción)

**Opción A — SQLite (rápida, recomendada para desarrollo local):**

El `.env.example` ya viene con `DB_CONNECTION=sqlite`. Solo crea el archivo y migra:

```bash
# Git Bash / macOS / Linux
mkdir -p database && touch database/database.sqlite

# PowerShell
# New-Item -ItemType File -Force database\database.sqlite
```

```bash
php artisan migrate --seed
php artisan storage:link
```

**Opción B — MySQL (usar el volcado `sissg.sql`):**

1. Crea la base de datos e importa el volcado desde la raíz del repo:

   ```bash
   mysql -u root -p < ../sissg.sql
   ```

2. Ajusta `backend/.env`:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistemasg
   DB_USERNAME=root
   DB_PASSWORD=tu_password
   ```

3. Ejecuta `php artisan migrate --seed` (los seeders usan `updateOrCreate`, así que no duplican registros) y `php artisan storage:link`.

### Arrancar la API

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

La API quedará en `http://localhost:8000/api`. Verifica con `http://localhost:8000/up`.

> Si además usas las vistas Blade de Laravel, instala los assets con `npm install && npm run dev` dentro de `backend/` (o `composer run dev`, que levanta Vite, el servidor, colas y logs a la vez). Para una SPA pura no es necesario.

## 2. Configurar el frontend

```bash
cd frontend
npm install
cp .env.development.example .env.development   # En PowerShell: copy .env.development.example .env.development
npm run dev
```

Abre **http://localhost:5173**. Vite lee `.env.development` automáticamente y aplica un proxy de `/api` y `/storage` al backend.

Variables importantes de `frontend/.env.development`:

```dotenv
VITE_API_URL=http://localhost:8000/api
VITE_STORAGE_URL=http://localhost:8000/storage
VITE_PORT=5173
```

En `backend/.env`, el CORS debe apuntar al frontend (ya viene así en la plantilla):

```dotenv
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:5173
```

## Usuarios de prueba (tras `migrate --seed`)

El login usa **username** + contraseña (`POST /api/login`):

| Rol | Username | Contraseña |
| --- | --- | --- |
| Administrador | `administrador_1234567` | `password` |
| Kardex | `kardex_2345678` | `password` |
| Secretaría | `secretaria_3456789` | `password` |
| Dirección | `dirección_4567890` | `password` |
| Docente tutor | `docente_6789012` | `password` |
| Estudiante | `Estudiante_7890123` | `10-04-2001` |
| Estudiante 2 | `Estudiante_8901234` | `22-08-2002` |

Las credenciales de estudiantes se generan como `Nombre_CI` y contraseña `dd-mm-aaaa` (fecha de nacimiento).

## Comandos útiles

```bash
# Backend
php artisan migrate --seed          # migrar + datos de prueba
php artisan storage:link            # enlace público para adjuntos
php artisan route:list              # listar endpoints
php artisan test                    # pruebas (o: composer test)
vendor/bin/pint --dirty --format agent  # formatear PHP

# Frontend
npm run dev                         # servidor de desarrollo
npm run build                       # build de producción -> dist/
npm run preview                     # servir el build localmente
```

## Producción (resumen)

1. Backend: copia `.env.production.example` a `.env`, genera `APP_KEY`, configura MySQL, CORS (`FRONTEND_URL`) y ejecuta:

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan storage:link
   php artisan optimize
   php artisan queue:work database --tries=3 --timeout=60
   ```

2. Frontend: copia `.env.production.example` a `.env.production` (ajusta `VITE_API_URL` y `VITE_STORAGE_URL` a los URLs públicos) y ejecuta `npm ci && npm run build`. Publica el contenido de `frontend/dist/`.

3. Sirve el backend con PHP-FPM/Nginx o `php artisan serve --host=0.0.0.0 --port=8000` detrás de un proxy con HTTPS.

## Git

Los archivos `.env`, `.env.development` y `.env.production` reales **están ignorados** y no deben subirse. Solo se versionan las plantillas `.env*.example`. Si clonas el repo, copialas según las secciones anteriores antes de arrancar.
