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

    public function satPortal($id) {
        // Verificar autenticación
        $this->checkAuth();
        
        // Verificar que el cliente existe
        $client = $this->clientModel->find($id);
        if (!$client) {
            $_SESSION['error'] = 'Cliente no encontrado';
            redirect('/clients');
        }

        // Verificar permisos
        if (!$this->hasPermission('view_client')) {
            $_SESSION['error'] = 'No tiene permisos para ver este cliente';
            redirect('/clients');
        }

        // Preparar datos para la vista
        $data = [
            'client_id' => $id,
            'client' => $client,
            'token' => $_SESSION['csrf_token']
        ];

        // Renderizar la vista
        $this->view('clients/sat_portal', $data);
    }
} 