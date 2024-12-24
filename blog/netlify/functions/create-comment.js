const fetch = require('node-fetch');

exports.handler = async function(event, context) {
  const { name, comment } = JSON.parse(event.body);

  const token = process.env.GITHUB_TOKEN; // Le token est stocké dans la variable d'environnement
  const repo = 'berru-g/blog';  // Remplace par ton dépôt
  const filePath = 'comments.json';

  // Récupérer le contenu actuel de comments.json
  const response = await fetch(`https://api.github.com/repos/${repo}/contents/${filePath}`, {
    headers: { Authorization: `token ${token}` },
  });

  if (!response.ok) {
    return { statusCode: 500, body: 'Erreur lors de la récupération du fichier' };
  }

  const data = await response.json();
  const content = Buffer.from(data.content, 'base64').toString('utf8');
  const comments = JSON.parse(content);

  // Ajouter un nouveau commentaire
  comments.push({ name, comment, date: new Date().toISOString() });

  // Mettre à jour le fichier sur GitHub
  const updatedContent = Buffer.from(JSON.stringify(comments)).toString('base64');

  const updateResponse = await fetch(`https://api.github.com/repos/${repo}/contents/${filePath}`, {
    method: 'PUT',
    headers: { Authorization: `token ${token}` },
    body: JSON.stringify({
      message: 'Ajout d\'un nouveau commentaire',
      content: updatedContent,
      sha: data.sha,
    }),
  });

  if (updateResponse.ok) {
    return { statusCode: 200, body: 'Commentaire ajouté avec succès' };
  } else {
    return { statusCode: 500, body: 'Erreur lors de l\'ajout du commentaire' };
  }
};
