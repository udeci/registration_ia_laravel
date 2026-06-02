# Full Security Review Workflow

Objetivo: realizar auditoría completa + remediación + validación CI.

---

# Paso 1 — Escaneo inicial

Ejecutar auditoría completa del proyecto.

Usar:

```bash
semgrep --config=auto .
snyk test
```

Analizar:

- SQL Injection
- XSS
- CSRF
- SSRF
- RCE
- File Inclusion
- hardcoded secrets
- permisos inseguros
- librerías vulnerables

Generar:

```text
/docs/security-audit.md
```

---

# Paso 2 — Clasificación de severidad

Categorizar:

- Critical
- High
- Medium
- Low

Priorizar:

1. exposición de datos
2. ejecución remota
3. escalamiento privilegios

---

# Paso 3 — Remediación asistida

Aplicar fixes compatibles.

Generar:

```text
/docs/remediation-plan.md
```

Para cada fix incluir:

- código antes
- código después
- explicación
- riesgo mitigado

---

# Paso 4 — Validación funcional

Ejecutar:

```bash
phpunit
```

o:

```bash
vendor/bin/phpunit
```

Confirmar:

- sin errores
- sin regresión

---

# Paso 5 — Seguridad automática en CI

Validar archivo:

```text
/.github/workflows/security.yml
```

Debe incluir:

- composer install
- tests
- semgrep
- snyk
- fail on critical vulnerabilities

---

# Resultado final esperado

Proyecto con:

- vulnerabilidades detectadas
- fixes aplicados
- documentación actualizada
- CI bloqueando riesgos futuros

Objetivo final:

Pasar de:

"Tengo un PHP legacy inseguro"

a:

"Tengo un sistema auditado y protegido automáticamente."
