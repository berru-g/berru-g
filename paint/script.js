const canvas = document.getElementById('paintCanvas');
const ctx = canvas.getContext('2d');

let drawing = false;
let startX = 0;
let startY = 0;

const tool = document.getElementById('tool');
const colorPicker = document.getElementById('colorPicker');
const brushSize = document.getElementById('brushSize');
const clearBtn = document.getElementById('clearBtn');
const saveBtn = document.getElementById('saveBtn');

let currentTool = 'brush';

tool.addEventListener('change', () => {
  currentTool = tool.value;
});

canvas.addEventListener('mousedown', (e) => {
  drawing = true;
  startX = e.offsetX;
  startY = e.offsetY;

  if (currentTool === 'brush' || currentTool === 'eraser') {
    draw(e);
  }
});

canvas.addEventListener('mouseup', (e) => {
  if (!drawing) return;
  drawing = false;

  if (['line', 'rect', 'circle'].includes(currentTool)) {
    drawShape(e.offsetX, e.offsetY);
  }

  ctx.beginPath();
});

canvas.addEventListener('mousemove', draw);

function draw(e) {
  if (!drawing || currentTool !== 'brush' && currentTool !== 'eraser') return;

  ctx.lineWidth = brushSize.value;
  ctx.lineCap = 'round';
  ctx.strokeStyle = currentTool === 'eraser' ? '#fff' : colorPicker.value;

  ctx.lineTo(e.offsetX, e.offsetY);
  ctx.stroke();
  ctx.beginPath();
  ctx.moveTo(e.offsetX, e.offsetY);
}

function drawShape(endX, endY) {
  ctx.lineWidth = brushSize.value;
  ctx.strokeStyle = colorPicker.value;
  ctx.fillStyle = colorPicker.value;

  switch (currentTool) {
    case 'line':
      ctx.beginPath();
      ctx.moveTo(startX, startY);
      ctx.lineTo(endX, endY);
      ctx.stroke();
      break;

    case 'rect':
      ctx.strokeRect(startX, startY, endX - startX, endY - startY);
      break;

    case 'circle':
      const radius = Math.sqrt(Math.pow(endX - startX, 2) + Math.pow(endY - startY, 2));
      ctx.beginPath();
      ctx.arc(startX, startY, radius, 0, 2 * Math.PI);
      ctx.stroke();
      break;
  }
}

clearBtn.addEventListener('click', () => {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
});

saveBtn.addEventListener('click', () => {
  const link = document.createElement('a');
  link.download = 'dessin.png';
  link.href = canvas.toDataURL();
  link.click();
});


// Ajout des variables existantes comme avant...

canvas.addEventListener('mousedown', startDraw);
canvas.addEventListener('mouseup', stopDraw);
canvas.addEventListener('mousemove', draw);

canvas.addEventListener('touchstart', (e) => startDraw(e.touches[0]));
canvas.addEventListener('touchend', stopDraw);
canvas.addEventListener('touchmove', (e) => {
  draw(e.touches[0]);
  e.preventDefault(); // empêche le scroll
}, { passive: false });

function startDraw(e) {
  drawing = true;
  startX = e.offsetX || (e.clientX - canvas.getBoundingClientRect().left);
  startY = e.offsetY || (e.clientY - canvas.getBoundingClientRect().top);

  if (currentTool === 'brush' || currentTool === 'eraser') {
    draw(e);
  }
}

function stopDraw() {
  if (!drawing) return;
  drawing = false;
  ctx.beginPath();
}

function draw(e) {
  if (!drawing) return;

  const x = e.offsetX || (e.clientX - canvas.getBoundingClientRect().left);
  const y = e.offsetY || (e.clientY - canvas.getBoundingClientRect().top);

  if (['brush', 'eraser'].includes(currentTool)) {
    ctx.lineWidth = brushSize.value;
    ctx.lineCap = 'round';
    ctx.strokeStyle = currentTool === 'eraser' ? '#fff' : colorPicker.value;

    ctx.lineTo(x, y);
    ctx.stroke();
    ctx.beginPath();
    ctx.moveTo(x, y);
  } else if (['line', 'rect', 'circle'].includes(currentTool)) {
    endX = x;
    endY = y;
  }
}
