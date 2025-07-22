document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");
    alert("hi")
    console.log(window.dashboardCalendarData.events);
  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: "timeGridWeek",
    locale: "fr",
    slotMinTime: "07:00:00",
    slotMaxTime: "20:00:00",
    nowIndicator: true,
    headerToolbar: {
      left: "prev,next today",
      center: "title",
      right: "dayGridMonth,timeGridWeek,timeGridDay",
    },
    events: window.dashboardCalendarData.events || [],
  });

  calendar.render();
});
