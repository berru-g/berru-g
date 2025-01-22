// dark mode
const currentTheme = localStorage.getItem('theme') || 'dark';
document.body.classList.add(currentTheme + '-mode');

// Gestion du bouton de bascule
const themeToggleButton = document.createElement('button');
themeToggleButton.className = 'theme-toggle';
themeToggleButton.textContent = 'Light Mode';

// Ajout du bouton au document
document.body.appendChild(themeToggleButton);

themeToggleButton.addEventListener('click', () => {
    if (document.body.classList.contains('dark-mode')) {
        document.body.classList.replace('dark-mode', 'light-mode');
        themeToggleButton.textContent = 'Dark Mode';
        localStorage.setItem('theme', 'light');
    } else {
        document.body.classList.replace('light-mode', 'dark-mode');
        themeToggleButton.textContent = 'Light Mode';
        localStorage.setItem('theme', 'dark');
    }
});

// gestion de l'api coingecko
// Fonction pour charger le cache depuis localStorage
function loadCache() {
    const cache = localStorage.getItem('tokenCache');
    return cache ? JSON.parse(cache) : {
        "btc": "bitcoin",
        "eth": "ethereum",
        "xrp": "ripple",
        "usdt": "tether",
        "sol": "solana",
        "bnb": "binancecoin",
        "doge": "dogecoin",
        "usdc": "usd-coin",
        "ada": "cardano",
        "trx": "tron",
        "dot": "polkadot",
        "matic": "polygon",
        "avax": "avalanche",
        "shib": "shiba-inu",
        "ltc": "litecoin",
        "wbtc": "wrapped-bitcoin",
        "dai": "dai",
        "uni": "uniswap",
        "leo": "leo-token",
        "atom": "cosmos",
        "link": "chainlink",
        "etc": "ethereum-classic",
        "xlm": "stellar",
        "bch": "bitcoin-cash",
        "algo": "algorand",
        "qnt": "quant-network",
        "near": "near",
        "cro": "crypto-com-chain",
        "vet": "vechain",
        "icp": "internet-computer",
        "hbar": "hedera-hashgraph",
        "fil": "filecoin",
        "apt": "aptos",
        "lunc": "terra-luna",
        "egld": "elrond",
        "ftm": "fantom",
        "sand": "the-sandbox",
        "axs": "axie-infinity",
        "mana": "decentraland",
        "chz": "chiliz",
        "xmr": "monero",
        "okb": "okb",
        "bsv": "bitcoin-sv",
        "theta": "theta-token",
        "eos": "eos",
        "flow": "flow",
        "aave": "aave",
        "frax": "frax",
        "klay": "klaytn",
        "xtz": "tezos",
        "rune": "thorchain",
        "zec": "zcash",
        "snx": "synthetix-network-token",
        "neo": "neo",
        "gala": "gala",
        "crv": "curve-dao-token",
        "kcs": "kucoin-shares",
        "miota": "iota",
        "ht": "huobi-token",
        "usdp": "paxos-standard",
        "btt": "bittorrent",
        "lrc": "loopring",
        "comp": "compound-governance-token",
        "one": "harmony",
        "enj": "enjincoin",
        "bat": "basic-attention-token",
        "mkr": "maker",
        "xem": "nem",
        "dcr": "decred",
        "waves": "waves",
        "tusd": "true-usd",
        "cdai": "cdai",
        "cusdc": "cusdc",
        "ceth": "ceth",
        "cusdt": "cusdt",
        "ftt": "ftx-token",
        "ksm": "kusama",
        "yfi": "yearn-finance",
        "btg": "bitcoin-gold",
        "omg": "omisego",
        "zrx": "0x",
        "ont": "ontology",
        "nano": "nano",
        "sc": "siacoin",
        "icx": "icon",
        "qtum": "qtum",
        "bnt": "bancor",
        "zen": "horizen",
        "sushi": "sushi",
        "dgb": "digibyte",
        "uma": "uma",
        "rev": "revain",
        "hive": "hive",
        "iotx": "iotex",
        "fet": "fetch-ai",
        "cel": "celsius",
        "srm": "serum",
        "rsv": "reserve",
        "ogn": "origin-protocol",
        "ankr": "ankr",
        "storj": "storj",
        "ocean": "ocean-protocol",
        "grt": "the-graph",
        "bal": "balancer",
        "band": "band-protocol",
        "akash": "akash-network",
        "rsr": "reserve-rights-token"
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

        // **Ajout : Calcul du Ratio Supply/Max Supply**
        const circulatingSupply = data.market_data.circulating_supply || 0; // Vérifier s'il est disponible
        const maxSupply = data.market_data.max_supply || 1; // Éviter la division par zéro
        const supplyRatio = maxSupply > 0 ? (circulatingSupply / maxSupply) * 100 : 0;

        // **Ajout : Récupération du Rang**
        const marketRank = data.market_cap_rank || 'Non classé';
        const priceChange24h = data.market_data.price_change_percentage_24h.toFixed(2);
        const priceChange7d = data.market_data.price_change_percentage_7d.toFixed(2);

        // Mise en forme du résultat
        resultDiv.innerHTML = `
                <p style="display: flex; justify-content: space-between; align-items: center;">
        <span><strong>Token:</strong> ${data.name} (${data.symbol.toUpperCase()})</span>
        <img src="${data.image.small}" alt="${data.name} logo" style="width: 50px; height: 50px; margin-left: 10px;"></p>
        <p><strong>Prix actuel:</strong> $${currentPrice.toFixed(4)}</p>
        <p><strong>Plus bas historique:</strong> $${allTimeLow.toFixed(4)}</p>
        <p><strong>Plus haut historique:</strong> $${allTimeHigh.toFixed(4)}</p>
        <p><strong>${data.name} est à </strong> 
            <span class="percentage ${percentageClass}">
                ${percentage.toFixed(2)}%
            </span>
            <strong> de sa capacité maximale</strong><br>
        </p>
        <p><strong>Rang :</strong> ${marketRank}</p>
        <p><strong>Tokens en circulation :</strong> ${supplyRatio.toFixed(2)}%</p>
        
        <p><strong>Sur 24h :</strong> ${priceChange24h}%</p>
        <p><strong>Sur 7 jours :</strong> ${priceChange7d}%</p>
        <span> Investir = ${percentageText}</span>

        <p><a href="https://www.coingecko.com/en/coins/${data.id}" target="_blank">Voir plus sur CoinGecko</a></p>
        

    `;
    } catch (error) {
        resultDiv.innerHTML = `<p style="color: red;">Erreur: ${error.message}</p>`;
    }
});

//share btn
document.addEventListener("DOMContentLoaded", () => {
    const shareButton = document.getElementById("shareButton");
    const sharePopup = document.getElementById("sharePopup");
    const currentURL = window.location.href;

    // Configurer les liens de partage
    const twitterShare = document.getElementById("twitterShare");
    twitterShare.href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentURL)}&text=${encodeURIComponent("Tool magic Fibonacci Retracement !")}`;

    const whatsappShare = document.getElementById("whatsappShare");
    whatsappShare.href = `https://api.whatsapp.com/send?text=${encodeURIComponent("Tool magic Fibonacci Retracement  : " + currentURL)}`;

    const facebookShare = document.getElementById("facebookShare");
    facebookShare.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentURL)}`;

    /*const mediumShare = document.getElementById("mediumShare");
    mediumShare.href = `https://medium.com/new-story?source=${encodeURIComponent(currentURL)}`;*/


    // Afficher ou masquer le popup
    shareButton.addEventListener("click", () => {
        const isVisible = sharePopup.style.display === "flex";
        sharePopup.style.display = isVisible ? "none" : "flex";
    });

    // Copier le lien dans le presse-papier
    window.copyToClipboard = () => {
        navigator.clipboard.writeText(currentURL).then(() => {
            alert("Lien copié dans le presse-papier !");
        }).catch(err => {
            console.error("Échec de la copie du lien : ", err);
        });
    };

    // Cacher le popup quand on clique en dehors
    document.addEventListener("click", (e) => {
        if (!shareButton.contains(e.target) && !sharePopup.contains(e.target)) {
            sharePopup.style.display = "none";
        }
    });
});
