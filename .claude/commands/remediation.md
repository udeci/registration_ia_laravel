# Remediation Command

Corregí las vulnerabilidades encontradas manteniendo compatibilidad funcional.

## Reglas

NO romper:

- comportamiento de negocio
- estructura pública
- endpoints existentes
- contratos API

---

## Para cada vulnerabilidad

Explicar:

1. por qué era riesgosa
2. qué impacto tenía
3. cómo funciona el fix
4. riesgos del cambio
5. cómo validarlo

---

## Ejemplos esperados

### SQL Injection

Antes:

```php
$id = $_GET['id'];
$sql = "SELECT * FROM users WHERE id=$id";
```

Después:

```php
$stmt = $pdo->prepare(
  "SELECT * FROM users WHERE id = ?"
);
$stmt->execute([$id]);
```

---

### XSS

Antes:

```php
echo $_POST['comment'];
```

Después:

```php
echo htmlspecialchars(
  $_POST['comment'],
  ENT_QUOTES,
  'UTF-8'
);
```

---

### Secretos hardcodeados

Mover a:

```text
.env
```

---

## Resultado esperado

Actualizar:

```text
/docs/remediation-plan.md
```

y proponer diffs seguros.
