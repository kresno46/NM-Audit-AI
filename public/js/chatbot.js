// public/js/chatbot.js
class AuditChatbot {
    constructor() {
        this.isOpen = false;
        this.messages = [];
        this.isTyping = false;
        this.currentContext = null;
        this.initializeElements();
        this.bindEvents();
        this.loadWelcomeMessage();
    }
    
    initializeElements() {
        this.chatbotToggle = document.getElementById('chatbotToggle');
        this.chatbotContainer = document.getElementById('chatbotContainer');
        this.chatbotClose = document.getElementById('chatbotClose');
        this.chatbotMessages = document.getElementById('chatbotMessages');
        this.chatbotInput = document.getElementById('chatbotInput');
        this.chatbotSend = document.getElementById('chatbotSend');
        
        // Set CSRF token for AJAX requests
        this.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    }
    
    bindEvents() {
        this.chatbotToggle.addEventListener('click', () => this.toggleChatbot());
        this.chatbotClose.addEventListener('click', () => this.closeChatbot());
        this.chatbotSend.addEventListener('click', () => this.sendMessage());
        this.chatbotInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });
        
        // Auto-resize input
        this.chatbotInput.addEventListener('input', () => {
            this.chatbotInput.style.height = 'auto';
            this.chatbotInput.style.height = this.chatbotInput.scrollHeight + 'px';
        });
    }
    
    toggleChatbot() {
        if (this.isOpen) {
            this.closeChatbot();
        } else {
            this.openChatbot();
        }
    }
    
    openChatbot() {
        this.chatbotContainer.classList.add('open');
        this.chatbotToggle.style.display = 'none';
        this.isOpen = true;
        
        // Focus on input
        setTimeout(() => {
            this.chatbotInput.focus();
        }, 300);
        
        // Load context if on audit page
        this.loadCurrentContext();
    }
    
    closeChatbot() {
        this.chatbotContainer.classList.remove('open');
        this.chatbotToggle.style.display = 'block';
        this.isOpen = false;
    }
    
    loadWelcomeMessage() {
        const welcomeMessage = {
            type: 'bot',
            content: 'Hello! I\'m your AI Audit Assistant. I can help you with:\n\n• Question clarification\n• Scoring guidelines\n• Best practices\n• Documentation tips\n\nHow can I assist you today?',
            timestamp: new Date()
        };
        
        this.addMessage(welcomeMessage);
    }
    
    loadCurrentContext() {
        // Get current page context
        const currentPage = window.location.pathname;
        const pageTitle = document.title;
        
        if (currentPage.includes('/audit/conduct')) {
            this.currentContext = {
                type: 'audit_conduct',
                employee: this.getEmployeeInfo(),
                question: this.getCurrentQuestion(),
                progress: this.getAuditProgress()
            };
        } else if (currentPage.includes('/audit/')) {
            this.currentContext = {
                type: 'audit_general',
                page: pageTitle
            };
        } else if (currentPage.includes('/dashboard')) {
            this.currentContext = {
                type: 'dashboard',
                stats: this.getDashboardStats()
            };
        }
    }
    
    getEmployeeInfo() {
        // Extract employee info from current page
        const employeeCard = document.querySelector('.card-body h6');
        if (employeeCard) {
            return {
                name: employeeCard.textContent,
                role: document.querySelector('.fw-bold').textContent,
                branch: document.querySelector('.col-6 .fw-bold').textContent
            };
        }
        return null;
    }
    
    getCurrentQuestion() {
        const questionText = document.querySelector('.lead');
        const questionNumber = document.querySelector('.text-primary');
        
        if (questionText && questionNumber) {
            return {
                number: questionNumber.textContent,
                text: questionText.textContent
            };
        }
        return null;
    }
    
    getAuditProgress() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            return {
                percentage: progressBar.style.width,
                current: document.querySelector('.text-muted').textContent
            };
        }
        return null;
    }
    
    getDashboardStats() {
        const statsCards = document.querySelectorAll('.card h3');
        const stats = {};
        
        statsCards.forEach((card, index) => {
            const title = card.parentElement.querySelector('h6').textContent;
            stats[title.toLowerCase()] = card.textContent;
        });
        
        return stats;
    }
    
    sendMessage() {
        const message = this.chatbotInput.value.trim();
        if (!message) return;
        
        // Add user message
        const userMessage = {
            type: 'user',
            content: message,
            timestamp: new Date()
        };
        
        this.addMessage(userMessage);
        this.chatbotInput.value = '';
        this.chatbotInput.style.height = 'auto';
        
        // Show typing indicator
        this.showTypingIndicator();
        
        // Send to AI
        this.sendToAI(message);
    }
    
    addMessage(message) {
        this.messages.push(message);
        this.renderMessage(message);
        this.scrollToBottom();
    }
    
    renderMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${message.type}-message mb-3`;
        
        const isBot = message.type === 'bot';
        const avatarClass = isBot ? 'bg-primary' : 'bg-secondary';
        const avatarIcon = isBot ? 'fa-robot' : 'fa-user';
        const alignClass = isBot ? 'flex-row' : 'flex-row-reverse';
        
        messageDiv.innerHTML = `
            <div class="d-flex ${alignClass} align-items-start">
                <div class="avatar-sm ${avatarClass} rounded-circle d-flex align-items-center justify-content-center me-2">
                    <i class="fas ${avatarIcon} text-white fa-sm"></i>
                </div>
                <div class="message-content flex-grow-1">
                    <div class="message-bubble p-3 rounded-3 ${isBot ? 'bg-light' : 'bg-primary text-white'}" style="max-width: 85%;">
                        <div class="message-text">${this.formatMessage(message.content)}</div>
                    </div>
                    <small class="text-muted mt-1 d-block">${this.formatTime(message.timestamp)}</small>
                </div>
            </div>
        `;
        
        this.chatbotMessages.appendChild(messageDiv);
    }
    
    formatMessage(content) {
        // Convert markdown-like formatting to HTML
        return content
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/\n/g, '<br>')
            .replace(/• /g, '• ');
    }
    
    formatTime(timestamp) {
        return timestamp.toLocaleTimeString([], { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    }
    
    showTypingIndicator() {
        this.isTyping = true;
        const typingDiv = document.createElement('div');
        typingDiv.className = 'typing-indicator mb-3';
        typingDiv.id = 'typingIndicator';
        
        typingDiv.innerHTML = `
            <div class="d-flex align-items-start">
                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                    <i class="fas fa-robot text-white fa-sm"></i>
                </div>
                <div class="message-content">
                    <div class="message-bubble p-3 rounded-3 bg-light">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        this.chatbotMessages.appendChild(typingDiv);
        this.scrollToBottom();
    }
    
    hideTypingIndicator() {
        this.isTyping = false;
        const typingIndicator = document.getElementById('typingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }
    
    sendToAI(message) {
        const payload = {
            message: message,
            context: this.currentContext,
            conversation_history: this.messages.slice(-10), // Last 10 messages for context
            _token: this.csrfToken
        };
        
        fetch('/api/chatbot/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            this.hideTypingIndicator();
            
            if (data.success) {
                const botMessage = {
                    type: 'bot',
                    content: data.response,
                    timestamp: new Date()
                };
                
                this.addMessage(botMessage);
                
                // Handle any actions suggested by the AI
                if (data.actions) {
                    this.handleAIActions(data.actions);
                }
            } else {
                this.addErrorMessage(data.error || 'Sorry, I encountered an error. Please try again.');
            }
        })
        .catch(error => {
            this.hideTypingIndicator();
            console.error('Chatbot error:', error);
            this.addErrorMessage('Sorry, I\'m having trouble connecting. Please try again later.');
        });
    }
    
    addErrorMessage(errorText) {
        const errorMessage = {
            type: 'bot',
            content: `⚠️ ${errorText}`,
            timestamp: new Date()
        };
        
        this.addMessage(errorMessage);
    }
    
    handleAIActions(actions) {
        actions.forEach(action => {
            switch (action.type) {
                case 'highlight_element':
                    this.highlightElement(action.selector);
                    break;
                case 'scroll_to_element':
                    this.scrollToElement(action.selector);
                    break;
                case 'show_guideline':
                    this.showGuideline(action.content);
                    break;
                case 'suggest_score':
                    this.suggestScore(action.score, action.reasoning);
                    break;
            }
        });
    }
    
    highlightElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.style.outline = '2px solid #007bff';
            element.style.outlineOffset = '2px';
            
            setTimeout(() => {
                element.style.outline = '';
                element.style.outlineOffset = '';
            }, 3000);
        }
    }
    
    scrollToElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }
    
    showGuideline(content) {
        // Create and show a modal with guidelines
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Audit Guidelines</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${this.formatMessage(content)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
        
        // Clean up after modal is hidden
        modal.addEventListener('hidden.bs.modal', () => {
            document.body.removeChild(modal);
        });
    }
    
    suggestScore(score, reasoning) {
        const suggestion = {
            type: 'bot',
            content: `💡 **Scoring Suggestion**: Based on the answer, I would suggest a score of **${score}/100**.\n\n**Reasoning**: ${reasoning}\n\nRemember, this is just a suggestion. Use your professional judgment for the final score.`,
            timestamp: new Date()
        };
        
        this.addMessage(suggestion);
    }
    
    scrollToBottom() {
        setTimeout(() => {
            this.chatbotMessages.scrollTop = this.chatbotMessages.scrollHeight;
        }, 100);
    }
    
    // Public methods for external use
    askQuestion(question) {
        if (!this.isOpen) {
            this.openChatbot();
        }
        
        setTimeout(() => {
            this.chatbotInput.value = question;
            this.sendMessage();
        }, 500);
    }
    
    clearChat() {
        this.messages = [];
        this.chatbotMessages.innerHTML = '';
        this.loadWelcomeMessage();
    }
    
    setContext(context) {
        this.currentContext = context;
    }
    
    getMessages() {
        return this.messages;
    }
    
    isTypingActive() {
        return this.isTyping;
    }
    
    // Method to handle quick actions
    handleQuickAction(action) {
        switch (action) {
            case 'help':
                this.askQuestion('What can you help me with?');
                break;
            case 'guidelines':
                this.askQuestion('Show me the audit guidelines for this question');
                break;
            case 'scoring':
                this.askQuestion('How should I score this answer?');
                break;
            case 'best_practices':
                this.askQuestion('What are the best practices for this type of question?');
                break;
            case 'documentation':
                this.askQuestion('How should I document this finding?');
                break;
            default:
                console.warn('Unknown quick action:', action);
        }
    }
    
    // Method to add quick action buttons
    addQuickActions() {
        const quickActionsDiv = document.createElement('div');
        quickActionsDiv.className = 'quick-actions mt-3 mb-3';
        quickActionsDiv.innerHTML = `
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-outline-primary btn-sm" onclick="chatbot.handleQuickAction('help')">
                    <i class="fas fa-question-circle me-1"></i> Help
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="chatbot.handleQuickAction('guidelines')">
                    <i class="fas fa-book me-1"></i> Guidelines
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="chatbot.handleQuickAction('scoring')">
                    <i class="fas fa-star me-1"></i> Scoring
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="chatbot.handleQuickAction('best_practices')">
                    <i class="fas fa-thumbs-up me-1"></i> Best Practices
                </button>
                <button class="btn btn-outline-primary btn-sm" onclick="chatbot.handleQuickAction('documentation')">
                    <i class="fas fa-file-alt me-1"></i> Documentation
                </button>
            </div>
        `;
        
        // Insert before the input section
        const inputSection = document.querySelector('.chatbot-input-section');
        if (inputSection) {
            inputSection.parentNode.insertBefore(quickActionsDiv, inputSection);
        }
    }
    
    // Method to update chatbot status
    updateStatus(status) {
        const statusIndicator = document.querySelector('.chatbot-status');
        if (statusIndicator) {
            statusIndicator.textContent = status;
            statusIndicator.className = `chatbot-status ${status.toLowerCase()}`;
        }
    }
    
    // Method to handle keyboard shortcuts
    handleKeyboardShortcuts(event) {
        // Ctrl/Cmd + Enter to send message
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            this.sendMessage();
        }
        
        // Escape to close chatbot
        if (event.key === 'Escape' && this.isOpen) {
            this.closeChatbot();
        }
        
        // Ctrl/Cmd + K to focus input
        if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
            event.preventDefault();
            if (this.isOpen) {
                this.chatbotInput.focus();
            } else {
                this.openChatbot();
            }
        }
    }
    
    // Initialize keyboard shortcuts
    initKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            this.handleKeyboardShortcuts(event);
        });
    }
    
    // Method to export chat history
    exportChatHistory() {
        const chatHistory = {
            timestamp: new Date().toISOString(),
            context: this.currentContext,
            messages: this.messages.map(msg => ({
                type: msg.type,
                content: msg.content,
                timestamp: msg.timestamp.toISOString()
            }))
        };
        
        const blob = new Blob([JSON.stringify(chatHistory, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `audit-chat-history-${new Date().getTime()}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    
    // Method to initialize the chatbot
    init() {
        this.initKeyboardShortcuts();
        this.addQuickActions();
        this.updateStatus('Ready');
        
        // Show welcome message after a delay
        setTimeout(() => {
            if (this.messages.length === 1) { // Only welcome message
                this.addMessage({
                    type: 'bot',
                    content: 'I\'m ready to help you with your audit. You can ask me questions or use the quick action buttons below.',
                    timestamp: new Date()
                });
            }
        }, 2000);
    }
}

// Initialize the chatbot when the page loads
document.addEventListener('DOMContentLoaded', function() {
    window.chatbot = new AuditChatbot();
    window.chatbot.init();
});

// Add CSS for typing indicator animation
const style = document.createElement('style');
style.textContent = `
    .typing-dots {
        display: flex;
        gap: 4px;
        align-items: center;
    }
    
    .typing-dots span {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background-color: #6c757d;
        animation: typing 1.4s infinite ease-in-out;
    }
    
    .typing-dots span:nth-child(1) {
        animation-delay: -0.32s;
    }
    
    .typing-dots span:nth-child(2) {
        animation-delay: -0.16s;
    }
    
    @keyframes typing {
        0%, 80%, 100% {
            transform: scale(0);
        }
        40% {
            transform: scale(1);
        }
    }
    
    .quick-actions {
        padding: 0 1rem;
    }
    
    .chatbot-status {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        display: inline-block;
    }
    
    .chatbot-status.ready {
        background-color: #d4edda;
        color: #155724;
    }
    
    .chatbot-status.typing {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .chatbot-status.error {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .message-bubble {
        word-wrap: break-word;
        max-width: 100%;
    }
    
    .avatar-sm {
        width: 32px;
        height: 32px;
        min-width: 32px;
        min-height: 32px;
    }
    
    .chatbot-container.open {
        transform: translateY(0);
        opacity: 1;
    }
    
    .chatbot-container {
        transform: translateY(100%);
        opacity: 0;
        transition: all 0.3s ease;
    }
`;