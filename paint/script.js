const canvas = document.getElementById("paintCanvas");
const ctx = canvas.getContext("2d");

// Canvas full screen adaptatif
function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
}
window.addEventListener("resize", resizeCanvas);
resizeCanvas();

// Tools
let currentTool = "brush";
let drawing = false;

const colorPicker = document.getElementById("colorPicker");
const clearBtn = document.getElementById("clearBtn");
const saveBtn = document.getElementById("saveBtn");

document.querySelectorAll(".toolbar button[data-tool]").forEach(btn => {
  btn.addEventListener("click", () => {
    currentTool = btn.getAttribute("data-tool");
  });
});

// Curseur précis sans décalage
function getCursorPos(e) {
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  return { x: clientX, y: clientY };
}

// Dessin
function startDraw(e) {
  drawing = true;
  const { x, y } = getCursorPos(e);
  ctx.beginPath();
  ctx.moveTo(x, y);
  draw(e);
}

function stopDraw() {
  drawing = false;
  ctx.beginPath();
}

function draw(e) {
  if (!drawing) return;
  const { x, y } = getCursorPos(e);

  ctx.lineWidth = brushSize;
  ctx.lineCap = "round";
  ctx.strokeStyle = currentTool === "eraser" ? "#fff" : colorPicker.value;

  ctx.lineTo(x, y);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(x, y);
}

// Events
canvas.addEventListener("mousedown", startDraw);
canvas.addEventListener("touchstart", startDraw);

canvas.addEventListener("mousemove", draw);
canvas.addEventListener("touchmove", (e) => {
  e.preventDefault();
  draw(e);
}, { passive: false });

canvas.addEventListener("mouseup", stopDraw);
canvas.addEventListener("touchend", stopDraw);

// Clear + Save
clearBtn.addEventListener("click", () => ctx.clearRect(0, 0, canvas.width, canvas.height));

saveBtn.addEventListener("click", () => {
  const link = document.createElement("a");
  link.download = "dessin.png";
  link.href = canvas.toDataURL();
  link.click();
});

// epaisseur .➡️
const sizeBtn = document.getElementById("sizeBtn");
const brushSizeSlider = document.getElementById("brushSize");

let brushSize = parseInt(brushSizeSlider.value);

// Afficher/Masquer la slider
sizeBtn.addEventListener("click", () => {
  const visible = brushSizeSlider.style.display === "block";
  brushSizeSlider.style.display = visible ? "none" : "block";
});

// Modifier la taille du pinceau en direct
brushSizeSlider.addEventListener("input", (e) => {
  brushSize = parseInt(e.target.value);
});

