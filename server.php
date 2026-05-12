<?php 
    session_start();

    $username = "";
    $email    = "";
    $errors = array(); 
    $_SESSION['success'] = "";

    // Definimos el nombre del archivo que hará de base de datos
    $file_db = 'users.json';

    // Crear el archivo si no existe
    if (!file_exists($file_db)) {
        file_put_contents($file_db, json_encode([]));
    }

    // Función auxiliar para leer usuarios
    function get_users($file) {
        $json_data = file_get_contents($file);
        return json_decode($json_data, true);
    }

    // REGISTER USER
    if (isset($_POST['reg_user'])) {
        // Limpiamos los datos (ya no usamos mysqli_real_escape_string)
        $username = htmlspecialchars(strip_tags($_POST['username']));
        $email = htmlspecialchars(strip_tags($_POST['email']));
        $password_1 = $_POST['password_1'];
        $password_2 = $_POST['password_2'];

        if (empty($username)) { array_push($errors, "Username is required"); }
        if (empty($email)) { array_push($errors, "Email is required"); }
        if (empty($password_1)) { array_push($errors, "Password is required"); }
        if ($password_1 != $password_2) {
            array_push($errors, "The two passwords do not match");
        }

        if (count($errors) == 0) {
            $users = get_users($file_db);

            // Verificar si el usuario ya existe
            foreach ($users as $user) {
                if ($user['username'] === $username) {
                    array_push($errors, "Username already exists");
                    break;
                }
            }

            if (count($errors) == 0) {
                $password = md5($password_1); 
                
                // Añadir nuevo usuario al arreglo
                $users[] = [
                    'username' => $username,
                    'email'    => $email,
                    'password' => $password
                ];

                // Guardar de nuevo en el archivo
                file_put_contents($file_db, json_encode($users, JSON_PRETTY_PRINT));

                $_SESSION['username'] = $username;
                $_SESSION['success'] = "You are now logged in";
                header('location: index.php');
            }
        }
    }

    // LOGIN USER
    if (isset($_POST['login_user'])) {
        $username = htmlspecialchars(strip_tags($_POST['username']));
        $password = $_POST['password'];

        if (empty($username)) { array_push($errors, "Username is required"); }
        if (empty($password)) { array_push($errors, "Password is required"); }

        if (count($errors) == 0) {
            $password = md5($password);
            $users = get_users($file_db);
            $found = false;

            foreach ($users as $user) {
                if ($user['username'] === $username && $user['password'] === $password) {
                    $found = true;
                    break;
                }
            }

            if ($found) {
                $_SESSION['username'] = $username;
                $_SESSION['success'] = "You are now logged in";
                header('location: index.php');
            } else {
                array_push($errors, "Wrong username/password combination");
            }
        }
    }
?>