/**
 * Claut Intranet - Master Calendar Master Controller
 * Handles visual administrative planning (Notion Style)
 */

document.addEventListener('DOMContentLoaded', function () {
    // console.log('🚀 Master Calendar Console Initializing...');
    initMasterCalendar();
    setupQuickEventForm();
});

let masterCalendar = null;
let allEventos = [];

/**
 * Initialize FullCalendar High Performance
 */
function initMasterCalendar() {
    const calendarEl = document.getElementById('admin-master-calendar');
    if (!calendarEl) return;

    masterCalendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'timeGridWeek',
        locale: 'es',
        headerToolbar: false, // Custom header in HTML
        slotDuration: '01:00:00',
        snapDuration: '00:15:00',
        firstDay: 1, // Lunes
        editable: true,
        selectable: true,
        droppable: true,
        nowIndicator: true,
        dayMaxEvents: true,
        height: '100%',
        slotLabelFormat: {
            hour: 'numeric',
            minute: '2-digit',
            omitZeroMinute: false,
            meridiem: 'short'
        },
        
        // Post-it Style Event Render
        eventDidMount: function(info) {
            const type = info.event.extendedProps.tipo || 'Evento';
            let color = '#334155'; // Slate
            
            switch(type) {
                case 'Asamblea': color = '#C7252B'; break;
                case 'Networking': color = '#3B82F6'; break;
                case 'Capacitación': color = '#10B981'; break;
                case 'Comunicado': color = '#F59E0B'; break;
            }
            
            info.el.style.backgroundColor = `${color}25`; // 15% opacity
            info.el.style.borderLeft = `4px solid ${color}`;
            info.el.style.color = '#fff';
            
            // Tooltip or small badge inside?
            const timeEl = info.el.querySelector('.fc-event-time');
            if (timeEl) timeEl.style.color = color;
        },

        // API Events Source
        events: async function(info, successCallback, failureCallback) {
            try {
                const response = await fetch('./api/eventos.php?action=listar');
                const data = await response.json();
                if (data.success) {
                    allEventos = data.eventos;
                    const events = data.eventos.map(e => ({
                        id: e.id,
                        title: e.titulo,
                        start: e.fecha_inicio,
                        end: e.fecha_fin || e.fecha_inicio,
                        extendedProps: { ...e }
                    }));
                    successCallback(events);
                }
            } catch (error) {
                console.error('Error fetching events:', error);
                failureCallback(error);
            }
        },

        // Interaction: Click to Create
        select: function(info) {
            openQuickEventModal();
            document.getElementById('event_fecha_inicio').value = info.startStr.substring(0, 16);
            document.getElementById('event_fecha_fin').value = info.endStr.substring(0, 16);
        },

        // Interaction: Click to Edit
        eventClick: function(info) {
            editEvent(info.event.id);
        },

        // Interaction: Drag & Drop Update
        eventDrop: async function(info) {
            await updateEventDates(info.event);
        },

        // Interaction: Resize Update
        eventResize: async function(info) {
            await updateEventDates(info.event);
        }
    });

    masterCalendar.render();

    // View Switching
    document.getElementById('viewWeek').onclick = () => {
        masterCalendar.changeView('timeGridWeek');
        updateActiveViewButton('viewWeek');
    };
    document.getElementById('viewMonth').onclick = () => {
        masterCalendar.changeView('dayGridMonth');
        updateActiveViewButton('viewMonth');
    };
}

function updateActiveViewButton(id) {
    const btns = ['viewWeek', 'viewMonth'];
    btns.forEach(b => {
        const el = document.getElementById(b);
        el.className = b === id 
            ? 'px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all bg-red-600 text-white shadow-lg shadow-red-600/20'
            : 'px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all text-slate-400 hover:text-white';
    });
}

/**
 * Sync Dates with DB after Drag/Resize
 */
async function updateEventDates(event) {
    const formData = new FormData();
    formData.append('id', event.id);
    formData.append('titulo', event.title);
    formData.append('fecha_inicio', event.start.toISOString().slice(0, 19).replace('T', ' '));
    formData.append('fecha_fin', event.end ? event.end.toISOString().slice(0, 19).replace('T', ' ') : event.start.toISOString().slice(0, 19).replace('T', ' '));
    
    // Maintain existing props
    const original = allEventos.find(e => e.id == event.id);
    if(original) {
        formData.append('tipo', original.tipo);
        formData.append('modalidad', original.modalidad);
    }

    try {
        const response = await fetch('./api/eventos.php?action=editar', { method: 'POST', body: formData });
        const result = await response.json();
        if (result.success) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Calendario Actualizado',
                showConfirmButton: false,
                timer: 2000,
                background: '#18181b',
                color: '#fff'
            });
        }
    } catch (error) {
        console.error('Error auto-updating event:', error);
    }
}

/**
 * Event Management Functions
 */
window.openQuickEventModal = function() {
    document.getElementById('quickEventForm').reset();
    document.getElementById('event_id').value = '';
    document.getElementById('btnDeleteEvent').classList.add('hidden');
    document.getElementById('quickEventModal').classList.remove('hidden');
};

window.closeQuickEventModal = function() {
    document.getElementById('quickEventModal').classList.add('hidden');
};

window.editEvent = function(id) {
    const event = allEventos.find(e => e.id == id);
    if (!event) return;

    openQuickEventModal();
    document.getElementById('event_id').value = event.id;
    document.getElementById('event_titulo').value = event.titulo;
    document.getElementById('event_fecha_inicio').value = event.fecha_inicio.substring(0, 16);
    document.getElementById('event_fecha_fin').value = (event.fecha_fin || event.fecha_inicio).substring(0, 16);
    document.getElementById('event_tipo').value = event.tipo || 'Networking';
    
    const delBtn = document.getElementById('btnDeleteEvent');
    delBtn.classList.remove('hidden');
    delBtn.onclick = () => deleteEvent(event.id);
};

function setupQuickEventForm() {
    const form = document.getElementById('quickEventForm');
    if (!form) return;

    form.onsubmit = async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const id = formData.get('id');
        const action = id ? 'editar' : 'crear';

        try {
            const response = await fetch(`./api/eventos.php?action=${action}`, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                closeQuickEventModal();
                masterCalendar.refetchEvents();
                Swal.fire({ title: 'Éxito', text: 'Evento guardado correctamente', icon: 'success', background: '#1e293b', color: '#fff' });
            }
        } catch (error) {
            console.error('Error saving event:', error);
        }
    };
}

async function deleteEvent(id) {
    if (!confirm('¿Eliminar este evento permanentemente?')) return;

    try {
        const response = await fetch(`./api/eventos.php?action=eliminar&id=${id}`, { method: 'DELETE' });
        const result = await response.json();
        if (result.success) {
            closeQuickEventModal();
            masterCalendar.refetchEvents();
            Swal.fire({ title: 'Eliminado', text: 'El post-it ha sido removido del calendario.', icon: 'info', background: '#1e293b', color: '#fff' });
        }
    } catch (error) {
        console.error('Error deleting event:', error);
    }
}
