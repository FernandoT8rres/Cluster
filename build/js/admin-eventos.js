/**
 * Claut Intranet - Admin Events Management
 * Handles CRUD operations for corporate events
 */

let allEventos = [];
let adminCalendar = null;

/**
 * Main initialization
 */
function loadAdminEventos() {
    // console.log('📅 Inicializando gestión de eventos administrativos...');
    fetchEventos();

    // Form submission
    const eventForm = document.getElementById('eventForm');
    if (eventForm) {
        eventForm.onsubmit = handleEventSubmit;
    }

    // Image preview
    const imageInput = document.getElementById('event_imagenFile');
    if (imageInput) {
        imageInput.onchange = handleImagePreview;
    }
}

/**
 * Alterna entre vista de tabla y calendario visual
 */
window.toggleEventView = function(view) {
    const tableView = document.getElementById('tableViewContainer');
    const calendarView = document.getElementById('calendarViewContainer');
    const btnTable = document.getElementById('btnViewTable');
    const btnCalendar = document.getElementById('btnViewCalendar');

    if (!tableView || !calendarView || !btnTable || !btnCalendar) return;

    if (view === 'calendar') {
        tableView.classList.add('hidden');
        calendarView.classList.remove('hidden');
        
        btnCalendar.classList.add('bg-red-600', 'text-white', 'shadow-lg', 'shadow-red-600/20');
        btnCalendar.classList.remove('text-slate-400', 'hover:text-white');
        
        btnTable.classList.remove('bg-red-600', 'text-white', 'shadow-lg', 'shadow-red-600/20');
        btnTable.classList.add('text-slate-400', 'hover:text-white');

        if (!adminCalendar) {
            initAdminCalendar();
        } else {
            // Un pequeño delay para que el contenedor esté visible antes del render
            setTimeout(() => {
                adminCalendar.render();
                syncCalendarEvents();
            }, 100);
        }
    } else {
        calendarView.classList.add('hidden');
        tableView.classList.remove('hidden');
        
        btnTable.classList.add('bg-red-600', 'text-white', 'shadow-lg', 'shadow-red-600/20');
        btnTable.classList.remove('text-slate-400', 'hover:text-white');
        
        btnCalendar.classList.remove('bg-red-600', 'text-white', 'shadow-lg', 'shadow-red-600/20');
        btnCalendar.classList.add('text-slate-400', 'hover:text-white');
    }
}

/**
 * Inicializa el calendario administrativo interactivo
 */
function initAdminCalendar() {
    const calendarEl = document.getElementById('adminCalendar');
    if (!calendarEl) return;

    adminCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek'
        },
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            week: 'Semana'
        },
        firstDay: 1, // Lunes a Domingo
        height: 'auto',
        selectable: true,
        editable: false,
        events: allEventos.map(event => ({
            id: event.id,
            title: event.titulo,
            start: event.fecha_inicio,
            end: event.fecha_fin,
            backgroundColor: '#C7252B',
            borderColor: 'rgba(255,255,255,0.1)',
            extendedProps: { ...event }
        })),
        dateClick: function(info) {
            window.openEventModal();
            // Pre-rellenar fecha con formato compatible con datetime-local
            const date = info.dateStr + 'T09:00';
            const dateInput = document.getElementById('event_fecha_inicio');
            if (dateInput) dateInput.value = date;
        },
        eventClick: function(info) {
            window.editEvent(info.event.id);
        }
    });

    adminCalendar.render();
}

/**
 * Sincroniza los eventos del objeto global con el calendario visual
 */
function syncCalendarEvents() {
    if (!adminCalendar) return;
    adminCalendar.removeAllEvents();
    adminCalendar.addEventSource(allEventos.map(event => ({
        id: event.id,
        title: event.titulo,
        start: event.fecha_inicio,
        end: event.fecha_fin,
        backgroundColor: '#C7252B',
        borderColor: 'rgba(255,255,255,0.1)',
        extendedProps: { ...event }
    })));
}

/**
 * Fetches all events from the API
 */
async function fetchEventos() {
    const tableBody = document.getElementById('eventsTableBody');
    if (!tableBody) return;

    try {
        const response = await fetch('./api/eventos.php?action=listar');
        const data = await response.json();

        if (data.success) {
            allEventos = data.eventos || [];
            renderEventsTable(allEventos);
            updateAdminStats(allEventos);
            syncCalendarEvents();
        } else {
            tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500">Error: ${data.message}</td></tr>`;
        }
    } catch (error) {
        console.error('Error fetching eventos:', error);
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-10 text-center text-red-500">Error de conexión con el servidor</td></tr>`;
    }
}

/**
 * Renders the events table
 */
function renderEventsTable(eventos) {
    const tableBody = document.getElementById('eventsTableBody');
    if (!tableBody || eventos.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" class="px-6 py-20 text-center text-slate-500 italic">No hay eventos registrados. Haga clic en 'Nuevo Evento' para comenzar.</td></tr>`;
        return;
    }

    tableBody.innerHTML = eventos.map(event => {
        const date = new Date(event.fecha_inicio).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
        const time = new Date(event.fecha_inicio).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
        
        // Status Badge Logic
        const now = new Date();
        const start = new Date(event.fecha_inicio);
        const end = event.fecha_fin ? new Date(event.fecha_fin) : start;
        
        let statusClass = 'bg-slate-500/10 text-slate-400';
        let statusText = 'Finalizado';
        
        if (now < start) {
            statusClass = 'bg-blue-500/10 text-blue-500';
            statusText = 'Programado';
        } else if (now >= start && now <= end) {
            statusClass = 'bg-emerald-500/10 text-emerald-500';
            statusText = 'En Curso';
        }

        return `
            <tr class="border-b border-white/5 hover:bg-white/5 transition-colors group">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center overflow-hidden flex-shrink-0">
                            ${event.imagen_url ? `<img src="${event.imagen_url}" class="w-full h-full object-cover">` : '<i class="fas fa-calendar text-slate-600"></i>'}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-white line-clamp-1">${event.titulo}</p>
                            <p class="text-[10px] text-slate-500 font-medium uppercase tracking-wider">${event.id}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="text-sm text-slate-300 font-medium">${date} | ${time}</span>
                        <span class="text-xs text-slate-500"><i class="fas fa-location-dot mr-1"></i>${event.ubicacion || 'Por definir'}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-white uppercase tracking-widest">${event.tipo}</span>
                        <span class="text-[10px] text-slate-400 font-medium">${event.modalidad}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 h-1.5 bg-white/5 rounded-full overflow-hidden max-w-[60px]">
                            <div class="h-full bg-red-600" style="width: ${(event.capacidad_actual/event.capacidad_maxima)*100}%"></div>
                        </div>
                        <span class="text-xs text-slate-400">${event.capacidad_actual}/${event.capacidad_maxima}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end gap-2">
                        <button onclick="editEvent(${event.id})" class="p-2 text-blue-400 hover:bg-blue-400/10 rounded-lg transition-all" title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteEvent(${event.id})" class="p-2 text-red-400 hover:bg-red-400/10 rounded-lg transition-all" title="Eliminar">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * Updates summary cards
 */
function updateAdminStats(eventos) {
    const now = new Date();
    const total = eventos.length;
    const proximos = eventos.filter(e => new Date(e.fecha_inicio) > now).length;
    const enCurso = eventos.filter(e => {
        const start = new Date(e.fecha_inicio);
        const end = e.fecha_fin ? new Date(e.fecha_fin) : start;
        return now >= start && now <= end;
    }).length;
    const totalRegistros = eventos.reduce((sum, e) => sum + (parseInt(e.capacidad_actual) || 0), 0);

    const animateCount = (id, target) => {
        const el = document.getElementById(id);
        if (el) {
            let current = 0;
            const step = Math.ceil(target / 20) || 1;
            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    el.textContent = target;
                    clearInterval(timer);
                } else {
                    el.textContent = current;
                }
            }, 30);
        }
    };

    animateCount('totalEventos', total);
    animateCount('proximosEventos', proximos);
    animateCount('enCursoEventos', enCurso);
    animateCount('totalRegistros', totalRegistros);
}

/**
 * Opens modal for creation
 */
window.openEventModal = function() {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const title = document.getElementById('eventModalTitle');
    
    if (form) form.reset();
    if (document.getElementById('event_id')) document.getElementById('event_id').value = '';
    if (title) title.textContent = 'Nuevo Evento';
    
    // Reset preview
    const preview = document.getElementById('imagePreview');
    if (preview) preview.innerHTML = '<i class="fas fa-image text-slate-600 text-xl"></i>';

    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Closes event modal
 */
window.closeEventModal = function() {
    const modal = document.getElementById('eventModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

/**
 * Handles image preview
 */
function handleImagePreview(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            const preview = document.getElementById('imagePreview');
            if (preview) {
                preview.innerHTML = `<img src="${event.target.result}" class="w-full h-full object-cover">`;
            }
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Edits an event
 */
window.editEvent = function(id) {
    const event = allEventos.find(e => e.id == id);
    if (!event) return;

    openEventModal();
    const title = document.getElementById('eventModalTitle');
    if (title) title.textContent = 'Editar Evento';

    document.getElementById('event_id').value = event.id;
    document.getElementById('event_titulo').value = event.titulo;
    document.getElementById('event_descripcion').value = event.descripcion || '';
    
    // Format date for datetime-local input
    if (event.fecha_inicio) {
        document.getElementById('event_fecha_inicio').value = event.fecha_inicio.substring(0, 16);
    }
    if (event.fecha_fin) {
        document.getElementById('event_fecha_fin').value = event.fecha_fin.substring(0, 16);
    }

    document.getElementById('event_tipo').value = event.tipo || 'Evento';
    document.getElementById('event_modalidad').value = event.modalidad || 'Presencial';
    document.getElementById('event_ubicacion').value = event.ubicacion || '';
    document.getElementById('event_capacidad_maxima').value = event.capacidad_maxima || 100;
    document.getElementById('event_link_evento').value = event.link_evento || '';
    document.getElementById('event_tiene_beneficio').checked = event.tiene_beneficio == 1;

    if (event.imagen_url) {
        document.getElementById('imagePreview').innerHTML = `<img src="${event.imagen_url}" class="w-full h-full object-cover">`;
    }
}

/**
 * Deletes an event
 */
window.deleteEvent = async function(id) {
    if (!confirm('¿Estás seguro de eliminar este evento? Esta acción no se puede deshacer.')) return;

    try {
        const response = await fetch(`./api/eventos.php?action=eliminar&id=${id}`, { method: 'DELETE' });
        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'Evento eliminado correctamente',
                background: '#1e293b',
                color: '#fff'
            });
            fetchEventos();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error deleting event:', error);
    }
}

/**
 * Handles form submission
 */
async function handleEventSubmit(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const id = formData.get('id');
    const action = id ? 'editar' : 'crear';
    
    // Show loading
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...';
    submitBtn.disabled = true;

    try {
        const response = await fetch(`./api/eventos.php?action=${action}`, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'Éxito',
                text: id ? 'Evento actualizado correctamente' : 'Evento creado exitosamente',
                background: '#1e293b',
                color: '#fff'
            });
            closeEventModal();
            fetchEventos();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: result.message,
                background: '#1e293b',
                color: '#fff'
            });
        }
    } catch (error) {
        console.error('Error saving event:', error);
        alert('Error de conexión al guardar el evento');
    } finally {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
}

// Global reference for navigation context
window.loadAdminEventos = loadAdminEventos;
