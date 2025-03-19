<?php require_once __DIR__ . '/../partials/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <h2>Subir XMLs para <?php echo htmlspecialchars($client['business_name']); ?></h2>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <!-- Área de Drop -->
                    <div id="drop-area" class="border rounded p-4 mb-3 text-center">
                        <p>Arrastra y suelta hasta 25 archivos XML aquí o</p>
                        <label for="xml_files" class="btn btn-primary mb-3">Seleccionar archivos</label>
                        <p class="text-muted">Máximo 25 archivos XML</p>
                        <div id="file-list" class="text-left"></div>
                    </div>

                    <form id="upload-form" action="<?php echo BASE_URL; ?>/clients/upload-xml" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $token; ?>">
                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                        <input type="file" id="xml_files" name="xml_file[]" accept=".xml" multiple style="display: none;">
                        
                        <div class="progress mb-3" style="display: none;">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>

                        <button type="submit" class="btn btn-success" id="submit-btn" disabled>
                            Subir XMLs
                        </button>
                        <a href="<?php echo BASE_URL; ?>/clients/view/<?php echo $client['id']; ?>" class="btn btn-secondary">
                            Cancelar
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
#drop-area {
    min-height: 200px;
    border: 2px dashed #ccc;
    transition: all 0.3s ease;
}

#drop-area.dragover {
    background-color: #e9ecef;
    border-color: #0d6efd;
}

#file-list {
    max-height: 300px;
    overflow-y: auto;
}

.file-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px;
    margin: 4px 0;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.remove-file {
    color: #dc3545;
    cursor: pointer;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('xml_files');
    const fileList = document.getElementById('file-list');
    const submitBtn = document.getElementById('submit-btn');
    const form = document.getElementById('upload-form');
    const MAX_FILES = 25;
    let files = [];

    // Prevenir comportamiento por defecto del drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
        document.body.addEventListener(eventName, preventDefaults, false);
    });

    // Efectos visuales durante el drag
    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, unhighlight, false);
    });

    // Manejar archivos soltados
    dropArea.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFiles);

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function highlight(e) {
        dropArea.classList.add('dragover');
    }

    function unhighlight(e) {
        dropArea.classList.remove('dragover');
    }

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const newFiles = [...dt.files];
        handleFileSelection(newFiles);
    }

    function handleFiles(e) {
        const newFiles = [...e.target.files];
        handleFileSelection(newFiles);
    }

    function handleFileSelection(newFiles) {
        // Filtrar solo archivos XML
        const xmlFiles = newFiles.filter(file => file.name.toLowerCase().endsWith('.xml'));
        
        // Verificar límite de archivos
        if (files.length + xmlFiles.length > MAX_FILES) {
            alert(`Solo se permiten ${MAX_FILES} archivos XML`);
            return;
        }

        // Agregar nuevos archivos
        files = [...files, ...xmlFiles];
        updateFileList();
        updateSubmitButton();
    }

    function updateFileList() {
        fileList.innerHTML = '';
        files.forEach((file, index) => {
            const div = document.createElement('div');
            div.className = 'file-item';
            div.innerHTML = `
                <span>${file.name}</span>
                <span class="remove-file" data-index="${index}">&times;</span>
            `;
            fileList.appendChild(div);
        });

        // Actualizar el input de archivos
        const dt = new DataTransfer();
        files.forEach(file => dt.items.add(file));
        fileInput.files = dt.files;
    }

    function updateSubmitButton() {
        submitBtn.disabled = files.length === 0;
    }

    // Remover archivos
    fileList.addEventListener('click', e => {
        if (e.target.classList.contains('remove-file')) {
            const index = parseInt(e.target.dataset.index);
            files.splice(index, 1);
            updateFileList();
            updateSubmitButton();
        }
    });

    // Manejar envío del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (files.length === 0) {
            alert('Por favor, seleccione al menos un archivo XML');
            return;
        }

        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            
            if (result.success) {
                window.location.href = `${BASE_URL}/clients/view/${formData.get('client_id')}`;
            } else {
                alert(result.message || 'Error al procesar los archivos');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir los archivos');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?> 