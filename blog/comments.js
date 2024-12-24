async function submitComment(event) {
  event.preventDefault();  // Empêche le rechargement de la page

  const name = document.getElementById('name').value;
  const comment = document.getElementById('comment').value;
  const date = new Date().toISOString();

  const newComment = { name, comment, date };

  // Utilisation de l'API GitHub pour ajouter le commentaire au fichier JSON
  const response = await fetch('https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json', {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer YOUR_GITHUB_TOKEN',  // Remplace par ton token GitHub
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
    loadComments();  // Recharge les commentaires après l'ajout
  } else {
    alert('Erreur lors de l\'ajout du commentaire');
  }
}
