/**
 * Fix específico para el slider del dashboard
 * Solución agresiva para el problema de pantalla morada
 */

console.log('🚨 Dashboard Slider Fix activado');

let fixAttempts = 0;
const maxFixAttempts = 5;

function forceFixDashboardSlider() {
    fixAttempts++;
    console.log(`🔧 Intento de fix #${fixAttempts} para dashboard slider`);
    
    const slidesContainer = document.getElementById('slidesContainer');
    const sliderLoading = document.getElementById('sliderLoading');
    const bannersSlider = document.getElementById('bannersSlider');
    
    console.log('🔍 Estado de elementos:');
    console.log('   - bannersSlider:', bannersSlider ? 'encontrado' : 'NO encontrado');
    console.log('   - slidesContainer:', slidesContainer ? 'encontrado' : 'NO encontrado');
    console.log('   - sliderLoading:', sliderLoading ? 'encontrado' : 'NO encontrado');
    
    if (slidesContainer) {
        console.log('   - slidesContainer children:', slidesContainer.children.length);
        console.log('   - slidesContainer innerHTML length:', slidesContainer.innerHTML.length);
    }
    
    // Si el loading aún está visible, ocultarlo inmediatamente
    if (sliderLoading && sliderLoading.style.display !== 'none') {
        console.log('🫥 Ocultando loading...');
        sliderLoading.style.display = 'none';
        sliderLoading.style.visibility = 'hidden';
        sliderLoading.style.opacity = '0';
    }
    
    // Si el slidesContainer está vacío, forzar carga
    if (slidesContainer && slidesContainer.children.length === 0) {
        console.log('📭 Container vacío, forzando carga...');
        loadBannersForcefully();
        return;
    }
    
    // Si hay slides pero no son visibles, forzar visibilidad
    if (slidesContainer && slidesContainer.children.length > 0) {
        console.log('🔍 Verificando visibilidad de slides existentes...');
        
        let hasVisibleSlide = false;
        Array.from(slidesContainer.children).forEach((slide, index) => {
            const styles = getComputedStyle(slide);
            console.log(`   Slide ${index}: opacity=${styles.opacity}, display=${styles.display}`);
            
            if (styles.opacity !== '0' && styles.display !== 'none') {
                hasVisibleSlide = true;
            }
        });
        
        if (!hasVisibleSlide) {
            console.log('👁️ No hay slides visibles, forzando visibilidad...');
            const firstSlide = slidesContainer.children[0];
            if (firstSlide) {
                firstSlide.style.opacity = '1';
                firstSlide.style.display = 'block';
                firstSlide.style.position = 'absolute';
                firstSlide.style.inset = '0';
                firstSlide.style.zIndex = '10';
                console.log('✅ Primer slide forzado a ser visible');
            }
        } else {
            console.log('✅ Ya hay slides visibles');
            return; // No necesita más fixes
        }
    }
    
    // Si llegamos aquí y no hemos resuelto el problema, intentar de nuevo
    if (fixAttempts < maxFixAttempts) {
        setTimeout(forceFixDashboardSlider, 1500);
    } else {
        console.log('⚠️ Máximo de intentos alcanzado, mostrando botón de debug');
        showDebugButton();
        emergencySliderFix();
    }
}

async function loadBannersForcefully() {
    console.log('💪 Carga forzada de banners iniciada...');
    
    try {
        // Intentar múltiples rutas para el API de banners
        let response;
        const apiUrls = [
            './api/banners.php?action=active&t=' + Date.now(),
            '../api/banners.php?action=active&t=' + Date.now(),
            '/api/banners.php?action=active&t=' + Date.now(),
            'https://intranet.clústermetropolitano.mx/api/banners.php?action=active&t=' + Date.now()
        ];
        
        console.log('🔍 Probando URLs de API:', apiUrls);
        
        let lastError = null;
        for (const url of apiUrls) {
            try {
                console.log(`📡 Intentando: ${url}`);
                response = await fetch(url);
                if (response.ok) {
                    console.log(`✅ Éxito con: ${url}`);
                    break;
                } else {
                    console.log(`❌ HTTP ${response.status} en: ${url}`);
                    lastError = new Error(`HTTP ${response.status}`);
                }
            } catch (error) {
                console.log(`❌ Error en ${url}:`, error.message);
                lastError = error;
            }
        }
        
        if (!response || !response.ok) {
            throw lastError || new Error('Todas las URLs de API fallaron');
        }
        
        const result = await response.json();
        
        if (result.success && result.data && result.data.length > 0) {
            console.log(`📊 ${result.data.length} banners recibidos de la API`);
            
            const slidesContainer = document.getElementById('slidesContainer');
            if (!slidesContainer) {
                console.error('❌ slidesContainer no encontrado');
                return;
            }
            
            // Crear HTML con estilos inline y mejor visibilidad
            const slidesHTML = result.data.map((banner, index) => {
                const imageUrl = banner.imagen_url || '';
                const title = (banner.titulo || `Banner ${index + 1}`).replace(/'/g, "&#39;");
                const description = (banner.descripcion || 'Bienvenido a Clúster Intranet').replace(/'/g, "&#39;");
                
                return `
                    <div style="position: absolute; 
                                top: 0; left: 0; right: 0; bottom: 0;
                                background-color: #1e293b;
                                background-image: url('${imageUrl}'); 
                                background-size: cover; 
                                background-position: center;
                                opacity: ${index === 0 ? '1' : '0'};
                                z-index: ${index === 0 ? '10' : index + 1};
                                transition: opacity 0.6s ease-in-out;"
                         data-slide-index="${index}"
                         class="banner-slide-forced">
                        <!-- Overlay ligero solo en la parte inferior para el texto -->
                        <div style="position: absolute;
                                    bottom: 0; left: 0; right: 0; height: 40%;
                                    background: linear-gradient(
                                        to top,
                                        rgba(0,0,0,0.3) 0%,
                                        rgba(0,0,0,0.1) 50%,
                                        rgba(0,0,0,0.0) 100%
                                    );">
                        </div>
                        <!-- Contenedor de texto con fondo más transparente -->
                        <div style="position: absolute;
                                    bottom: 0; left: 0; right: 0;
                                    padding: 2rem;">
                            <div style="background: rgba(0,0,0,0.15);
                                        padding: 1.5rem;
                                        border-radius: 0.75rem;
                                        backdrop-filter: blur(3px);
                                        border: 1px solid rgba(255,255,255,0.2);">
                                <h5 style="font-size: 1.5rem;
                                           font-weight: bold;
                                           margin-bottom: 0.75rem;
                                           color: #ffffff;
                                           text-shadow:
                                               0 2px 4px rgba(0,0,0,0.8),
                                               0 1px 2px rgba(0,0,0,0.6);
                                           line-height: 1.2;">${title}</h5>
                                <p style="font-size: 0.875rem;
                                          color: #f8fafc;
                                          line-height: 1.5;
                                          margin: 0;
                                          text-shadow:
                                              0 1px 3px rgba(0,0,0,0.7),
                                              0 1px 2px rgba(0,0,0,0.5);">${description}</p>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            slidesContainer.innerHTML = slidesHTML;
            console.log(`✅ ${result.data.length} slides insertados con estilos inline`);
            
            // Iniciar rotación automática si hay más de un banner
            if (result.data.length > 1) {
                initAutoRotation(result.data.length);
            }
            
            // Verificar inmediatamente
            setTimeout(() => {
                const slides = slidesContainer.querySelectorAll('.banner-slide-forced');
                console.log(`🔍 Verificación: ${slides.length} slides encontrados después de inserción`);
                
                slides.forEach((slide, index) => {
                    const styles = getComputedStyle(slide);
                    console.log(`   Slide ${index}: opacity=${styles.opacity}, z-index=${styles.zIndex}`);
                });
            }, 500);
            
        } else {
            console.log('⚠️ No hay banners activos, mostrando fallback');
            showEmergencyFallback();
        }
        
    } catch (error) {
        console.error('❌ Error en carga forzada:', error);
        showEmergencyFallback();
    }
}

function emergencySliderFix() {
    console.log('🚨 Aplicando solución de emergencia...');
    showEmergencyFallback();
}

function showDebugButton() {
    const debugBtn = document.getElementById('sliderDebugBtn');
    if (debugBtn) {
        debugBtn.style.display = 'block';
        console.log('🔧 Botón de debug mostrado');
    }
}

function showEmergencyFallback() {
    const slidesContainer = document.getElementById('slidesContainer');
    const sliderLoading = document.getElementById('sliderLoading');
    
    if (sliderLoading) {
        sliderLoading.style.display = 'none';
    }
    
    if (slidesContainer) {
        slidesContainer.innerHTML = `
            <div style="position: absolute; 
                        top: 0; left: 0; right: 0; bottom: 0;
                        background-color: #1e293b;
                        background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        background-size: cover;
                        background-position: center;
                        z-index: 100;">
                <!-- Overlay ligero solo en la parte inferior -->
                <div style="position: absolute;
                            bottom: 0; left: 0; right: 0; height: 40%;
                            background: linear-gradient(
                                to top,
                                rgba(0,0,0,0.3) 0%,
                                rgba(0,0,0,0.1) 50%,
                                rgba(0,0,0,0.0) 100%
                            );">
                </div>
                <!-- Contenido con el mismo estilo que los banners -->
                <div style="position: absolute;
                            bottom: 0; left: 0; right: 0;
                            padding: 2rem;">
                    <div style="background: rgba(0,0,0,0.15);
                                padding: 1.5rem;
                                border-radius: 0.75rem;
                                backdrop-filter: blur(3px);
                                border: 1px solid rgba(255,255,255,0.2);
                                text-align: center;">
                        <div style="font-size: 2.5rem; margin-bottom: 1rem;">🏢</div>
                        <h5 style="font-size: 1.5rem; 
                                   font-weight: bold; 
                                   margin-bottom: 0.75rem;
                                   color: #ffffff;
                                   text-shadow:
                                       0 2px 4px rgba(0,0,0,0.8),
                                       0 1px 2px rgba(0,0,0,0.6);
                                   line-height: 1.2;">
                            Bienvenido a Clúster Intranet
                        </h5>
                        <p style="font-size: 0.875rem; 
                                  color: #f8fafc;
                                  line-height: 1.5;
                                  margin: 0;
                                  text-shadow:
                                      0 1px 3px rgba(0,0,0,0.7),
                                      0 1px 2px rgba(0,0,0,0.5);">
                            Conecta con el clúster automotriz líder de México
                        </p>
                    </div>
                </div>
            </div>
        `;
        console.log('✅ Fallback de emergencia mostrado con estilo mejorado');
    }
}

// Función para ejecutar desde consola
function debugSliderState() {
    console.log('🔍 Estado actual del slider:');
    
    const elements = {
        bannersSlider: document.getElementById('bannersSlider'),
        slidesContainer: document.getElementById('slidesContainer'),
        sliderLoading: document.getElementById('sliderLoading')
    };
    
    for (const [name, element] of Object.entries(elements)) {
        if (element) {
            const styles = getComputedStyle(element);
            console.log(`${name}:`, {
                found: true,
                display: styles.display,
                opacity: styles.opacity,
                zIndex: styles.zIndex,
                innerHTML_length: element.innerHTML.length
            });
            
            if (name === 'slidesContainer') {
                console.log(`   Children: ${element.children.length}`);
                Array.from(element.children).forEach((child, i) => {
                    const childStyles = getComputedStyle(child);
                    console.log(`     Child ${i}: opacity=${childStyles.opacity}, display=${childStyles.display}`);
                });
            }
        } else {
            console.log(`${name}: NO ENCONTRADO`);
        }
    }
}

// Múltiples puntos de ejecución
document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 Dashboard Slider Fix: DOM cargado');
    setTimeout(forceFixDashboardSlider, 1000);
});

window.addEventListener('load', () => {
    console.log('🌍 Dashboard Slider Fix: Window cargado');
    setTimeout(forceFixDashboardSlider, 500);
});

// Fix adicional después de un tiempo
setTimeout(() => {
    console.log('⏰ Dashboard Slider Fix: Timer 3 segundos');
    forceFixDashboardSlider();
}, 3000);

setTimeout(() => {
    console.log('⏰ Dashboard Slider Fix: Timer 5 segundos');
    forceFixDashboardSlider();
}, 5000);

// Variables para rotación automática
let currentSlideIndex = 0;
let slideInterval = null;
let totalSlides = 0;

// Función de rotación automática
function initAutoRotation(slideCount) {
    totalSlides = slideCount;
    currentSlideIndex = 0;
    
    console.log(`🔄 Iniciando rotación automática cada 4 segundos para ${slideCount} slides`);
    
    // Limpiar intervalo anterior si existe
    if (slideInterval) {
        clearInterval(slideInterval);
    }
    
    // Configurar nuevo intervalo cada 4 segundos
    slideInterval = setInterval(() => {
        nextSlide();
    }, 4000);
}

function nextSlide() {
    const slides = document.querySelectorAll('.banner-slide-forced');
    if (slides.length === 0) return;
    
    // Ocultar slide actual
    if (slides[currentSlideIndex]) {
        slides[currentSlideIndex].style.opacity = '0';
        slides[currentSlideIndex].style.zIndex = '1';
    }
    
    // Avanzar al siguiente slide
    currentSlideIndex = (currentSlideIndex + 1) % totalSlides;
    
    // Mostrar nuevo slide
    if (slides[currentSlideIndex]) {
        slides[currentSlideIndex].style.opacity = '1';
        slides[currentSlideIndex].style.zIndex = '10';
    }
    
    console.log(`🎬 Cambiando a slide ${currentSlideIndex + 1} de ${totalSlides}`);
    
    // Actualizar indicadores si existen
    updateIndicators();
}

function goToSlide(index) {
    const slides = document.querySelectorAll('.banner-slide-forced');
    if (slides.length === 0 || index < 0 || index >= slides.length) return;
    
    // Ocultar slide actual
    if (slides[currentSlideIndex]) {
        slides[currentSlideIndex].style.opacity = '0';
        slides[currentSlideIndex].style.zIndex = '1';
    }
    
    // Mostrar slide seleccionado
    currentSlideIndex = index;
    if (slides[currentSlideIndex]) {
        slides[currentSlideIndex].style.opacity = '1';
        slides[currentSlideIndex].style.zIndex = '10';
    }
    
    console.log(`🎯 Saltando a slide ${currentSlideIndex + 1}`);
    updateIndicators();
    
    // Reiniciar el timer automático
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = setInterval(nextSlide, 4000);
    }
}

function updateIndicators() {
    const indicators = document.querySelectorAll('#sliderIndicators button');
    indicators.forEach((indicator, index) => {
        if (index === currentSlideIndex) {
            indicator.style.background = 'rgba(255,255,255,0.9)';
            indicator.style.width = '32px';
        } else {
            indicator.style.background = 'rgba(255,255,255,0.5)';
            indicator.style.width = '8px';
        }
    });
}

function pauseAutoRotation() {
    if (slideInterval) {
        clearInterval(slideInterval);
        slideInterval = null;
        console.log('⏸️ Rotación automática pausada');
    }
}

function resumeAutoRotation() {
    if (!slideInterval && totalSlides > 1) {
        slideInterval = setInterval(nextSlide, 4000);
        console.log('▶️ Rotación automática reanudada');
    }
}

// Pausar rotación cuando el mouse está sobre el slider
document.addEventListener('DOMContentLoaded', () => {
    const bannersSlider = document.getElementById('bannersSlider');
    if (bannersSlider) {
        bannersSlider.addEventListener('mouseenter', pauseAutoRotation);
        bannersSlider.addEventListener('mouseleave', resumeAutoRotation);
    }
});

// Hacer funciones globales para debugging
window.forceFixDashboardSlider = forceFixDashboardSlider;
window.loadBannersForcefully = loadBannersForcefully;
window.debugSliderState = debugSliderState;
window.emergencySliderFix = emergencySliderFix;
window.nextSlide = nextSlide;
window.goToSlide = goToSlide;
window.pauseAutoRotation = pauseAutoRotation;
window.resumeAutoRotation = resumeAutoRotation;

console.log('✅ Dashboard Slider Fix con rotación automática registrado globalmente');

// Función adicional para forzar carga desde consola
window.forceBannerReload = function() {
    console.log('🔄 Recarga forzada de banners desde consola...');
    loadBannersForcefully();
};

// Detectar cuando el usuario se autentica y cargar banners
window.addEventListener('userAuthenticated', () => {
    console.log('👤 Usuario autenticado detectado, cargando banners...');
    setTimeout(loadBannersForcefully, 1000);
});

// También intentar cargar si detectamos que el sistema de auth se inicializa
let authCheckAttempts = 0;
const maxAuthCheck = 10;

function checkAuthAndLoadBanners() {
    authCheckAttempts++;
    
    if (window.authSessionManager && window.authSessionManager.isAuthenticated) {
        console.log('🔓 Sistema de auth detectado y usuario autenticado, cargando banners...');
        loadBannersForcefully();
        return;
    }
    
    // Si el sistema de auth está en modo desarrollo, cargar banners
    if (window.authSessionManager && window.authSessionManager.currentUser) {
        console.log('🔧 Modo desarrollo activo, cargando banners...');
        loadBannersForcefully();
        return;
    }
    
    // Si no hay sistema de auth pero llevamos tiempo intentando, cargar de todos modos
    if (authCheckAttempts >= maxAuthCheck) {
        console.log('⏰ Timeout de auth alcanzado, cargando banners de todos modos...');
        loadBannersForcefully();
        return;
    }
    
    // Intentar de nuevo en 1 segundo
    if (authCheckAttempts < maxAuthCheck) {
        setTimeout(checkAuthAndLoadBanners, 1000);
    }
}

// Iniciar verificación de auth después de un momento
setTimeout(checkAuthAndLoadBanners, 2000);

console.log('📢 Funciones de debug disponibles:');
console.log('   - forceBannerReload() → Recargar banners desde BD');
console.log('   - debugSliderState() → Ver estado del slider');
console.log('   - forceFixDashboardSlider() → Fix completo del slider');