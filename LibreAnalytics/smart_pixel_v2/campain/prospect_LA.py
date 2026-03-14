import requests
from bs4 import BeautifulSoup
import time
import csv
import os

# Configuration
GOOGLE_SEARCH_URL = "https://www.google.com/search?q="
QUERIES = [
    'site:.fr intext:"UA-" OR "G-" OR "gtag.js" OR "Google Analytics"',  # France entière
    'site:.fr inurl:contact OR inurl:about intext:"UA-" OR "G-" OR "gtag.js"',  # Pages "Contact" ou "À propos"
    'site:.fr inurl:agence OR inurl:web OR inurl:dev intext:"Google Analytics"'  # Sites d'agences/webdev
]

# En-têtes pour imiter un navigateur
HEADERS = {
    "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
}

def search_google(query):
    """Effectue une recherche Google et retourne les URLs uniques."""
    try:
        response = requests.get(f"{GOOGLE_SEARCH_URL}{query}", headers=HEADERS, timeout=10)
        soup = BeautifulSoup(response.text, "html.parser")
        links = set()  # Évite les doublons
        for result in soup.find_all("a"):
            href = result.get("href")
            if href and "/url?q=" in href:
                url = href.split("/url?q=")[1].split("&")[0]
                if "http" in url and ".fr" in url:
                    clean_url = url.replace("https://", "").replace("http://", "").replace("www.", "").split("/")[0]
                    links.add(clean_url)
        return list(links)
    except Exception as e:
        print(f"⚠️ Erreur lors de la recherche Google : {e}")
        return []

def generate_email(domain):
    """Génère un email au format contact@domaine.fr."""
    return f"contact@{domain}"

def main():
    prospects = []
    for query in QUERIES:
        print(f"🔍 Recherche en cours pour : {query}")
        domains = search_google(query)
        if domains:
            for domain in domains:
                email = generate_email(domain)
                prospects.append({
                    "name": "Équipe Technique",
                    "email": email,
                    "site": f"https://{domain}"
                })
                print(f"✅ Trouvé : {domain} → {email}")
        else:
            print(f"❌ Aucune URL trouvée pour : {query}")
        time.sleep(3)  # Délai pour éviter les bans

    # Génère les fichiers uniquement si des prospects sont trouvés
    if prospects:
        with open("prospects.csv", "w", newline="", encoding="utf-8") as csvfile:
            fieldnames = ["Nom", "Email", "Site"]
            writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
            writer.writeheader()
            for prospect in prospects:
                writer.writerow({
                    "Nom": prospect["name"],
                    "Email": prospect["email"],
                    "Site": prospect["site"]
                })

        with open("prospects.php", "w", encoding="utf-8") as phpfile:
            phpfile.write("<?php\n$recipients = [\n")
            for prospect in prospects:
                phpfile.write(f'    ["name" => "{prospect["name"]}", "email" => "{prospect["email"]}"],\n')
            phpfile.write("];\n?>")

        print(f"✨ Terminé ! {len(prospects)} prospects générés.")
        print("Fichiers créés : prospects.csv et prospects.php")
    else:
        print("❌ Aucun prospect trouvé. Aucun fichier généré.")

if __name__ == "__main__":
    main()
