// editar-empresa.js - Función para editar empresa existente

function editarEmpresaModal(empresaId) {
    // Mostrar loading
    Swal.fire({
        title: 'Cargando...',
        text: 'Obteniendo información de la empresa',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Obtener datos de la empresa
    fetch(`./api/empresas.php?action=get&id=${empresaId}`)
        .then(response => response.json())
        .then(result => {
            Swal.close();
            
            if (result.success && result.data) {
                const empresa = result.data;
                
                // Llenar formulario con datos existentes
                document.getElementById('empresaId').value = empresa.id;
                document.getElementById('nombre_empresa').value = empresa.nombre_empresa || '';
                document.getElementById('categoria').value = empresa.categoria || '';
                document.getElementById('email').value = empresa.email || '';
                document.getElementById('telefono').value = empresa.telefono || '';
                document.getElementById('sitio_web').value = empresa.sitio_web || '';
                document.getElementById('logo_url').value = empresa.logo_url || '';
                document.getElementById('direccion').value = empresa.direccion || '';
                document.getElementById('descripcion').value = empresa.descripcion || '';
                document.getElementById('beneficios').value = empresa.beneficios || '';
                document.getElementById('descuento').value = empresa.descuento || '';
                document.getElementById('fecha_inicio_convenio').value = empresa.fecha_inicio_convenio || '';
                document.getElementById('fecha_fin_convenio').value = empresa.fecha_fin_convenio || '';
                
                // Checkboxes
                document.getElementById('activo').checked = empresa.activo == 1;
                document.getElementById('destacado').checked = empresa.destacado == 1;
                
                // Actualizar título del modal
                document.getElementById('modalTitle').textContent = 'Editar Empresa';
                document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i>Actualizar Empresa';
                
                // Mostrar modal
                document.getElementById('modalEmpresa').classList.remove('hidden');
            } else {
                throw new Error(result.message || 'Error al obtener datos de la empresa');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al cargar datos de la empresa'
            });
        });
}

// Función para actualizar empresa
async function editarEmpresa(formData) {
    try {
        const response = await fetch('./api/empresas.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'update',
                ...formData
            })
        });

        const result = await response.json();
        
        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: '¡Éxito!',
                text: 'Empresa actualizada correctamente',
                timer: 2000,
                showConfirmButton: false
            });
            
            cerrarModalEmpresa();
            cargarEmpresas();
            actualizarEstadisticas();
        } else {
            throw new Error(result.message || 'Error al actualizar empresa');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'Error al actualizar empresa'
        });
    }
}

// Función para cambiar estado rápido (activo/inactivo)
async function toggleEstadoEmpresa(empresaId, estadoActual) {
    const nuevoEstado = estadoActual == 1 ? 0 : 1;
    const textoEstado = nuevoEstado == 1 ? 'activar' : 'desactivar';
    
    const result = await Swal.fire({
        title: `¿${textoEstado.charAt(0).toUpperCase() + textoEstado.slice(1)} empresa?`,
        text: `¿Estás seguro de que quieres ${textoEstado} esta empresa?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado == 1 ? '#10b981' : '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${textoEstado}`,
        cancelButtonText: 'Cancelar'
    });
    
    if (result.isConfirmed) {
        try {
            const response = await fetch('./api/empresas.php', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle_status',
                    id: empresaId,
                    activo: nuevoEstado
                })
            });

            const apiResult = await response.json();
            
            if (apiResult.success) {
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: `Empresa ${nuevoEstado == 1 ? 'activada' : 'desactivada'} correctamente`,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                cargarEmpresas();
                actualizarEstadisticas();
            } else {
                throw new Error(apiResult.message || 'Error al cambiar estado');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'Error al cambiar estado de la empresa'
            });
        }
    }
}