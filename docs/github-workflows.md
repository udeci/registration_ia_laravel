# GitHub Actions Workflows

Documentación de los workflows de CI/CD del proyecto Laravel.

---

## Índice

- [pipeline.yml — CI Pipeline](#pipelineyml--ci-pipeline)
- [ia_code_review.yml — Code Review con IA](#ia_code_reviewyml--code-review-con-ia)
- [security.yml — Escaneo de Seguridad](#securityyml--escaneo-de-seguridad)
- [Secrets requeridos](#secrets-requeridos)
- [Cambios realizados para compatibilidad](#cambios-realizados-para-compatibilidad)

---

## pipeline.yml — CI Pipeline

**Archivo:** `.github/workflows/pipeline.yml`

### Qué hace

Pipeline principal de integración continua. Se ejecuta en cada push y pull request sobre `main`. Instala dependencias, configura el entorno, corre migraciones y ejecuta el test suite completo.

### Trigger

```
push → main
pull_request → main
```

### Pasos

| Paso | Descripción |
|---|---|
| Checkout Code | Clona el repositorio |
| Setup PHP 8.3 | Instala PHP con extensiones necesarias (`pdo_sqlite`, `mbstring`, `xml`, `bcmath`, `zip`) |
| Install Composer Dependencies | `composer install` sin flags deprecados |
| Prepare Environment File | Copia `.env.example` e inyecta `APP_KEY` desde Secrets |
| Generate Application Key | Genera la key solo si el Secret está vacío (fallback seguro) |
| Create SQLite database | Crea `database/database.sqlite` para los tests |
| Run Migrations | `php artisan migrate --force` |
| Run Test Suite | `php artisan test --compact` |

### Secrets necesarios

Este workflow **no requiere Secrets**. La `APP_KEY` se genera automáticamente en cada ejecución con `php artisan key:generate --force`, lo cual es seguro para CI ya que las sesiones no persisten entre runs.

### Sobre la `LARAVEL_APP_KEY`

La `APP_KEY` es la clave criptográfica que Laravel usa para cifrar sesiones, cookies y datos. Para el entorno **local** se genera con:

```bash
php artisan key:generate --show
# Output: base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
```

Ese valor vive en tu `.env` local y no necesita subirse a GitHub para que el pipeline de tests funcione.

---

## ia_code_review.yml — Code Review con IA

**Archivo:** `.github/workflows/ia_code_review.yml`

### Qué hace

Analiza automáticamente los cambios de código PHP en cada push a `main` usando la API de Google Gemini. Genera un reporte de seguridad y buenas prácticas directamente en los logs del workflow.

### Trigger

```
push → main
```

### Pasos

| Paso | Descripción |
|---|---|
| Checkout Code | Clona el repo con `fetch-depth: 2` para poder calcular el diff |
| Get Code Diff | Extrae el diff de archivos `.php` entre el commit actual y el anterior |
| Setup Python 3.10 | Entorno para ejecutar el script de auditoría |
| Install Gemini SDK | `pip install google-generativeai` |
| Run AI Audit | Llama a `gemini-2.0-flash` con el diff y devuelve el reporte en los logs |

### Qué analiza la IA

El prompt instruye al modelo a detectar:

1. **Vulnerabilidades de seguridad** — SQL Injection, XSS, Mass Assignment, falta de Middlewares
2. **Malas prácticas de arquitectura** — Lógica de negocio en controladores en lugar de Services/Models
3. **Nivel de riesgo** — Bajo / Medio / Alto

### Secrets necesarios

| Secret | Descripción | Requerido |
|---|---|---|
| `GEMINI_API_KEY` | API Key de Google Gemini | Sí |

### Dónde obtener la `GEMINI_API_KEY`

1. Ir a **[Google AI Studio](https://aistudio.google.com/app/apikey)**
2. Hacer clic en **"Create API key"**
3. Seleccionar o crear un proyecto de Google Cloud
4. Copiar la key generada (`AIza...`)
5. Agregarla en GitHub: **Settings → Secrets and variables → Actions → New repository secret**
   - Name: `GEMINI_API_KEY`
   - Value: la key copiada

> El plan gratuito de Gemini tiene límite de 15 requests/minuto y 1.500 requests/día — suficiente para CI/CD.

### Comportamiento cuando no hay cambios PHP

Si el diff de archivos `.php` está vacío, el script imprime un mensaje informativo y termina con código 0 (no falla el pipeline).

### Limitaciones

- Solo analiza archivos `.php` (no Blade, JS, etc.)
- El reporte es informativo; **no bloquea el merge** aunque detecte problemas
- Costo: gratuito dentro del free tier de Google AI Studio

---

## security.yml — Escaneo de Seguridad

**Archivo:** `.github/workflows/security.yml`

### Qué hace

Workflow de seguridad profunda que combina múltiples herramientas de análisis estático y de dependencias.

### Trigger

```
push → todas las ramas
pull_request → todas las ramas
```

> **Nota:** Al dispararse en todas las ramas, puede ser costoso. Considerar limitar a `main` y `develop`.

### Herramientas utilizadas

| Herramienta | Qué analiza |
|---|---|
| PHPUnit | Tests de la aplicación |
| **Semgrep** | Análisis estático de código (patrones de vulnerabilidades conocidos) |
| **Snyk** | Vulnerabilidades en dependencias (`composer.lock`, `package-lock.json`) |
| **OWASP Dependency-Check** | CVEs en dependencias del proyecto |

### Secrets necesarios

| Secret | Descripción | Requerido |
|---|---|---|
| `SNYK_TOKEN` | Token de autenticación de Snyk | Sí |

### Registro en Snyk

1. Crear cuenta en [snyk.io](https://snyk.io) (plan gratuito disponible)
2. Ir a **Account Settings → Auth Token**
3. Agregar el token como Secret: `SNYK_TOKEN`

---

## Secrets requeridos

Resumen de todos los Secrets a configurar en **GitHub → Settings → Secrets and variables → Actions**:

| Secret | Usado en | Cómo obtenerlo |
|---|---|---|
| `LARAVEL_APP_KEY` | — | No requerido; el pipeline genera la key en cada run |
| `GEMINI_API_KEY` | `ia_code_review.yml` | [aistudio.google.com/app/apikey](https://aistudio.google.com/app/apikey) |
| `SNYK_TOKEN` | `security.yml` | [app.snyk.io/account](https://app.snyk.io/account) |

---

## Cambios realizados para compatibilidad

Al validar los workflows contra el proyecto (PHP 8.3, Laravel 13, SQLite), se corrigió `pipeline.yml`:

| Problema original | Corrección aplicada |
|---|---|
| `php-version: '8.2'` | Actualizado a `'8.3'` (requerido por `composer.json`) |
| Servicio MySQL con usuario vacío | Eliminado; reemplazado por SQLite (driver por defecto del proyecto) |
| `composer install --no-suggest` | Eliminado `--no-suggest` (deprecado en Composer 2) |
| `php artisan migrate --env=testing` | Simplificado a `--force` (no se usa `.env.testing`) |
| `php artisan test` sin flags | Actualizado a `php artisan test --compact` (convención del proyecto) |
| Extensiones PHP incompletas | Agregados: `pdo`, `pdo_sqlite`, `sqlite3`, `bcmath`, `zip` |
| Lógica de `APP_KEY` duplicada | Un solo paso con fallback condicional (`grep` + `key:generate`) |
