// ChatBot pour Smart Pixel (intégration directe)
const SmartPixelChat = {
    init: function () {
        // Créer l'icône flottante
        this.icon = document.createElement('div');
        this.icon.id = 'sp-chat-icon';
        this.icon.innerHTML = '?';
        this.icon.style.position = 'fixed';
        this.icon.style.bottom = '20px';
        this.icon.style.right = '20px';
        this.icon.style.width = '60px';
        this.icon.style.height = '60px';
        this.icon.style.backgroundColor = '#9d86ff';
        this.icon.style.color = 'white';
        this.icon.style.borderRadius = '50%';
        this.icon.style.display = 'flex';
        this.icon.style.alignItems = 'center';
        this.icon.style.justifyContent = 'center';
        this.icon.style.fontSize = '24px';
        this.icon.style.cursor = 'pointer';
        this.icon.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        this.icon.style.zIndex = '1000';

        // Créer le conteneur du chat
        this.chat = document.createElement('div');
        this.chat.id = 'sp-chat-container';
        this.chat.style.position = 'fixed';
        this.chat.style.bottom = '90px';
        this.chat.style.right = '20px';
        this.chat.style.width = '350px';
        this.chat.style.maxHeight = '500px';
        this.chat.style.backgroundColor = 'white';
        this.chat.style.borderRadius = '8px';
        this.chat.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        this.chat.style.display = 'none';
        this.chat.style.flexDirection = 'column';
        this.chat.style.zIndex = '999';
        this.chat.style.overflow = 'hidden';

        // Header du chat
        this.chatHeader = document.createElement('div');
        this.chatHeader.style.padding = '12px 16px';
        this.chatHeader.style.backgroundColor = '#9d86ff';
        this.chatHeader.style.color = 'white';
        this.chatHeader.style.fontWeight = 'bold';
        this.chatHeader.innerHTML = 'Assistance Smart Pixel';

        // Zone des messages
        this.messages = document.createElement('div');
        this.messages.id = 'sp-chat-messages';
        this.messages.style.flex = '1';
        this.messages.style.padding = '16px';
        this.messages.style.overflowY = 'auto';

        // Input pour envoyer des messages
        this.inputContainer = document.createElement('div');
        this.inputContainer.style.padding = '12px';
        this.inputContainer.style.borderTop = '1px solid #eee';
        this.inputContainer.style.display = 'flex';

        this.input = document.createElement('input');
        this.input.type = 'text';
        this.input.id = 'sp-chat-input';
        this.input.placeholder = 'Poser une question...';
        this.input.style.flex = '1';
        this.input.style.padding = '8px 12px';
        this.input.style.border = '1px solid #ddd';
        this.input.style.borderRadius = '4px';
        this.input.style.outline = 'none';

        this.sendButton = document.createElement('button');
        this.sendButton.id = 'sp-chat-send';
        this.sendButton.innerHTML = '→';
        this.sendButton.style.marginLeft = '8px';
        this.sendButton.style.padding = '8px 12px';
        this.sendButton.style.backgroundColor = '#9d86ff';
        this.sendButton.style.color = 'white';
        this.sendButton.style.border = 'none';
        this.sendButton.style.borderRadius = '4px';
        this.sendButton.style.cursor = 'pointer';

        // Assemblage
        this.inputContainer.appendChild(this.input);
        this.inputContainer.appendChild(this.sendButton);
        this.chat.appendChild(this.chatHeader);
        this.chat.appendChild(this.messages);
        this.chat.appendChild(this.inputContainer);

        // Ajout au DOM
        document.body.appendChild(this.icon);
        document.body.appendChild(this.chat);

        // Événements
        this.icon.addEventListener('click', () => {
            this.chat.style.display = this.chat.style.display === 'none' ? 'flex' : 'none';
        });

        this.sendButton.addEventListener('click', () => this.sendMessage());
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });

        // Message de bienvenue
        this.addMessage("Bonjour ! Posez-moi une question sur Smart Pixel.", 'bot');
    },

    sendMessage: function () {
        const query = this.input.value.trim();
        if (!query) return;

        this.addMessage(query, 'user');
        this.input.value = '';

        // URL absolue (à adapter)
        const backendUrl = `${window.location.origin}/smart_pixel_v2/chat-bot/index.php`;

        fetch(`${backendUrl}?query=${encodeURIComponent(query)}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    this.addMessage(data.error, 'bot');
                } else {
                    for (const [source, content] of Object.entries(data)) {
                        this.addMessage(`<strong>${content.title} :</strong>`, 'bot');
                        content.results.forEach(section => {
                            this.addMessage(
                                `<strong>${section.title}</strong><br>${section.content}`,
                                'bot'
                            );
                        });
                    }
                }
            })
            .catch(error => {
                this.addMessage(`Erreur: ${error.message}`, 'bot');
                console.error('Erreur:', error);
            });
    },

    addMessage: function (text, sender) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `sp-message ${sender}`;
        messageDiv.style.marginBottom = '12px';
        messageDiv.style.padding = '8px 12px';
        messageDiv.style.borderRadius = '6px';
        messageDiv.style.color = 'black';
        messageDiv.style.maxWidth = '80%';
        messageDiv.style.lineHeight = '1.4';

        if (sender === 'user') {
            messageDiv.style.marginLeft = 'auto';
            messageDiv.style.backgroundColor = '#9d86ff';
            messageDiv.style.color = 'black';
            messageDiv.style.textAlign = 'right';
        } else {
            messageDiv.style.backgroundColor = '#f5f5f5';
        }

        messageDiv.innerHTML = text;
        this.messages.appendChild(messageDiv);
        this.messages.scrollTop = this.messages.scrollHeight;
    }
};

// Initialisation automatique
document.addEventListener('DOMContentLoaded', () => {
    SmartPixelChat.init();
});
