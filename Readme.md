### Actividad 1 - Módulo 4 - Lección 6

Se asume un manejo básico para de terminal en linux. El estudiante realiza la presente en Linux Arch. 

#### Instalar herramientas necesarias (Linux Arch)

1. Instalar php: `sudo pacman -S php`
2. Instalar composer: `sudo pacman -S composer`
3. Habilitar PDO: `sudo sed -i 's|;extension=pdo_mysql|extension=pdo_mysql|' /etc/php/php.ini`

#### Requisitos

El proyecto requiere una instancia de MySQL/MariaDB. El estudiante no provee una por defecto.

#### Inicializar proyecto

1. Instalar dependencias
`composer install`
2. Crear archivo secrets.php
`cp secret.php.sample secret.php`
3. Crear archivo db.php
`cp db.php.sample db.php`
4. Generar contraseña para archivo secret
`< /dev/urandom tr -cd '[:alnum:]' | head -c 60`
5. Configurar archivo db.php con los datos de la conexión a la base de datos.
6. Cargar el modelo de datos db.sql en la instancia de MySQL.
`mariadb -h IP -P PUERTO SCHEMA < db.sql`
En otros sistemas en donde mariadb no está disponible:
`mysql -h IP -P PUERTO SCHEMA < db.sql`

#### Inicio del proyecto

Se deberá seleccionar un puerto libre por encima de 1000. Luego dentro de la carpeta del proyecto (en la raíz), se debe iniciar el servidor de desarrollo de PHP.

`php -S localhost:[PUERTO]`

#### Usuarios

Usuario / Contraseña
- admin / 123456
- user / 123456

#### Testing de la API

El estudiante usó la herramienta httpie

##### Instalación de httpie

`sudo pacman -S httpie`

##### Inicio de sesión

Usuario `administrador`:
`http -f POST "http://localhost:[PUERTO]/login.php" username=admin password=123456`

Respuesta esperada:
```
HTTP/1.1 200 OK
Connection: close
Content-type: text/html; charset=UTF-8
Date: Sun, 06 Jul 2025 20:47:22 GMT
Host: localhost:5000
X-Powered-By: PHP/8.4.8

{
    "auth_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmbGNhbmVsbGFzLmNvbSIsImF1ZCI6ImZsY2FuZWxsYXMuY29tIiwiaWF0IjoxNzUxODM0ODQyLCJleHAiOjE3NTE4MzY2NDIsInVzZXJuYW1lIjoiYWRtaW4iLCJyb2xlIjoxfQ.qlrwkC11RnFP2F7hqSVrKkGhtTUUxmQ1HRUPTb9qJQU",
    "username": "admin"
}
```

Usuario `user`:
`http -f POST "http://localhost:[PUERTO]/login.php" username=user password=123456`

Respuesta esperada:
```
HTTP/1.1 200 OK
Connection: close
Content-type: text/html; charset=UTF-8
Date: Sun, 06 Jul 2025 20:47:55 GMT
Host: localhost:5000
X-Powered-By: PHP/8.4.8

{
    "auth_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJmbGNhbmVsbGFzLmNvbSIsImF1ZCI6ImZsY2FuZWxsYXMuY29tIiwiaWF0IjoxNzUxODM0ODc1LCJleHAiOjE3NTE4MzY2NzUsInVzZXJuYW1lIjoidXNlciIsInJvbGUiOjF9.7FuJf2zzedd4Ur8IJHSNYjozMzKJ1rLTuTCjiOZO964",
    "username": "user"
}
```

##### Eliminación de un usuario

Para evitar eliminar a los usuarios que se crean por defecto se puede insertar un nuevo usuario:
```SQL
-- Usuario administrador
INSERT INTO `usuario`(`usuario`, `password_hash`, `rol_fk`, `activo`)
VALUES('test_admin', '', 1, 1);
-- Usuario sin privilegios
INSERT INTO `usuario`(`usuario`, `password_hash`, `rol_fk`, `activo`)
VALUES('test_user', '', 1, 1);
```
Cabe mencionar que no se setea una contraseña, por lo que dichos usuarios solo servirán para testear la funcionalidad de eliminación.

Listar los usuarios
```SQL
SELECT * FROM `usuario`;
```

- Eliminar usuario
`http GET "http://localhost:[PUERTO]/delete-user.php?id=[ID-USER]" "Authorization: Bearer [TOKEN-JWT]"`

Respuesta esperada:
```
HTTP/1.1 204 No Content
Connection: close
Content-type: text/html; charset=UTF-8
Date: Sun, 06 Jul 2025 20:22:55 GMT
Host: localhost:5000
X-Powered-By: PHP/8.4.8
```