// Fonction pour soumettre un commentaire via la fonction Netlify
async function submitComment(event) {
  event.preventDefault();

  const name = document.getElementById('name').value;
  const comment = document.getElementById('comment').value;

  const newComment = { name, comment };

  const response = await fetch('/.netlify/functions/submitComment', {
    method: 'POST',
    body: JSON.stringify(newComment),
    headers: {
      'Content-Type': 'application/json'
    }
  });

  const result = await response.json();

  if (response.ok) {
    alert('Commentaire ajouté avec succès');
    loadComments(); // Recharger les commentaires après l'ajout
  } else {
    alert('Erreur lors de l\'ajout du commentaire : ' + result.message);
  }
}
