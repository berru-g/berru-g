const checkboxes = document.querySelectorAll('input[type="checkbox"]');
const percentText = document.getElementById('percent');
const progressPath = document.getElementById('progress-path');
const timeSpentElement = document.getElementById('time-spent');
const timeRemainingElement = document.getElementById('time-remaining');

function parseTime(timeStr) {
    if (timeStr.includes('h')) {
        return parseFloat(timeStr.replace('~', '').replace('h', '')) * 60;
    } else if (timeStr.includes('min')) {
        return parseFloat(timeStr.replace('~', '').replace('min', ''));
    }
    return 0;
}

function formatTime(minutes) {
    if (minutes >= 60) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        return `${hours}h${mins > 0 ? `${mins}min` : ''}`;
    }
    return `${minutes}min`;
}

function updateProgress() {
    const total = checkboxes.length;
    const checked = [...checkboxes].filter(cb => cb.checked).length;
    const percent = Math.round((checked / total) * 100);
    percentText.textContent = `${percent}%`;
    progressPath.setAttribute('stroke-dasharray', `${percent}, 100`);

    // Calcul du temps
    let totalTime = 0;
    let timeSpent = 0;
    let timeRemaining = 0;

    checkboxes.forEach(cb => {
        const timeElement = cb.parentElement.querySelector('.time');
        if (timeElement) {
            const timeStr = timeElement.textContent.trim();
            const minutes = parseTime(timeStr);
            totalTime += minutes;
            if (cb.checked) {
                timeSpent += minutes;
            }
        }
    });

    timeRemaining = totalTime - timeSpent;

    timeSpentElement.textContent = formatTime(timeSpent);
    timeRemainingElement.textContent = formatTime(timeRemaining);
}

checkboxes.forEach(cb => cb.addEventListener('change', updateProgress));
updateProgress(); // Init au chargement