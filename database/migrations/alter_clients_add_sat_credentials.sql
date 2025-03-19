ALTER TABLE clients
ADD COLUMN cer_path VARCHAR(255) NULL COMMENT 'Ruta al archivo .cer del SAT',
ADD COLUMN key_path VARCHAR(255) NULL COMMENT 'Ruta al archivo .key del SAT',
ADD COLUMN key_password VARCHAR(255) NULL COMMENT 'Contraseña de la llave privada'; 