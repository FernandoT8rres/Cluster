/**
 * Claut Intranet - Dashboard Calendar System
 * Porsche-inspired Design System integration for FullCalendar v6
 */

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) return;

    // Initialize FullCalendar - Master Visual Viewer
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: false, // Hiding default toolbar to use our custom master controller
        allDaySlot: false,
        height: 'auto',
        contentHeight: 'auto', // Vital para que no colapse verticalmente
        handleWindowResize: true,
        slotDuration: '01:00:00',
        firstDay: 1, // Lunes a Domingo
        editable: false,
        selectable: false,
        nowIndicator: true,
        // Sync custom title
        datesSet: function(info) {
            const titleEl = document.getElementById('calendarTitle');
            if (titleEl) titleEl.textContent = info.view.title;
        },
        events: async function(info, successCallback, failureCallback) {
            try {
                const response = await fetch('./api/eventos.php?action=listar');
                const data = await response.json();
                
                if (data.success && data.eventos) {
                    const events = data.eventos.map(event => ({
                        id: event.id,
                        title: event.titulo,
                        start: event.fecha_inicio,
                        end: event.fecha_fin,
                        description: event.descripcion,
                        location: event.ubicacion,
                        color: event.tipo === 'Asamblea' ? '#C7252B' : 
                               event.tipo === 'Networking' ? '#3B82F6' : '#1a1a1a',
                        extendedProps: {
                            modalidad: event.modalidad,
                            link: event.link_evento,
                            imagen: event.imagen_url,
                            beneficio: event.tiene_beneficio
                        }
                    }));
                    successCallback(events);
                } else {
                    successCallback([]);
                }
            } catch (error) {
                console.error('Error fetching events:', error);
                failureCallback(error);
            }
        },
        eventClick: function(info) {
            // Show event details in a premium modal
            showEventDetail(info.event);
        },
        // Post-it Style Event Render Viewer
        eventDidMount: function(info) {
            const type = info.event.extendedProps.tipo || 'Evento';
            let color = '#334155'; // Default Slate
            
            switch(type) {
                case 'Asamblea': color = '#C7252B'; break;
                case 'Networking': color = '#3B82F6'; break;
                case 'Capacitación': color = '#10B981'; break;
                case 'Comunicado': color = '#F59E0B'; break;
            }
            
            // Apply Post-it Glow Style
            info.el.style.backgroundColor = `${color}15`; // 8% opacity
            info.el.style.borderLeft = `4px solid ${color}`;
            info.el.style.borderRadius = '8px';
            info.el.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            info.el.style.color = '#fff';
            
            const titleEl = info.el.querySelector('.fc-event-title');
            if (titleEl) titleEl.style.fontWeight = '700';
            
            const timeEl = info.el.querySelector('.fc-event-time');
            if (timeEl) timeEl.style.color = color;
        },
        dayMaxEvents: true,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false
        }
    });

    calendar.render();

    // 🚀 FIX CRÍTICO PARA TAILWIND:
    // Forzamos recalcular dimensiones asíncronamente para evitar
    // el desfase a la derecha y que no asuma altura cero.
    setTimeout(() => {
        if (calendar) calendar.updateSize();
    }, 250);

    // View Switching Logic for Dashboard
    setupViewSwitching(calendar);
    setupNavigation(calendar);
});

/**
 * Sync Navigation Buttons
 */
function setupNavigation(calendar) {
    const prev = document.getElementById('prevBtn');
    const next = document.getElementById('nextBtn');
    const today = document.getElementById('todayBtn');

    if (prev) prev.onclick = () => calendar.prev();
    if (next) next.onclick = () => calendar.next();
    if (today) today.onclick = () => calendar.today();
}

/**
 * Sync View Buttons with Calendar
 */
function setupViewSwitching(calendar) {
    const btnWeek = document.getElementById('viewWeek');
    const btnMonth = document.getElementById('viewMonth');
    if (!btnWeek || !btnMonth) return;

    btnWeek.onclick = () => {
        calendar.changeView('timeGridWeek');
        updateActiveViewButton('viewWeek');
    };
    btnMonth.onclick = () => {
        calendar.changeView('dayGridMonth');
        updateActiveViewButton('viewMonth');
    };
}

function updateActiveViewButton(id) {
    const btns = ['viewWeek', 'viewMonth'];
    btns.forEach(b => {
        const el = document.getElementById(b);
        if(!el) return;
        el.className = b === id 
            ? 'px-5 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all bg-red-600 text-white shadow-lg shadow-red-600/20'
            : 'px-5 py-2.5 rounded-lg text-[10px] font-black uppercase tracking-widest transition-all text-slate-400 hover:text-white';
    });
}

/**
 * Shows event details in a premium modal
 */
function showEventDetail(event) {
    const props = event.extendedProps;
    
    // Create or use existing modal
    let modal = document.getElementById('eventDetailModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'eventDetailModal';
        modal.className = 'fixed inset-0 z-[9999] hidden flex items-center justify-center p-4';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" onclick="closeEventModal()"></div>
            <div class="relative bg-slate-900 border border-white/10 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden animate-in fade-in zoom-in duration-300">
                <div id="eventModalContent"></div>
            </div>
        `;
        document.body.appendChild(modal);
    }

    const content = document.getElementById('eventModalContent');
    const hasImage = props.imagen && props.imagen !== '';
    
    content.innerHTML = `
        ${hasImage ? `<div class="h-48 w-full overflow-hidden">
            <img src="${props.imagen}" class="w-full h-full object-cover" alt="${event.title}">
        </div>` : ''}
        <div class="p-6">
            <div class="flex justify-between items-start mb-4">
                <span class="px-2 py-1 rounded bg-red-500/10 text-red-500 text-[10px] font-bold uppercase tracking-wider">${event.extendedProps.modalidad}</span>
                <button onclick="closeEventModal()" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <h3 class="text-2xl font-black text-white mb-2">${event.title}</h3>
            <p class="text-slate-400 text-sm mb-6 leading-relaxed">${event.extendedProps.description || 'Sin descripción disponible.'}</p>
            
            <div class="space-y-3 mb-8">
                <div class="flex items-center text-slate-300">
                    <i class="far fa-calendar-alt w-6 text-red-500"></i>
                    <span class="text-sm">${new Date(event.start).toLocaleString('es-MX', { dateStyle: 'long', timeStyle: 'short' })}</span>
                </div>
                ${event.extendedProps.location ? `
                <div class="flex items-center text-slate-300">
                    <i class="fas fa-map-marker-alt w-6 text-red-500"></i>
                    <span class="text-sm">${event.extendedProps.location}</span>
                </div>` : ''}
                ${props.beneficio == 1 ? `
                <div class="flex items-center text-emerald-400">
                    <i class="fas fa-award w-6"></i>
                    <span class="text-sm">Evento con Beneficios Clúster</span>
                </div>` : ''}
            </div>

            <div class="flex gap-4">
                ${props.link ? `
                <a href="${props.link}" target="_blank" class="flex-1 bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl text-center transition-all shadow-lg shadow-red-600/20">
                    Unirse al Evento
                </a>` : ''}
                <button onclick="closeEventModal()" class="flex-1 bg-white/5 hover:bg-white/10 text-white font-bold py-3 px-4 rounded-xl border border-white/10 transition-all">
                    Cerrar
                </button>
            </div>
        </div>
    `;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEventModal() {
    const modal = document.getElementById('eventDetailModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function applyPorscheStyles() {
    // These styles are applied via document.head to ensure they override FullCalendar defaults
    const style = document.createElement('style');
    style.innerHTML = `
        :root {
            --fc-border-color: rgba(255, 255, 255, 0.1);
            --fc-daygrid-event-dot-width: 8px;
            --fc-today-bg-color: rgba(199, 37, 43, 0.1);
        }
        
        #calendar {
            background: transparent;
            color: #fff;
            font-family: 'Inter', sans-serif;
            border-radius: 1.5rem;
            overflow: hidden;
            min-height: 800px !important; /* Asegura visualización completa de 30 días */
        }

        .fc .fc-view-harness {
            background: transparent !important;
        }

        .fc .fc-scrollgrid-section-header > td {
            background: rgba(255, 255, 255, 0.03) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
        }

        .fc .fc-toolbar-title {
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: -0.05em;
            color: #fff;
            font-size: 1.5rem;
        }

        .fc .fc-button-primary {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            text-transform: capitalize;
            font-weight: 600;
            border-radius: 8px;
            padding: 8px 16px;
            transition: all 0.3s ease;
        }

        .fc .fc-button-primary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .fc .fc-button-primary:disabled {
            background-color: transparent;
            opacity: 0.3;
        }

        .fc .fc-button-active {
            background-color: #C7252B !important;
            border-color: #C7252B !important;
            box-shadow: 0 4px 12px rgba(199, 37, 43, 0.3) !important;
        }

        .fc-theme-standard td, .fc-theme-standard th {
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* Nuclear Header Reset - Erradica el bloque blanco */
        .fc-col-header-cell {
            background: transparent !important;
            background-color: transparent !important;
        }

        .fc .fc-col-header-cell-cushion {
            color: #fff !important;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.65rem;
            letter-spacing: 0.15em;
            padding: 20px 0 !important;
            text-decoration: none !important;
            display: block !important;
        }

        .fc-col-header {
            background: rgba(255, 255, 255, 0.03) !important;
        }

        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
        }

        .fc .fc-daygrid-day-number {
            color: #64748b;
            font-weight: 600;
            padding: 8px 12px;
        }

        .fc .fc-day-today .fc-daygrid-day-number {
            color: #C7252B;
            font-weight: 900;
        }

        .fc-event {
            border-radius: 6px;
            padding: 2px 6px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .fc-event:hover {
            transform: scale(1.02);
            z-index: 50;
        }

        .fc-v-event .fc-event-main {
            padding: 2px 4px !important;
        }

        .fc-h-event .fc-event-title {
            font-weight: 600;
            font-size: 0.8rem;
        }

        /* List View Styles */
        .fc-list-event {
            background: rgba(255, 255, 255, 0.02);
            cursor: pointer;
        }

        .fc-list-day-cushion {
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .fc-list-event-title {
            font-weight: 700;
            color: #fff;
        }
    `;
    document.head.appendChild(style);
}

// Global window reference for modal
window.closeEventModal = closeEventModal;
