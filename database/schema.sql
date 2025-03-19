-- Crear tabla de usuarios
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'contador') NOT NULL DEFAULT 'contador',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login DATETIME,
    reset_token VARCHAR(64),
    reset_token_expiry DATETIME,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de clientes
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfc VARCHAR(13) NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    legal_name VARCHAR(150) NOT NULL,
    fiscal_regime VARCHAR(50) NOT NULL,
    address TEXT NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    contact_name VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    csf_path VARCHAR(255),
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de contadores
CREATE TABLE accountants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de relación contador-cliente
CREATE TABLE IF NOT EXISTS accountant_clients (
    accountant_id INT NOT NULL,
    client_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (accountant_id, client_id),
    FOREIGN KEY (accountant_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de reportes fiscales
CREATE TABLE tax_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accountant_id INT NOT NULL,
    client_id INT NOT NULL,
    period ENUM('Mensual', 'Bimestral') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_iva_16 DECIMAL(10,2) DEFAULT 0.00,
    total_iva_8 DECIMAL(10,2) DEFAULT 0.00,
    total_iva_exento DECIMAL(10,2) DEFAULT 0.00,
    total_iva_0 DECIMAL(10,2) DEFAULT 0.00,
    xml_count INT DEFAULT 0,
    report_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accountant_id) REFERENCES accountants(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    INDEX idx_date_range (start_date, end_date),
    INDEX idx_client_date (client_id, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de XMLs subidos
CREATE TABLE uploaded_xmls (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accountant_id INT NOT NULL,
    client_id INT NOT NULL,
    tax_report_id INT,
    invoice_uuid VARCHAR(36) NOT NULL,
    xml_path VARCHAR(255) NOT NULL,
    processed BOOLEAN DEFAULT FALSE,
    processing_errors TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (accountant_id) REFERENCES accountants(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (tax_report_id) REFERENCES tax_reports(id),
    UNIQUE KEY unique_invoice (invoice_uuid),
    INDEX idx_processing (processed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de XMLs de clientes
CREATE TABLE IF NOT EXISTS client_xmls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    xml_type ENUM('ingreso', 'egreso', 'pago', 'otro') NOT NULL,
    uuid VARCHAR(36) NOT NULL UNIQUE,
    xml_path VARCHAR(255) NOT NULL,
    emission_date DATETIME NOT NULL,
    certification_date DATETIME NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    status ENUM('active', 'cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar usuario administrador por defecto
INSERT INTO users (username, password, email, role) VALUES 
('admin', '$argon2id$v=19$m=65536,t=4,p=3$WHpVeUVhS3FxWVFXVHNGbg$2Bxm9h1W4QEpDCBHzPV+PLJWx0XVHoHFjV/FQZ+kK8Y', 'admin@ideamia.tech', 'admin')
ON DUPLICATE KEY UPDATE id=id;

-- Insertar contador de prueba
INSERT INTO users (username, password, email, role) VALUES 
('contador_test', '$argon2id$v=19$m=65536,t=4,p=3$WHpVeUVhS3FxWVFXVHNGbg$2Bxm9h1W4QEpDCBHzPV+PLJWx0XVHoHFjV/FQZ+kK8Y', 'contador@test.com', 'contador')
ON DUPLICATE KEY UPDATE id=id; 