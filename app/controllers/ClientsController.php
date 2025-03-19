<?php

class ClientsController {
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

    public function satPortal($clientId) {
        // Verificar autenticación y permisos
        $this->checkAuth();
        
        // Verificar que el cliente existe
        $client = $this->clientModel->find($clientId);
        if (!$client) {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('/clients');
        }

        // Pasar el ID del cliente a la vista
        $data = [
            'client_id' => $clientId
        ];

        // Renderizar la vista
        $this->view('clients/sat_portal', $data);
    }
} 