# Security Audit Report — registration_ia_laravel

**Fecha:** 2026-05-19  
**Auditor:** Claude Code (claude-sonnet-4-6)  
**Rama analizada:** `secure`  
**Alcance:** Todo el código fuente en `app/`, `routes/`, `config/`, `resources/views/`, `bootstrap/`, `.env.example`, `composer.json`, `composer.lock`

---

## Resumen Ejecutivo

| Severidad | Cantidad |
|-----------|----------|
| CRÍTICA   | 0        |
| ALTA      | 2        |
| MEDIA     | 4        |
| BAJA      | 5        |
| INFO      | 3        |
| **Total** | **14**   |

El proyecto es una aplicación Laravel 13 scaffoldeada con Laravel Breeze. No se detectaron vulnerabilidades críticas (RCE, SQLi, credenciales hardcodeadas). Los riesgos más importantes son la ausencia de verificación de email obligatoria y la falta completa de headers de seguridad HTTP.

---

## Tabla de Hallazgos

| # | Hallazgo | Severidad | Archivo | Línea | Explicación | Fix recomendado |
|---|----------|-----------|---------|-------|-------------|-----------------|
| 1 | Email verification deshabilitada | ALTA | `app/Models/User.php` | 5 | `MustVerifyEmail` está comentado. Usuarios pueden acceder al dashboard sin verificar su dirección de correo. Un atacante puede registrarse con el email de otra persona y obtener acceso. | Descomentar `// use Illuminate\Contracts\Auth\MustVerifyEmail;` e implementar la interfaz en la clase `User`. |
| 2 | Rate limiting ausente en `/register` | ALTA | `routes/auth.php` | 18 | La ruta `POST /register` no tiene ningún middleware de throttling. Cualquiera puede crear cuentas de forma masiva (account enumeration, spam de base de datos, abuso de recursos). El login SÍ tiene rate limiting via `LoginRequest::ensureIsNotRateLimited()`, pero el registro no. | Agregar `->middleware('throttle:10,1')` a la ruta `POST register`, o definir un límite en `AppServiceProvider` usando `RateLimiter::for('register', ...)`. |
| 3 | `SESSION_SECURE_COOKIE` sin valor explícito | MEDIA | `config/session.php`, `.env.example` | 172 / — | El valor es `env('SESSION_SECURE_COOKIE')` sin default, lo que resulta en `null` (falso). La cookie de sesión puede enviarse sobre HTTP, exponiéndola a intercepción. | Establecer `SESSION_SECURE_COOKIE=true` en `.env` de producción, o fijar `'secure' => env('SESSION_SECURE_COOKIE', true)` en `config/session.php`. |
| 4 | `SESSION_ENCRYPT=false` en `.env.example` | MEDIA | `.env.example` | 32 | El cifrado de sesión está deshabilitado por defecto. Si la clave de sesión (APP_KEY) se filtra junto con el almacenamiento de sesiones (DB), los datos pueden leerse en texto claro. | Cambiar a `SESSION_ENCRYPT=true` en producción. |
| 5 | `APP_DEBUG=true` en `.env.example` | MEDIA | `.env.example` | 4 | El template de entorno tiene `APP_DEBUG=true`. Si alguien copia `.env.example` sin modificarlo en producción, se expondrán stack traces con rutas internas, versiones de paquetes y posibles variables de entorno. | Cambiar a `APP_DEBUG=false` en `.env.example`. Dejar el valor `true` únicamente en documentación de desarrollo. |
| 6 | `LOG_LEVEL=debug` en `.env.example` | MEDIA | `.env.example` | 21 | El nivel de log por defecto es `debug`, lo que puede escribir datos sensibles (queries SQL, payloads de peticiones, tokens) en los archivos de log. | Cambiar a `LOG_LEVEL=error` o `LOG_LEVEL=warning` en `.env.example`. Usar `debug` solo en entornos locales explícitamente. |
| 7 | Headers de seguridad HTTP ausentes | BAJA | `bootstrap/app.php` | 13–16 | No se configura ningún middleware global de seguridad: faltan `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`, `Referrer-Policy`. | Agregar un middleware global en `bootstrap/app.php` usando `$middleware->append(SecurityHeadersMiddleware::class)` o usar un paquete como `spatie/laravel-csp`. Mínimo recomendado: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`. |
| 8 | Cookie `SameSite=lax` (no `strict`) | BAJA | `config/session.php` | 202 | El valor `lax` permite el envío de cookies en navegación de nivel superior desde sitios externos (ej. clic en un enlace). Para aplicaciones sin integración OAuth, `strict` es preferible. | Cambiar `'same_site' => 'strict'` si no se usan flujos OAuth o redirecciones cross-site. |
| 9 | `SESSION_LIFETIME=120` sin `expire_on_close` | BAJA | `.env.example`, `config/session.php` | 35 / 37 | La sesión dura 120 minutos incluso si el usuario cierra el navegador. En equipos compartidos esto es un riesgo. | Considerar `SESSION_EXPIRE_ON_CLOSE=true` o reducir `SESSION_LIFETIME` a 60 minutos para aplicaciones de mayor sensibilidad. |
| 10 | `forceFill` con datos del request | BAJA | `app/Http/Controllers/Auth/NewPasswordController.php` | 46–49 | `$user->forceFill(['password' => ..., 'remember_token' => ...])` evita la protección `$fillable`. En este caso los campos son hardcodeados (no provenientes de input del usuario), por lo que el riesgo es bajo. Sin embargo, el patrón puede propagarse de forma insegura. | Preferir `$user->update(['password' => Hash::make(...)])` que respeta `$fillable` y es más seguro como patrón general. |
| 11 | Archivo `.env.example` establece `APP_ENV=local` | BAJA | `.env.example` | 2 | Si el `.env.example` se copia sin editar, la aplicación corre en modo `local` con comportamientos de desarrollo (excepciones detalladas, etc.). | Documentar claramente que `APP_ENV=production` debe setearse antes del deploy. |
| 12 | No hay Content Security Policy | INFO | — | — | Sin CSP definida, el navegador ejecutará cualquier script inline o de terceros sin restricciones. Reduce la superficie de ataque frente a XSS. | Definir una política CSP mínima como middleware: `default-src 'self'; script-src 'self'; object-src 'none'`. |
| 13 | No hay `robots.txt` restrictivo | INFO | `public/robots.txt` | — | El `robots.txt` actual no restringe el indexado de rutas sensibles como `/login`, `/register`, `/reset-password`. Esto no es una vulnerabilidad en sí, pero ayuda a reducir exposición. | Agregar `Disallow: /login`, `Disallow: /register`, `Disallow: /forgot-password` al `robots.txt`. |
| 14 | `SESSION_DOMAIN=null` sin configurar | INFO | `.env.example` | 36 | El dominio de cookie no está restringido. En entornos con múltiples subdominios podría haber fuga de cookies. | Configurar `SESSION_DOMAIN=.tu-dominio.com` en producción. |

---

## Detalles por Categoría

### Autenticación y Autorización

- **Bien:** Rate limiting en login (5 intentos por IP+email). Uso correcto de `Auth::attempt()`, regeneración de token CSRF en logout, invalidación de sesión en logout.
- **Bien:** Tokens de reset de password con expiración de 60 minutos y throttle de 60 segundos entre reenvíos.
- **Bien:** Confirmación de password antes de acciones destructivas (`ConfirmablePasswordController`).
- **Bien:** Uso de `Rules\Password::defaults()` para validación de complejidad de contraseñas.
- **Riesgo:** Email verification deshabilitada (hallazgo #1).
- **Riesgo:** Sin rate limiting en registro (hallazgo #2).
- **Ausente:** No existen Policies ni Gates más allá del middleware `auth`. Para una aplicación simple esto es aceptable, pero debe documentarse.

### CSRF

- Todas las rutas POST/PATCH/PUT/DELETE con formularios Blade usan `@csrf`. No se detectaron formularios sin token CSRF.
- Laravel incluye el middleware `VerifyCsrfToken` de forma global para rutas web.

### XSS

- No se encontró uso de `{!! !!}` en ninguna vista. Todas las variables de usuario se renderizan con `{{ }}` (escapeado automático de Blade).
- **Sin hallazgos de XSS.**

### SQL Injection

- No se encontraron consultas crudas con interpolación de variables.
- No hay uso de `DB::statement`, `DB::select`, `DB::insert` con datos de usuario.
- El modelo `User` usa el ORM Eloquent con `$fillable` definido via atributo PHP 8 `#[Fillable]`.
- **Sin hallazgos de SQLi.**

### Mass Assignment

- Modelo `User` protegido con `#[Fillable(['name', 'email', 'password'])]`. No hay `$guarded = []`.
- **Sin hallazgos de mass assignment.**

### Secretos y Credenciales

- No se encontraron credenciales hardcodeadas en código PHP.
- El archivo `.env` está correctamente en `.gitignore` y no está trackeado en git.
- No se encontraron archivos `.sql`, `.bak` ni dumps en el repositorio.
- **Sin hallazgos de secretos expuestos.**

### Funciones Peligrosas

- No se encontró uso de `exec()`, `shell_exec()`, `system()`, `passthru()`, `eval()`, `unserialize()`, `include`/`require` con variables.
- **Sin hallazgos de RCE o deserialización insegura.**

### Upload de Archivos

- La aplicación no implementa funcionalidad de upload de archivos en este momento.
- **Sin hallazgos de upload inseguro.**

---

## Sección de Dependencias

### Versiones de paquetes principales

| Paquete | Versión instalada | Estado |
|---------|------------------|--------|
| laravel/framework | v13.8.0 | Reciente (mayo 2025) |
| symfony/http-kernel | v7.4.10 | Reciente |
| symfony/http-foundation | v7.4.8 | Reciente |
| guzzlehttp/guzzle | 7.10.0 | Reciente |
| monolog/monolog | 3.10.0 | Reciente |
| vlucas/phpdotenv | v5.6.3 | Reciente |
| laravel/tinker | v3.0.2 | Reciente |
| ramsey/uuid | 4.9.2 | Reciente |

### Evaluación de CVEs

No se identificaron CVEs conocidos activos para las versiones instaladas al momento de esta auditoría (2026-05-19). Todas las dependencias son versiones recientes de sus ramas estables.

**Recomendación:** Ejecutar `composer audit` periódicamente para detectar nuevas vulnerabilidades reportadas en el advisory database de Packagist.

```bash
composer audit
```

---

## Sección de Configuración

| Configuración | Valor actual | Recomendado para producción | Riesgo |
|---------------|-------------|----------------------------|--------|
| `APP_DEBUG` | `true` (en .env.example) | `false` | MEDIA |
| `APP_ENV` | `local` (en .env.example) | `production` | BAJA |
| `LOG_LEVEL` | `debug` | `error` o `warning` | MEDIA |
| `SESSION_ENCRYPT` | `false` | `true` | MEDIA |
| `SESSION_SECURE_COOKIE` | `null` (sin valor) | `true` | MEDIA |
| `SESSION_SAME_SITE` | `lax` | `strict` | BAJA |
| `SESSION_SERIALIZATION` | `json` | `json` (correcto) | OK |
| `BCRYPT_ROUNDS` | `12` | `12` (correcto) | OK |
| `password_timeout` | `10800s` (3h) | Considerar reducir | INFO |
| Headers HTTP de seguridad | Ninguno | CSP, X-Frame-Options, etc. | BAJA |

---

## Recomendaciones Generales

### Prioridad alta

1. **Habilitar verificación de email:** Implementar `MustVerifyEmail` en el modelo `User` para asegurar que solo usuarios con emails válidos accedan a recursos protegidos.

2. **Rate limiting en registro:** Proteger `POST /register` contra registro masivo y enumeración de usuarios.

### Prioridad media

3. **Revisar `.env.example` antes de cada deploy:** Asegurar que `APP_DEBUG=false`, `APP_ENV=production`, `LOG_LEVEL=error` y `SESSION_SECURE_COOKIE=true` estén seteados en producción.

4. **Habilitar cifrado de sesión:** Activar `SESSION_ENCRYPT=true` en producción como capa adicional de protección.

### Prioridad baja

5. **Implementar headers de seguridad HTTP:** Agregar un middleware global o configurar el servidor web (nginx/Apache) para enviar: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, y una política CSP básica.

6. **Configurar `SESSION_SECURE_COOKIE=true` explícitamente:** Garantizar que las cookies de sesión solo se transmitan por HTTPS.

7. **Auditoría periódica de dependencias:** Incorporar `composer audit` al pipeline de CI/CD.

8. **Monitoreo de logs:** Configurar alertas sobre errores en producción en lugar de nivel `debug`.

---

## Metodología de Auditoría

- Análisis estático manual de todos los archivos PHP en `app/`, `routes/`, `config/`, `bootstrap/`
- Revisión de todas las vistas Blade en `resources/views/`
- Búsqueda de patrones de vulnerabilidades conocidas: SQLi, XSS (`{!! !!}`), CSRF, SSRF, command injection, file inclusion, mass assignment, deserialización
- Revisión de `.gitignore` y estado de git para secretos comprometidos
- Verificación de permisos del filesystem (sin archivos con chmod 777)
- Análisis de `composer.lock` contra versiones conocidas
- Revisión de configuración de sesión, autenticación y logging
