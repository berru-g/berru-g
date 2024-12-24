
    // Fonction pour calculer le temps de lecture
    function calculateReadTime() {
      const wordsPerMinute = 140; // Vitesse de lecture moyenne
      const articleContainer = document.getElementById('article-content');
      const text = articleContainer.innerText || articleContainer.textContent; // Texte total
      const wordCount = text.split(/\s+/).length; // Compte les mots
      const readTime = Math.ceil(wordCount / wordsPerMinute); // Temps en minutes

      // Affiche le temps de lecture
      const readTimeElement = document.getElementById('read-time');
      readTimeElement.innerText = `Temps de lecture estimé : ${readTime} minute${readTime > 1 ? 's' : ''}`;
  }

  // Appelle la fonction au chargement de la page
  calculateReadTime();