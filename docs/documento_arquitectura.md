# Documento de Arquitectura — Sistema de Registro y Autenticación

## 1. Visión General

El sistema fue migrado de una arquitectura PHP plana (sin framework) a una aplicación **Laravel 13** con patrón **MVC** completo. La nueva versión incorpora autenticación provista por **Laravel Breeze**, persistencia en base de datos relacional mediante **Eloquent ORM**, y frontend reactivo con **Alpine.js** y **Tailwind CSS** compilado con **Vite**.

---

## 2. Stack Tecnológico

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | PHP | 8.3 |
| Framework | Laravel | 13 |
| Autenticación | Laravel Breeze | 2 |
| ORM / Persistencia | Eloquent + SQLite | — |
| Frontend (estilos) | Tailwind CSS | 3 |
| Frontend (interactividad) | Alpine.js | 3 |
| Bundler de assets | Vite + laravel-vite-plugin | 8 / 3 |
| Motor de plantillas | Blade | — |
| Sesiones | Base de datos (tabla `sessions`) | — |
| Testing | PHPUnit | 12 |
| Formateador de código | Laravel Pint | 1 |
| Control de versiones | Git | — |

---

## 3. Estructura de Directorios

```
registration_ia_laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                   # Controladores de autenticación (Breeze)
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── VerifyEmailController.php
│   │   │   │   └── ConfirmablePasswordController.php
│   │   │   ├── ProfileController.php   # Gestión de perfil de usuario
│   │   │   └── Controller.php
│   │   └── Requests/
│   │       ├── Auth/
│   │       │   └── LoginRequest.php    # Validación + rate limiting de login
│   │       └── ProfileUpdateRequest.php
│   ├── Models/
│   │   └── User.php                    # Modelo Eloquent principal
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── View/
│       └── Components/
│           ├── AppLayout.php           # Layout autenticado
│           └── GuestLayout.php         # Layout público
├── bootstrap/
│   ├── app.php                         # Configuración del kernel HTTP
│   └── providers.php
├── config/                             # Configuración de Laravel (app, auth, db, etc.)
├── database/
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_cache_table.php
│   │   └── 0001_01_01_000002_create_jobs_table.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── public/
│   └── index.php                       # Punto de entrada HTTP único
├── resources/
│   ├── views/
│   │   ├── auth/                       # Vistas de autenticación (Blade)
│   │   ├── components/                 # Componentes Blade reutilizables
│   │   ├── layouts/                    # Layouts app.blade.php / guest.blade.php
│   │   ├── profile/                    # Vistas de perfil y partials
│   │   ├── dashboard.blade.php
│   │   └── welcome.blade.php
│   └── css / js                        # Assets fuente (procesados por Vite)
├── routes/
│   ├── web.php                         # Rutas principales
│   ├── auth.php                        # Rutas de autenticación
│   └── console.php
└── tests/
    ├── Feature/
    │   ├── Auth/                        # Tests de registro, login, verificación, etc.
    │   └── ProfileTest.php
    └── Unit/
```

---

## 4. Responsabilidades por Capa

### Rutas (`routes/`)
- `web.php`: rutas públicas (`/`, `/dashboard`) y rutas protegidas de perfil (`/profile`).
- `auth.php`: todas las rutas del ciclo de autenticación, agrupadas por middleware `guest` y `auth`.

### Controladores (`app/Http/Controllers/`)

| Controlador | Responsabilidad |
|---|---|
| `RegisteredUserController` | Mostrar y procesar el formulario de registro |
| `AuthenticatedSessionController` | Login y logout |
| `PasswordResetLinkController` | Envío del email de recuperación de contraseña |
| `NewPasswordController` | Formulario y procesamiento del restablecimiento de contraseña |
| `PasswordController` | Actualización de contraseña desde el perfil |
| `EmailVerificationPromptController` | Recordatorio de verificación de email |
| `VerifyEmailController` | Procesamiento del enlace firmado de verificación |
| `ConfirmablePasswordController` | Re-confirmación de contraseña para acciones sensibles |
| `ProfileController` | Ver, actualizar y eliminar el perfil del usuario |

### Form Requests (`app/Http/Requests/`)
- `LoginRequest`: valida credenciales e implementa **rate limiting** (máx. 5 intentos por email+IP, bloqueo temporal con mensaje localizado).
- `ProfileUpdateRequest`: valida la actualización de nombre y email.

### Modelo (`app/Models/User.php`)
- Extiende `Authenticatable` de Laravel.
- Campos rellenables declarados vía atributo PHP 8 `#[Fillable]`: `name`, `email`, `password`.
- Campo `password` con cast `hashed` (bcrypt automático).
- Campos ocultos vía `#[Hidden]`: `password`, `remember_token`.
- Usa traits `HasFactory` y `Notifiable`.

### Vistas (`resources/views/`)
- Motor **Blade** con layouts y componentes reutilizables.
- Componentes de UI: `text-input`, `input-label`, `input-error`, `primary-button`, `secondary-button`, `danger-button`, `modal`, `dropdown`, `nav-link`, `auth-session-status`.
- Layouts: `layouts/app.blade.php` (autenticado con navegación) y `layouts/guest.blade.php` (público, centrado).

---

## 5. Modelo de Datos

### Tabla `users`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | bigint (PK) | Auto-incremental |
| `name` | string | Nombre del usuario |
| `email` | string (unique) | Correo electrónico |
| `email_verified_at` | timestamp (nullable) | Fecha de verificación de email |
| `password` | string | Hash bcrypt |
| `remember_token` | string (nullable) | Token "recuérdame" |
| `created_at` / `updated_at` | timestamp | Timestamps automáticos |

### Tabla `sessions`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | string (PK) | ID de sesión |
| `user_id` | bigint (nullable, FK) | Usuario asociado |
| `ip_address` | string (nullable) | IP del cliente |
| `user_agent` | text (nullable) | Navegador del cliente |
| `payload` | longtext | Datos de sesión serializados |
| `last_activity` | integer | Timestamp de última actividad |

### Tabla `password_reset_tokens`

| Columna | Tipo | Descripción |
|---|---|---|
| `email` | string (PK) | Email al que se envió el token |
| `token` | string | Token hash de restablecimiento |
| `created_at` | timestamp (nullable) | Fecha de creación |

---

## 6. Flujo de Ejecución

### Registro

```
GET /register
    └─► RegisteredUserController::create()
          └─► view('auth.register')

POST /register
    └─► RegisteredUserController::store()
          ├─► Validación (name, email único, password confirmado + reglas)
          ├─► User::create() → password hasheado con bcrypt
          ├─► event(Registered) → dispara notificación de verificación de email
          ├─► Auth::login($user)
          └─► redirect('/dashboard')
```

### Login

```
GET /login
    └─► AuthenticatedSessionController::create()
          └─► view('auth.login')

POST /login
    └─► AuthenticatedSessionController::store()
          └─► LoginRequest::authenticate()
                ├─► ensureIsNotRateLimited() [máx. 5 intentos / email+IP]
                ├─► Auth::attempt(email, password, remember)
                │     ├─► FALLO: RateLimiter::hit() → ValidationException
                │     └─► ÉXITO: RateLimiter::clear()
                ├─► session()->regenerate()
                └─► redirect('/dashboard')
```

### Logout

```
POST /logout
    └─► AuthenticatedSessionController::destroy()
          ├─► Auth::logout()
          ├─► session()->invalidate()
          ├─► session()->regenerateToken()
          └─► redirect('/')
```

### Acceso a ruta protegida

```
GET /dashboard
    ├─► Middleware 'auth': ¿usuario autenticado?
    │     └─► NO: redirect('/login')
    ├─► Middleware 'verified': ¿email verificado?
    │     └─► NO: redirect('/verify-email')
    └─► SÍ: view('dashboard')
```

### Recuperación de contraseña

```
GET /forgot-password → formulario de email
POST /forgot-password → envía email con enlace firmado (token en BD)
GET /reset-password/{token} → formulario de nueva contraseña
POST /reset-password → valida token, actualiza contraseña, redirige a login
```

### Gestión de perfil

```
GET  /profile → ProfileController::edit() → view('profile.edit')
PATCH /profile → ProfileController::update() → actualiza name/email
                  (si cambia email: email_verified_at = null)
DELETE /profile → ProfileController::destroy() → elimina cuenta + cierra sesión
```

---

## 7. Gestión de Sesiones

Las sesiones se almacenan en la **base de datos** (tabla `sessions`), no en el sistema de archivos.

| Operación | Mecanismo |
|---|---|
| Inicio de sesión | `Auth::login($user)` / `Auth::attempt()` + `session()->regenerate()` |
| Verificación de sesión | Middleware `auth` |
| Cierre de sesión | `Auth::logout()` + `session()->invalidate()` + `session()->regenerateToken()` |
| Cookie cliente | `laravel_session` (HTTP-only, configurable como Secure/SameSite) |

---

## 8. Seguridad Implementada

| Mecanismo | Implementación |
|---|---|
| Hash de contraseñas | `bcrypt` vía cast `hashed` en Eloquent |
| Protección CSRF | Token automático de Laravel en todos los formularios POST/PATCH/DELETE |
| Rate limiting de login | `RateLimiter` en `LoginRequest`: 5 intentos / email+IP, bloqueo con backoff |
| Regeneración de sesión | `session()->regenerate()` al login para prevenir session fixation |
| Invalidación de sesión | `session()->invalidate()` + `regenerateToken()` al logout |
| Rutas protegidas | Middleware `auth` y `verified` en grupos de rutas |
| Verificación de email | Evento `Registered` + enlace firmado (`signed` middleware) |
| Confirmación de contraseña | Middleware `password.confirm` para operaciones sensibles |
| Eliminación de cuenta | Requiere confirmación de contraseña actual (`current_password`) |
| Sanitización | Validación con Form Requests + reglas de Laravel (tipo, longitud, unicidad) |

---

## 9. Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────┐
│                        Navegador                         │
│         HTML / Blade + Alpine.js + Tailwind CSS          │
└───────────────────────────┬─────────────────────────────┘
                            │ HTTP (GET / POST / PATCH / DELETE)
                            ▼
┌─────────────────────────────────────────────────────────┐
│                  public/index.php                        │
│              (único punto de entrada)                    │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│                   Kernel HTTP / Router                   │
│         Middleware: auth, guest, verified, throttle      │
└───────────┬───────────────────────────┬─────────────────┘
            │                           │
            ▼                           ▼
┌──────────────────────┐    ┌──────────────────────────────┐
│  Auth Controllers    │    │  ProfileController            │
│  (Breeze scaffolding)│    │  (editar / eliminar perfil)  │
└──────────┬───────────┘    └────────────┬─────────────────┘
           │                             │
           ▼                             ▼
┌─────────────────────────────────────────────────────────┐
│                   Eloquent ORM                           │
│                  Modelo User                             │
└───────────────────────────┬─────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────┐
│                   SQLite (base de datos)                 │
│  Tablas: users · sessions · password_reset_tokens       │
│          cache · jobs                                    │
└─────────────────────────────────────────────────────────┘
```

---

## 10. Testing

Los tests se ubican en `tests/` y se ejecutan con PHPUnit 12 mediante `php artisan test`.

| Archivo | Cobertura |
|---|---|
| `Feature/Auth/RegistrationTest.php` | Flujo completo de registro |
| `Feature/Auth/AuthenticationTest.php` | Login / logout |
| `Feature/Auth/PasswordResetTest.php` | Solicitud y uso de token de restablecimiento |
| `Feature/Auth/PasswordUpdateTest.php` | Actualización de contraseña desde perfil |
| `Feature/Auth/PasswordConfirmationTest.php` | Re-confirmación de contraseña |
| `Feature/Auth/EmailVerificationTest.php` | Verificación de email |
| `Feature/ProfileTest.php` | Ver, actualizar y eliminar perfil |
| `Feature/ExampleTest.php` | Test de humo básico |
| `Unit/ExampleTest.php` | Ejemplo unitario base |

---

## 11. Comparativa con la Arquitectura Anterior

| Aspecto | Versión anterior (PHP plano) | Versión actual (Laravel 13) |
|---|---|---|
| Arquitectura | Monolítica sin capas | MVC con separación explícita |
| Persistencia | `users.json` (archivo local) | SQLite con Eloquent ORM |
| Autenticación | Manual con `$_SESSION` | Laravel Auth + Breeze |
| Hash de contraseñas | MD5 (inseguro) | bcrypt (seguro) |
| Protección CSRF | Ninguna | Token CSRF automático en todos los formularios |
| Rate limiting | Ninguno | 5 intentos por email+IP con backoff |
| Recuperación de contraseña | No existía | Flujo completo con email + token firmado |
| Verificación de email | No existía | Disponible (comentado, activable) |
| Gestión de perfil | No existía | Editar nombre/email, cambiar contraseña, eliminar cuenta |
| Frontend | HTML + CSS manual | Blade + Tailwind CSS v3 + Alpine.js v3 |
| Bundler | Ninguno | Vite |
| Testing | Sin tests | PHPUnit 12 con cobertura de todos los flujos |
| Sesiones | Archivos temporales PHP | Tabla `sessions` en base de datos |
