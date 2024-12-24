// Fonction pour soumettre un commentaire via GitHub API
async function submitComment(event) {
  event.preventDefault();

  const name = document.getElementById('name').value;
  const comment = document.getElementById('comment').value;
  const date = new Date().toISOString();

  const newComment = { name, comment, date };

  // Charger les commentaires existants
  const existingComments = await loadCommentsFromGitHub();

  // Utilisation de l'API GitHub pour ajouter le commentaire au fichier JSON
  const response = await fetch('https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json', {
      method: 'GET', // On fait une première requête pour récupérer les données existantes
      headers: {
          'Authorization': `token ${process.env.GITHUB_TOKEN}`, // Utilisation de la variable d'environnement
          'Accept': 'application/vnd.github.v3+json',
      },
  });

  if (response.ok) {
      const fileData = await response.json();
      const fileSha = fileData.sha; // Récupère la SHA du fichier

      // Préparer les données à envoyer
      const newContent = [...existingComments, newComment]; // Ajouter le nouveau commentaire aux commentaires existants
      const encodedContent = btoa(JSON.stringify(newContent)); // Sérialise et encode en base64

      // Requête PUT pour mettre à jour le fichier
      const putResponse = await fetch('https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json', {
          method: 'PUT',
          headers: {
              'Authorization': `token ${process.env.GITHUB_TOKEN}`, // Utilise le token GitHub
              'Content-Type': 'application/json',
          },
          body: JSON.stringify({
              message: 'Ajout d\'un nouveau commentaire',
              content: encodedContent, // Contenu du fichier mis à jour
              sha: fileSha, // SHA du fichier à mettre à jour
          }),
      });

      if (putResponse.ok) {
          alert('Commentaire ajouté avec succès');
      } else {
          alert('Erreur lors de l\'ajout du commentaire');
      }
  } else {
      alert('Erreur lors du chargement des commentaires');
  }
}

// Fonction pour charger les commentaires depuis GitHub
async function loadCommentsFromGitHub() {
  const response = await fetch('https://raw.githubusercontent.com/berru-g/berru-g/main/blog/comments.json');
  const comments = await response.json();
  return comments || []; // Retourner les commentaires ou un tableau vide si aucun commentaire n'est trouvé
}

// Fonction pour afficher les commentaires sur la page
async function loadComments() {
  const comments = await loadCommentsFromGitHub();
  const commentSection = document.getElementById('comments-section');
  commentSection.innerHTML = ''; // Clear existing comments

  comments.forEach(comment => {
      const commentDiv = document.createElement('div');
      commentDiv.innerHTML = `
          <h4>${comment.name} - ${comment.date}</h4>
          <p>${comment.comment}</p>
      `;
      commentSection.appendChild(commentDiv);
  });
}

// Charger les commentaires au démarrage
window.onload = loadComments;
