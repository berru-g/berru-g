POUR CONFIGURER L'ENVOI EMAIL :
Option A : SMTP Local (recommandé - discret)

    Ouvre un terminal ADMIN

    Lance un serveur SMTP local :

cmd

python -m smtpd -n -c DebuggingServer localhost:1025

    Laisse cette fenêtre ouverte (elle recevra les emails)

Option B : ProtonMail direct

Modifie la configuration :
python

CONFIG = {
    'EMAIL_TO': 'securite-perso@proton.me',
    'EMAIL_FROM': 'ton_email@proton.me',
    'SMTP_SERVER': '127.0.0.1',  # Change en 'smtp.protonmail.ch'
    'SMTP_PORT': 587,  # Port ProtonMail
    'USE_TLS': True,
    # Ajoute si besoin:
    # 'EMAIL_USER': 'ton_email@proton.me',
    # 'EMAIL_PASSWORD': 'ton_mot_de_passe',
}