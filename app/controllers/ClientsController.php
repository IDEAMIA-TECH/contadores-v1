<?php

class ClientsController {
    private $db;
    private $security;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->security = new Security();
    }

    public function update() {
        if (isset($_FILES['cer_file']) && $_FILES['cer_file']['error'] === UPLOAD_ERR_OK) {
            $cerPath = 'path/to/upload/directory/' . uniqid() . '_' . $_FILES['cer_file']['name'];
            move_uploaded_file($_FILES['cer_file']['tmp_name'], $cerPath);
            // Guardar $cerPath en la base de datos
        }

        if (isset($_FILES['key_file']) && $_FILES['key_file']['error'] === UPLOAD_ERR_OK) {
            $keyPath = 'path/to/upload/directory/' . uniqid() . '_' . $_FILES['key_file']['name'];
            move_uploaded_file($_FILES['key_file']['tmp_name'], $keyPath);
            // Guardar $keyPath en la base de datos
        }
    }

    public function satPortal($client_id) {
        try {
            // Verificar autenticación
            if (!$this->security->isAuthenticated()) {
                header('Location: ' . BASE_URL . '/login');
                exit;
            }

            // Verificar que el client_id sea válido
            if (!is_numeric($client_id)) {
                throw new Exception('ID de cliente inválido');
            }

            // Aquí puedes agregar lógica adicional para verificar permisos
            // o cargar datos específicos del cliente si es necesario

            // Pasar el ID del cliente a la vista
            $data = [
                'client_id' => $client_id
            ];
            
            // Incluir la vista
            extract($data);
            require_once __DIR__ . '/../views/clients/sat_portal.php';

        } catch (Exception $e) {
            error_log("Error en satPortal: " . $e->getMessage());
            $_SESSION['error'] = 'Error al acceder al portal SAT';
            header('Location: ' . BASE_URL . '/clients');
            exit;
        }
    }
} 