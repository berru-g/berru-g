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
let startX, startY; // Pour les formes

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
  const { x, y } = getCursorPos(e);
  startX = x;
  startY = y;
  
  if (currentTool === "brush" || currentTool === "eraser") {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(x, y);
    draw(e);
  } else if (currentTool === "fill") {
    floodFill(x, y, colorPicker.value);
  } else {
    drawing = true;
  }
}

function stopDraw() {
  if (!drawing) return;
  
  if (currentTool === "rectangle") {
    drawRectangle();
  } else if (currentTool === "circle") {
    drawCircle();
  }
  
  drawing = false;
}

function draw(e) {
  if (!drawing) return;
  
  if (currentTool === "brush" || currentTool === "eraser") {
    const { x, y } = getCursorPos(e);

    ctx.lineWidth = brushSize;
    ctx.lineCap = "round";
    ctx.strokeStyle = currentTool === "eraser" ? "#fff" : colorPicker.value;

    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
  }
}

// Fonction pour dessiner un rectangle
function drawRectangle() {
  const { x, y } = getCursorPos(event);
  
  ctx.beginPath();
  ctx.lineWidth = brushSize;
  ctx.strokeStyle = colorPicker.value;
  ctx.fillStyle = colorPicker.value;
  
  const width = x - startX;
  const height = y - startY;
  
  ctx.rect(startX, startY, width, height);
  ctx.fill();
  ctx.stroke();
}

// Fonction pour dessiner un cercle
function drawCircle() {
  const { x, y } = getCursorPos(event);
  
  ctx.beginPath();
  ctx.lineWidth = brushSize;
  ctx.strokeStyle = colorPicker.value;
  ctx.fillStyle = colorPicker.value;
  
  const radius = Math.sqrt(Math.pow(x - startX, 2) + Math.pow(y - startY, 2));
  
  ctx.arc(startX, startY, radius, 0, Math.PI * 2);
  ctx.fill();
  ctx.stroke();
}

// Algorithme de remplissage (flood fill)
function floodFill(startX, startY, fillColor) {
  const pixelStack = [[startX, startY]];
  const canvasWidth = canvas.width;
  const canvasHeight = canvas.height;
  
  // Créer une image des pixels actuels
  const imageData = ctx.getImageData(0, 0, canvasWidth, canvasHeight);
  const pixels = imageData.data;
  
  // Convertir la couleur de départ en RGBA
  const startPos = (Math.floor(startY) * canvasWidth + Math.floor(startX)) * 4;
  const startR = pixels[startPos];
  const startG = pixels[startPos + 1];
  const startB = pixels[startPos + 2];
  const startA = pixels[startPos + 3];
  
  // Convertir la couleur de remplissage en RGBA
  const hexToRgb = (hex) => {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return [r, g, b, 255];
  };
  
  const fillRgba = hexToRgb(fillColor);
  
  // Si la couleur de départ est la même que la couleur de remplissage, on ne fait rien
  if (startR === fillRgba[0] && startG === fillRgba[1] && startB === fillRgba[2]) {
    return;
  }
  
  while (pixelStack.length) {
    const newPos = pixelStack.pop();
    const x = newPos[0];
    const y = newPos[1];
    
    // Obtenir la position actuelle dans le tableau de pixels
    const pixelPos = (Math.floor(y) * canvasWidth + Math.floor(x)) * 4;
    
    // Vérifier si on est dans les limites
    if (x < 0 || x >= canvasWidth || y < 0 || y >= canvasHeight) continue;
    
    // Vérifier si le pixel correspond à la couleur de départ
    const r = pixels[pixelPos];
    const g = pixels[pixelPos + 1];
    const b = pixels[pixelPos + 2];
    const a = pixels[pixelPos + 3];
    
    if (r === startR && g === startG && b === startB && a === startA) {
      // Définir la nouvelle couleur
      pixels[pixelPos] = fillRgba[0];
      pixels[pixelPos + 1] = fillRgba[1];
      pixels[pixelPos + 2] = fillRgba[2];
      pixels[pixelPos + 3] = fillRgba[3];
      
      // Ajouter les pixels voisins à la pile
      pixelStack.push([x + 1, y]);
      pixelStack.push([x - 1, y]);
      pixelStack.push([x, y + 1]);
      pixelStack.push([x, y - 1]);
    }
  }
  
  // Mettre à jour le canvas avec les nouveaux pixels
  ctx.putImageData(imageData, 0, 0);
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

