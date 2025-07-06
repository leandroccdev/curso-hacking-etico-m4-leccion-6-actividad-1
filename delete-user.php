<?php

// Trae las funciones para procesar la eliminación
require_once 'fn.php';
require_once 'user_roles.php';

// Usuario no autorizado
if ( !auth_user() ) {
    http_response_code(401);

// Autorizado, se elimina el usuario
} else {
    // Decodifica el token para leer el rol
    $token = parse_token();
    $decoded_token = decode_token($token);

    // Solo administración puede eliminar usuarios
    if ($decoded_token->role === ROLE_ADMIN ) {
        // Sanitiza $_GET
        $_GET = sanitize_array($_GET);
        $user_id = $_GET['id'] ?? null;

        // id no viene
        if (!$user_id) {
            http_response_code(400);
            exit;
        }

        // user no existe
        if (!user_id_exists($user_id)) {
            http_response_code(404);
            exit;
        }

        // Intenta realizar la eliminación del usuario
        if (delete_user($user_id)) {
            http_response_code(204);
            exit;
        // Error en la eliminación
        } else {
            http_response_code(500);
            exit;
        }
    }
}

?>