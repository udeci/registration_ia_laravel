# Security Audit Report

## Objetivo

Auditar el proyecto Laravel para detectar vulnerabilidades de seguridad usando IA + herramientas automáticas.

---

## Herramientas utilizadas

### Semgrep

Instalación:

```bash
pip install semgrep
```

Ejecución:

```bash
semgrep --config=auto .
```

Detecta:

- SQL Injection
- XSS
- SSRF
- uso inseguro de funciones PHP
- malas prácticas

---

### Snyk

Instalación:

```bash
npm install -g snyk
```

Login:

```bash
snyk auth
```

Ejecución:

```bash
snyk test
```

Detecta:

- dependencias vulnerables
- CVEs conocidos

---

### OWASP Dependency-Check

```bash
docker run --rm \
-v $PWD:/src \
owasp/dependency-check \
--scan /src
```

---

### GitHub Advanced Security

Verificar:

- Code Scanning
- Secret Scanning
- Dependabot Alerts

---

# Prompt IA para auditoría

```text
Auditá este proyecto Laravel buscando:

1. código inseguro
2. dependencias vulnerables
3. secretos expuestos
4. malas prácticas
5. nivel de severidad
6. propuesta de remediación

Generá una tabla:

| hallazgo | severidad | archivo | explicación | fix |
```

---

# Hallazgos

## Vulnerabilidad 1

**Tipo:** SQL Injection  
**Severidad:** Critical  
**Archivo:** ___________________

### Código detectado

```php
DB::select("SELECT * FROM users WHERE id=$id");
```

### Riesgo

Permite ejecución arbitraria de SQL.

### Fix sugerido

```php
User::where('id', $id)->first();
```

---

## Vulnerabilidad 2

**Tipo:** XSS  
**Severidad:** High  
**Archivo:** ___________________

### Código detectado

```php
{!! $comment !!}
```

### Fix sugerido

```php
{{ $comment }}
```

---

## Vulnerabilidad 3

**Tipo:** Secret expuesto  
**Severidad:** High  
**Archivo:** ___________________

### Verificar

```bash
.env
```

---

## Resumen final

| Métrica | Resultado |
|---------|-----------|
| Vulnerabilidades críticas | ___ |
| Vulnerabilidades altas | ___ |
| Vulnerabilidades medias | ___ |
| Riesgo general | ___ |
