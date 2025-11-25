/**
 * SERSOLTEC - CHATBOT WIDGET v2.2 (FIXED)
 * Floating chat widget z auto-scroll i ulepszoną obsługą błędów
 */

class ChatbotWidget {
    constructor(config = {}) {
        this.config = {
            apiUrl: config.apiUrl || '/api/chatbot-widget.php',
            currentLang: config.currentLang || 'pl',
            debugMode: config.debugMode || false,
            ...config
        };
        
        this.isOpen = false;
        this.messages = [];
        this.conversationHistory = [];
        this.faqLoaded = false;
        
        this.init();
    }
    
    init() {
        this.createWidget();
        this.attachEventListeners();
        this.setupAutoScroll();
        this.loadFAQ();
        this.addWelcomeMessage();
        
        if (this.config.debugMode) {
            console.log('🤖 Chatbot Widget v2.2 initialized');
            console.log('📍 API URL:', this.config.apiUrl);
            console.log('🌍 Language:', this.config.currentLang);
        }
    }
    
    createWidget() {
        const widget = document.createElement('div');
        widget.className = 'chatbot-widget';
        widget.innerHTML = `
            <!-- Przycisk do góry strony -->
            <button class="chatbot-scroll-top" id="chatbotScrollTop" title="Do góry" aria-label="Przewiń do góry">
                ↑
            </button>
            
            <!-- Przycisk otwarcia czatu -->
            <button class="chatbot-toggle" id="chatbotToggle" title="Otwórz chat" aria-label="Otwórz chat">
                💬
            </button>
            
            <!-- Okno czatu -->
            <div class="chatbot-window" id="chatbotWindow">
                <!-- Header -->
                <div class="chatbot-header">
                    <h2>Sersoltec Bot</h2>
                    <button class="chatbot-close" id="chatbotClose" aria-label="Zamknij chat">×</button>
                </div>
                
                <!-- Treść czatu -->
                <div class="chatbot-content" id="chatbotContent">
                    <div class="chatbot-empty" id="chatbotEmpty">
                        <div class="chatbot-empty-icon">💬</div>
                        <div class="chatbot-empty-text">Jak się masz? 👋<br>Naciśnij poniżej, aby zacząć!</div>
                    </div>
                </div>
                
                <!-- Formularz -->
                <form class="chatbot-form" id="chatbotForm">
                    <input 
                        type="text" 
                        class="chatbot-input" 
                        id="chatbotInput" 
                        placeholder="Wpisz wiadomość..."
                        autocomplete="off"
                    >
                    <button type="submit" class="chatbot-send" id="chatbotSend" title="Wyślij">
                        ⇤
                    </button>
                </form>
            </div>
        `;
        
        document.body.appendChild(widget);
        
        this.toggle = document.getElementById('chatbotToggle');
        this.window = document.getElementById('chatbotWindow');
        this.content = document.getElementById('chatbotContent');
        this.form = document.getElementById('chatbotForm');
        this.input = document.getElementById('chatbotInput');
        this.send = document.getElementById('chatbotSend');
        this.close = document.getElementById('chatbotClose');
        this.empty = document.getElementById('chatbotEmpty');
        this.scrollTopBtn = document.getElementById('chatbotScrollTop');
        
        this.addStyles();
    }
    
    addStyles() {
        if (document.getElementById('chatbot-widget-styles')) return;
        
        const link = document.createElement('link');
        link.id = 'chatbot-widget-styles';
        link.rel = 'stylesheet';
        link.href = '/assets/css/chatbot-widget.css';
        document.head.appendChild(link);
    }
    
    setupAutoScroll() {
        const observer = new MutationObserver(() => {
            setTimeout(() => {
                this.scrollToBottom();
            }, 10);
        });
        
        observer.observe(this.content, {
            childList: true,
            subtree: true,
            characterData: true
        });
    }
    
    scrollToBottom() {
        this.content.scrollTop = this.content.scrollHeight;
    }
    
    attachEventListeners() {
        this.toggle.addEventListener('click', () => this.toggleChat());
        this.close.addEventListener('click', () => this.toggleChat());
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendMessage();
        });
        
        this.input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                this.sendMessage();
            }
        });
        
        this.scrollTopBtn.addEventListener('click', () => this.scrollToTop());
        
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 300) {
                this.scrollTopBtn.classList.add('visible');
            } else {
                this.scrollTopBtn.classList.remove('visible');
            }
        });
    }
    
    toggleChat() {
        this.isOpen = !this.isOpen;
        
        if (this.isOpen) {
            this.window.classList.add('active');
            this.toggle.classList.add('open');
            this.input.focus();
            setTimeout(() => this.scrollToBottom(), 100);
        } else {
            this.window.classList.remove('active');
            this.toggle.classList.remove('open');
        }
    }
    
    addWelcomeMessage() {
        const welcomeTexts = {
            'pl': '👋 Witaj! Jestem botem Sersoltec. Jak się masz? Mogę odpowiedzieć na pytania dotyczące produktów, dostaw, gwarancji i zamówień!',
            'en': '👋 Hello! I\'m Sersoltec bot. How can I help? I can answer questions about products, shipping, warranty and orders!',
            'es': '👋 ¡Hola! Soy el bot de Sersoltec. ¿Cómo puedo ayudarte? ¡Puedo responder preguntas sobre productos, envío, garantía y pedidos!'
        };
        
        const text = welcomeTexts[this.config.currentLang] || welcomeTexts['pl'];
        this.addMessage(text, 'bot');
    }
    
    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    loadFAQ() {
        const data = {
            action: 'get_faq',
            lang: this.config.currentLang
        };
        
        fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.faq) {
                this.faqData = data.faq;
                this.faqLoaded = true;
                this.displayFAQ();
                
                if (this.config.debugMode) {
                    console.log('✅ FAQ loaded:', data.faq.length, 'items');
                }
            }
        })
        .catch(err => {
            console.error('❌ FAQ load error:', err);
            if (this.config.debugMode) {
                this.addMessage('⚠️ Nie udało się załadować FAQ. Spróbuj odświeżyć stronę.', 'bot');
            }
        });
    }
    
    displayFAQ() {
        if (!this.faqLoaded || !this.messages.length) return;
        
        if (this.empty) {
            this.empty.remove();
            this.empty = null;
        }
        
        const faqContainer = document.createElement('div');
        faqContainer.className = 'chat-message bot';
        faqContainer.innerHTML = `
            <div style="width: 100%;">
                <div style="margin-bottom: 0.5rem; font-size: 0.9rem; color: #999;">Popularne pytania:</div>
                ${this.faqData.map((item, idx) => `
                    <div class="faq-card-widget" data-faq="${idx}">
                        <h4>${this.escapeHtml(item.question)}</h4>
                        <p>${this.escapeHtml(item.answer)}</p>
                    </div>
                `).join('')}
            </div>
        `;
        
        this.content.appendChild(faqContainer);
        
        faqContainer.querySelectorAll('.faq-card-widget').forEach(card => {
            card.addEventListener('click', () => {
                const idx = card.dataset.faq;
                const faq = this.faqData[idx];
                this.addMessage(faq.question, 'user');
                this.addMessage(faq.answer, 'bot');
            });
        });
        
        this.scrollToBottom();
    }
    
    sendMessage() {
        const text = this.input.value.trim();
        
        if (!text) return;
        
        if (this.empty) {
            this.empty.remove();
            this.empty = null;
        }
        
        this.addMessage(text, 'user');
        this.input.value = '';
        this.input.focus();
        
        this.addLoadingMessage();
        
        // Przygotuj dane
        const data = {
            action: 'send_message',
            message: text,
            email: '',
            lang: this.config.currentLang,
            history: this.conversationHistory
        };
        
        if (this.config.debugMode) {
            console.log('📤 Sending message:', data);
        }
        
        fetch(this.config.apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => {
            if (this.config.debugMode) {
                console.log('📥 Response status:', response.status);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.json();
        })
        .then(data => {
            this.removeLoadingMessage();
            
            if (this.config.debugMode) {
                console.log('📥 Response data:', data);
            }
            
            if (data.success && data.response) {
                this.addMessage(data.response, 'bot');
                
                if (data.fallback && this.config.debugMode) {
                    console.warn('⚠️ Using fallback response');
                }
            } else {
                const errorTexts = {
                    'pl': '❌ Błąd: Spróbuj ponownie',
                    'en': '❌ Error: Please try again',
                    'es': '❌ Error: Intenta de nuevo'
                };
                this.addMessage(errorTexts[this.config.currentLang] || errorTexts['pl'], 'bot');
                
                if (this.config.debugMode) {
                    console.error('❌ Invalid response:', data);
                }
            }
        })
        .catch(err => {
            this.removeLoadingMessage();
            console.error('❌ Send message error:', err);
            
            const errorTexts = {
                'pl': `❌ Błąd połączenia: ${err.message}. Sprawdź konsolę aby uzyskać więcej informacji.`,
                'en': `❌ Connection error: ${err.message}. Check console for details.`,
                'es': `❌ Error de conexión: ${err.message}. Revisa la consola para más detalles.`
            };
            
            this.addMessage(errorTexts[this.config.currentLang] || errorTexts['pl'], 'bot');
            
            if (this.config.debugMode) {
                this.addMessage(`🔍 Debug: ${err.stack}`, 'bot');
            }
        });
    }
    
    addMessage(text, sender = 'bot') {
        const message = document.createElement('div');
        message.className = `chat-message ${sender}`;
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.textContent = text;
        
        message.appendChild(bubble);
        this.content.appendChild(message);
        
        this.conversationHistory.push({
            sender: sender,
            text: text,
            timestamp: new Date().toISOString()
        });
        
        this.messages.push({ text, sender, timestamp: new Date() });
        
        this.scrollToBottom();
    }
    
    addLoadingMessage() {
        const message = document.createElement('div');
        message.className = 'chat-message bot';
        message.id = 'chatbot-loading';
        
        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = `
            <div class="chatbot-loading">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        
        message.appendChild(bubble);
        this.content.appendChild(message);
        
        this.scrollToBottom();
    }
    
    removeLoadingMessage() {
        const loading = document.getElementById('chatbot-loading');
        if (loading) loading.remove();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    destroy() {
        const widget = document.querySelector('.chatbot-widget');
        if (widget) widget.remove();
    }
}

// ========================================
// AUTO INIT NA DOMContentLoaded
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const htmlLang = document.documentElement.lang || 'pl';
    const apiUrl = window.CHATBOT_API_URL || '/api/chatbot-widget.php';
    const debugMode = window.CHATBOT_DEBUG || false;
    
    window.chatbotWidget = new ChatbotWidget({
        currentLang: htmlLang,
        apiUrl: apiUrl,
        debugMode: debugMode
    });
    
    console.log('✅ Chatbot Widget v2.2 initialized');
    console.log('📍 API:', apiUrl);
    console.log('🌍 Lang:', htmlLang);
    console.log('🐛 Debug:', debugMode);
});
