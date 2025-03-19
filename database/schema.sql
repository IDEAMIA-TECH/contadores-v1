-- Crear tabla de usuarios
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('admin', 'contador', 'cliente') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear tabla de clientes
CREATE TABLE clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    rfc VARCHAR(13) NOT NULL UNIQUE,
    business_name VARCHAR(150) NOT NULL,
    legal_name VARCHAR(150) NOT NULL,
    fiscal_regime VARCHAR(100),
    address TEXT,
    phone VARCHAR(20),
    email VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
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
CREATE TABLE accountant_clients (
    id INT PRIMARY KEY AUTO_INCREMENT,
    accountant_id INT NOT NULL,
    client_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (accountant_id) REFERENCES accountants(id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    UNIQUE KEY unique_accountant_client (accountant_id, client_id)
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