# Mistral est comme les autres IA, complétement teubé, faut tout repeter sans cesse donc on recode !
# Scrape des sites français utilisant Google Analytics via DuckDuckGo.
# Inspiré de : https://github.com/berru-g/OTTO/blob/main/scrap/PainScraper/scrap-sub-reddit-search-problem.py

import requests
from bs4 import BeautifulSoup
import re
import time
import random
from urllib.parse import urlparse, parse_qs

# --- User Agents aléatoires ---
USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36",
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
]

# --- Requêtes de recherche (ciblage France/Loire-Atlantique) ---
QUERIES = [
    "agence web france site:.fr intext:\"Google Analytics\"",
    "développeur freelance france site:.fr intext:\"UA-\"",
    "PME Loire-Atlantique site:.fr intext:\"gtag.js\"",
    "boutique en ligne france site:.fr intext:\"Google Analytics\" intext:\"RGPD\"",
    "entreprise nantes site:.fr intext:\"Google Analytics\" intext:\"confidentialité\""
]

# --- Fonction pour scraper DuckDuckGo ---
def scrape_duckduckgo(query):
    """Scrape les résultats de recherche DuckDuckGo."""
    url = f"https://html.duckduckgo.com/html/"
    params = {
        "q": query,
        "kl": "fr-fr"  # Résultats en français
    }
    headers = {"User-Agent": random.choice(USER_AGENTS)}

    try:
        response = requests.post(url, data=params, headers=headers, timeout=10)
        soup = BeautifulSoup(response.text, "html.parser")
        results = []

        for result in soup.select(".result"):
            link = result.select_one(".result__url")["href"]
            title = result.select_one(".result__title a").get_text()
            results.append({"title": title, "url": link})

        return results
    except Exception as e:
        print(f"⚠️ Erreur DuckDuckGo pour '{query}': {str(e)[:50]}...")
        return []

# --- Fonction pour vérifier GA + RGPD sur un site ---
def check_site(url):
    """Vérifie si un site utilise GA et mentionne le RGPD."""
    try:
        headers = {"User-Agent": random.choice(USER_AGENTS)}
        response = requests.get(url, headers=headers, timeout=10)
        html = response.text

        # Vérifie GA (gtag.js, UA-, G-)
        has_ga = bool(re.search(r'gtag\.js|google-analytics\.com|UA-\d+-\d+|G-[A-Z0-9]+', html))

        # Vérifie RGPD
        has_rgpd = bool(re.search(r'RGPD|règlement général sur la protection des données|politique de confidentialité|cookies', html, re.IGNORECASE))

        # Extraire l'email
        email = None
        emails = re.findall(r'[\w\.-]+@[\w\.-]+\.\w+', html)
        if emails:
            domain = urlparse(url).netloc
            for e in emails:
                if domain in e.lower():
                    email = e
                    break

        return {
            "url": url,
            "has_ga": has_ga,
            "has_rgpd": has_rgpd,
            "email": email,
            "title": BeautifulSoup(html, "html.parser").title.string if BeautifulSoup(html, "html.parser").title else url
        }
    except Exception as e:
        print(f"⚠️ Erreur sur {url}: {str(e)[:50]}...")
        return None

# --- Fonction principale ---
def main():
    all_results = []
    for query in QUERIES:
        print(f"\n🔍 Recherche: {query}")
        results = scrape_duckduckgo(query)
        for result in results:
            url = result["url"]
            print(f"  - Analyse de {url}...")
            data = check_site(url)
            if data and data["has_ga"] and data["has_rgpd"]:
                data["sector"] = (
                    "Agence Web" if "agence" in query.lower() else
                    "Développeur Freelance" if "freelance" in query.lower() else
                    "PME E-commerce"
                )
                all_results.append(data)
            time.sleep(random.uniform(1, 3))  # Évite les blocages

    # --- Affichage des résultats ---
    if all_results:
        print(f"\n🎉 {len(all_results)} sites qualifiés trouvés :")
        with open("ga_users.txt", "w", encoding="utf-8") as f:
            f.write("=== SITES UTILISANT GA + RGPD (France/Loire-Atlantique) ===\n\n")
            for i, result in enumerate(all_results, 1):
                print(f"{i}. {result['title']}")
                print(f"   🌐 {result['url']}")
                print(f"   📧 {result['email'] or 'Non trouvé'}")
                print(f"   🏷️ {result['sector']}\n")

                f.write(f"{i}. {result['title']}\n")
                f.write(f"   Site: {result['url']}\n")
                f.write(f"   Email: {result['email'] or 'Non trouvé'}\n")
                f.write(f"   Secteur: {result['sector']}\n\n")
        print(f"\n📄 Résultats sauvegardés dans 'ga_users.txt'.")
    else:
        print("\n⚠️ Aucun site qualifié trouvé.")

if __name__ == "__main__":
    main()
