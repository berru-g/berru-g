//import node from "https://esm.sh/node";
//import express from "https://esm.sh/express";

function updateDateTime() {
  const now = new Date();
  const dateTimeString = now.toLocaleString('fr-FR', {
      month: 'short', day: 'numeric',
      hour: '2-digit', minute: '2-digit'
  });
  document.getElementById('date-time').textContent = dateTimeString;
}
updateDateTime();
setInterval(updateDateTime, 1000);

document.addEventListener('DOMContentLoaded', function () {
  const taskList = document.getElementById('todo-list');
  const addTaskButton = document.getElementById('add-task');
  const totalWalletValue = document.getElementById('wallet-total-value');
  const bitcoinPriceElement = document.getElementById('bitcoin-price');
  const solanaPriceElement = document.getElementById('solana-price');
  const pepePriceElement = document.getElementById('pepe-price');
  const teslaPriceElement = document.getElementById('tesla-price');
  
  // Sample wallet values
  const wallet = {
      bitcoin: 0.0000,
      solana: 0,
      pepe: 106000000
  };

  // Fetch Crypto Prices
  async function fetchCryptoPrices() {
      const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=bitcoin,solana,pepe&vs_currencies=usd&include_24hr_change=true');
      const data = await response.json();

      const bitcoinPrice = data.bitcoin.usd;
      const solanaPrice = data.solana.usd;
      const pepePrice = data.pepe.usd;

      bitcoinPriceElement.textContent = `$${bitcoinPrice}`;
      solanaPriceElement.textContent = `$${solanaPrice}`;
      pepePriceElement.textContent = `$${pepePrice}`;

      bitcoinPriceElement.style.color = data.bitcoin.usd_24h_change >= 0 ? '#2ed573' : '#ff6348';
      solanaPriceElement.style.color = data.solana.usd_24h_change >= 0 ? '#2ed573' : '#ff6348';
      pepePriceElement.style.color = data.pepe.usd_24h_change >= 0 ? '#2ed573' : '#ff6348';

      totalWalletValue.textContent = `$${(wallet.bitcoin * bitcoinPrice + wallet.solana * solanaPrice + wallet.pepe * pepePrice).toFixed(2)}`;
  }

  // Fetch Tesla Stock Price
  async function fetchTeslaPrice() {
      const response = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=tesla&vs_currencies=usd');
      const data = await response.json();

      const teslaPrice = data.tesla.usd;
      teslaPriceElement.textContent = `$${teslaPrice}`;
  }

  // Fetch prices initially and then every 60 seconds
  fetchCryptoPrices();
  fetchTeslaPrice();
  setInterval(fetchCryptoPrices, 60000);
  setInterval(fetchTeslaPrice, 60000);

  // Add Task
  addTaskButton.addEventListener('click', function () {
      const task = document.createElement('div');
      task.className = 'task';

      const titleInput = document.createElement('input');
      titleInput.type = 'text';
      titleInput.placeholder = 'Task Title';

      const descInput = document.createElement('input');
      descInput.type = 'text';
      descInput.placeholder = 'Task Description';

      const timerInput = document.createElement('input');
      timerInput.type = 'number';
      timerInput.placeholder = 'Minutes';

      const deleteButton = document.createElement('button');
      deleteButton.textContent = 'Delete';
      deleteButton.addEventListener('click', function () {
          taskList.removeChild(task);
      });

      const startButton = document.createElement('button');
      startButton.textContent = 'Start Timer';
      startButton.addEventListener('click', function () {
          const timerValue = parseInt(timerInput.value, 10) * 60;
          const endTime = Date.now() + timerValue * 1000;

          const timerInterval = setInterval(function () {
              const remainingTime = endTime - Date.now();
              if (remainingTime <= 0) {
                  clearInterval(timerInterval);
                  
                  new Audio('https://github.com/berru-g/console-play-music/raw/master/son/FL_HHL_Green_078_Guitars.wav').play();
              } else {
                  const minutes = Math.floor(remainingTime / 60000);
                  const seconds = Math.floor((remainingTime % 60000) / 1000);
                  timerInput.value = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
              }
          }, 1000);
      });

      task.appendChild(titleInput);
      task.appendChild(descInput);
      task.appendChild(timerInput);
      task.appendChild(startButton);
      task.appendChild(deleteButton);
      taskList.appendChild(task);
  });
});
// Chat Widget Logic
  const chatBody = document.getElementById('chat-body');
  const chatInput = document.getElementById('chat-input');
  const sendBtn = document.getElementById('send-btn');

  sendBtn.addEventListener('click', function () {
      const userInput = chatInput.value;
      if (userInput.trim() === '') return;

      appendMessage(userInput, 'user');
      chatInput.value = '';

      fetchChatGPTResponse(userInput);
  });

  chatInput.addEventListener('keypress', function (e) {
      if (e.key === 'Enter') {
          sendBtn.click();
      }
  });

  function appendMessage(message, sender) {
      const messageElement = document.createElement('div');
      messageElement.classList.add('message', sender);
      messageElement.textContent = message;
      chatBody.appendChild(messageElement);
      chatBody.scrollTop = chatBody.scrollHeight;
  }

  async function fetchChatGPTResponse(message) {
      // Remember that to actually use OpenAI's API, you need to secure API calls with a backend server to avoid exposing your API key directly on the frontend.
      const response = await fetch('https://api.openai.com/v1/models/davinci-codex/completions/APIKey', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json'
          },
          body: JSON.stringify({ message })
      });
      const data = await response.json();
      const botMessage = data.reply;

      appendMessage(botMessage, 'bot');
  }
//server.js for api chatgpt
/*
const express = require('express');
const fetch = require('node-fetch'); // Pour effectuer des requêtes HTTP
const cors = require('cors'); // Pour gérer les requêtes cross-origin
require('dotenv').config(); // Pour utiliser les variables d'environnement

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json()); // Pour analyser le corps des requêtes JSON

// Route pour l'API ChatGPT
app.post('/api/chatgpt', async (req, res) => {
  const message = req.body.message;

  if (!message) {
      return res.status(400).json({ error: 'Message is required' });
  }

  try {
      const response = await fetch('https://api.openai.com/v1/engines/davinci-codex/completions', {
          method: 'POST',
          headers: {
              'Content-Type': 'application/json',
              'Authorization': `Bearer ${process.env.OPENAI_API_KEY}`
          },
          body: JSON.stringify({
              prompt: message,
              max_tokens: 150
          })
      });

      const data = await response.json();
      if (response.ok) {
          res.json({ reply: data.choices[0].text.trim() });
      } else {
          res.status(response.status).json(data);
      }
  } catch (error) {
      res.status(500).json({ error: 'Failed to fetch ChatGPT response' });
  }
});

app.listen(PORT, () => {
  console.log(`Server is running on port ${PORT}`);
});
*/
// MENU via lib sweetalert2
const hamburgerMenu = document.querySelector('.hamburger-menu');

hamburgerMenu.addEventListener('click', () => {
// Utilisation de SweetAlert pour afficher la fenêtre contextuelle
Swal.fire({
  title: '🌍',
  html: '<ul><li><a href="#">Ledger</a></li><li><a href="#">Binance</a></li><li><a href="#">Calendar</a></li><li><a href="#">Analytics</a></li><li><a href="#">Budget</a></li></ul>',
  showCloseButton: true,
  showConfirmButton: false,
  customClass: {
    popup: 'custom-swal-popup',
    closeButton: 'custom-swal-close-button',
    content: 'custom-swal-content',
  }
});
});