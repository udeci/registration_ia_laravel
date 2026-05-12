# Documento Funcional — Sistema de Registro y Autenticación

## 1. Descripción General

El sistema es una aplicación web de registro y autenticación de usuarios desarrollada en PHP. Permite a los usuarios crear una cuenta, iniciar sesión y acceder a una página protegida. El almacenamiento de datos se realiza mediante un archivo JSON local, sin necesidad de base de datos relacional.

El proyecto tiene un origen educativo, basado en el tutorial "CodeWithAwa", y ha sido adaptado para utilizar JSON como mecanismo de persistencia en lugar de MySQL.

---

## 2. Actores

| Actor | Descripción |
|---|---|
| Usuario no autenticado | Persona que accede al sistema sin sesión activa. Solo puede acceder al formulario de registro y al de login. |
| Usuario autenticado | Persona que ha iniciado sesión correctamente. Puede acceder a la página principal y cerrar sesión. |

---

## 3. Casos de Uso

### CU-01: Registro de usuario

**Actor:** Usuario no autenticado

**Precondición:** El usuario no tiene una sesión activa.

**Flujo principal:**
1. El usuario accede a `register.php`.
2. Completa el formulario con: nombre de usuario, email, contraseña y confirmación de contraseña.
3. Envía el formulario.
4. El sistema valida que todos los campos estén completos.
5. El sistema valida que la contraseña y su confirmación coincidan.
6. El sistema verifica que el nombre de usuario no esté ya registrado.
7. El sistema guarda el nuevo usuario con la contraseña cifrada.
8. El sistema inicia una sesión para el usuario.
9. El sistema redirige al usuario a la página principal (`index.php`).

**Flujos alternativos:**
- Si algún campo está vacío → se muestra un mensaje de error.
- Si las contraseñas no coinciden → se muestra un mensaje de error.
- Si el nombre de usuario ya existe → se muestra el mensaje "Username already taken".

**Postcondición:** El usuario queda registrado, autenticado y redirigido a la página principal.

---

### CU-02: Inicio de sesión

**Actor:** Usuario no autenticado

**Precondición:** El usuario tiene una cuenta registrada.

**Flujo principal:**
1. El usuario accede a `login.php`.
2. Ingresa su nombre de usuario y contraseña.
3. Envía el formulario.
4. El sistema valida que los campos no estén vacíos.
5. El sistema busca el usuario en el archivo de datos.
6. El sistema compara la contraseña ingresada (cifrada) con la almacenada.
7. El sistema inicia una sesión para el usuario.
8. El sistema redirige al usuario a la página principal.

**Flujos alternativos:**
- Si algún campo está vacío → se muestra un mensaje de error.
- Si las credenciales no coinciden → se muestra el mensaje "Wrong username/password combination".

**Postcondición:** El usuario queda autenticado y redirigido a la página principal.

---

### CU-03: Acceso a página protegida

**Actor:** Usuario autenticado

**Precondición:** El usuario tiene una sesión activa.

**Flujo principal:**
1. El usuario accede a `index.php`.
2. El sistema verifica la existencia de la sesión.
3. El sistema muestra el mensaje de bienvenida con el nombre de usuario.

**Flujos alternativos:**
- Si no hay sesión activa → el sistema redirige automáticamente a `login.php`.

---

### CU-04: Cierre de sesión

**Actor:** Usuario autenticado

**Precondición:** El usuario tiene una sesión activa y está en la página principal.

**Flujo principal:**
1. El usuario hace clic en el botón de logout.
2. El sistema destruye la sesión activa.
3. El sistema redirige al usuario a `login.php`.

---

## 4. Reglas de Negocio

| ID | Regla |
|---|---|
| RN-01 | El nombre de usuario debe ser único en el sistema. |
| RN-02 | Todos los campos del formulario de registro son obligatorios. |
| RN-03 | La contraseña y su confirmación deben ser idénticas al registrarse. |
| RN-04 | Las contraseñas se almacenan cifradas (hash MD5). |
| RN-05 | Solo los usuarios con sesión activa pueden acceder a `index.php`. |
| RN-06 | El nombre de usuario y el email se sanitizan antes de almacenarse. |

---

## 5. Validaciones de Formulario

### Registro (`register.php`)

| Campo | Validación |
|---|---|
| Username | No vacío, no duplicado, sanitizado |
| Email | No vacío, sanitizado |
| Password | No vacío |
| Confirm Password | Debe coincidir con Password |

### Login (`login.php`)

| Campo | Validación |
|---|---|
| Username | No vacío |
| Password | No vacío, comparado con hash almacenado |

---

## 6. Mensajes del Sistema

| Situación | Mensaje |
|---|---|
| Registro exitoso | Sesión iniciada, redirección a index.php |
| Username duplicado | "Username already taken" |
| Credenciales incorrectas | "Wrong username/password combination" |
| Campo vacío en registro | Mensaje específico por campo |
| Campo vacío en login | Mensaje específico por campo |

---

## 7. Flujo de Navegación

```
[Usuario no autenticado]
          │
          ├──► register.php ──► (registro exitoso) ──► index.php
          │
          └──► login.php ──────► (login exitoso) ──────► index.php
                                                               │
                                                         logout (?logout=1)
                                                               │
                                                          login.php
```

---

## 8. Notas sobre Seguridad

Las siguientes limitaciones son conocidas y relevantes si el sistema se llevara a un entorno productivo:

- El hash MD5 para contraseñas es débil; se recomienda reemplazarlo por `password_hash()` con bcrypt.
- No se implementa protección CSRF en los formularios.
- No hay límite de intentos de login (sin rate limiting).
- El email no tiene verificación de formato ni verificación por correo.
