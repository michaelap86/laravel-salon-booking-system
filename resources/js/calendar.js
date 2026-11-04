import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

const el = document.getElementById('calendar');

if (el) {
    const cfg = JSON.parse(el.dataset.config);
    let hidden = new Set();

    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
        initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridDay,timeGridWeek,dayGridMonth,listWeek' },
        buttonText: { today: 'Today', day: 'Day', week: 'Week', month: 'Month', list: 'List' },
        height: 'auto',
        contentHeight: 760,
        nowIndicator: true,
        slotMinTime: '08:00:00',
        slotMaxTime: '21:00:00',
        slotDuration: '00:15:00',
        slotLabelInterval: '01:00',
        allDaySlot: false,
        expandRows: true,
        firstDay: 0,
        editable: cfg.canMove,
        eventDurationEditable: false,
        selectable: false,
        eventTimeFormat: { hour: 'numeric', minute: '2-digit', meridiem: 'short' },
        events: (info, ok, fail) => {
            const params = new URLSearchParams({ start: info.startStr, end: info.endStr });
            fetch(`${cfg.urls.events}?${params}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then((r) => r.json()).then((rows) => ok(rows.filter((e) => !hidden.has(String(e.extendedProps.staffId))))).catch(fail);
        },
        eventContent: (arg) => {
            const p = arg.event.extendedProps;
            const wrap = document.createElement('div');
            wrap.className = 'px-1 leading-tight overflow-hidden';
            wrap.innerHTML = `<div class="font-semibold truncate">${arg.timeText}</div><div class="truncate">${p.client}</div><div class="truncate opacity-80">${p.service}</div>`;
            if (p.status === 'cancelled' || p.status === 'no_show') wrap.style.textDecoration = 'line-through';
            return { domNodes: [wrap] };
        },
        eventClick: (info) => {
            info.jsEvent.preventDefault();
            window.dispatchEvent(new CustomEvent('appt-open', { detail: { id: info.event.id, title: info.event.title, start: info.event.start, end: info.event.end, ...info.event.extendedProps } }));
        },
        dateClick: (info) => {
            if (!cfg.canCreate || info.view.type === 'dayGridMonth') return;
            const d = info.date;
            const pad = (n) => String(n).padStart(2, '0');
            window.location = `${cfg.urls.create}?date=${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}&time=${pad(d.getHours())}:${pad(d.getMinutes())}`;
        },
        eventDrop: async (info) => {
            const d = info.event.start;
            const pad = (n) => String(n).padStart(2, '0');
            try {
                await window.api(cfg.urls.move.replace('__ID__', info.event.id), { method: 'POST', body: { start: `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}` } });
                window.toast('Appointment moved. The client has been emailed.');
                calendar.refetchEvents();
            } catch (e) {
                info.revert();
                window.toast(e.errors?.time?.[0] ?? e.message ?? 'Could not move that appointment.', 'error');
            }
        },
    });
    calendar.render();

    document.querySelectorAll('[data-staff-toggle]').forEach((box) => box.addEventListener('change', () => {
        box.checked ? hidden.delete(box.value) : hidden.add(box.value);
        calendar.refetchEvents();
    }));
    window.addEventListener('calendar-refresh', () => calendar.refetchEvents());
}
