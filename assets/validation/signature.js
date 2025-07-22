document.addEventListener("DOMContentLoaded", function () {
  const canvas = document.getElementById("signature-pad");
  const ctx = canvas.getContext("2d");
  const signatureInput = document.getElementById("signature");
  const clearBtn = document.getElementById("clear-signature");

  // Ajuste la taille du canvas pour un bon rendu
  function resizeCanvas() {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    ctx.scale(ratio, ratio);
  }
  resizeCanvas();
  window.addEventListener("resize", resizeCanvas);

  let drawing = false;

  function startDrawing(e) {
    drawing = true;
    draw(e);
  }

  function stopDrawing() {
    drawing = false;
    ctx.beginPath();
    signatureInput.value = canvas.toDataURL();
  }

  function draw(e) {
    if (!drawing) return;

    e.preventDefault();

    let rect = canvas.getBoundingClientRect();
    let x, y;

    if (e.touches) {
      x = e.touches[0].clientX - rect.left;
      y = e.touches[0].clientY - rect.top;
    } else {
      x = e.clientX - rect.left;
      y = e.clientY - rect.top;
    }

    ctx.lineWidth = 2;
    ctx.lineCap = "round";
    ctx.strokeStyle = "#000";

    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
  }

  // Événements souris
  canvas.addEventListener("mousedown", startDrawing);
  canvas.addEventListener("mouseup", stopDrawing);
  canvas.addEventListener("mouseout", stopDrawing);
  canvas.addEventListener("mousemove", draw);

  // Événements tactiles
  canvas.addEventListener("touchstart", startDrawing);
  canvas.addEventListener("touchend", stopDrawing);
  canvas.addEventListener("touchcancel", stopDrawing);
  canvas.addEventListener("touchmove", draw);

  clearBtn.addEventListener("click", function () {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    signatureInput.value = "";
  });
});
