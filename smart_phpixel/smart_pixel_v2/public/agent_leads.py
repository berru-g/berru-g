#!/usr/bin/env python3
# admin/agent_leads.py
import os
import sys
import requests
from bs4 import BeautifulSoup
import re
import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import pymysql
import time
from datetime import datetime

# Charger la configuration depuis config.php
sys.path.append(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from includes.config import * #pas de paquet path pas de paquet path

# --- Fonctions ---
def search_google(query):
    """Recherche gratuite via des requêtes HTTP basiques (sans API payante)."""
    # Utilise des moteurs de recherche alternatifs gratuits ou des requêtes directes
    # Ici, on simule avec des résultats statiques pour éviter les blocages
    mock_results = {
        'site:.fr intext:"Google Analytics" intext:"RGPD"': [
            {"url": "https://agence-web-nantes.fr", "title": "Agence Web Créative - Nantes"},
            {"url": "https://boutique-bio-loire.fr", "title": "Boutique Bio Loire-Atlantique"}
        ],
        'site:.fr intext:"UA-" intext:"protection des données"': [
            {"url": "https://dev-freelance-nantes.fr", "title": "Développeur Freelance Nantes"}
        ]
    }
    return mock_results.get(query, [])

def check_ga_and_rgpd(url):
    """Vérifie si un site utilise GA et mentionne le RGPD."""
    try:
        headers = {"User-Agent": "Mozilla/5.0"}
        response = requests.get(url, headers=headers, timeout=10)
        html = response.text

        # Vérifie la présence de Google Analytics
        has_ga = bool(re.search(r'gtag\.js|google-analytics\.com|UA-\d+-\d+|G-[A-Z0-9]+', html))

        # Vérifie la mention du RGPD
        has_rgpd = bool(re.search(r'RGPD|règlement général sur la protection des données|politique de confidentialité|cookies', html, re.IGNORECASE))

        # Extraire l'email
        email = None
        email_pattern = r'[\w\.-]+@[\w\.-]+\.\w+'
        emails = re.findall(email_pattern, html)
        if emails:
            domain = url.replace("https://", "").replace("http://", "").replace("www.", "").split("/")[0]
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
        print(f"Erreur pour {url}: {e}")
        return None

def save_to_db(leads):
    """Sauvegarde les leads dans MySQL."""
    try:
        conn = pymysql.connect(
            host=DB_HOST,
            user=DB_USER,
            password=DB_PASS,
            database=DB_NAME,
            charset='utf8mb4',
            cursorclass=pymysql.cursors.DictCursor
        )
        with conn.cursor() as cursor:
            for lead in leads:
                cursor.execute("""
                    INSERT INTO leads (company_name, email, sector, website, status, notes)
                    VALUES (%s, %s, %s, %s, 'à faire', 'Trouvé par l\'agent automatique')
                    ON DUPLICATE KEY UPDATE status=VALUES(status)
                """, (lead['title'], lead['email'], lead['sector'], lead['url']))
        conn.commit()
    except Exception as e:
        print(f"Erreur base de données: {e}")
    finally:
        conn.close()

def send_email(leads):
    """Envoie les leads par email."""
    subject = f"[Smart Pixel] {len(leads)} nouveaux leads qualifiés"
    body = f"""
    <h2>Nouveaux leads qualifiés pour Smart Pixel</h2>
    <p>{len(leads)} sites utilisant Google Analytics et mentionnant le RGPD :</p>
    <table border="1" cellpadding="5" style="border-collapse: collapse; width: 100%;">
        <thead><tr><th>Nom</th><th>Site Web</th><th>Secteur</th><th>Email</th></tr></thead>
        <tbody>
    """

    for lead in leads:
        body += f"""
        <tr>
            <td>{lead['title']}</td>
            <td><a href="{lead['url']}">{lead['url']}</a></td>
            <td>{lead['sector']}</td>
            <td>{lead['email'] or 'Non trouvé'}</td>
        </tr>
        """

    body += "</tbody></table>"

    msg = MIMEMultipart('alternative')
    msg['Subject'] = subject
    msg['From'] = SMTP_USER
    msg['To'] = TO_EMAIL
    msg.attach(MIMEText(body, 'html'))

    try:
        with smtplib.SMTP(SMTP_SERVER, SMTP_PORT) as server:
            server.starttls()
            server.login(SMTP_USER, SMTP_PASS)
            server.sendmail(SMTP_USER, TO_EMAIL, msg.as_string())
        print("✅ Email envoyé avec succès.")
    except Exception as e:
        print(f"❌ Erreur email: {e}")

def main():
    leads = []
    for query in SEARCH_QUERIES:
        print(f"🔍 Recherche: {query}")
        results = search_google(query)
        for result in results:
            url = result['url']
            print(f"  - Vérification de {url}")
            lead_data = check_ga_and_rgpd(url)
            if lead_data and lead_data['has_ga'] and lead_data['has_rgpd']:
                # Détermine le secteur en fonction de l'URL ou du titre
                sector = "Agence Web" if "agence" in lead_data['title'].lower() else \
                        "Développeur Indépendant" if "freelance" in lead_data['title'].lower() or "dev" in url else \
                        "PME E-commerce"
                lead_data['sector'] = sector
                leads.append(lead_data)
            time.sleep(1)  # Évite les blocages

    if leads:
        print(f"🎉 {len(leads)} leads qualifiés trouvés:")
        for lead in leads:
            print(f"  - {lead['title']} ({lead['url']})")

        save_to_db(leads)
        send_email(leads)
    else:
        print("⚠️ Aucun lead qualifié trouvé.")

if __name__ == "__main__":
    main()
