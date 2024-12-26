// Fonction pour soumettre un commentaire via la fonction Netlify
async function submitComment(event) {
  event.preventDefault();  // Empêche le rechargement du formulaire

  const name = document.getElementById('name').value;
  const comment = document.getElementById('comment').value;

  // Envoi des données via la fonction Netlify
  const response = await fetch('../netlify/functions/create-comment', {
      method: 'POST',
      body: JSON.stringify({ name, comment }),
      headers: {
          'Content-Type': 'application/json',
      },
  });

  if (response.ok) {
      alert('Commentaire ajouté avec succès');
      loadComments();  // Recharge les commentaires après l'ajout
  } else {
      alert('Erreur lors de l\'ajout du commentaire');
  }
}

// Fonction pour charger les commentaires depuis GitHub (affichage côté client)
async function loadComments() {
  const response = await fetch('https://raw.githubusercontent.com/berru-g/berru-g/main/blog/comments.json');
  const comments = await response.json();

  const commentSection = document.getElementById('comments-section');
  commentSection.innerHTML = '';  // Vide la section avant de la remplir

  comments.forEach(comment => {
      const commentDiv = document.createElement('div');
      commentDiv.innerHTML = `
          <h4>${comment.name}</h4>
          <p>${comment.comment}</p>
          <p>${comment.date}</p>
      `;
      commentSection.appendChild(commentDiv);
  });
}

// Charger les commentaires au démarrage de la page
window.onload = loadComments;

/*
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
*/