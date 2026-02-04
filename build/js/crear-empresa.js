// crear-empresa.js - Función para crear nueva empresa

function abrirModalEmpresa() {
    // Limpiar formulario
    document.getElementById('formEmpresa').reset();
    document.getElementById('empresaId').value = '';
    document.getElementById('modalTitle').textContent = 'Nueva Empresa';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Guardar Empresa';
    
    // Marcar activo por defecto
    document.getElementById('activo').checked = true;
    
    // Mostrar modal
    document.getElementById('modalEmpresa').classList.remove('hidden');
}

function cerrarModalEmpresa() {
    document.getElementById('modalEmpresa').classList.add('hidden');
}

// Función para crear empresa
async function crearEmpresa(formData) {
    try {
        const response = await fetch('./api/empresas.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'create',
                ...formData
            })
        });

        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Empresa creada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            
            cerrarModalEmpresa();
            cargarEmpresas();
            actualizarEstadisticas();
        } else {
            throw new Error(result.message || 'Error al crear empresa');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al crear empresa'
        });
    }
}

// Event listener para el formulario
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('formEmpresa').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        
        // Convertir FormData a objeto
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Manejar checkboxes
        data.activo = document.getElementById('activo').checked ? 1 : 0;
        data.destacado = document.getElementById('destacado').checked ? 1 : 0;
        
        // Validar campos requeridos
        if (!data.nombre_empresa.trim()) {
            Swal.fire({
                icon: 'warning',
                title: 'Campo requerido',
                text: 'El nombre de la empresa es obligatorio'
            });
            return;
        }
        
        // Determinar si es creación o edición
        const empresaId = document.getElementById('empresaId').value;
        
        if (empresaId) {
            // Es edición
            data.id = empresaId;
            editarEmpresa(data);
        } else {
            // Es creación
            crearEmpresa(data);
        }
    });
});