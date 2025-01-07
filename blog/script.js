
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

document.addEventListener("DOMContentLoaded", () => {
  const shareButton = document.getElementById("shareButton");
  const sharePopup = document.getElementById("sharePopup");
  const currentURL = window.location.href;

  // Configurer les liens de partage
  const twitterShare = document.getElementById("twitterShare");
  twitterShare.href = `https://twitter.com/intent/tweet?url=${encodeURIComponent(currentURL)}&text=${encodeURIComponent("Découvrez cet article incroyable !")}`;

  const whatsappShare = document.getElementById("whatsappShare");
  whatsappShare.href = `https://api.whatsapp.com/send?text=${encodeURIComponent("Découvrez cet article : " + currentURL)}`;

  const facebookShare = document.getElementById("facebookShare");
  facebookShare.href = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(currentURL)}`;

  // Afficher ou masquer le popup
  shareButton.addEventListener("click", () => {
    const isVisible = sharePopup.style.display === "flex";
    sharePopup.style.display = isVisible ? "none" : "flex";
  });

  // Copier le lien dans le presse-papier
  window.copyToClipboard = () => {
    navigator.clipboard.writeText(currentURL).then(() => {
      alert("Lien copié dans le presse-papier !");
    }).catch(err => {
      console.error("Échec de la copie du lien : ", err);
    });
  };

  // Cacher le popup quand on clique en dehors
  document.addEventListener("click", (e) => {
    if (!shareButton.contains(e.target) && !sharePopup.contains(e.target)) {
      sharePopup.style.display = "none";
    }
  });
});
