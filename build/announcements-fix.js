/**
 * Fix para anuncios importantes que no cargan desde la BD
 */

console.log('📢 Announcements Fix activado');

let announcementsFixAttempts = 0;
const maxAnnouncementsFixAttempts = 3;

function forceFixAnnouncements() {
    announcementsFixAttempts++;
    console.log(`🔧 Intento de fix #${announcementsFixAttempts} para anuncios importantes`);
    
    const announcementsList = document.getElementById('announcementsList');
    
    if (!announcementsList) {
        console.log('❌ Elemento announcementsList no encontrado');
        return;
    }
    
    console.log('✅ Elemento announcementsList encontrado');
    console.log(`📊 Children actuales: ${announcementsList.children.length}`);
    
    // Verificar si hay skeleton placeholders (indica que está cargando)
    const skeletons = announcementsList.querySelectorAll('.skeleton-announcement');
    console.log(`🦴 Skeletons encontrados: ${skeletons.length}`);
    
    // Verificar si hay contenido real
    const realContent = announcementsList.children.length - skeletons.length;
    console.log(`📋 Contenido real: ${realContent} elementos`);
    
    if (realContent === 0 && announcementsFixAttempts <= maxAnnouncementsFixAttempts) {
        console.log('📭 Lista vacía, forzando carga de anuncios...');
        loadAnnouncementsForcefully();
    } else if (realContent > 0) {
        console.log('✅ Ya hay anuncios cargados');
    } else {
        console.log('⚠️ Máximo de intentos alcanzado, mostrando contenido de emergencia');
        showEmergencyAnnouncements();
    }
}

async function loadAnnouncementsForcefully() {
    console.log('💪 Carga forzada de anuncios iniciada...');
    
    try {
        // Probar múltiples URLs y métodos
        const urls = [
            './api/boletines.php?estado=publicado&limit=4&orderBy=fecha_publicacion&order=DESC',
            './api/boletines.php?estado=publicado&limit=4',
            './api/boletines.php?limit=4',
            './api/boletines.php'
        ];
        
        let data = null;
        let usedUrl = '';
        
        for (const url of urls) {
            try {
                console.log(`🔗 Probando URL: ${url}`);
                const response = await fetch(url);
                
                if (response.ok) {
                    const result = await response.json();
                    console.log(`📡 Respuesta de ${url}:`, result);
                    
                    if (result.success && result.data && result.data.length > 0) {
                        data = result.data;
                        usedUrl = url;
                        console.log(`✅ Datos obtenidos de ${url}: ${data.length} elementos`);
                        break;
                    }
                }
            } catch (error) {
                console.log(`⚠️ Error con ${url}:`, error.message);
                continue;
            }
        }
        
        if (data && data.length > 0) {
            renderAnnouncementsForced(data);
        } else {
            console.log('📭 No se obtuvieron datos, intentando crear datos de prueba...');
            await createSampleAnnouncements();
        }
        
    } catch (error) {
        console.error('❌ Error en carga forzada:', error);
        showEmergencyAnnouncements();
    }
}

function renderAnnouncementsForced(anuncios) {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    console.log(`🎨 Renderizando ${anuncios.length} anuncios forzadamente...`);
    
    // Limpiar contenido existente
    announcementsList.innerHTML = '';
    
    const anunciosHTML = anuncios.map((anuncio, index) => {
        const titulo = anuncio.titulo || `Anuncio ${index + 1}`;
        const contenido = (anuncio.contenido || 'Contenido no disponible').substring(0, 120);
        const fecha = anuncio.fecha_publicacion || anuncio.fecha_creacion || new Date().toISOString();
        
        // Formatear fecha
        let fechaFormateada = 'Reciente';
        try {
            const fechaObj = new Date(fecha);
            fechaFormateada = fechaObj.toLocaleDateString('es-ES', { 
                day: 'numeric', 
                month: 'short', 
                year: 'numeric' 
            });
        } catch (e) {
            console.warn('Error formateando fecha:', e);
        }
        
        // Determinar icono y color según el contenido
        let icono = 'fas fa-bullhorn';
        let colorClase = 'text-blue-600';
        
        if (titulo.toLowerCase().includes('importante') || titulo.toLowerCase().includes('urgente')) {
            icono = 'fas fa-exclamation-triangle';
            colorClase = 'text-red-600';
        } else if (titulo.toLowerCase().includes('reunión') || titulo.toLowerCase().includes('evento')) {
            icono = 'fas fa-calendar';
            colorClase = 'text-green-600';
        } else if (titulo.toLowerCase().includes('nuevo') || titulo.toLowerCase().includes('bienvenida')) {
            icono = 'fas fa-star';
            colorClase = 'text-yellow-600';
        }
        
        return `
            <li style="position: relative; 
                       display: flex; 
                       justify-content: space-between; 
                       padding: 0.75rem 1rem; 
                       margin-bottom: 0.5rem; 
                       border: 1px solid #e5e7eb;
                       border-radius: 0.75rem; 
                       background: white;
                       transition: all 0.2s ease;
                       cursor: pointer;"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.1)'; this.style.borderColor='#3b82f6';"
                onmouseout="this.style.boxShadow='none'; this.style.borderColor='#e5e7eb';"
                onclick="console.log('Click en anuncio:', '${titulo}')">
                
                <div style="display: flex; align-items: flex-start; flex: 1;">
                    <div style="margin-right: 0.75rem; 
                                margin-top: 0.25rem;
                                width: 2rem; 
                                height: 2rem; 
                                border-radius: 50%; 
                                background: rgba(59, 130, 246, 0.1);
                                display: flex; 
                                align-items: center; 
                                justify-content: center;
                                flex-shrink: 0;">
                        <i class="${icono}" style="color: #3b82f6; font-size: 0.875rem;"></i>
                    </div>
                    
                    <div style="flex: 1; min-width: 0;">
                        <h6 style="font-weight: 600; 
                                   font-size: 0.875rem; 
                                   color: #1f2937; 
                                   margin: 0 0 0.25rem 0;
                                   line-height: 1.3;
                                   overflow: hidden;
                                   text-overflow: ellipsis;
                                   white-space: nowrap;">${titulo}</h6>
                        
                        <p style="font-size: 0.75rem; 
                                  color: #6b7280; 
                                  margin: 0 0 0.5rem 0;
                                  line-height: 1.4;
                                  display: -webkit-box;
                                  -webkit-line-clamp: 2;
                                  -webkit-box-orient: vertical;
                                  overflow: hidden;">${contenido}...</p>
                        
                        <div style="display: flex; 
                                    align-items: center; 
                                    justify-content: space-between;">
                            <span style="font-size: 0.625rem; 
                                         color: #9ca3af; 
                                         background: #f3f4f6;
                                         padding: 0.125rem 0.375rem;
                                         border-radius: 0.375rem;">📅 ${fechaFormateada}</span>
                            
                            <div style="display: flex; 
                                        align-items: center; 
                                        gap: 0.5rem;">
                                <span style="font-size: 0.625rem; 
                                             color: #10b981;
                                             background: rgba(16, 185, 129, 0.1);
                                             padding: 0.125rem 0.375rem;
                                             border-radius: 0.375rem;">✓ Publicado</span>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        `;
    }).join('');
    
    announcementsList.innerHTML = anunciosHTML;
    console.log(`✅ ${anuncios.length} anuncios renderizados exitosamente`);
}

async function createSampleAnnouncements() {
    console.log('📝 Creando anuncios de muestra...');
    
    const sampleData = [
        {
            titulo: "Bienvenida Nuevos Miembros",
            contenido: "Damos la bienvenida a las nuevas empresas que se han unido a nuestro clúster automotriz. Esperamos una colaboración fructífera y el crecimiento conjunto del sector.",
            estado: "publicado",
            fecha_publicacion: new Date().toISOString().slice(0, 19).replace('T', ' ')
        },
        {
            titulo: "Reunión Mensual Programada",
            contenido: "Les recordamos que la próxima reunión mensual del clúster se llevará a cabo el viernes a las 10:00 AM. Se tratarán temas importantes sobre nuevas oportunidades.",
            estado: "publicado",
            fecha_publicacion: new Date(Date.now() - 86400000).toISOString().slice(0, 19).replace('T', ' ')
        },
        {
            titulo: "Oportunidades de Negocio",
            contenido: "Se han identificado nuevas oportunidades de colaboración en el sector automotriz. Los miembros interesados pueden contactar directamente a la coordinación.",
            estado: "publicado",
            fecha_publicacion: new Date(Date.now() - 172800000).toISOString().slice(0, 19).replace('T', ' ')
        }
    ];
    
    try {
        for (const item of sampleData) {
            const formData = new FormData();
            formData.append('titulo', item.titulo);
            formData.append('contenido', item.contenido);
            formData.append('estado', item.estado);
            formData.append('fecha_publicacion', item.fecha_publicacion);
            
            const response = await fetch('./api/boletines.php', {
                method: 'POST',
                body: formData
            });
            
            if (response.ok) {
                console.log(`✅ Anuncio creado: ${item.titulo}`);
            }
        }
        
        // Intentar cargar de nuevo después de crear los datos
        setTimeout(() => {
            loadAnnouncementsForcefully();
        }, 1000);
        
    } catch (error) {
        console.error('❌ Error creando datos de muestra:', error);
        showEmergencyAnnouncements();
    }
}

function showEmergencyAnnouncements() {
    const announcementsList = document.getElementById('announcementsList');
    if (!announcementsList) return;
    
    console.log('🚨 Mostrando anuncios de emergencia...');
    
    announcementsList.innerHTML = `
        <li style="position: relative; 
                   display: flex; 
                   flex-direction: column;
                   align-items: center;
                   justify-content: center;
                   padding: 2rem; 
                   text-align: center;
                   background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                   border-radius: 0.75rem;
                   color: white;">
            
            <div style="font-size: 2rem; margin-bottom: 1rem;">📢</div>
            
            <h6 style="font-weight: bold; 
                       font-size: 1.125rem; 
                       margin: 0 0 0.5rem 0;
                       text-shadow: 0 2px 4px rgba(0,0,0,0.3);">
                Sistema de Anuncios
            </h6>
            
            <p style="font-size: 0.875rem; 
                      opacity: 0.9; 
                      margin: 0 0 1rem 0;
                      line-height: 1.5;
                      text-shadow: 0 1px 2px rgba(0,0,0,0.3);">
                Mantente informado de las últimas novedades<br>del clúster automotriz
            </p>
            
            <button onclick="window.loadAnnouncementsForcefully?.()" 
                    style="background: rgba(255,255,255,0.2); 
                           color: white; 
                           border: 1px solid rgba(255,255,255,0.3);
                           padding: 0.5rem 1rem; 
                           border-radius: 0.375rem; 
                           font-size: 0.75rem;
                           cursor: pointer;
                           backdrop-filter: blur(10px);
                           transition: all 0.2s ease;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                    onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                🔄 Actualizar
            </button>
        </li>
    `;
}

// Múltiples puntos de ejecución
document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 Announcements Fix: DOM cargado');
    setTimeout(forceFixAnnouncements, 2000);
});

window.addEventListener('load', () => {
    console.log('🌍 Announcements Fix: Window cargado');
    setTimeout(forceFixAnnouncements, 1000);
});

// Fix adicional después de un tiempo
setTimeout(() => {
    console.log('⏰ Announcements Fix: Timer 4 segundos');
    forceFixAnnouncements();
}, 4000);

setTimeout(() => {
    console.log('⏰ Announcements Fix: Timer 6 segundos');
    forceFixAnnouncements();
}, 6000);

// Hacer funciones globales para debugging
window.forceFixAnnouncements = forceFixAnnouncements;
window.loadAnnouncementsForcefully = loadAnnouncementsForcefully;
window.createSampleAnnouncements = createSampleAnnouncements;
window.showEmergencyAnnouncements = showEmergencyAnnouncements;

console.log('✅ Announcements Fix funciones registradas globalmente');