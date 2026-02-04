// ==================== SOLUCIONADOR DE PROBLEMAS ESPECÍFICOS ====================
// Soluciona: Ver configuraciones, cambiar color, editar información

console.log('🔧 Cargando solucionador de problemas específicos...');

// Función para solucionar el problema de ver configuraciones
function solucionarVerConfiguraciones() {
    console.log('📁 Solucionando visualización de configuraciones...');
    
    // Crear función global para ver configuraciones
    window.verConfiguracionesGuardadas = function() {
        console.log('📋 Verificando configuraciones guardadas...');
        
        const configs = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
        console.log(`Encontradas ${configs.length} configuraciones`);
        
        if (configs.length === 0) {
            alert('No hay configuraciones guardadas aún.\n\nPara guardar:\n1. Configura tu gráfico\n2. Haz clic en "Guardar Configuración"\n3. Ingresa un nombre');
            return;
        }
        
        // Crear modal mejorado para mostrar configuraciones
        const modalHTML = `
            <div id="modalConfiguraciones" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.7);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                padding: 20px;
                box-sizing: border-box;
            ">
                <div style="
                    background: white;
                    padding: 30px;
                    border-radius: 12px;
                    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
                    max-width: 600px;
                    width: 100%;
                    max-height: 80vh;
                    overflow-y: auto;
                ">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3 style="margin: 0; color: #1f2937; font-size: 24px;">
                            📚 Configuraciones Guardadas (${configs.length})
                        </h3>
                        <button onclick="cerrarModalConfiguraciones()" style="
                            background: #6b7280;
                            color: white;
                            border: none;
                            border-radius: 50%;
                            width: 30px;
                            height: 30px;
                            cursor: pointer;
                            font-size: 16px;
                        ">×</button>
                    </div>
                    
                    <div id="listaConfiguraciones">
                        ${configs.map((config, index) => `
                            <div style="
                                border: 2px solid #e5e7eb;
                                border-radius: 8px;
                                padding: 16px;
                                margin-bottom: 16px;
                                background: #f9fafb;
                                transition: all 0.2s ease;
                            " onmouseover="this.style.borderColor='#3b82f6'" onmouseout="this.style.borderColor='#e5e7eb'">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 8px 0; color: #1f2937; font-size: 18px;">
                                            ${config.name}
                                        </h4>
                                        ${config.descripcion ? `<p style="margin: 0 0 8px 0; color: #6b7280; font-size: 14px;">${config.descripcion}</p>` : ''}
                                        <div style="font-size: 12px; color: #9ca3af;">
                                            📊 ${config.data ? config.data.length : 0} registros • 
                                            🎨 ${config.config ? config.config.type : 'N/A'} • 
                                            🌈 ${config.config ? config.config.color : 'N/A'}<br>
                                            💾 ${new Date(config.timestamp).toLocaleString()}
                                        </div>
                                    </div>
                                    <div style="margin-left: 16px;">
                                        <button onclick="cargarConfiguracionSeleccionada(${index})" style="
                                            background: #3b82f6;
                                            color: white;
                                            border: none;
                                            border-radius: 6px;
                                            padding: 8px 16px;
                                            cursor: pointer;
                                            font-size: 14px;
                                            margin-bottom: 8px;
                                            width: 100%;
                                        ">📁 Cargar</button>
                                        <button onclick="eliminarConfiguracion(${index})" style="
                                            background: #ef4444;
                                            color: white;
                                            border: none;
                                            border-radius: 6px;
                                            padding: 6px 12px;
                                            cursor: pointer;
                                            font-size: 12px;
                                            width: 100%;
                                        ">🗑️ Eliminar</button>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    
                    <div style="margin-top: 20px; text-align: center;">
                        <button onclick="cerrarModalConfiguraciones()" style="
                            background: #6b7280;
                            color: white;
                            border: none;
                            border-radius: 6px;
                            padding: 10px 20px;
                            cursor: pointer;
                            font-size: 14px;
                        ">Cerrar</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    };
    
    // Función para cerrar modal
    window.cerrarModalConfiguraciones = function() {
        const modal = document.getElementById('modalConfiguraciones');
        if (modal) modal.remove();
    };
    
    // Función para cargar configuración
    window.cargarConfiguracionSeleccionada = function(index) {
        const configs = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
        if (configs[index]) {
            const config = configs[index];
            console.log('📁 Cargando configuración:', config.name);
            
            // Aplicar datos
            if (window.sistemaEmergencia) {
                window.sistemaEmergencia.currentData = config.data;
                window.sistemaEmergencia.config = config.config;
                window.sistemaEmergencia.crearGrafico();
            }
            
            // Actualizar controles de la interfaz
            actualizarControlesInterfaz(config.config);
            
            alert(`✅ Configuración "${config.name}" cargada correctamente`);
            cerrarModalConfiguraciones();
        }
    };
    
    // Función para eliminar configuración
    window.eliminarConfiguracion = function(index) {
        if (confirm('¿Estás seguro de eliminar esta configuración?')) {
            const configs = JSON.parse(localStorage.getItem('configs_emergencia') || '[]');
            const nombreEliminado = configs[index].name;
            configs.splice(index, 1);
            localStorage.setItem('configs_emergencia', JSON.stringify(configs));
            
            alert(`🗑️ "${nombreEliminado}" eliminada`);
            
            // Recargar el modal
            cerrarModalConfiguraciones();
            setTimeout(verConfiguracionesGuardadas, 100);
        }
    };
}

// Función para solucionar cambio de color
function solucionarCambioColor() {
    console.log('🎨 Solucionando cambio de color...');
    
    // Configurar selector de color principal
    const colorPrincipal = document.getElementById('primaryColor');
    if (colorPrincipal) {
        colorPrincipal.addEventListener('change', function(e) {
            const nuevoColor = e.target.value;
            console.log(`🎨 Color cambiado a: ${nuevoColor}`);
            
            if (window.sistemaEmergencia) {
                window.sistemaEmergencia.config.color = nuevoColor;
                window.sistemaEmergencia.crearGrafico();
                
                // Feedback visual
                this.style.border = '3px solid #10b981';
                setTimeout(() => {
                    this.style.border = '';
                }, 1000);
                
                alert(`🎨 Color actualizado a ${nuevoColor}`);
            }
        });
        
        console.log('✅ Selector de color configurado');
    }
    
    // Configurar presets de color
    const presets = document.querySelectorAll('.color-preset');
    presets.forEach(preset => {
        preset.addEventListener('click', function(e) {
            const color = this.dataset.color;
            if (color) {
                console.log(`🎨 Preset seleccionado: ${color}`);
                
                // Actualizar el input principal
                if (colorPrincipal) {
                    colorPrincipal.value = color;
                }
                
                // Aplicar el color
                if (window.sistemaEmergencia) {
                    window.sistemaEmergencia.config.color = color;
                    window.sistemaEmergencia.crearGrafico();
                }
                
                // Feedback visual
                this.style.transform = 'scale(1.2)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 200);
                
                alert(`🎨 Color preset aplicado: ${color}`);
            }
        });
    });
    
    console.log(`✅ ${presets.length} presets de color configurados`);
}

// Función para solucionar edición de información
function solucionarEdicionInformacion() {
    console.log('📝 Solucionando edición de información...');
    
    // Mejorar el botón de editar datos
    const btnEditor = document.getElementById('editarDatosManualmente');
    if (btnEditor) {
        // Remover listeners anteriores
        btnEditor.onclick = null;
        
        btnEditor.addEventListener('click', function() {
            console.log('📝 Abriendo editor mejorado...');
            abrirEditorMejorado();
        });
    }
    
    // Función para abrir editor mejorado
    window.abrirEditorMejorado = function() {
        const panel = document.getElementById('customDataPanel');
        if (!panel) {
            console.error('❌ Panel de editor no encontrado');
            alert('❌ Panel de editor no encontrado en el HTML');
            return;
        }
        
        panel.style.display = 'flex';
        
        // Cargar datos actuales
        const editor = document.getElementById('customDataEditor');
        if (editor && window.sistemaEmergencia) {
            editor.value = JSON.stringify(window.sistemaEmergencia.currentData, null, 2);
        }
        
        // Configurar botones del editor
        configurarBotonesEditor();
        
        alert('📝 Editor abierto. Puedes:\n• Modificar el JSON\n• Usar los botones de ayuda\n• Validar antes de aplicar');
    };
    
    // Configurar todos los botones del editor
    function configurarBotonesEditor() {
        console.log('⚙️ Configurando botones del editor...');
        
        // Botón validar
        const btnValidar = document.getElementById('validarDatos');
        if (btnValidar) {
            btnValidar.onclick = function() {
                console.log('✅ Validando datos...');
                validarDatosEditor();
            };
        }
        
        // Botón ejemplo
        const btnEjemplo = document.getElementById('cargarEjemplo');
        if (btnEjemplo) {
            btnEjemplo.onclick = function() {
                console.log('📋 Cargando ejemplo...');
                cargarEjemploEditor();
            };
        }
        
        // Botón aleatorio
        const btnAleatorio = document.getElementById('generarAleatorio');
        if (btnAleatorio) {
            btnAleatorio.onclick = function() {
                console.log('🎲 Generando datos aleatorios...');
                generarDatosAleatorios();
            };
        }
        
        // Botón formatear
        const btnFormatear = document.getElementById('formatearJSON');
        if (btnFormatear) {
            btnFormatear.onclick = function() {
                console.log('✨ Formateando JSON...');
                formatearJSON();
            };
        }
        
        // Botón limpiar
        const btnLimpiar = document.getElementById('limpiarEditor');
        if (btnLimpiar) {
            btnLimpiar.onclick = function() {
                console.log('🧹 Limpiando editor...');
                limpiarEditor();
            };
        }
        
        // Botón guardar datos
        const btnGuardarDatos = document.getElementById('guardarDatosPersonalizados');
        if (btnGuardarDatos) {
            btnGuardarDatos.onclick = function() {
                console.log('💾 Aplicando datos personalizados...');
                aplicarDatosPersonalizados();
            };
        }
        
        // Botón cerrar
        const btnCerrar = document.getElementById('cerrarCustomData');
        if (btnCerrar) {
            btnCerrar.onclick = function() {
                console.log('❌ Cerrando editor...');
                cerrarEditor();
            };
        }
    }
    
    // Funciones específicas del editor
    window.validarDatosEditor = function() {
        const editor = document.getElementById('customDataEditor');
        const preview = document.getElementById('dataPreview');
        
        if (!editor) {
            alert('❌ Editor no encontrado');
            return;
        }
        
        try {
            const datos = JSON.parse(editor.value);
            
            if (!Array.isArray(datos)) {
                throw new Error('Los datos deben ser un array');
            }
            
            if (datos.length === 0) {
                throw new Error('El array no puede estar vacío');
            }
            
            // Mostrar preview
            if (preview) {
                preview.innerHTML = `
                    <div style="padding: 15px; background: #f0f9ff; border-radius: 8px;">
                        <h4 style="margin: 0 0 10px 0; color: #1e40af;">✅ Datos Válidos</h4>
                        <p><strong>Registros:</strong> ${datos.length}</p>
                        <p><strong>Campos:</strong> ${Object.keys(datos[0]).join(', ')}</p>
                        <p><strong>Primer registro:</strong></p>
                        <pre style="background: white; padding: 8px; border-radius: 4px; font-size: 12px;">${JSON.stringify(datos[0], null, 2)}</pre>
                    </div>
                `;
            }
            
            alert(`✅ Datos válidos: ${datos.length} registros`);
            
        } catch (error) {
            if (preview) {
                preview.innerHTML = `
                    <div style="padding: 15px; background: #fef2f2; border-radius: 8px; color: #dc2626;">
                        <h4 style="margin: 0 0 10px 0;">❌ Error en los datos</h4>
                        <p>${error.message}</p>
                    </div>
                `;
            }
            
            alert(`❌ Error: ${error.message}`);
        }
    };
    
    window.cargarEjemploEditor = function() {
        const ejemplo = [
            { "mes": "Enero", "ventas": 25, "categoria": "Productos" },
            { "mes": "Febrero", "ventas": 32, "categoria": "Productos" },
            { "mes": "Marzo", "ventas": 28, "categoria": "Productos" },
            { "mes": "Abril", "ventas": 35, "categoria": "Productos" },
            { "mes": "Mayo", "ventas": 42, "categoria": "Productos" }
        ];
        
        const editor = document.getElementById('customDataEditor');
        if (editor) {
            editor.value = JSON.stringify(ejemplo, null, 2);
            alert('📋 Ejemplo cargado. Puedes modificar estos datos.');
        }
    };
    
    window.generarDatosAleatorios = function() {
        const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio'];
        const datos = meses.map(mes => ({
            mes: mes,
            valor: Math.floor(Math.random() * 50) + 10,
            categoria: `Tipo ${String.fromCharCode(65 + Math.floor(Math.random() * 3))}`
        }));
        
        const editor = document.getElementById('customDataEditor');
        if (editor) {
            editor.value = JSON.stringify(datos, null, 2);
            alert('🎲 Datos aleatorios generados. Revisa y modifica según necesites.');
        }
    };
    
    window.formatearJSON = function() {
        const editor = document.getElementById('customDataEditor');
        if (!editor) return;
        
        try {
            const datos = JSON.parse(editor.value);
            editor.value = JSON.stringify(datos, null, 2);
            alert('✨ JSON formateado correctamente');
        } catch (error) {
            alert('❌ Error formateando: JSON inválido');
        }
    };
    
    window.limpiarEditor = function() {
        const editor = document.getElementById('customDataEditor');
        const preview = document.getElementById('dataPreview');
        
        if (editor) editor.value = '';
        if (preview) preview.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 40px;">Escribe o pega tu JSON aquí</p>';
        
        alert('🧹 Editor limpiado');
    };
    
    window.aplicarDatosPersonalizados = function() {
        const editor = document.getElementById('customDataEditor');
        if (!editor) return;
        
        try {
            const datos = JSON.parse(editor.value);
            
            if (!Array.isArray(datos) || datos.length === 0) {
                throw new Error('Datos inválidos');
            }
            
            // Aplicar los datos
            if (window.sistemaEmergencia) {
                window.sistemaEmergencia.currentData = datos;
                window.sistemaEmergencia.crearGrafico();
            }
            
            // Cerrar editor
            cerrarEditor();
            
            alert(`✅ Datos aplicados: ${datos.length} registros\nEl gráfico se ha actualizado.`);
            
        } catch (error) {
            alert(`❌ Error aplicando datos: ${error.message}`);
        }
    };
    
    window.cerrarEditor = function() {
        const panel = document.getElementById('customDataPanel');
        if (panel) {
            panel.style.display = 'none';
        }
    };
}

// Función auxiliar para actualizar controles de interfaz
function actualizarControlesInterfaz(config) {
    if (!config) return;
    
    // Actualizar tipo de gráfico
    if (config.type) {
        const radio = document.querySelector(`input[name="chartType"][value="${config.type}"]`);
        if (radio) radio.checked = true;
    }
    
    // Actualizar color
    if (config.color) {
        const colorInput = document.getElementById('primaryColor');
        if (colorInput) colorInput.value = config.color;
    }
    
    // Actualizar título
    if (config.title) {
        const titleInput = document.getElementById('chartTitle');
        if (titleInput) titleInput.value = config.title;
    }
}

// Ejecutar todas las soluciones
function ejecutarSoluciones() {
    console.log('🚀 Ejecutando soluciones para problemas específicos...');
    
    try {
        solucionarVerConfiguraciones();
        console.log('✅ Solución 1: Ver configuraciones - OK');
        
        solucionarCambioColor();
        console.log('✅ Solución 2: Cambio de color - OK');
        
        solucionarEdicionInformacion();
        console.log('✅ Solución 3: Edición de información - OK');
        
        // Configurar el botón de ver configuraciones en el menú
        setTimeout(() => {
            const btnVerConfigs = document.getElementById('verConfiguraciones');
            if (btnVerConfigs) {
                btnVerConfigs.onclick = function(e) {
                    e.preventDefault();
                    verConfiguracionesGuardadas();
                };
            }
        }, 500);
        
        console.log('🎉 Todas las soluciones aplicadas correctamente');
        
    } catch (error) {
        console.error('❌ Error aplicando soluciones:', error);
    }
}

// Ejecutar cuando esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(ejecutarSoluciones, 1500);
    });
} else {
    setTimeout(ejecutarSoluciones, 1500);
}

console.log('🔧 Solucionador de problemas específicos cargado');
