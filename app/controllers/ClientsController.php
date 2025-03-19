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
} 