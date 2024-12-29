import fetch from 'node-fetch';

 // Remplace par const fetch = require('node-fetch') si CommonJS

export async function handler(event, context) {
    if (event.httpMethod !== "POST") {
        return {
            statusCode: 405,
            body: JSON.stringify({ message: 'Méthode non autorisée.' }),
        };
    }

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

        // Étape 1 : Récupérer les commentaires existants
        const commentsResponse = await fetch(
            'https://raw.githubusercontent.com/berru-g/berru-g/main/blog/comments.json'
        );

        if (!commentsResponse.ok) {
            throw new Error('Erreur lors de la récupération des commentaires.');
        }

        const comments = await commentsResponse.json();

        // Ajouter le nouveau commentaire
        comments.push(newComment);

        // Étape 2 : Récupérer le SHA du fichier
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

        // Étape 3 : Mettre à jour le fichier sur GitHub
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
                    content: Buffer.from(JSON.stringify(comments)).toString('base64'),
                    sha,
                }),
            }
        );

        if (!githubResponse.ok) {
            throw new Error('Erreur lors de l\'ajout du commentaire sur GitHub.');
        }

        return {
            statusCode: 200,
            body: JSON.stringify({ message: 'Commentaire ajouté avec succès.' }),
        };

    } catch (error) {
        console.error('Erreur :', error.message);
        return {
            statusCode: 500,
            body: JSON.stringify({ message: 'Une erreur est survenue.', error: error.message }),
        };
    }
}
console.log('Données reçues :', { name, comment });
console.error('Erreur détectée :', error.message);
