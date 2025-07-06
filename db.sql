DROP Table `sesion_usuario`;
DROP TABLE `usuario`;
DROP TABLE `rol_usuario`;

CREATE TABLE `rol_usuario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(20) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_spanish_ci' NULL,
  PRIMARY KEY (`id`));

INSERT INTO `rol_usuario` (`nombre`) VALUES ('admin'), ('user');

CREATE TABLE `usuario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `usuario` VARCHAR(40) NOT NULL,
  `password_hash` VARCHAR(60) NOT NULL,
  `rol_fk` INT NULL,
  `activo` VARCHAR(45) CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_spanish_ci' NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `usuario_UNIQUE` (`usuario` ASC) VISIBLE,
  FOREIGN KEY ( `rol_fk` ) REFERENCES rol_usuario (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
-- admin: 123456
-- user: 123456
INSERT INTO `usuario` (`id`, `usuario`, `password_hash`, `rol_fk`, `activo`)
VALUES
   (1, 'admin', '$2y$12$3joLTds0z5dIOHBb0KcgUO/cR3.WFAy04h46igB0VNCf5j4GG5.P6', 1, 1)
  ,(2, 'user', '$2y$12$3joLTds0z5dIOHBb0KcgUO/cR3.WFAy04h46igB0VNCf5j4GG5.P6', 1, 1);

CREATE TABLE `sesion_usuario` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `token` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_spanish_ci' NULL,
  `activa` TINYINT NOT NULL DEFAULT 1,
  `expiracion` INT NULL,
  `usuario_fk` INT NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY ( `usuario_fk` ) REFERENCES usuario (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);