let factureCount = localStorage.getItem('factureCount') || 1;
const invoiceNumber = `2025-${String(factureCount).padStart(3, '0')}`;
document.getElementById('invoice-number').textContent = invoiceNumber;
document.getElementById('invoice-date').textContent = new Date().toLocaleDateString('fr-FR');

const form = document.getElementById('service-form');
const body = document.getElementById('invoice-body');
const totalDisplay = document.getElementById('invoice-total');

form.addEventListener('change', () => {
    body.innerHTML = '';
    let total = 0;
    document.querySelectorAll('.item:checked').forEach(item => {
        const label = item.getAttribute('data-label');
        const price = parseFloat(item.getAttribute('data-price'));
        total += price;
        body.innerHTML += `
          <tr>
            <td>${label}</td>
            <td>1</td>
            <td>${price.toFixed(2)} €</td>
            <td>${price.toFixed(2)} €</td>
          </tr>
        `;
    });
    totalDisplay.textContent = `${total.toFixed(2)} €`;
});

function downloadPDF() {
    localStorage.setItem('factureCount', parseInt(factureCount) + 1);

    const name = document.getElementById('client-name').value;
    const email = document.getElementById('client-email').value;
    const clientInfo = `${name}\nEmail : ${email}`;
    const clientPara = document.createElement('p');
    clientPara.innerText = clientInfo;
    document.querySelector('h2 + p').replaceWith(clientPara);

    setTimeout(() => {
        html2pdf().set({
            margin: 1,
            filename: `Facture_BerruDev_${invoiceNumber}.pdf`,
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        }).from(document.getElementById('invoice')).save();
    }, 300);
}
// gestion des variables checklist
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('service-form');
  const complexFormCheckbox = form.querySelector('input[data-label="Formulaire et bdd"]');
  const simpleFormCheckbox = form.querySelector('input[data-label="Formulaire simple"]');
  const hostingCheckbox = form.querySelector('input[data-label="hébergement"]');

  function handleFormInteractions() {
    // 1. Gestion de l'exclusivité entre les deux formulaires
    if (this === complexFormCheckbox && this.checked) {
      simpleFormCheckbox.checked = false;
    } else if (this === simpleFormCheckbox && this.checked) {
      complexFormCheckbox.checked = false;
    }

    // 2. Gestion de l'hébergement (logique existante)
    if (complexFormCheckbox.checked) {
      hostingCheckbox.checked = true;
      hostingCheckbox.disabled = true;
    } else if (simpleFormCheckbox.checked) {
      hostingCheckbox.checked = false;
      hostingCheckbox.disabled = true;
    } else {
      hostingCheckbox.disabled = false;
    }
  }

  // Écouteurs d'événements
  complexFormCheckbox.addEventListener('change', handleFormInteractions);
  simpleFormCheckbox.addEventListener('change', handleFormInteractions);
  
  // Initialisation au chargement
  handleFormInteractions.call({});
});

// Gestion de l'envoi du formulaire
document.getElementById('devisForm').addEventListener('submit', function(e) {
  e.preventDefault(); // Empêche l'envoi classique
  
  // Récupère toutes les données
  const devisData = {
    numero: document.getElementById('invoice-number').textContent,
    date: document.getElementById('invoice-date').textContent,
    client: {
      nom: document.getElementById('client-name').value,
      email: document.getElementById('client-email').value
    },
    services: Array.from(document.querySelectorAll('.item:checked')).map(item => ({
      label: item.dataset.label,
      price: item.dataset.price
    })),
    total: document.getElementById('invoice-total').textContent
  };

  // Envoi AJAX
  fetch('submit.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(devisData)
  })
  .then(response => response.text())
  .then(data => {
    alert("Devis envoyé avec succès !");
    console.log(data);
  })
  .catch(error => {
    console.error('Error:', error);
    alert("Une erreur est survenue lors de l'envoi.");
  });
});