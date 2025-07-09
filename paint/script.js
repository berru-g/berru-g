const history = [];
let historyIndex = -1;

function saveState() {
  historyIndex++;
  if (historyIndex < history.length) history.length = historyIndex;
  history.push(canvas.toDataURL());
  if (history.length > 50) history.shift();
}

function undo() {
  if (historyIndex <= 0) return;
  historyIndex--;
  restoreCanvas();
}

function redo() {
  if (historyIndex >= history.length - 1) return;
  historyIndex++;
  restoreCanvas();
}

function restoreCanvas() {
  const img = new Image();
  img.onload = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.drawImage(img, 0, 0);
  };
  img.src = history[historyIndex];
}

const canvas = document.getElementById("paintCanvas");
const ctx = canvas.getContext("2d");

// Canvas full screen adaptatif
function resizeCanvas() {
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;
  saveState(); // Sauvegarder après redimensionnement
}
window.addEventListener("resize", resizeCanvas);
resizeCanvas();


// Tools
let currentTool = "brush";
let drawing = false;
let startX, startY;
let textInputActive = false;

const colorPicker = document.getElementById("colorPicker");
const clearBtn = document.getElementById("clearBtn");
const saveBtn = document.getElementById("saveBtn");
const undoBtn = document.getElementById("undoBtn");
const redoBtn = document.getElementById("redoBtn");

// Création dynamique de l'input texte si non présent dans le HTML
let textInput;
if (!document.getElementById("textInput")) {
  textInput = document.createElement("input");
  textInput.id = "textInput";
  textInput.type = "text";
  textInput.style.position = "absolute";
  textInput.style.display = "none";
  textInput.style.border = "1px dashed #000";
  textInput.style.padding = "5px";
  textInput.style.fontFamily = "Arial";
  textInput.style.fontSize = "20px";
  document.body.appendChild(textInput);
} else {
  textInput = document.getElementById("textInput");
}

document.querySelectorAll(".toolbar button[data-tool]").forEach(btn => {
  btn.addEventListener("click", () => {
    currentTool = btn.getAttribute("data-tool");
    if (currentTool === "text") {
      activateTextTool();
    } else if (textInputActive) {
      deactivateTextTool();
    }
  });
});

function activateTextTool() {
  textInputActive = true;
  canvas.style.cursor = "text";
}

function deactivateTextTool() {
  textInputActive = false;
  textInput.style.display = "none";
  canvas.style.cursor = "default";
}

// Curseur précis sans décalage
function getCursorPos(e) {
  const rect = canvas.getBoundingClientRect();
  const clientX = e.touches ? e.touches[0].clientX : e.clientX;
  const clientY = e.touches ? e.touches[0].clientY : e.clientY;
  return {
    x: clientX - rect.left,
    y: clientY - rect.top
  };
}

// Dessin
function startDraw(e) {
  if (textInputActive) {
    handleTextInput(e);
    return;
  }

  const { x, y } = getCursorPos(e);
  startX = x;
  startY = y;

  if (currentTool === "brush" || currentTool === "eraser") {
    drawing = true;
    ctx.beginPath();
    ctx.moveTo(x, y);
    draw(e);
    saveState();
  } else if (currentTool === "fill") {
    floodFill(x, y, colorPicker.value);
    saveState();
  } else {
    drawing = true;
  }
}

function handleTextInput(e) {
  const { x, y } = getCursorPos(e);

  textInput.style.display = "block";
  textInput.style.left = `${x + window.scrollX}px`;
  textInput.style.top = `${y + window.scrollY}px`;
  textInput.style.color = colorPicker.value;
  textInput.focus();

  textInput.onkeydown = (evt) => {
    if (evt.key === "Enter") {
      ctx.font = `${brushSize * 4}px Arial`;
      ctx.fillStyle = colorPicker.value;
      ctx.fillText(textInput.value, x, y);
      saveState();
      textInput.value = "";
      deactivateTextTool();
    } else if (evt.key === "Escape") {
      textInput.value = "";
      deactivateTextTool();
    }
  };
}

function stopDraw() {
  if (!drawing || textInputActive) return;

  if (currentTool === "rectangle") {
    drawRectangle();
    saveState();
  } else if (currentTool === "circle") {
    drawCircle();
    saveState();
  }

  drawing = false;
}

function draw(e) {
  if (!drawing || textInputActive) return;

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

// Algorithme de remplissage (flood fill) - gardez votre implémentation existante
function floodFill(startX, startY, fillColor) {
  // Sauvegarde avant modification
  const imageDataBefore = ctx.getImageData(0, 0, canvas.width, canvas.height);

  const pixelStack = [[Math.floor(startX), Math.floor(startY)]];
  const canvasWidth = canvas.width;
  const canvasHeight = canvas.height;
  const imageData = ctx.getImageData(0, 0, canvasWidth, canvasHeight);
  const pixels = imageData.data;

  // Couleur de départ (celle qu'on remplace)
  const startPos = (Math.floor(startY) * canvasWidth + Math.floor(startX)) * 4;
  const startR = pixels[startPos];
  const startG = pixels[startPos + 1];
  const startB = pixels[startPos + 2];
  const startA = pixels[startPos + 3];

  // Conversion hex to RGB
  const hexToRgb = (hex) => {
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return [r, g, b, 255];
  };

  const fillRgba = hexToRgb(fillColor);

  // Si la couleur cible est identique à la couleur de départ
  if (startR === fillRgba[0] && startG === fillRgba[1] && startB === fillRgba[2]) {
    return;
  }

  // Algorithme de remplissage
  while (pixelStack.length) {
    const newPos = pixelStack.pop();
    const x = newPos[0];
    const y = newPos[1];
    const pixelPos = (y * canvasWidth + x) * 4;

    // Vérification des limites
    if (x < 0 || x >= canvasWidth || y < 0 || y >= canvasHeight) continue;

    // Vérification de la correspondance de couleur
    const r = pixels[pixelPos];
    const g = pixels[pixelPos + 1];
    const b = pixels[pixelPos + 2];
    const a = pixels[pixelPos + 3];

    if (r === startR && g === startG && b === startB && a === startA) {
      // Modification du pixel
      pixels[pixelPos] = fillRgba[0];
      pixels[pixelPos + 1] = fillRgba[1];
      pixels[pixelPos + 2] = fillRgba[2];
      pixels[pixelPos + 3] = fillRgba[3];

      // Propagation aux pixels voisins
      pixelStack.push([x + 1, y]);
      pixelStack.push([x - 1, y]);
      pixelStack.push([x, y + 1]);
      pixelStack.push([x, y - 1]);
    }
  }

  // Application des modifications
  ctx.putImageData(imageData, 0, 0);
  saveState(); // Sauvegarde APRÈS la modification
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
clearBtn.addEventListener("click", () => {
  saveState();
  ctx.clearRect(0, 0, canvas.width, canvas.height);
});

saveBtn.addEventListener("click", () => {
  const link = document.createElement("a");
  link.download = "dessin.png";
  link.href = canvas.toDataURL();
  link.click();
});

// Undo/Redo
undoBtn.addEventListener("click", undo);
redoBtn.addEventListener("click", redo);

// Gestion de l'épaisseur
const sizeBtn = document.getElementById("sizeBtn");
const brushSizeSlider = document.getElementById("brushSize");

let brushSize = parseInt(brushSizeSlider.value);

sizeBtn.addEventListener("click", () => {
  const visible = brushSizeSlider.style.display === "block";
  brushSizeSlider.style.display = visible ? "none" : "block";
});

brushSizeSlider.addEventListener("input", (e) => {
  brushSize = parseInt(e.target.value);
});

// Sauvegarde initiale
saveState();


// Gestion de la toolbar mobile
const toolbarContainer = document.querySelector('.toolbar-container');
const toolbarHandle = document.querySelector('.toolbar-handle');

let isToolbarExpanded = false;

function toggleToolbar() {
  isToolbarExpanded = !isToolbarExpanded;
  toolbarContainer.classList.toggle('expanded', isToolbarExpanded);

  // Fermer automatiquement après sélection d'un outil
  if (isToolbarExpanded) {
    document.querySelectorAll('.toolbar button').forEach(btn => {
      btn.addEventListener('click', () => {
        setTimeout(() => {
          toolbarContainer.classList.remove('expanded');
          isToolbarExpanded = false;
        }, 300);
      }, { once: true }); // Ne s'exécute qu'une fois
    });
  }
}

toolbarHandle.addEventListener('click', toggleToolbar);

// Fermer la toolbar quand on commence à dessiner
canvas.addEventListener('mousedown', () => {
  if (isToolbarExpanded && window.innerWidth <= 768) {
    toolbarContainer.classList.remove('expanded');
    isToolbarExpanded = false;
  }
});

canvas.addEventListener('touchstart', () => {
  if (isToolbarExpanded && window.innerWidth <= 768) {
    toolbarContainer.classList.remove('expanded');
    isToolbarExpanded = false;
  }
});