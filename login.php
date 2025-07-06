<?php

// Trae las funciones para procesar el login del usuario
require_once 'fn.php';

// No se implementa protección contra ataques CSRF
$_POST = sanitize_array($_POST);
$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;

$jwt = login_user($username, $password);

if (!$jwt)
    http_response_code(401);
else {
    http_response_code(200);
    echo json_encode((object) [
        "auth_token" => $jwt,
        "username"   => $username
    ]);
}
?>