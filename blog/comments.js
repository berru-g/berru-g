// Fonction pour soumettre un commentaire via GitHub API
async function submitComment(event) {
    event.preventDefault();
  
    const name = document.getElementById('name').value;
    const comment = document.getElementById('comment').value;
    const date = new Date().toISOString();
  
    const newComment = { name, comment, date };
  
    // Utilisation de l'API GitHub pour ajouter le commentaire au fichier JSON https://github.com/berru-g/berru-g/tree/main/blog
    const response = await fetch('https://api.github.com/repos/berru-g/berru-g/tree/main/blog/comments.json', {
      method: 'PUT',
      headers: {
        'Authorization': '#', // Remplace par ton token GitHub
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: 'Ajout d\'un nouveau commentaire',
        content: btoa(JSON.stringify([...existingComments, newComment])), // Sérialise et encode en base64
        sha: 'SHA_DE_LA_FICHE_COMMENTS.JSON' // Utilise l'API GitHub pour récupérer la SHA du fichier actuel
      })
    });
  
    if (response.ok) {
      alert('Commentaire ajouté avec succès');
    } else {
      alert('Erreur lors de l\'ajout du commentaire');
    }
  }

  // Fonction pour charger les commentaires depuis GitHub
async function loadComments() {
    const response = await fetch('https://raw.githubusercontent.com/berru-g/berru-g/tree/main/blog/comments.json');
    const comments = await response.json();
  
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
  
  