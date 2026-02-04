// eliminar-empresa.js - Función para eliminar empresa

async function eliminarEmpresa(empresaId, nombreEmpresa) {
    // Confirmación con doble verificación
    const result = await Swal.fire({
        title: '¿Eliminar empresa?',
        html: `
            <div class="text-left">
                <p class="mb-4">¿Estás seguro de que quieres eliminar la empresa:</p>
                <p class="font-bold text-lg mb-4">"${nombreEmpresa}"</p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 font-semibold">¡Advertencia!</span>
                    </div>
                    <ul class="text-red-700 text-sm mt-2 ml-6">
                        <li>• Esta acción no se puede deshacer</li>
                        <li>• Se perderán todos los datos de la empresa</li>
                        <li>• Se eliminarán todos los convenios asociados</li>
                    </ul>
                </div>
                <p class="text-sm text-gray-600">Para confirmar, escribe <span class="font-mono bg-gray-100 px-2 py-1 rounded">ELIMINAR</span> en el campo siguiente:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'Escribe ELIMINAR para confirmar',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Eliminar empresa',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR') {
                return 'Debes escribir exactamente "ELIMINAR" para confirmar';
            }
        },
        customClass: {
            popup: 'swal2-large'
        }
    });
    
    if (result.isConfirmed) {
        // Mostrar loading
        Swal.fire({
            title: 'Eliminando...',
            text: 'Por favor espera mientras se elimina la empresa',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            const response = await fetch('./api/empresas.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete',
                    id: empresaId
                })
            });

            const apiResult = await response.json();
            
            if (apiResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Empresa eliminada!',
                    text: 'La empresa ha sido eliminada correctamente',
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // Recargar datos
                cargarEmpresas();
                actualizarEstadisticas();
            } else {
                throw new Error(apiResult.message || 'Error al eliminar empresa');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al eliminar',
                text: error.message || 'Ocurrió un error al eliminar la empresa. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    }
}

// Función para eliminar múltiples empresas
async function eliminarEmpresasSeleccionadas() {
    const checkboxes = document.querySelectorAll('.empresa-checkbox:checked');
    
    if (checkboxes.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Ninguna empresa seleccionada',
            text: 'Debes seleccionar al menos una empresa para eliminar',
            confirmButtonText: 'Entendido'
        });
        return;
    }
    
    const empresasIds = Array.from(checkboxes).map(cb => cb.value);
    const nombresEmpresas = Array.from(checkboxes).map(cb => cb.dataset.nombre);
    
    const result = await Swal.fire({
        title: `¿Eliminar ${empresasIds.length} empresa(s)?`,
        html: `
            <div class="text-left">
                <p class="mb-4">¿Estás seguro de que quieres eliminar las siguientes empresas?</p>
                <div class="bg-gray-50 border rounded-lg p-4 mb-4 max-h-40 overflow-y-auto">
                    <ul class="text-sm">
                        ${nombresEmpresas.map(nombre => `<li class="mb-1">• ${nombre}</li>`).join('')}
                    </ul>
                </div>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                        <span class="text-red-800 font-semibold">¡Advertencia!</span>
                    </div>
                    <p class="text-red-700 text-sm mt-2">Esta acción eliminará permanentemente todas las empresas seleccionadas y no se puede deshacer.</p>
                </div>
                <p class="text-sm text-gray-600">Para confirmar, escribe <span class="font-mono bg-gray-100 px-2 py-1 rounded">ELIMINAR TODO</span>:</p>
            </div>
        `,
        input: 'text',
        inputPlaceholder: 'Escribe ELIMINAR TODO para confirmar',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Eliminar ${empresasIds.length} empresa(s)`,
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (value !== 'ELIMINAR TODO') {
                return 'Debes escribir exactamente "ELIMINAR TODO" para confirmar';
            }
        }
    });
    
    if (result.isConfirmed) {
        Swal.fire({
            title: 'Eliminando empresas...',
            text: `Eliminando ${empresasIds.length} empresa(s)`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            const response = await fetch('./api/empresas.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'delete_multiple',
                    ids: empresasIds
                })
            });

            const apiResult = await response.json();
            
            if (apiResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Empresas eliminadas!',
                    text: `Se eliminaron ${empresasIds.length} empresa(s) correctamente`,
                    timer: 3000,
                    showConfirmButton: false
                });
                
                // Recargar datos
                cargarEmpresas();
                actualizarEstadisticas();
                
                // Limpiar selecciones
                document.getElementById('selectAll').checked = false;
            } else {
                throw new Error(apiResult.message || 'Error al eliminar empresas');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error al eliminar',
                text: error.message || 'Ocurrió un error al eliminar las empresas. Inténtalo de nuevo.',
                confirmButtonText: 'Entendido'
            });
        }
    }
}

// Función para seleccionar/deseleccionar todas las empresas
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.empresa-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateDeleteButtonVisibility();
}

// Función para mostrar/ocultar botón de eliminar múltiples
function updateDeleteButtonVisibility() {
    const checkedBoxes = document.querySelectorAll('.empresa-checkbox:checked');
    const deleteButton = document.getElementById('deleteSelectedBtn');
    
    if (checkedBoxes.length > 0) {
        if (!deleteButton) {
            // Crear botón si no existe
            const filterContainer = document.querySelector('.bg-white.rounded-lg.shadow-lg.p-6 .flex.space-x-2');
            const newButton = document.createElement('button');
            newButton.id = 'deleteSelectedBtn';
            newButton.onclick = eliminarEmpresasSeleccionadas;
            newButton.className = 'px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors';
            newButton.innerHTML = `<i class="fas fa-trash mr-2"></i>Eliminar Seleccionadas (${checkedBoxes.length})`;
            filterContainer.appendChild(newButton);
        } else {
            deleteButton.innerHTML = `<i class="fas fa-trash mr-2"></i>Eliminar Seleccionadas (${checkedBoxes.length})`;
        }
    } else {
        if (deleteButton) {
            deleteButton.remove();
        }
    }
}

// Event listeners para checkboxes
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('empresa-checkbox')) {
        updateDeleteButtonVisibility();
    }
});