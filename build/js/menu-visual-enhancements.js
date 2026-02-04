/**
 * Mejoras visuales para el menú del dashboard
 * Clúster Intranet
 */

// CSS mejorado para el menú
const enhancedMenuStyles = `
  /* Estilos mejorados para el sidebar */
  .sidebar-enhanced {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.98) 100%);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-right: 1px solid rgba(226, 232, 240, 0.8);
    box-shadow: 
      0 20px 25px -5px rgba(0, 0, 0, 0.1),
      0 10px 10px -5px rgba(0, 0, 0, 0.04),
      0 0 0 1px rgba(255, 255, 255, 0.05);
  }

  /* Efectos hover mejorados para elementos del menú */
  .authenticated .restricted-nav-item a[data-restricted="true"]:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.08) 0%, rgba(220, 38, 38, 0.05) 100%);
    transform: translateX(4px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.15);
    border-radius: 12px;
    color: #dc2626;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  }

  /* Iconos con gradientes */
  .authenticated .restricted-nav-item a[data-restricted="true"]:hover .ni,
  .authenticated .restricted-nav-item a[data-restricted="true"]:hover .fas {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    transform: scale(1.1);
    transition: all 0.3s ease;
  }

  /* Efectos para el ítem activo (Inicio) */
  .bg-clúster-red\\/20 {
    background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    color: white !important;
    box-shadow: 0 8px 16px rgba(239, 68, 68, 0.3);
    border-radius: 12px;
  }

  .bg-clúster-red\\/20 .ni {
    color: white !important;
  }

  /* Animación de entrada para elementos del menú */
  @keyframes menuItemFadeIn {
    from {
      opacity: 0;
      transform: translateX(-10px);
    }
    to {
      opacity: 1;
      transform: translateX(0);
    }
  }

  .authenticated .restricted-nav-item {
    animation: menuItemFadeIn 0.5s ease-out;
  }

  /* Delayed animations for staggered effect */
  .authenticated .restricted-nav-item:nth-child(2) { animation-delay: 0.1s; }
  .authenticated .restricted-nav-item:nth-child(3) { animation-delay: 0.2s; }
  .authenticated .restricted-nav-item:nth-child(4) { animation-delay: 0.3s; }
  .authenticated .restricted-nav-item:nth-child(5) { animation-delay: 0.4s; }
  .authenticated .restricted-nav-item:nth-child(6) { animation-delay: 0.5s; }
  .authenticated .restricted-nav-item:nth-child(7) { animation-delay: 0.6s; }
  .authenticated .restricted-nav-item:nth-child(8) { animation-delay: 0.7s; }

  /* Scroll mejorado */
  .scroll-enhanced::-webkit-scrollbar {
    width: 6px;
  }

  .scroll-enhanced::-webkit-scrollbar-track {
    background: rgba(241, 245, 249, 0.5);
    border-radius: 3px;
  }

  .scroll-enhanced::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 3px;
    transition: all 0.3s ease;
  }

  .scroll-enhanced::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    transform: scaleY(1.2);
  }

  /* Efectos de brillo en elementos activos */
  .authenticated .restricted-nav-item a[data-restricted="true"]:active {
    transform: translateX(4px) scale(0.98);
    box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
  }

  /* Indicador visual para elementos disponibles */
  .authenticated .restricted-nav-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 0;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 0 2px 2px 0;
    transition: height 0.3s ease;
  }

  .authenticated .restricted-nav-item:hover::before {
    height: 20px;
  }

  /* Efecto de pulso para nuevas funcionalidades */
  @keyframes pulse {
    0%, 100% {
      opacity: 1;
    }
    50% {
      opacity: 0.7;
    }
  }

  .authenticated .restricted-nav-item.new-feature {
    animation: pulse 2s infinite;
  }

  /* Badge para indicar funcionalidades nuevas */
  .authenticated .restricted-nav-item.new-feature::after {
    content: 'NUEVO';
    position: absolute;
    top: 8px;
    right: 8px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    font-size: 8px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 8px;
    text-transform: uppercase;
  }

  /* Mejoras para el header del sidebar */
  .sidebar-enhanced .h-19 a {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(220, 38, 38, 0.02) 100%);
    border-radius: 12px;
    transition: all 0.3s ease;
    border: 1px solid rgba(239, 68, 68, 0.1);
  }

  .sidebar-enhanced .h-19 a:hover {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.05) 100%);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
    transform: translateY(-1px);
  }

  /* Separador mejorado */
  .sidebar-enhanced hr {
    background: linear-gradient(90deg, transparent 0%, rgba(239, 68, 68, 0.3) 50%, transparent 100%);
    height: 1px;
    border: none;
    margin: 1rem 0;
  }

  /* Título de sección mejorado */
  .sidebar-enhanced h6 {
    color: #64748b;
    font-weight: 600;
    letter-spacing: 0.05em;
    position: relative;
    padding-left: 12px;
  }

  .sidebar-enhanced h6::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 12px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-radius: 2px;
  }

  /* Efecto de ondas para elementos clickeados */
  @keyframes ripple {
    0% {
      transform: scale(0);
      opacity: 0.6;
    }
    100% {
      transform: scale(4);
      opacity: 0;
    }
  }

  .authenticated .restricted-nav-item a[data-restricted="true"]::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    background: rgba(239, 68, 68, 0.3);
    border-radius: 50%;
    transform: translate(-50%, -50%) scale(0);
    pointer-events: none;
  }

  .authenticated .restricted-nav-item a[data-restricted="true"]:active::after {
    animation: ripple 0.6s ease-out;
  }

  /* Estados de loading */
  .menu-loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
  }

  .menu-loading::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    animation: loading-shimmer 1.5s infinite;
  }

  @keyframes loading-shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  /* Responsive improvements */
  @media (max-width: 1024px) {
    .sidebar-enhanced {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(25px);
    }
  }

  /* Dark mode support */
  @media (prefers-color-scheme: dark) {
    .dark .sidebar-enhanced {
      background: linear-gradient(135deg, rgba(30, 41, 59, 0.95) 0%, rgba(51, 65, 85, 0.98) 100%);
      border-right-color: rgba(71, 85, 105, 0.8);
    }

    .dark .authenticated .restricted-nav-item a[data-restricted="true"]:hover {
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(220, 38, 38, 0.1) 100%);
      color: #fecaca;
    }
  }

  /* Success state for enabled menu items */
  .authenticated .restricted-nav-item.enabled {
    border-left: 3px solid #10b981;
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
  }

  /* Notification badge for menu items */
  .authenticated .restricted-nav-item[data-notifications]::after {
    content: attr(data-notifications);
    position: absolute;
    top: 12px;
    right: 12px;
    background: #ef4444;
    color: white;
    font-size: 10px;
    font-weight: bold;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid white;
  }
`;

// Función para aplicar los estilos mejorados
function applyEnhancedStyles() {
  const styleId = 'enhanced-menu-styles';
  let existingStyle = document.getElementById(styleId);
  
  if (existingStyle) {
    existingStyle.remove();
  }
  
  const style = document.createElement('style');
  style.id = styleId;
  style.textContent = enhancedMenuStyles;
  document.head.appendChild(style);
  
  console.log('✨ Estilos mejorados aplicados al menú');
}

// Función para agregar efectos especiales
function addMenuEnhancements() {
  const restrictedItems = document.querySelectorAll('.restricted-nav-item');
  
  restrictedItems.forEach((item, index) => {
    // Agregar clases especiales
    item.classList.add('enhanced-item');
    
    // Agregar efecto de ondas al hacer click
    const link = item.querySelector('a');
    if (link) {
      link.addEventListener('click', function(e) {
        // Solo aplicar efecto si el elemento está habilitado
        if (this.classList.contains('authenticated')) {
          this.style.position = 'relative';
          this.style.overflow = 'hidden';
        }
      });
    }
    
    // Agregar números de notificación aleatorios para demo
    if (Math.random() > 0.7) {
      const notificationCount = Math.floor(Math.random() * 5) + 1;
      item.setAttribute('data-notifications', notificationCount);
    }
  });
  
  console.log('🎨 Efectos especiales agregados al menú');
}

// Función para animar la habilitación del menú
function animateMenuActivation() {
  const restrictedItems = document.querySelectorAll('.restricted-nav-item');
  
  restrictedItems.forEach((item, index) => {
    setTimeout(() => {
      item.classList.add('enabled');
      
      // Efecto de brillo temporal
      item.style.boxShadow = '0 0 20px rgba(16, 185, 129, 0.3)';
      setTimeout(() => {
        item.style.boxShadow = '';
      }, 1000);
      
    }, index * 100);
  });
  
  console.log('✅ Animación de activación del menú completada');
}

// Inicializar mejoras cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
  console.log('🎨 Inicializando mejoras visuales del menú...');
  
  // Aplicar estilos inmediatamente
  applyEnhancedStyles();
  
  // Esperar un poco para que otros scripts se carguen
  setTimeout(() => {
    addMenuEnhancements();
    
    // Verificar si el usuario está autenticado y animar
    const isAuthenticated = document.body.classList.contains('authenticated') ||
                           (window.authSessionManager && window.authSessionManager.isAuthenticated);
    
    if (isAuthenticated) {
      setTimeout(animateMenuActivation, 500);
    }
  }, 1000);
});

// También aplicar inmediatamente si el DOM ya está listo
if (document.readyState !== 'loading') {
  applyEnhancedStyles();
  setTimeout(() => {
    addMenuEnhancements();
  }, 100);
}

// Escuchar eventos de autenticación para activar animaciones
document.addEventListener('userLoggedIn', function() {
  console.log('👤 Usuario logueado - activando animaciones del menú');
  setTimeout(animateMenuActivation, 500);
});

// Función global para refrescar estilos
window.refreshMenuStyles = function() {
  applyEnhancedStyles();
  addMenuEnhancements();
  console.log('🔄 Estilos del menú actualizados');
};

// Función para mostrar estado del menú
window.showMenuStatus = function() {
  const info = {
    isAuthenticated: document.body.classList.contains('authenticated'),
    restrictedItemsCount: document.querySelectorAll('.restricted-nav-item').length,
    enabledItemsCount: document.querySelectorAll('.restricted-nav-item.enabled').length,
    hasEnhancedStyles: document.getElementById('enhanced-menu-styles') !== null
  };
  
  console.table(info);
  return info;
};

console.log('✨ Mejoras visuales del menú cargadas');
console.log('Comandos disponibles:');
console.log('- refreshMenuStyles(): Actualizar estilos del menú');
console.log('- showMenuStatus(): Mostrar estado del menú');
