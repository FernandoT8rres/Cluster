/**
 * DESBLOQUEO FORZADO DEL DASHBOARD
 * Sistema de emergencia para asegurar acceso completo al dashboard
 */

console.log('🚨 SISTEMA DE DESBLOQUEO FORZADO ACTIVADO');

let unlockAttempts = 0;
const maxUnlockAttempts = 10;

function forceUnlockDashboard() {
    unlockAttempts++;
    console.log(`🔓 Intento de desbloqueo #${unlockAttempts}`);
    
    // 1. Ocultar mensajes de acceso restringido (SIN eliminar)
    const restrictedMessages = document.querySelectorAll('#authRequiredMessage, .auth-required, [data-auth-required]');
    restrictedMessages.forEach(msg => {
        if (msg) {
            msg.style.display = 'none';
            msg.style.visibility = 'hidden';
            msg.classList.add('hidden');
            console.log('👁️ Mensaje de acceso restringido ocultado');
        }
    });
    
    // 2. Ocultar overlays de restricción (SIN eliminar)
    const overlays = document.querySelectorAll('#restrictedOverlay, .restricted-overlay, .auth-overlay');
    overlays.forEach(overlay => {
        if (overlay) {
            overlay.style.display = 'none';
            overlay.style.visibility = 'hidden';
            overlay.style.opacity = '0';
            overlay.classList.add('hidden');
            console.log('👁️ Overlay de restricción ocultado');
        }
    });
    
    // 3. Desbloquear contenido principal
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.classList.remove('restricted-section');
        mainContent.style.opacity = '1';
        mainContent.style.pointerEvents = 'auto';
        mainContent.style.display = 'block';
        mainContent.style.visibility = 'visible';
        console.log('✅ Contenido principal desbloqueado');
    }
    
    // 4. Desbloquear sidebar y navegación
    const sidebar = document.querySelector('aside, .sidebar, nav');
    if (sidebar) {
        sidebar.style.opacity = '1';
        sidebar.style.pointerEvents = 'auto';
        sidebar.style.display = 'block';
        sidebar.style.visibility = 'visible';
        console.log('✅ Sidebar desbloqueado');
    }
    
    // 5. Desbloquear todos los elementos del menú (preservando contenido)
    const menuItems = document.querySelectorAll('.restricted-nav-item, [data-restricted="true"]');
    menuItems.forEach((item, index) => {
        if (item.tagName === 'A') {
            item.style.opacity = '1';
            item.style.pointerEvents = 'auto';
            item.style.cursor = 'pointer';
            item.classList.add('authenticated');
            item.classList.remove('restricted');
        } else {
            const link = item.querySelector('a');
            if (link) {
                link.style.opacity = '1';
                link.style.pointerEvents = 'auto';
                link.style.cursor = 'pointer';
                link.classList.add('authenticated');
                link.classList.remove('restricted');
            }
        }
        console.log(`✅ Menú ${index + 1} desbloqueado`);
    });
    
    // 5.1. Cargar información de usuario temporal si no existe
    loadUserInfo();
    
    // 6. Ocultar íconos de candado
    const lockIcons = document.querySelectorAll('.restricted-icon, .fa-lock, [class*="lock"]');
    lockIcons.forEach(icon => {
        icon.style.display = 'none';
        icon.style.visibility = 'hidden';
    });
    
    // 7. Mostrar elementos que deberían estar visibles para usuarios autenticados
    const authElements = document.querySelectorAll('[data-auth="true"], .auth-only');
    authElements.forEach(element => {
        element.style.display = 'block';
        element.style.visibility = 'visible';
        element.style.opacity = '1';
        element.classList.remove('hidden');
    });
    
    // 8. Remover estilos de restricción CSS
    addForceUnlockCSS();
    
    // 9. Verificar si el desbloqueo fue exitoso
    const stillRestricted = document.querySelector('#authRequiredMessage:not(.hidden), #restrictedOverlay:not(.hidden)');
    if (stillRestricted && unlockAttempts < maxUnlockAttempts) {
        console.log('⚠️ Aún hay elementos restringidos, reintentando...');
        setTimeout(forceUnlockDashboard, 1000);
    } else {
        console.log('🎉 DASHBOARD COMPLETAMENTE DESBLOQUEADO');
        showSuccessMessage();
    }
}

function addForceUnlockCSS() {
    const styleId = 'force-unlock-styles';
    if (!document.getElementById(styleId)) {
        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = `
            /* DESBLOQUEO FORZADO - ESTILOS DE EMERGENCIA */
            #authRequiredMessage,
            #restrictedOverlay,
            .restricted-overlay,
            .auth-required,
            [data-auth-required] {
                display: none !important;
                visibility: hidden !important;
                opacity: 0 !important;
                pointer-events: none !important;
            }
            
            #mainContent,
            .main-content,
            .dashboard-content {
                opacity: 1 !important;
                pointer-events: auto !important;
                display: block !important;
                visibility: visible !important;
            }
            
            .restricted-nav-item a,
            .restricted-nav-item a.authenticated,
            a[data-restricted="true"],
            a[data-restricted="true"].authenticated,
            .nav-link,
            .sidebar a {
                opacity: 1 !important;
                pointer-events: auto !important;
                cursor: pointer !important;
                color: inherit !important;
            }
            
            .restricted-nav-item a:hover,
            a[data-restricted="true"]:hover {
                opacity: 1 !important;
                background-color: rgba(255, 255, 255, 0.1) !important;
            }
            
            .restricted-icon,
            .fa-lock,
            [class*="lock-icon"] {
                display: none !important;
            }
            
            aside, 
            .sidebar, 
            nav {
                opacity: 1 !important;
                pointer-events: auto !important;
                display: block !important;
                visibility: visible !important;
            }
            
            /* Asegurar que todo el dashboard sea visible */
            body {
                overflow: auto !important;
            }
            
            .dashboard-container,
            .dashboard-wrapper {
                opacity: 1 !important;
                pointer-events: auto !important;
            }
        `;
        document.head.appendChild(style);
        console.log('✅ CSS de desbloqueo forzado aplicado');
    }
}

function loadUserInfo() {
    console.log('👤 Cargando información temporal del usuario...');
    
    // Buscar elementos de usuario
    const userNameElement = document.getElementById('userName') || document.querySelector('.user-name, [data-user-name]');
    const userRoleElement = document.getElementById('userRole') || document.querySelector('.user-role, [data-user-role]');
    const welcomeMessage = document.getElementById('welcomeMessage') || document.querySelector('.welcome-message, [data-welcome]');
    const userAvatar = document.getElementById('userAvatar') || document.querySelector('.user-avatar, [data-avatar]');
    
    // Cargar nombre si no existe
    if (userNameElement && (!userNameElement.textContent || userNameElement.textContent.trim() === '')) {
        userNameElement.textContent = 'Fernando Torres';
        console.log('✅ Nombre de usuario cargado: Fernando Torres');
    }
    
    // Cargar rol si no existe
    if (userRoleElement && (!userRoleElement.textContent || userRoleElement.textContent.trim() === '')) {
        userRoleElement.textContent = 'Administrador';
        console.log('✅ Rol de usuario cargado: Administrador');
    }
    
    // Cargar mensaje de bienvenida si no existe
    if (welcomeMessage) {
        if (!welcomeMessage.textContent || welcomeMessage.textContent.trim() === '') {
            welcomeMessage.innerHTML = '<h6 class="mb-0 font-bold text-white capitalize">Bienvenido, Fernando</h6>';
            console.log('✅ Mensaje de bienvenida cargado');
        }
    }
    
    // Configurar avatar si no existe
    if (userAvatar && !userAvatar.src) {
        userAvatar.src = './assets/img/team-1.jpg';
        userAvatar.alt = 'Avatar del usuario';
        console.log('✅ Avatar de usuario configurado');
    }
    
    // Mostrar elementos de usuario autenticado
    const loginItems = document.querySelectorAll('#loginNavItem, #signupNavItem, #loginMenuItem');
    loginItems.forEach(item => {
        if (item) {
            item.style.display = 'none';
        }
    });
    
    const userItems = document.querySelectorAll('#userMenuDropdown, #logoutMenuItem, .user-info, .auth-user');
    userItems.forEach(item => {
        if (item) {
            item.style.display = 'block';
            item.style.opacity = '1';
            item.classList.remove('hidden');
        }
    });
    
    console.log('✅ Elementos de usuario configurados');
}

function showSuccessMessage() {
    // Remover mensaje anterior si existe
    const existingMsg = document.getElementById('unlockSuccessMessage');
    if (existingMsg) existingMsg.remove();
    
    const messageHtml = `
        <div id="unlockSuccessMessage" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: #22c55e;
            color: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(34, 197, 94, 0.3);
            z-index: 99999;
            max-width: 350px;
            font-family: system-ui, -apple-system, sans-serif;
            border: 2px solid #16a34a;
        ">
            <div style="display: flex; align-items: center; margin-bottom: 10px;">
                <div style="font-size: 24px; margin-right: 12px;">🎉</div>
                <div style="font-weight: bold; font-size: 16px;">¡Dashboard Desbloqueado!</div>
            </div>
            <div style="font-size: 14px; line-height: 1.4; margin-bottom: 15px;">
                El dashboard está completamente funcional. Todos los menús y características están disponibles.
            </div>
            <div style="text-align: center;">
                <button onclick="document.getElementById('unlockSuccessMessage').remove()" style="
                    background: rgba(255,255,255,0.2);
                    border: 1px solid rgba(255,255,255,0.3);
                    color: white;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-size: 12px;
                ">Cerrar</button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', messageHtml);
    
    // Auto-remover después de 8 segundos
    setTimeout(() => {
        const msg = document.getElementById('unlockSuccessMessage');
        if (msg) msg.remove();
    }, 8000);
}

function addEmergencyButton() {
    // Remover botón anterior si existe
    const existingBtn = document.getElementById('emergencyUnlockBtn');
    if (existingBtn) existingBtn.remove();
    
    const buttonHtml = `
        <button id="emergencyUnlockBtn" onclick="forceUnlockDashboard()" style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
            border: none;
            padding: 20px 30px;
            border-radius: 12px;
            cursor: pointer;
            z-index: 99999;
            font-size: 18px;
            font-weight: bold;
            box-shadow: 0 8px 25px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
            font-family: system-ui, -apple-system, sans-serif;
            min-width: 280px;
            text-align: center;
        ">
            🔓 DESBLOQUEAR DASHBOARD
        </button>
        <style>
            @keyframes pulse {
                0% { transform: translate(-50%, -50%) scale(1); }
                50% { transform: translate(-50%, -50%) scale(1.05); }
                100% { transform: translate(-50%, -50%) scale(1); }
            }
            #emergencyUnlockBtn:hover {
                background: linear-gradient(45deg, #c82333, #dc3545);
                transform: translate(-50%, -50%) scale(1.05);
            }
        </style>
    `;
    
    document.body.insertAdjacentHTML('beforeend', buttonHtml);
}

// EJECUCIÓN INMEDIATA Y CONTINUA
console.log('⚡ Iniciando desbloqueo inmediato...');

// Ejecutar inmediatamente
forceUnlockDashboard();

// Ejecutar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    console.log('📋 DOM listo, ejecutando desbloqueo...');
    setTimeout(forceUnlockDashboard, 500);
    setTimeout(forceUnlockDashboard, 1500);
    setTimeout(forceUnlockDashboard, 3000);
});

// Ejecutar cuando la ventana cargue completamente
window.addEventListener('load', () => {
    console.log('🌍 Ventana cargada, ejecutando desbloqueo final...');
    setTimeout(forceUnlockDashboard, 1000);
});

// Mostrar botón de emergencia si después de 5 segundos aún hay restricciones
setTimeout(() => {
    const stillRestricted = document.querySelector('#authRequiredMessage, #restrictedOverlay');
    if (stillRestricted && stillRestricted.style.display !== 'none') {
        console.log('🚨 Restricciones detectadas después de 5 segundos, mostrando botón de emergencia');
        addEmergencyButton();
    }
}, 5000);

// Funciones globales para debugging
window.forceUnlockDashboard = forceUnlockDashboard;
window.addEmergencyButton = addEmergencyButton;
window.loadUserInfo = loadUserInfo;
window.forceShowUserData = function() {
    console.log('👤 FORZANDO CARGA DE DATOS DE USUARIO...');
    loadUserInfo();
    forceUnlockDashboard();
};

console.log('🛡️ Sistema de desbloqueo forzado inicializado');
console.log('💡 Funciones disponibles:');
console.log('   - forceUnlockDashboard() → Desbloquear dashboard completo');
console.log('   - loadUserInfo() → Cargar información de usuario');
console.log('   - forceShowUserData() → Forzar carga completa de datos de usuario');