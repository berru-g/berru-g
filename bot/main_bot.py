# main_bot.py
import praw
import time
import mysql.connector
from datetime import datetime
from detectors.keyword_detector import KeywordDetector

class SmartPixelBot:
    def __init__(self):
        # Reddit API
        self.reddit = praw.Reddit(
            client_id='TON_CLIENT_ID',
            client_secret='TON_SECRET',
            user_agent='SmartPixelBot/1.0'
        )
        
        # Détecteur de mots-clés
        self.detector = KeywordDetector()
        
        # DB pour logging
        self.db = mysql.connector.connect(
            host="localhost",
            user="ton_user",
            password="ton_mdp",
            database="smart_pixel_bot"
        )
        
        # Configuration
        self.SUBREDDITS = ['analytics', 'webdev', 'SideProject', 'SaaS', 'startups']
        self.RATE_LIMIT = 60  # secondes entre réponses
        self.MAX_POSTS_PER_SUB = 15
        
    def log_interaction(self, post_id, title, decision_data):
        """Loggue l'interaction en base"""
        cursor = self.db.cursor()
        query = """
        INSERT INTO interactions 
        (platform, platform_id, title, should_respond, score, 
         found_keywords, categories, created_at)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
        """
        cursor.execute(query, (
            'reddit',
            post_id,
            title[:255],
            decision_data['should'],
            decision_data['score'],
            str(decision_data['found_keywords']),
            str(decision_data['categories']),
            datetime.now()
        ))
        self.db.commit()
    
    def already_responded(self, post_id):
        """Vérifie si on a déjà répondu à ce post"""
        cursor = self.db.cursor()
        cursor.execute(
            "SELECT id FROM interactions WHERE platform_id = %s AND responded = 1",
            (post_id,)
        )
        return cursor.fetchone() is not None
    
    def mark_responded(self, interaction_id, comment_id):
        """Marque le post comme ayant reçu une réponse"""
        cursor = self.db.cursor()
        cursor.execute(
            "UPDATE interactions SET responded = 1, comment_id = %s WHERE id = %s",
            (comment_id, interaction_id)
        )
        self.db.commit()
    
    def generate_response(self, found_keywords, categories):
        """Génère une réponse personnalisée basée sur les mots-clés trouvés"""
        # Détecte le "pain point" principal
        pain_points = {
            'rgpd': 'RGPD' in str(found_keywords) or categories['rgpd'] > 0,
            'complexite': any(x in str(found_keywords) for x in ['complexe', 'galère']),
            'performance': 'performance' in str(found_keywords),
            'france': any(x in str(found_keywords) for x in ['français', 'france'])
        }
        
        # Phrases d'accroche selon le pain
        if pain_points['rgpd']:
            intro = "Je vois que tu parles de RGPD et Google Analytics..."
        elif pain_points['complexite']:
            intro = "Je vois que tu galères avec la complexité de GA4..."
        elif pain_points['france']:
            intro = "Je vois que tu cherches une solution française..."
        else:
            intro = "Je vois que tu cherches une alternative à Google Analytics..."
        
        # Code promo différent selon la plateforme
        code = "REDDIT3MOIS"  # 3 mois gratuits pour Reddit
        
        response = f"""{intro}

J'étais exactement dans le même cas, c'est pour ça que j'ai créé Smart Pixel :
✅ Alternative 100% française à GA4
✅ RGPD compliant par défaut (données en France)
✅ Dashboard hyper simple (install en 2min)
✅ Open source et transparent

Si tu veux tester : {code} pour 3 mois gratuits.
Lien : https://smart-pixel.fr

Désolé si c'est un peu direct, mais quand je vois quelqu'un galérer avec GA4, 
je me sens obligé de partager ma solution 😅

Bonne journée !"""
        
        return response
    
    def monitor_subreddit(self, subreddit_name):
        """Monitor un subreddit spécifique"""
        print(f"Monitoring r/{subreddit_name}...")
        
        subreddit = self.reddit.subreddit(subreddit_name)
        
        try:
            for post in subreddit.new(limit=self.MAX_POSTS_PER_SUB):
                # Vérifie qu'on a pas déjà répondu
                if self.already_responded(post.id):
                    continue
                
                # Analyse le post avec notre détecteur
                should_respond, decision_data = self.detector.should_respond(
                    post.selftext, post.title
                )
                
                # Log l'interaction
                self.log_interaction(post.id, post.title, decision_data)
                
                if should_respond:
                    print(f"✓ Post détecté: {post.title[:50]}...")
                    print(f"  Score: {decision_data['score']}")
                    print(f"  Mots trouvés: {decision_data['found_keywords']}")
                    
                    # Génère et poste la réponse
                    response = self.generate_response(
                        decision_data['found_keywords'],
                        decision_data['categories']
                    )
                    
                    try:
                        comment = post.reply(response)
                        print(f"  ✓ Réponse postée: {comment.id}")
                        
                        # Marque comme répondu en DB
                        cursor = self.db.cursor()
                        cursor.execute(
                            "SELECT id FROM interactions WHERE platform_id = %s",
                            (post.id,)
                        )
                        interaction_id = cursor.fetchone()[0]
                        self.mark_responded(interaction_id, comment.id)
                        
                        # Respecte le rate limit
                        time.sleep(self.RATE_LIMIT)
                        
                    except Exception as e:
                        print(f"  ✗ Erreur réponse: {e}")
                
        except Exception as e:
            print(f"Erreur avec r/{subreddit_name}: {e}")
    
    def run(self):
        """Lance le monitoring"""
        print("=== Smart Pixel Bot démarre ===")
        print(f"Heure: {datetime.now()}")
        
        while True:
            try:
                for sub in self.SUBREDDITS:
                    self.monitor_subreddit(sub)
                    time.sleep(10)  # Pause entre subreddits
                
                print(f"Cycle terminé. Prochain cycle dans 10 minutes.")
                time.sleep(600)  # 10 minutes entre les cycles complets
                
            except KeyboardInterrupt:
                print("\nBot arrêté manuellement.")
                break
            except Exception as e:
                print(f"Erreur générale: {e}")
                time.sleep(300)  # Attente en cas d'erreur

if __name__ == "__main__":
    bot = SmartPixelBot()
    bot.run()