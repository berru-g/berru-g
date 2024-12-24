// Fonction Netlify qui gère la soumission du commentaire
import fetch from 'node-fetch'; // N'oublie pas d'importer `node-fetch` pour que `fetch` fonctionne côté serveur.

export async function handler(event, context) {
    if (event.httpMethod === "POST") {
        const { name, comment } = JSON.parse(event.body); // Récupère les données du formulaire

        const date = new Date().toISOString(); // Date actuelle en format ISO

        const newComment = { name, comment, date };

        // Récupère les commentaires existants depuis le fichier JSON GitHub
        const commentsResponse = await fetch('https://raw.githubusercontent.com/berru-g/berru-g/main/blog/comments.json');
        const comments = await commentsResponse.json();

        // Ajoute le nouveau commentaire
        comments.push(newComment);

        const content = JSON.stringify(comments);

        // Envoie les commentaires mis à jour à GitHub (en utilisant l'API PUT)
        const githubResponse = await fetch('https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json', {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${process.env.GITHUB_TOKEN}`, // Utilisation du token GitHub via une variable d'environnement
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: 'Ajout d\'un commentaire',
                content: Buffer.from(content).toString('base64'), // Encode les données en base64
                sha: 'SHA_DU_FICHIER_COMMENTAIRE', // SHA à récupérer ou à mettre à jour pour éviter les conflits
            }),
        });

        if (githubResponse.ok) {
            return {
                statusCode: 200,
                body: JSON.stringify({ message: 'Commentaire ajouté avec succès' }),
            };
        } else {
            return {
                statusCode: 500,
                body: JSON.stringify({ message: 'Erreur lors de l\'ajout du commentaire' }),
            };
        }
    }

    // Retourne une erreur si ce n'est pas une méthode POST
    return {
        statusCode: 405,
        body: JSON.stringify({ message: 'Méthode non autorisée' }),
    };
}

/*const fetch = require('node-fetch');

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
*/