// Fonction Netlify qui gère la soumission du commentaire
import fetch from 'node-fetch';

export async function handler(event, context) {
    if (event.httpMethod === "POST") {
        try {
            const { name, comment } = JSON.parse(event.body);

            if (!name || !comment) {
                return {
                    statusCode: 400,
                    body: JSON.stringify({ message: 'Nom et commentaire obligatoires.' }),
                };
            }

            const date = new Date().toISOString();
            const newComment = { name, comment, date };

            // Récupérer les commentaires existants
            const commentsResponse = await fetch(
                'https://raw.githubusercontent.com/berru-g/berru-g/main/blog/comments.json'
            );

            if (!commentsResponse.ok) {
                throw new Error('Erreur lors de la récupération des commentaires.');
            }

            const comments = await commentsResponse.json();

            // Ajouter le nouveau commentaire
            comments.push(newComment);

            const content = JSON.stringify(comments);

            // Récupérer le SHA du fichier existant
            const fileResponse = await fetch(
                'https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json',
                {
                    headers: {
                        'Authorization': `Bearer ${process.env.GITHUB_TOKEN}`,
                    },
                }
            );

            if (!fileResponse.ok) {
                throw new Error('Erreur lors de la récupération du SHA.');
            }

            const fileData = await fileResponse.json();
            const sha = fileData.sha;

            // Envoyer les commentaires mis à jour
            const githubResponse = await fetch(
                'https://api.github.com/repos/berru-g/berru-g/contents/blog/comments.json',
                {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${process.env.GITHUB_TOKEN}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: 'Ajout d\'un commentaire',
                        content: Buffer.from(content).toString('base64'),
                        sha, // SHA récupéré dynamiquement
                    }),
                }
            );

            if (githubResponse.ok) {
                return {
                    statusCode: 200,
                    body: JSON.stringify({ message: 'Commentaire ajouté avec succès.' }),
                };
            } else {
                throw new Error('Erreur lors de l\'ajout du commentaire sur GitHub.');
            }
        } catch (error) {
            console.error('Erreur :', error.message);
            return {
                statusCode: 500,
                body: JSON.stringify({ message: 'Une erreur est survenue.', error: error.message }),
            };
        }
    }

    return {
        statusCode: 405,
        body: JSON.stringify({ message: 'Méthode non autorisée.' }),
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