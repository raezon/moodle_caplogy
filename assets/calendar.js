document.addEventListener('DOMContentLoaded', function() {
    const calendarElement = document.getElementById('calendar');
    if (!calendarElement || !window.dashboardCalendarData) return;

    const { events, year, month } = window.dashboardCalendarData;

    const daysOfWeek = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];

    let html = '<table border="1" style="border-collapse: collapse; width: 100%;">';
    html += '<thead><tr>';
    daysOfWeek.forEach(function(day) {
        html += '<th style="padding:5px; background:#eee;">' + day + '</th>';
    });
    html += '</tr></thead>';

    const firstDay = new Date(year, month - 1, 1);
    let startWeekday = firstDay.getDay(); // 0=dimanche, 1=lundi...
    if (startWeekday === 0) startWeekday = 7; // dimanche = 7

    const daysInMonth = new Date(year, month, 0).getDate();

    let day = 1;
    html += '<tbody><tr>';

    // Cases vides avant premier jour
    for (let i = 1; i < startWeekday; i++) {
        html += '<td></td>';
    }

    // Premier rang
    for (let i = startWeekday; i <= 7; i++) {
        const dateKey = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');

        html += '<td style="vertical-align: top; padding: 5px;">';
        html += '<strong>' + day + '</strong><br/>';

        if (events[dateKey]) {
            events[dateKey].forEach(function(ev) {
                html += '<div style="font-size:0.85em; margin-top:3px;">' + ev.time + ' - ' + ev.name + '</div>';
            });
        }
        html += '</td>';
        day++;
    }
    html += '</tr>';

    // Rangs suivants
    while (day <= daysInMonth) {
        html += '<tr>';
        for (let i = 1; i <= 7; i++) {
            if (day > daysInMonth) {
                html += '<td></td>';
            } else {
                const dateKey = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                html += '<td style="vertical-align: top; padding: 5px;">';
                html += '<strong>' + day + '</strong><br/>';

                if (events[dateKey]) {
                    events[dateKey].forEach(function(ev) {
                        html += '<div style="font-size:0.85em; margin-top:3px;">' + ev.time + ' - ' + ev.name + '</div>';
                    });
                }
                html += '</td>';
                day++;
            }
        }
        html += '</tr>';
    }

    html += '</tbody></table>';

    calendarElement.innerHTML = html;
});
