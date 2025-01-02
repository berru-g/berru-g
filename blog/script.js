
    // Fonction pour calculer le temps de lecture
    function calculateReadTime() {
      const wordsPerMinute = 130; // Vitesse de lecture moyenne
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

function showInfo() {
      Swal.fire({
        title: 'Protection de vos données',
        text: "Conformément à mon engagement en faveur de la protection de votre vie privée, seules les informations que vous choisissez de partager explicitement, comme votre nom, sont affichées publiquement. Votre adresse e-mail n’est jamais partagée ou publiée telle quelle. Elle peut être affichée sous une forme partiellement masquée ou chiffrée pour protéger votre confidentialité. Pensez à utiliser un alias de messagerie 🧑‍💻",
        icon: 'info',
        confirmButtonText: 'Compris',
        customClass: {
          popup: 'swal-wide',
        },
      });
    }