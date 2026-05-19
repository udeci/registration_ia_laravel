# Security Audit Command

Auditá este proyecto PHP completo como auditor de seguridad.

## Objetivo

Detectar vulnerabilidades, malas prácticas y riesgos de seguridad.

## Analizar

### 1. Código inseguro

Buscar:

- SQL Injection
- XSS
- CSRF
- SSRF
- File Inclusion
- Command Injection
- Session Hijacking
- uso inseguro de `eval()`
- uso inseguro de `exec()`
- uso inseguro de `shell_exec()`
- deserialización insegura
- uploads inseguros

---

### 2. Librerías vulnerables

Revisar:

- `composer.json`
- `composer.lock`

Detectar:

- dependencias desactualizadas
- CVEs conocidas

---

### 3. Secretos expuestos

Buscar:

- passwords hardcodeadas
- API keys
- tokens
- credenciales DB
- archivos `.env`
- backups `.sql`

---

### 4. Configuración insegura

Revisar:

- permisos `777`
- `display_errors = On`
- debug habilitado
- logs públicos
- directorios sensibles accesibles

---

## Herramientas sugeridas

Ejecutar si corresponde:

```bash
semgrep --config=auto .
snyk test
```

---

## Resultado esperado

Generar:

```text
/docs/security-audit.md
```

Formato:

| hallazgo | severidad | archivo | explicación | fix |
