function loadCache() {
            const cache = localStorage.getItem('tokenCache');
            return cache ? JSON.parse(cache) : {
                "btc": "bitcoin",
                "eth": "ethereum",
                "xrp": "ripple"
            };
        }

        // Fonction pour sauvegarder le cache dans localStorage
        function saveCache(cache) {
            localStorage.setItem('tokenCache', JSON.stringify(cache));
        }

        // Charger le cache initial
        const tokenCache = loadCache();

        document.getElementById('calculate').addEventListener('click', async () => {
            const userInput = document.getElementById('token').value.toLowerCase();
            const resultDiv = document.getElementById('result');

            try {
                let token = userInput;

                // Vérifier si le token existe déjà dans le cache
                if (tokenCache[token]) {
                    token = tokenCache[token];
                } else {
                    // Si non trouvé, faire une requête pour valider et récupérer le nom officiel
                    const response = await fetch(`https://api.coingecko.com/api/v3/coins/${token}`);
                    if (!response.ok) throw new Error('Token introuvable');
                    const data = await response.json();

                    // Ajouter le token au cache avec son nom officiel
                    tokenCache[userInput] = data.id;
                    saveCache(tokenCache); // Sauvegarder dans localStorage
                }

                // Faire la requête pour obtenir les informations du token
                const response = await fetch(`https://api.coingecko.com/api/v3/coins/${token}`);
                if (!response.ok) throw new Error('Token introuvable');

                const data = await response.json();
                const currentPrice = data.market_data.current_price.usd;
                const allTimeHigh = data.market_data.ath.usd;
                const allTimeLow = data.market_data.atl.usd;

                // Calcul du pourcentage
                const percentage = ((currentPrice - allTimeLow) / (allTimeHigh - allTimeLow)) * 100;
                let percentageClass = '';
                let percentageText = '';

                // Attribution de la couleur selon le pourcentage
                if (percentage > 75) {
                    percentageClass = 'red';
                    percentageText = '⚠️';
                } else if (percentage > 50) {
                    percentageClass = 'orange';
                    percentageText = 'DYOR';
                } else if (percentage > 25) {
                    percentageClass = 'blue';
                    percentageText = '🤷‍♂️';
                } else {
                    percentageClass = 'green';
                    percentageText = '🚀';
                }

                // Mise en forme du résultat
                resultDiv.innerHTML = `
            <p><strong>Token:</strong> ${data.name} (${data.symbol.toUpperCase()})</p>
            <p><strong>Prix actuel:</strong> $${currentPrice.toFixed(4)}</p>
            <p><strong>Plus bas historique:</strong> $${allTimeLow.toFixed(4)}</p>
            <p><strong>Plus haut historique:</strong> $${allTimeHigh.toFixed(4)}</p>
            <p><strong>${data.name} est à </strong> 
                <span class="percentage ${percentageClass}">
                    ${percentage.toFixed(2)}%
                </span>
                <strong> de sa capacité maximale</strong><br>
                <span> Investir = ${percentageText}</span>
            </p>
        `;
            } catch (error) {
                resultDiv.innerHTML = `<p style="color: red;">Erreur: ${error.message}</p>`;
            }
        });
