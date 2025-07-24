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
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('numero', document.getElementById('invoice-number').textContent);
    formData.append('date', document.getElementById('invoice-date').textContent);
    formData.append('client_nom', document.getElementById('client-name').value);
    formData.append('client_email', document.getElementById('client-email').value);
    formData.append('total', document.getElementById('invoice-total').textContent);
    
    // Ajout systématique de toutes les checkboxes
    const checkboxes = {
        'site_vitrine': 'Développement site vitrine',
        'formulaire_simple': 'Formulaire simple',
        'formulaire_et_bdd': 'Formulaire et bdd',
        'optimisation_seo': 'Optimisation SEO',
        'systeme_de_paiement': 'Système de paiement',
        'interface_administrative': 'Interface administrative',
        'nom_de_domaine': 'Nom de domaine',
        'hebergement': 'Hébergement'
    };

    Object.keys(checkboxes).forEach(key => {
        const el = document.querySelector(`input[data-label="${checkboxes[key]}"]`);
        formData.append(key, el.checked ? '1' : '0');
    });

    fetch('submit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error('Erreur réseau');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert('Devis envoyé avec succès !');
            localStorage.setItem('factureCount', parseInt(factureCount) + 1);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        } else {
            throw new Error(data.error || 'Erreur inconnue');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Erreur: ' + error.message);
    });
});