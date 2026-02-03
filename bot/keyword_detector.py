# detectors/keyword_detector.py
import mysql.connector
import re
from typing import List, Dict, Tuple

class KeywordDetector:
    def __init__(self):
        self.db = mysql.connector.connect(
            host="localhost",
            user="ton_user",
            password="ton_mdp",
            database="smart_pixel_bot"
        )
        self.keywords = self.load_keywords()
    
    def load_keywords(self) -> Dict[str, Dict]:
        """Charge les mots-clés depuis la DB"""
        cursor = self.db.cursor(dictionary=True)
        cursor.execute("SELECT keyword, category, weight FROM keywords")
        keywords = {}
        for row in cursor:
            # Normalisation : lowercase, sans accents
            normalized = self.normalize_text(row['keyword'])
            keywords[normalized] = {
                'original': row['keyword'],
                'category': row['category'],
                'weight': row['weight']
            }
        return keywords
    
    def normalize_text(self, text: str) -> str:
        """Normalise le texte pour la comparaison"""
        text = text.lower()
        # Remplace accents (simplifié)
        accents = {'é': 'e', 'è': 'e', 'ê': 'e', 'ë': 'e', 'à': 'a', 'â': 'a'}
        for acc, rep in accents.items():
            text = text.replace(acc, rep)
        return text
    
    def detect_keywords(self, text: str, title: str = "") -> Tuple[int, List, Dict]:
        """
        Détecte les mots-clés dans le texte
        Retourne: (score total, liste des mots trouvés, détails par catégorie)
        """
        full_text = f"{title} {text}"
        normalized = self.normalize_text(full_text)
        
        found_keywords = []
        category_scores = {'pain': 0, 'solution': 0, 'competitor': 0, 'rgpd': 0}
        
        for keyword, data in self.keywords.items():
            # Recherche exacte du mot-clé
            if keyword in normalized:
                found_keywords.append(data['original'])
                category_scores[data['category']] += data['weight']
        
        # Calcul du score total (pondéré)
        total_score = (
            category_scores['pain'] * 3 +      # Les douleurs = très important
            category_scores['solution'] * 2 +  # Solutions recherchées = important
            category_scores['rgpd'] * 2 +      # RGPD = important
            category_scores['competitor'] * 1  # Concurrents = moins important
        )
        
        return total_score, found_keywords, category_scores
    
    def should_respond(self, text: str, title: str = "") -> Tuple[bool, Dict]:
        """
        Décide si on doit répondre au post
        Règles:
        - Au moins 3 mots-clés trouvés
        - OU score > 5 (avec pondération)
        - ET au moins 1 mot-clé de catégorie 'pain'
        """
        score, found, categories = self.detect_keywords(text, title)
        
        conditions = {
            'min_keywords': len(found) >= 3,
            'min_score': score >= 5,
            'has_pain': categories['pain'] > 0,
            'not_too_many_competitors': categories['competitor'] <= 3
        }
        
        should = (
            (conditions['min_keywords'] or conditions['min_score'])
            and conditions['has_pain']
            and conditions['not_too_many_competitors']
        )
        
        return should, {
            'should': should,
            'score': score,
            'found_keywords': found,
            'categories': categories,
            'conditions': conditions
        }