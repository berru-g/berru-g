// netlify/functions/submitComment.js
const fetch = require('node-fetch'); // Utilisé pour faire des requêtes HTTP
const { GITHUB_TOKEN, GITHUB_REPO, GITHUB_FILE_PATH } = process.env;

exports.handler = async (event, context) => {
  // Vérifier que la méthode HTTP est POST
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      body: JSON.stringify({ message: 'Méthode non autorisée' })
    };
  }

  // Récupérer les données envoyées dans la requête
  const { name, comment } = JSON.parse(event.body);
  const date = new Date().toISOString();

  const newComment = { name, comment, date };

  try {
    // Récupérer les commentaires existants depuis GitHub
    const response = await fetch(`https://api.github.com/repos/${GITHUB_REPO}/contents/${GITHUB_FILE_PATH}`);
    const data = await response.json();
    const comments = JSON.parse(Buffer.from(data.content, 'base64').toString('utf8')) || [];

    // Ajouter le nouveau commentaire
    comments.push(newComment);

    // Mettre à jour le fichier sur GitHub avec les nouveaux commentaires
    const updateResponse = await fetch(`https://api.github.com/repos/${GITHUB_REPO}/contents/${GITHUB_FILE_PATH}`, {
      method: 'PUT',
      headers: {
        'Authorization': `token ${GITHUB_TOKEN}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: 'Ajout d\'un nouveau commentaire',
        content: Buffer.from(JSON.stringify(comments)).toString('base64'),
        sha: data.sha
      })
    });

    if (updateResponse.ok) {
      return {
        statusCode: 200,
        body: JSON.stringify({ message: 'Commentaire ajouté avec succès' })
      };
    } else {
      return {
        statusCode: 500,
        body: JSON.stringify({ message: 'Erreur lors de l\'ajout du commentaire' })
      };
    }
  } catch (error) {
    console.error('Erreur', error);
    return {
      statusCode: 500,
      body: JSON.stringify({ message: 'Erreur interne' })
    };
  }
};
/*
import fetch from 'node-fetch'; // Utilisé pour faire des requêtes HTTP
const { GITHUB_TOKEN, GITHUB_REPO, GITHUB_FILE_PATH } = process.env;

export async function handler(event, context) {
  // Vérifier que la méthode HTTP est POST
  if (event.httpMethod !== 'POST') {
    return {
      statusCode: 405,
      body: JSON.stringify({ message: 'Méthode non autorisée' })
    };
  }

  // Récupérer les données envoyées dans la requête
  const { name, comment } = JSON.parse(event.body);
  const date = new Date().toISOString();

  const newComment = { name, comment, date };

  try {
    // Récupérer les commentaires existants depuis GitHub
    const response = await fetch(`https://api.github.com/repos/${GITHUB_REPO}/contents/${GITHUB_FILE_PATH}`);
    const data = await response.json();
    const comments = JSON.parse(Buffer.from(data.content, 'base64').toString('utf8')) || [];

    // Ajouter le nouveau commentaire
    comments.push(newComment);

    // Mettre à jour le fichier sur GitHub avec les nouveaux commentaires
    const updateResponse = await fetch(`https://api.github.com/repos/${GITHUB_REPO}/contents/${GITHUB_FILE_PATH}`, {
      method: 'PUT',
      headers: {
        'Authorization': `token ${GITHUB_TOKEN}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        message: 'Ajout d\'un nouveau commentaire',
        content: Buffer.from(JSON.stringify(comments)).toString('base64'),
        sha: data.sha
      })
    });

    if (updateResponse.ok) {
      return {
        statusCode: 200,
        body: JSON.stringify({ message: 'Commentaire ajouté avec succès' })
      };
    } else {
      return {
        statusCode: 500,
        body: JSON.stringify({ message: 'Erreur lors de l\'ajout du commentaire' })
      };
    }
  } catch (error) {
    console.error('Erreur', error);
    return {
      statusCode: 500,
      body: JSON.stringify({ message: 'Erreur interne' })
    };
  }
}
*/