<?php

require 'vendor/autoload.php';
 // Importa datos de conexión (archivo no versionable)
require_once 'db.php';
// Importa archivo con secreto de JWT (archivo no versionable)
require_once 'secret.php';

// Conexión a la base de datos
$pdo = pdo_connect($pdo_dsn, $pdo_user, $pdo_pass, $pdo_options);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

// Funciones
// --------------------------------------------------------------------
// Realiza la sanitización de variables dinámicamente en un arreglo asociativo
function sanitize_array($data)
{
    $sanitized = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            // sanitización recursiva
            $sanitized[$key] = sanitize_array($value);
        } else {
            $sanitized[$key] = htmlentities($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    return $sanitized;
}

// Crea un usuario no privilegiado
// Usada para parametrizar la tabla de usuarios
function create_user($username, $password)
{
    global $pdo;
    $p = password_hash($password, PASSWORD_BCRYPT);

     try {
        $sql = "INSERT INTO usuario (usuario, password_hash, rol_fk, activo)"
            ." VALUES(:usuario, :password_hash, 2, 1)";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([
            ':usuario'       => $username,
            ':password_hash' => $p
        ]);

    } catch (PDOException $e) {
        // echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Lee un usuario desde la base de datos
// Si no encuentra el usuario devuelve null
function read_user($username) {
    global $pdo;

    try {
        $sql = "SELECT id, usuario, password_hash, rol_fk FROM usuario"
            ." WHERE activo = 1 AND usuario = :usuario";
        // echo str_replace(":usuario", "'$username'", $sql);
        $stmt = $pdo->prepare($sql);
        $status = $stmt->execute([
            ':usuario' => $username,
        ]);

        // Usuario no encontrado
        if (!$status)
            return null;

        return $stmt->fetch();
    } catch (PDOException $e) {
        // echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Crea una sesión de usuario vacía inactiva
// Retorna el id de la sesión o null
function create_empty_session($user_id) {
    global $pdo;

    try {
        $sql = "INSERT INTO sesion_usuario (activa, usuario_fk) VALUES (0, :id)";
        $stmt = $pdo->prepare($sql);
        $status = $stmt->execute([
            ':id' => $user_id
        ]);
        if (!$status)
            return null;
        else
            return $pdo->lastInsertId();
    } catch (PDOException $e) {
        // echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Activa una sesión de usuario
// La activación solo funciona una vez por sesión
// Devuelve true cuando la sesión fue activada, false en caso contrario
function enable_session($session_id, $user_id, $token, $expiration) {
    global $pdo;

    try {
        $sql = "UPDATE sesion_usuario SET token = :token, expiracion = :expiracion, activa = 1"
            ." WHERE usuario_fk = :id AND id = :session_id AND activa = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id'         => $user_id,
            ':session_id' => $session_id,
            ':token'      => $token,
            ':expiracion' => $expiration
        ]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Crea un token de sesión jwt con una duración de 30 minutos
// Retorna el token creado
function create_jwt_token($username, $user_role) {
    $now = time();
    $expiration = $now + 1800; // 30 min
    $payload = [
        "iss" => 'flcanellas.com',
        "aud" => "flcanellas.com",
        "iat" => $now,
        "exp" => $expiration,
        "username" => $username,
        "role"     => $user_role
    ];
    $token = JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    return (object) [
        'token' => $token,
        'expiration' => $expiration
    ];
}

// Devuelve null o el token jwt cuando el usuario se loguea correctamente
function login_user($username, $password) {
    try {
        // Recupera el usuario
        $user = read_user($username);

        // Usuario no encontrado
        if (!$user)
            return null;
        $user = (object) $user;

        // Autentica al usuario
        $login_ok = password_verify($password, $user->password_hash);
        // Usuario no autenticado
        if (!$login_ok)
            return null;

        // Crea la sesión
        $session_id = create_empty_session($user->id);
        // No se puedo crear la sesión
        if (!$session_id)
            return null;

        // Crea el token de sesión
        $jwt = create_jwt_token($username, $user->rol_fk);

        // Activa la sesión
        $enabled = enable_session($session_id, $user->id, $jwt->token, $jwt->expiration);
        if (!$enabled)
            return null;

        // Usuario logueado
        return $jwt->token;

    } catch (PDOException $e) {
        //echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    } catch (ExpiredException $e) {
        // echo "Error de token expirado!";
        return false;
    }
}

// Verifica la sesión desde mysql
// Devuelve true cuando la sesión es válida, false en caso contrario
function check_token_session($token, $expiration) {
    global $pdo;

    try {
        $sql = "SELECT COUNT(id) AS items FROM sesion_usuario"
            ." WHERE activa = 1 AND expiracion = :expiration AND token = :token";
        $stmt = $pdo->prepare($sql);
        $status = $stmt->execute([
            ':expiration' => $expiration,
            ':token' => $token
        ]);

        // Sesion no encontrada
        if (!$status)
            return false;

        return $stmt->fetchObject()->items  == 1;
    } catch (PDOException $e) {
       // echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Autentica al usuario
// Devuelve true cuando la autenticación es correcta, false si no
function auth_user() {
    try {
        $token = parse_token();
        // No se pudo parsear el token
        if (!$token)
            return false;
        $decoded_token = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));

        // Verifica la expiración
        if ($decoded_token->exp < time())
            return false;

        // Verifica la sesion del token
        return check_token_session($token, $decoded_token->exp);
    } catch (ExpiredException $e) {
        //echo "Error de token expirado!";
        return false;
    }
}

// Decodifica un token devolviendo el objeto contenido o devuelve null
function decode_token($token) {
    try {
        return JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
    } catch (ExpiredException $e) {
        return null;
    }
}

// Parsea el token jwt o devuelve false
function parse_token() {
    if ( !isset( $_SERVER['HTTP_AUTHORIZATION'] ) )
        return false;

    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    $r = preg_match('/^Bearer\s+([a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+\.[a-zA-Z0-9\-_]+)/', $auth, $matches);
    // Token no encontrado
    if (!$r)
        return false;
    return $matches[1];
}

// Realiza la conexión a pdo para mysql
// Errores deben ser externamente manejados
function pdo_connect($dsn, $user, $pass, $options) {
    return new PDO($dsn, $user, $pass, $options);
}

// Realiza la eliminación de un usuario
// Retorna true cuando la eliminación se efectuó correctamente, false en caso contrario
function delete_user($user_id) {
    global $pdo;
    try {
        $sql = "DELETE FROM usuario WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        // echo str_replace(":id", $user_id, $sql); exit;
        $status = $stmt->execute([ ':id' => $user_id ]);

        return $status;
    } catch (PDOException $e) {
        //echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}

// Verifica si el usuario existe por id
function user_id_exists($user_id) {
    global $pdo;
    try {
        // Preparar e insertar los datos
        $sql = "SELECT COUNT(id) AS items FROM usuario WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $status = $stmt->execute([
            ':id' => $user_id
        ]);

        // Error en la consulta
        if (!$status)
            return false;

        return $stmt->fetchObject()->items > 0;
    } catch (PDOException $e) {
        //echo "Error de conexión o ejecución: " . $e->getMessage();
        return false;
    }
}
// --------------------------------------------------------------------