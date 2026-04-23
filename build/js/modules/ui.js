/**
 * UI Component Manager (ui.js)
 * Archivo central para interacciones de Interfaz compartidas (Modals, Toasts de Alerta, Loaders)
 */

export const UI = {
    /**
     * Muestra un toast o snackbar de éxito o error
     */
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded shadow-lg text-white font-medium z-50 transform transition-all duration-300 translate-y-10 opacity-0`;
        toast.style.backgroundColor = type === 'success' ? '#22c55e' : '#ef4444';
        toast.innerText = message;
        
        document.body.appendChild(toast);
        
        // Animación de entrada
        setTimeout(() => {
            toast.classList.remove('translate-y-10', 'opacity-0');
        }, 50);
        
        // Animación de salida programada
        setTimeout(() => {
            toast.classList.add('translate-y-10', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    /**
     * Mostrar/Ocultar Loading Overlay global
     */
    toggleLoader(show = true) {
        let loader = document.getElementById('global-loader');
        
        if (!loader && show) {
            loader = document.createElement('div');
            loader.id = 'global-loader';
            loader.className = 'fixed inset-0 bg-white bg-opacity-75 z-50 flex justify-center items-center backdrop-blur-sm transition-opacity duration-300';
            loader.innerHTML = `
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            `;
            document.body.appendChild(loader);
        }
        
        if (loader) {
            if (show) {
                loader.style.display = 'flex';
                loader.style.opacity = '1';
            } else {
                loader.style.opacity = '0';
                setTimeout(() => loader.style.display = 'none', 300);
            }
        }
    }
};
