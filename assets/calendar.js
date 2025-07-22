document.addEventListener("DOMContentLoaded", function () {
  const calendarEl = document.getElementById("calendar");

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

    eventContent: function (arg) {
      // arg.view.type est le nom de la vue courante
      if (arg.view.type === "dayGridMonth") {
        return {
          html: `<div style="background-color: blue;color:white; padding: 2px 6px; border-radius: 4px; color: black;">
               ${arg.event.title}
             </div>`,
        };
      } else {
        // Affichage par défaut (sans HTML personnalisé)
        return { html: arg.event.title };
      }
    },

    eventClick: function (info) {
      info.jsEvent.preventDefault();

      const event = info.event;
      const typeActivite = event.extendedProps.typeActivite || "Non précisé";
      const module = event.extendedProps.matiere || "Non précisé";
      const ecole = event.extendedProps.ecole || "Non précisé";

      const modalContent = `
        <div style="
          background: #fff;
          padding: 25px 30px;
          border-radius: 12px;
          max-width: 420px;
          width: 90vw;
          box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
          font-family: Arial, sans-serif;
          color: #333;
          text-align: left;
        ">
          <h3 style="margin-top: 0; margin-bottom: 15px; font-weight: 600; font-size: 1.4em;">${event.title}</h3>
          <p><strong>Type d'activité :</strong> ${typeActivite}</p>
          <p><strong>Module :</strong> ${module}</p>
          <p><strong>École :</strong> ${ecole}</p>
          <div style="margin-top: 25px; text-align: right;">
            <button id="process-session-btn" style="
              padding: 10px 22px;
              background: #28a745;
              color: white;
              border: none;
              border-radius: 6px;
              cursor: pointer;
              font-weight: 600;
              transition: background-color 0.3s ease;
            ">Traiter la séance</button>
            <button id="close-modal-btn" style="
              padding: 10px 22px;
              background: #dc3545;
              color: white;
              border: none;
              border-radius: 6px;
              cursor: pointer;
              font-weight: 600;
              margin-left: 12px;
              transition: background-color 0.3s ease;
            ">Fermer</button>
          </div>
        </div>
      `;

      let modal = document.getElementById("custom-event-modal");
      if (!modal) {
        modal = document.createElement("div");
        modal.id = "custom-event-modal";
        Object.assign(modal.style, {
          position: "fixed",
          top: 0,
          left: 0,
          right: 0,
          bottom: 0,
          backgroundColor: "rgba(0,0,0,0.4)",
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          zIndex: 9999,
          padding: "20px",
        });
        document.body.appendChild(modal);
      }
      modal.innerHTML = modalContent;
      modal.style.display = "flex";

      // Hover effects
      const processBtn = modal.querySelector("#process-session-btn");
      const closeBtn = modal.querySelector("#close-modal-btn");

      processBtn.onmouseover = () =>
        (processBtn.style.backgroundColor = "#218838");
      processBtn.onmouseout = () =>
        (processBtn.style.backgroundColor = "#28a745");

      closeBtn.onmouseover = () => (closeBtn.style.backgroundColor = "#c82333");
      closeBtn.onmouseout = () => (closeBtn.style.backgroundColor = "#dc3545");

      modal.querySelector("#close-modal-btn").onclick = () => {
        modal.style.display = "none";
      };

      modal.querySelector("#process-session-btn").onclick = () => {
          alert("Séance traitée !");
          //localhost/blocks/dashboard/pages/validation.php/
         window.location.href = "/blocks/dashboard/pages/validation.php/";

        // Logique de traitement côté client ou appel API ici
      };
    },
  });

  calendar.render();
});
