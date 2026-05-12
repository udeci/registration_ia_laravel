# Documento de Arquitectura — Sistema de Registro y Autenticación

## 1. Visión General

El sistema sigue una arquitectura web monolítica simple de una sola capa, donde el servidor PHP procesa tanto la lógica de presentación como la lógica de negocio. No utiliza frameworks ni separación MVC formal. La persistencia de datos se implementa mediante un archivo JSON local.

---

## 2. Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 7+ |
| Persistencia | JSON (sistema de archivos) |
| Frontend | HTML 5 + CSS 3 (sin framework) |
| Sesiones | PHP Sessions (cookies de servidor) |
| Control de versiones | Git |

---

## 3. Estructura de Archivos

```
registration_ia/
├── index.php          # Página principal protegida + lógica de logout
├── register.php       # Formulario de registro (vista + punto de entrada)
├── login.php          # Formulario de login (vista + punto de entrada)
├── server.php         # Lógica de negocio: registro, login, sesiones
├── errors.php         # Componente reutilizable para mostrar errores
├── style.css          # Estilos globales compartidos
└── users.json         # Almacenamiento de usuarios
```

---

## 4. Responsabilidades por Archivo

### `server.php`
Es el núcleo de la aplicación. Contiene toda la lógica de negocio:
- Función `register_user()`: valida datos, verifica duplicados, cifra la contraseña, persiste en JSON e inicia sesión.
- Función `login_user()`: valida credenciales contra el archivo JSON e inicia sesión.
- Se incluye (`require`) en `register.php` y `login.php` para ser ejecutado ante el envío de formularios.

### `register.php`
- Incluye `server.php` al inicio.
- Detecta el envío del formulario via `$_POST['reg_user']`.
- Llama a `register_user()` con los datos del formulario.
- Renderiza el HTML del formulario y muestra los errores mediante `errors.php`.

### `login.php`
- Incluye `server.php` al inicio.
- Detecta el envío del formulario via `$_POST['login_user']`.
- Llama a `login_user()` con las credenciales.
- Renderiza el HTML del formulario y muestra los errores.

### `index.php`
- Inicia la sesión y verifica `$_SESSION['username']`.
- Si no hay sesión activa, redirige a `login.php`.
- Si se recibe el parámetro `?logout=1`, destruye la sesión y redirige a `login.php`.
- Muestra el contenido protegido al usuario autenticado.

### `errors.php`
- Componente de presentación reutilizable.
- Itera sobre el array `$errors` y renderiza cada mensaje.

### `users.json`
- Archivo de persistencia plana.
- Contiene un array JSON de objetos usuario.
- Es leído y escrito directamente por `server.php` usando `file_get_contents()` y `file_put_contents()`.

---

## 5. Modelo de Datos

### Entidad: Usuario

```json
{
    "username": "string",
    "email":    "string",
    "password": "string (hash MD5)"
}
```

El archivo `users.json` almacena un array de estos objetos:

```json
[
    {
        "username": "avazquez",
        "email":    "usuario@example.com",
        "password": "5f4dcc3b5aa765d61d8327deb882cf99"
    }
]
```

---

## 6. Flujo de Ejecución

### Registro

```
register.php (GET)
    └─► Renderiza formulario HTML

register.php (POST, reg_user)
    └─► include server.php
         └─► register_user()
               ├─► Valida campos
               ├─► Lee users.json
               ├─► Verifica username duplicado
               ├─► Hashea password (MD5)
               ├─► Agrega usuario al array
               ├─► Escribe users.json
               ├─► Inicia $_SESSION['username']
               └─► header('Location: index.php')
```

### Login

```
login.php (GET)
    └─► Renderiza formulario HTML

login.php (POST, login_user)
    └─► include server.php
         └─► login_user()
               ├─► Valida campos
               ├─► Lee users.json
               ├─► Itera usuarios, compara hash
               ├─► Inicia $_SESSION['username']
               └─► header('Location: index.php')
```

### Acceso a página protegida

```
index.php (GET)
    ├─► session_start()
    ├─► [¿$_SESSION['username'] existe?]
    │     └─► NO: header('Location: login.php')
    │     └─► SÍ: renderiza bienvenida
    └─► [¿$_GET['logout'] == 1?]
          └─► SÍ: session_destroy() → header('Location: login.php')
```

---

## 7. Gestión de Sesiones

El sistema usa el mecanismo nativo de sesiones de PHP:

| Operación | Código |
|---|---|
| Inicio de sesión | `session_start(); $_SESSION['username'] = $username;` |
| Verificación de sesión | `isset($_SESSION['username'])` |
| Cierre de sesión | `session_destroy(); unset($_SESSION['username']);` |

Las sesiones se almacenan en el servidor (directorio temporal de PHP). El cliente recibe una cookie de sesión (`PHPSESSID`).

---

## 8. Seguridad Implementada

| Mecanismo | Implementación |
|---|---|
| Cifrado de contraseñas | `md5($password)` antes de persistir y al comparar |
| Sanitización de entrada | `htmlspecialchars(strip_tags($data))` para username y email |
| Protección de rutas | Redirección a login si no hay sesión activa en `index.php` |
| Prevención de duplicados | Búsqueda en JSON antes de registrar un nuevo usuario |

---

## 9. Diagrama de Componentes

```
┌──────────────────────────────────────────────┐
│                  Navegador                    │
│  register.php  │  login.php  │  index.php    │
└───────┬────────┴──────┬──────┴──────┬────────┘
        │               │             │
        └───────┬────────┘             │
                ▼                      │
         ┌─────────────┐               │
         │  server.php  │◄─────────────┘
         │  (lógica de  │
         │   negocio)   │
         └──────┬───────┘
                │ lee / escribe
                ▼
         ┌─────────────┐
         │  users.json  │
         │ (persistencia│
         │    local)    │
         └─────────────┘
```

---

## 10. Limitaciones de la Arquitectura Actual

| Limitación | Impacto |
|---|---|
| Persistencia en JSON | Sin concurrencia: escrituras simultáneas pueden corromper el archivo. No escala a múltiples usuarios simultáneos. |
| Sin separación de capas (MVC) | La lógica de presentación y de negocio están mezcladas en los archivos PHP. |
| Hash MD5 | No es seguro para producción; susceptible a ataques de diccionario y rainbow tables. |
| Sin migraciones ni esquema formal | No hay control de versiones del modelo de datos. |
| Sin HTTPS obligatorio | Las credenciales pueden viajar en texto claro si no se configura TLS en el servidor. |

---

## 11. Posibles Mejoras

| Área | Mejora sugerida |
|---|---|
| Persistencia | Reemplazar JSON por SQLite o MySQL/MariaDB |
| Seguridad de contraseñas | Usar `password_hash()` + `password_verify()` con bcrypt |
| Arquitectura | Aplicar patrón MVC separando rutas, controladores y vistas |
| Protección de formularios | Agregar tokens CSRF |
| Validación de email | Agregar `filter_var($email, FILTER_VALIDATE_EMAIL)` |
| Rate limiting | Limitar intentos de login fallidos por IP |
