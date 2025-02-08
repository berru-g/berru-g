fetch('https://api.coingecko.com/api/v3/simple/price?ids=solana,bittensor,injective-protocol,reserve-rights-token,near,mysterium,ethereum,optimism,apu-s-club,render-token&vs_currencies=usd&include_24hr_change=true')

  .then(response => response.json())
  .then(data => {
    const container = document.getElementById('crypto-prices');

     const tokenHoldings = {
      solana: 1.72,
      bittensor: 1,
      'injective-protocol': 6,
      'reserve-rights-token': 42007,
      near: 98,
      mysterium: 5,
      ethereum: 0.0001,
      //'apu-s-club':1000000,
       optimism: 0.18,
       'render-token': 32.61,
    };
  

    let totalPortfolioValue = 0; // Initialisation du total

    for (const crypto in data) {
      const price = data[crypto].usd;
      const change24h = data[crypto].usd_24h_change.toFixed(2); // Variation en pourcentage
      const holdings = tokenHoldings[crypto] || 0; // Montant détenu (0 si absent)
      const totalValue = (price * holdings).toFixed(2); // Valeur totale de ce token
      totalPortfolioValue += parseFloat(totalValue); // Ajout au total du portefeuille

      let cryptoElement = document.querySelector(`#${crypto}`);

      // Si l'élément n'existe pas encore, on le crée
      if (!cryptoElement) {
        cryptoElement = document.createElement('div');
        cryptoElement.id = crypto;
        cryptoElement.classList.add('crypto-item');
        cryptoElement.innerHTML = `
          <h2><a href="https://www.coingecko.com/en/coins/${crypto}" rel="noopener" target="_blank">${crypto.charAt(0).toUpperCase() + crypto.slice(1).replace(/-/g, ' ')}</a></h2>  
          <!--<p class="holdings" style="color:grey;">${holdings}</p>-->
          <p class="price">$${price}</p>
          <p class="holdings" style="color:grey;">$${totalValue}</p>
          <p class="change">${change24h}%</p>
        `;
        container.appendChild(cryptoElement);
      } else {
        // Si l'élément existe déjà, mettez à jour les informations avec animation
        const currentPrice = parseFloat(cryptoElement.querySelector('.price').textContent.replace(' $', ''));
        const currentChange = parseFloat(cryptoElement.querySelector('.change').textContent.replace('24h : ', '').replace('%', ''));
        const newTotalValue = (price * holdings).toFixed(2);
        

        if (currentPrice !== price) {
          cryptoElement.querySelector('.price').textContent = `$${price}`;
          cryptoElement.querySelector('.holdings').textContent = `Montant détenu : ${holdings} (Valeur totale : $${newTotalValue})`;
          cryptoElement.classList.add('updated');
          setTimeout(() => cryptoElement.classList.remove('updated'), 1000);
        }

        if (currentChange !== change24h) {
          cryptoElement.querySelector('.change').textContent = `24h : ${change24h}%`;
        }
      }

      // Changer la couleur selon le changement sur 24h
      const changeElement = cryptoElement.querySelector('.change');
      if (change24h < 0) {
        changeElement.style.color = '#0ebeff';
      } else {
        changeElement.style.color = '#ab9ff2';
      }
    }

    // Afficher le total du portefeuille
    let totalElement = document.querySelector('#portfolio-total');
    if (!totalElement) {
      totalElement = document.createElement('div');
      totalElement.id = 'portfolio-total';
      totalElement.classList.add('portfolio-total');
      container.appendChild(totalElement);
    }
    
    totalElement.innerHTML = `<h3 id="total">${totalPortfolioValue.toFixed(2) } 💵</h3>`;
  
  })
  .catch(error => {
    console.error('Erreur lors de la récupération des données:', error);

  });

const hamburgerMenu = document.querySelector('.hamburger-menu');

hamburgerMenu.addEventListener('click', () => {
  // Utilisation de SweetAlert pour afficher la fenêtre contextuelle
  Swal.fire({
    title: 'All my wallets',
    html: '<ul><li><a href="https://shop.ledger.com/?r=">Ledger</a></li><li><a href="https://accounts.binance.com/register?ref=">Binance</a></li><li><a href="#">Phantom</a></li><li><a href="https://solscan.io/account/D6khWoqvc2zX46HVtSZcNrPumnPLPM72SnSuDhBrZeTC#portfolio">Solscan.io</a></li><li><a href="https://pump.fun/profile/D6khWo">Pump.fun</a></li><li><a href="https://jup.ag">jup.ag</a></li></ul>',
    showCloseButton: true,
    showConfirmButton: false,
    customClass: {
      popup: 'custom-swal-popup',
      closeButton: 'custom-swal-close-button',
      content: 'custom-swal-content',
    }
  });
});

document.addEventListener("DOMContentLoaded", function () {
  const toggleButton = document.getElementById("toggle-dark-mode");
  const body = document.body;

  // Vérifier la préférence stockée
  if (localStorage.getItem("theme") === "dark") {
    body.classList.add("dark-mode");
  } else {
    body.classList.add("light-mode");
  }

  toggleButton.addEventListener("click", function () {
    if (body.classList.contains("dark-mode")) {
      body.classList.replace("dark-mode", "light-mode");
      localStorage.setItem("theme", "light");
    } else {
      body.classList.replace("light-mode", "dark-mode");
      localStorage.setItem("theme", "dark");
    }
  });
});