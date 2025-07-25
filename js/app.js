// Main Application JavaScript
class SpeedTyper {
    constructor() {
        this.currentCode = '';
        this.currentIndex = 0;
        this.startTime = null;
        this.endTime = null;
        this.isTestActive = false;
        this.errors = 0;
        this.timer = null;
        this.currentLanguage = 'python';
        this.codeSnippets = {};
        this.currentSnippetIndex = 0;
        this.autoScrollEnabled = true;
        
        this.initializeElements();
        this.loadCodeSnippets();
        this.bindEvents();
        this.initializeChat();
        this.checkLoginStatus();
        this.setupAutoCodeRotation();
    }
    
    initializeElements() {
        // DOM elements
        this.languageSelect = document.getElementById('languageSelect');
        this.startTestBtn = document.getElementById('startTest');
        this.resetTestBtn = document.getElementById('resetTest');
        this.codeDisplay = document.getElementById('codeDisplay');
        this.hiddenInput = document.getElementById('hiddenInput');
        this.wpmDisplay = document.getElementById('wpm');
        this.accuracyDisplay = document.getElementById('accuracy');
        this.errorsDisplay = document.getElementById('errors');
        this.timerDisplay = document.getElementById('timer');
        this.resultsContainer = document.getElementById('results');
        this.integratedContainer = document.getElementById('integratedContainer');
        
        // Modal elements
        this.loginModal = document.getElementById('loginModal');
        
        // Login elements
        this.loginBtn = document.getElementById('loginBtn');
        this.loginForm = document.getElementById('loginForm');
        
        // Chat elements
        this.chatBtn = document.getElementById('chatBtn');
        
        // Leaderboard elements
        this.leaderboardBtn = document.getElementById('leaderboardBtn');
    }
    
    bindEvents() {
        // Test controls
        this.startTestBtn.addEventListener('click', () => this.startTest());
        this.resetTestBtn.addEventListener('click', () => this.resetTest());
        this.languageSelect.addEventListener('change', (e) => this.changeLanguage(e.target.value));
        
        // Focus management for integrated container
        this.integratedContainer.addEventListener('click', () => {
            if (this.isTestActive) {
                this.hiddenInput.focus();
                this.integratedContainer.classList.add('focused');
            }
        });
        
        this.hiddenInput.addEventListener('input', (e) => this.handleInput(e));
        this.hiddenInput.addEventListener('keydown', (e) => this.handleKeyDown(e));
        this.hiddenInput.addEventListener('blur', () => {
            if (this.isTestActive) {
                setTimeout(() => this.hiddenInput.focus(), 10);
            } else {
                this.integratedContainer.classList.remove('focused');
            }
        });
        
        // Modal controls
        document.querySelectorAll('.close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (e) => this.closeModal(e.target.closest('.modal')));
        });
        
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });
        
        // Login
        if (this.loginBtn) {
            this.loginBtn.addEventListener('click', () => this.openModal(this.loginModal));
        }
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', (e) => this.handleLogin(e));
        }
        
        // Chat
        if (this.chatBtn) {
            this.chatBtn.addEventListener('click', () => this.openChatModal());
        }
        
        // Leaderboard
        if (this.leaderboardBtn) {
            this.leaderboardBtn.addEventListener('click', () => this.openLeaderboardModal());
        }
        
        // Results
        const saveScoreBtn = document.getElementById('saveScore');
        const newTestBtn = document.getElementById('newTest');
        
        if (saveScoreBtn) {
            saveScoreBtn.addEventListener('click', () => this.saveScore());
        }
        if (newTestBtn) {
            newTestBtn.addEventListener('click', () => this.startNewTest());
        }
        
        // Prevent tab key default behavior in test area
        document.addEventListener('keydown', (e) => {
            if (this.isTestActive && e.key === 'Tab') {
                e.preventDefault();
            }
        });
    }
    
    async loadCodeSnippets() {
        try {
            const languages = ['python', 'javascript', 'cpp', 'go', 'rust', 'scala', 'c'];
            
            for (const lang of languages) {
                const response = await fetch(`code_snippets/${lang}.json`);
                if (response.ok) {
                    this.codeSnippets[lang] = await response.json();
                }
            }
            
            this.loadRandomCode();
        } catch (error) {
            console.error('Error loading code snippets:', error);
            this.showFallbackCode();
        }
    }
    
    showFallbackCode() {
        const fallbackCodes = {
            python: `def fibonacci(n):
    if n <= 1:
        return n
    return fibonacci(n-1) + fibonacci(n-2)

for i in range(10):
    print(f"F({i}) = {fibonacci(i)}")`,
            javascript: `function quickSort(arr) {
    if (arr.length <= 1) return arr;
    const pivot = arr[Math.floor(arr.length / 2)];
    const left = [], right = [], equal = [];
    
    for (let element of arr) {
        if (element < pivot) left.push(element);
        else if (element > pivot) right.push(element);
        else equal.push(element);
    }
    
    return [...quickSort(left), ...equal, ...quickSort(right)];
}`,
            cpp: `#include <iostream>
#include <vector>
using namespace std;

int binarySearch(vector<int>& arr, int target) {
    int left = 0, right = arr.size() - 1;
    
    while (left <= right) {
        int mid = left + (right - left) / 2;
        if (arr[mid] == target) return mid;
        else if (arr[mid] < target) left = mid + 1;
        else right = mid - 1;
    }
    
    return -1;
}`,
            go: `package main

import "fmt"

func mergeSort(arr []int) []int {
    if len(arr) <= 1 {
        return arr
    }
    
    mid := len(arr) / 2
    left := mergeSort(arr[:mid])
    right := mergeSort(arr[mid:])
    
    return merge(left, right)
}`,
            rust: `fn binary_search<T: Ord>(arr: &[T], target: &T) -> Option<usize> {
    let mut left = 0;
    let mut right = arr.len();
    
    while left < right {
        let mid = left + (right - left) / 2;
        match arr[mid].cmp(target) {
            std::cmp::Ordering::Equal => return Some(mid),
            std::cmp::Ordering::Less => left = mid + 1,
            std::cmp::Ordering::Greater => right = mid,
        }
    }
    
    None
}`,
            scala: `def quickSort[T](arr: List[T])(implicit ord: Ordering[T]): List[T] = {
  import ord._
  
  arr match {
    case Nil => Nil
    case head :: tail =>
      val (smaller, larger) = tail.partition(_ < head)
      quickSort(smaller) ::: head :: quickSort(larger)
  }
}`,
            c: `#include <stdio.h>
#include <stdlib.h>

int compare(const void *a, const void *b) {
    return (*(int*)a - *(int*)b);
}

void bubbleSort(int arr[], int n) {
    for (int i = 0; i < n-1; i++) {
        for (int j = 0; j < n-i-1; j++) {
            if (arr[j] > arr[j+1]) {
                int temp = arr[j];
                arr[j] = arr[j+1];
                arr[j+1] = temp;
            }
        }
    }
}`
        };
        
        this.currentCode = fallbackCodes[this.currentLanguage] || fallbackCodes.python;
        this.displayCode();
    }
    
    changeLanguage(language) {
        this.currentLanguage = language;
        this.loadRandomCode();
        this.resetTest();
    }
    
    loadRandomCode() {
        if (this.codeSnippets[this.currentLanguage] && this.codeSnippets[this.currentLanguage].length > 0) {
            const snippets = this.codeSnippets[this.currentLanguage];
            this.currentSnippetIndex = Math.floor(Math.random() * snippets.length);
            this.currentCode = snippets[this.currentSnippetIndex];
        } else {
            this.showFallbackCode();
        }
        this.displayCode();
    }
    
    displayCode() {
        if (!this.codeDisplay) return;
        
        this.codeDisplay.innerHTML = '';
        
        for (let i = 0; i < this.currentCode.length; i++) {
            const char = document.createElement('span');
            char.textContent = this.currentCode[i];
            char.classList.add('char');
            char.setAttribute('data-index', i);
            
            if (i < this.currentIndex) {
                char.classList.add('char-untyped');
            } else if (i === this.currentIndex && this.isTestActive) {
                char.classList.add('char-current');
            } else {
                char.classList.add('char-untyped');
            }
            
            this.codeDisplay.appendChild(char);
        }
        
        this.scrollToCurrentChar();
    }
    
    scrollToCurrentChar() {
        if (!this.isTestActive || !this.autoScrollEnabled) return;
        
        const currentChar = this.codeDisplay.querySelector(`[data-index="${this.currentIndex}"]`);
        if (currentChar) {
            currentChar.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
                inline: 'nearest'
            });
        }
    }
    
    startTest() {
        this.isTestActive = true;
        this.startTime = Date.now();
        this.currentIndex = 0;
        this.errors = 0;
        this.hiddenInput.disabled = false;
        this.hiddenInput.focus();
        this.hiddenInput.value = '';
        this.resultsContainer.style.display = 'none';
        this.integratedContainer.classList.add('focused');
        
        this.displayCode();
        this.startTimer();
        
        this.startTestBtn.textContent = 'Test Berjalan...';
        this.startTestBtn.disabled = true;
    }
    
    resetTest() {
        this.isTestActive = false;
        this.startTime = null;
        this.endTime = null;
        this.currentIndex = 0;
        this.errors = 0;
        this.hiddenInput.disabled = true;
        this.hiddenInput.value = '';
        this.resultsContainer.style.display = 'none';
        this.integratedContainer.classList.remove('focused');
        
        this.startTestBtn.textContent = 'Mulai Test';
        this.startTestBtn.disabled = false;
        
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        
        this.updateStats();
        this.displayCode();
    }
    
    startTimer() {
        this.timer = setInterval(() => {
            if (this.isTestActive && this.startTime) {
                const elapsed = (Date.now() - this.startTime) / 1000;
                this.timerDisplay.textContent = `${Math.floor(elapsed)}s`;
                this.updateStats();
            }
        }, 100);
    }
    
    handleInput(e) {
        if (!this.isTestActive) return;
        
        const inputValue = e.target.value;
        
        // Clear previous highlighting
        this.codeDisplay.querySelectorAll('.char').forEach(char => {
            char.classList.remove('char-correct', 'char-incorrect', 'char-current');
            char.classList.add('char-untyped');
        });
        
        // Highlight typed characters
        let errorCount = 0;
        
        for (let i = 0; i < inputValue.length; i++) {
            const char = this.codeDisplay.querySelector(`[data-index="${i}"]`);
            if (char) {
                char.classList.remove('char-untyped');
                if (inputValue[i] === this.currentCode[i]) {
                    char.classList.add('char-correct');
                } else {
                    char.classList.add('char-incorrect');
                    errorCount++;
                }
            }
        }
        
        // Highlight current character
        const currentChar = this.codeDisplay.querySelector(`[data-index="${inputValue.length}"]`);
        if (currentChar) {
            currentChar.classList.remove('char-untyped');
            currentChar.classList.add('char-current');
        }
        
        this.currentIndex = inputValue.length;
        this.errors = errorCount;
        
        // Auto scroll
        this.scrollToCurrentChar();
        
        // Check if test is complete
        if (inputValue === this.currentCode) {
            this.completeTest();
        }
        
        this.updateStats();
    }
    
    handleKeyDown(e) {
        if (!this.isTestActive) return;
        
        // Prevent certain shortcuts that could be used to cheat
        if (e.ctrlKey || e.metaKey) {
            if (['a', 'c', 'v', 'x', 'z', 'y'].includes(e.key.toLowerCase())) {
                e.preventDefault();
            }
        }
        
        // Handle Tab character properly
        if (e.key === 'Tab') {
            e.preventDefault();
            // Insert tab character into hidden input
            const start = this.hiddenInput.selectionStart;
            const end = this.hiddenInput.selectionEnd;
            const value = this.hiddenInput.value;
            this.hiddenInput.value = value.substring(0, start) + '\t' + value.substring(end);
            this.hiddenInput.selectionStart = this.hiddenInput.selectionEnd = start + 1;
            
            // Trigger input event
            this.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
    
    completeTest() {
        this.isTestActive = false;
        this.endTime = Date.now();
        
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
        
        this.hiddenInput.disabled = true;
        this.integratedContainer.classList.remove('focused');
        this.startTestBtn.textContent = 'Mulai Test';
        this.startTestBtn.disabled = false;
        
        this.showResults();
    }
    
    showResults() {
        const wpm = this.calculateWPM();
        const accuracy = this.calculateAccuracy();
        const timeElapsed = this.getTimeElapsed();
        
        document.getElementById('finalWpm').textContent = wpm;
        document.getElementById('finalAccuracy').textContent = `${accuracy}%`;
        document.getElementById('finalErrors').textContent = this.errors;
        document.getElementById('finalTime').textContent = `${timeElapsed}s`;
        
        this.resultsContainer.style.display = 'block';
        this.resultsContainer.scrollIntoView({ behavior: 'smooth' });
    }
    
    calculateWPM() {
        if (!this.startTime || !this.endTime) return 0;
        
        const timeInMinutes = (this.endTime - this.startTime) / 60000;
        const wordsTyped = this.currentCode.length / 5; // Standard: 5 chars = 1 word
        
        return Math.round(wordsTyped / timeInMinutes);
    }
    
    calculateAccuracy() {
        if (this.currentCode.length === 0) return 100;
        
        const correctChars = this.currentCode.length - this.errors;
        return Math.round((correctChars / this.currentCode.length) * 100);
    }
    
    getTimeElapsed() {
        if (!this.startTime || !this.endTime) return 0;
        return Math.round((this.endTime - this.startTime) / 1000);
    }
    
    updateStats() {
        // Update WPM
        if (this.startTime && this.isTestActive) {
            const timeInMinutes = (Date.now() - this.startTime) / 60000;
            const wordsTyped = this.currentIndex / 5;
            const currentWPM = timeInMinutes > 0 ? Math.round(wordsTyped / timeInMinutes) : 0;
            this.wpmDisplay.textContent = currentWPM;
        } else {
            this.wpmDisplay.textContent = '0';
        }
        
        // Update accuracy
        if (this.currentIndex > 0) {
            const correctChars = this.currentIndex - this.errors;
            const accuracy = Math.round((correctChars / this.currentIndex) * 100);
            this.accuracyDisplay.textContent = `${accuracy}%`;
        } else {
            this.accuracyDisplay.textContent = '100%';
        }
        
        // Update errors
        this.errorsDisplay.textContent = this.errors;
    }
    
    setupAutoCodeRotation() {
        // Auto-rotate code every 30 seconds when not testing
        setInterval(() => {
            if (!this.isTestActive) {
                this.loadRandomCode();
            }
        }, 30000);
    }
    
    checkLoginStatus() {
        // This would typically check if user is logged in via session
        // For now, we'll assume guest login is available
    }
    
    // Modal functions
    openModal(modal) {
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }
    
    closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }
    
    // Login functions
    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const username = formData.get('username');
        
        // Validate username
        if (!username || username.length < 3 || username.length > 20) {
            alert('Username harus 3-20 karakter');
            return;
        }
        
        if (!/^[a-zA-Z0-9_]+$/.test(username)) {
            alert('Username hanya boleh mengandung huruf, angka, dan underscore');
            return;
        }
        
        try {
            const response = await fetch('api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ username: username })
            });
            
            const result = await response.json();
            
            if (result.success) {
                location.reload(); // Reload to update session
            } else {
                alert(result.message || 'Login gagal');
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('Terjadi kesalahan saat login');
        }
    }
    
    // Chat functions
    initializeChat() {
        // Load chat messages periodically
        setInterval(() => {
            if (this.chatModal && this.chatModal.style.display === 'block') {
                this.loadChatMessages();
            }
        }, 3000);
    }
    
    async openChatModal() {
        // Create chat modal dynamically if it doesn't exist
        if (!this.chatModal) {
            this.createChatModal();
        }
        this.openModal(this.chatModal);
        await this.loadChatMessages();
    }
    
    createChatModal() {
        const modalHTML = `
            <div id="chatModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>💬 Live Chat</h2>
                    <div id="chatMessages" class="chat-messages"></div>
                    <div class="chat-input-container">
                        <input type="text" id="chatInput" placeholder="Ketik pesan..." maxlength="200">
                        <button id="sendMessage" class="btn btn-primary">Kirim</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.chatModal = document.getElementById('chatModal');
        this.chatMessages = document.getElementById('chatMessages');
        this.chatInput = document.getElementById('chatInput');
        this.sendMessageBtn = document.getElementById('sendMessage');
        
        // Bind events for new elements
        this.chatModal.querySelector('.close').addEventListener('click', () => this.closeModal(this.chatModal));
        this.sendMessageBtn.addEventListener('click', () => this.sendMessage());
        this.chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.sendMessage();
        });
    }
    
    async loadChatMessages() {
        if (!this.chatMessages) return;
        
        try {
            const response = await fetch('api/get_chat.php');
            const result = await response.json();
            
            if (result.success) {
                this.displayChatMessages(result.data);
            }
        } catch (error) {
            console.error('Chat load error:', error);
        }
    }
    
    displayChatMessages(messages) {
        if (!messages || !this.chatMessages) return;
        
        const scrollBottom = this.chatMessages.scrollTop + this.chatMessages.clientHeight >= this.chatMessages.scrollHeight - 5;
        
        let html = '';
        
        messages.forEach(msg => {
            const timestamp = new Date(msg.timestamp).toLocaleTimeString();
            html += `
                <div class="chat-message">
                    <span class="username">${this.escapeHtml(msg.username)}</span>
                    <span class="timestamp">${timestamp}</span>
                    <div class="message">${this.escapeHtml(msg.message)}</div>
                </div>
            `;
        });
        
        this.chatMessages.innerHTML = html;
        
        if (scrollBottom) {
            this.chatMessages.scrollTop = this.chatMessages.scrollHeight;
        }
    }
    
    async sendMessage() {
        if (!this.chatInput) return;
        
        const message = this.chatInput.value.trim();
        if (!message) return;
        
        try {
            const response = await fetch('api/save_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message: message })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.chatInput.value = '';
                await this.loadChatMessages();
            } else {
                alert(result.message || 'Gagal mengirim pesan');
            }
        } catch (error) {
            console.error('Send message error:', error);
            alert('Terjadi kesalahan saat mengirim pesan');
        }
    }
    
    // Leaderboard functions
    async openLeaderboardModal() {
        if (!this.leaderboardModal) {
            this.createLeaderboardModal();
        }
        this.openModal(this.leaderboardModal);
        await this.loadLeaderboard();
    }
    
    createLeaderboardModal() {
        const modalHTML = `
            <div id="leaderboardModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>🏆 Leaderboard</h2>
                    <div class="leaderboard-filter">
                        <label for="leaderboardLanguage">Filter Bahasa:</label>
                        <select id="leaderboardLanguage">
                            <option value="all">Semua Bahasa</option>
                            <option value="python">Python</option>
                            <option value="javascript">JavaScript</option>
                            <option value="cpp">C++</option>
                            <option value="go">Go</option>
                            <option value="rust">Rust</option>
                            <option value="scala">Scala</option>
                            <option value="c">C</option>
                        </select>
                    </div>
                    <div id="leaderboardContent" class="leaderboard-content"></div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.leaderboardModal = document.getElementById('leaderboardModal');
        this.leaderboardContent = document.getElementById('leaderboardContent');
        this.leaderboardLanguage = document.getElementById('leaderboardLanguage');
        
        // Bind events
        this.leaderboardModal.querySelector('.close').addEventListener('click', () => this.closeModal(this.leaderboardModal));
        this.leaderboardLanguage.addEventListener('change', () => this.loadLeaderboard());
    }
    
    async loadLeaderboard() {
        if (!this.leaderboardContent) return;
        
        this.leaderboardContent.innerHTML = '<div class="loading">Memuat...</div>';
        
        try {
            const language = this.leaderboardLanguage ? this.leaderboardLanguage.value : 'all';
            const response = await fetch(`api/get_leaderboard.php?language=${language}`);
            const result = await response.json();
            
            if (result.success) {
                this.displayLeaderboard(result.data);
            } else {
                this.leaderboardContent.innerHTML = '<div class="loading">Gagal memuat leaderboard</div>';
            }
        } catch (error) {
            console.error('Leaderboard error:', error);
            this.leaderboardContent.innerHTML = '<div class="loading">Terjadi kesalahan</div>';
        }
    }
    
    displayLeaderboard(data) {
        if (!data || data.length === 0) {
            this.leaderboardContent.innerHTML = '<div class="loading">Belum ada data</div>';
            return;
        }
        
        let html = '';
        
        data.forEach((item, index) => {
            const rank = index + 1;
            const rankClass = rank <= 3 ? `rank-${rank}` : '';
            
            html += `
                <div class="leaderboard-item">
                    <div class="leaderboard-rank ${rankClass}">#${rank}</div>
                    <div class="leaderboard-user">
                        <span class="username">${this.escapeHtml(item.username)}</span>
                        <span class="language">${this.escapeHtml(item.language)}</span>
                    </div>
                    <div class="leaderboard-stats">
                        <div class="wpm">${item.wpm} WPM</div>
                        <div class="accuracy">${item.accuracy}%</div>
                    </div>
                </div>
            `;
        });
        
        this.leaderboardContent.innerHTML = html;
    }
    
    // Score functions
    async saveScore() {
        if (!this.endTime || !this.startTime) {
            alert('Tidak ada data test untuk disimpan');
            return;
        }
        
        const scoreData = {
            language: this.currentLanguage,
            wpm: this.calculateWPM(),
            accuracy: this.calculateAccuracy(),
            errors: this.errors,
            time: this.getTimeElapsed()
        };
        
        try {
            const response = await fetch('api/save_score.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(scoreData)
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Skor berhasil disimpan!');
                document.getElementById('saveScore').style.display = 'none';
            } else {
                alert(result.message || 'Gagal menyimpan skor');
            }
        } catch (error) {
            console.error('Save score error:', error);
            alert('Terjadi kesalahan saat menyimpan skor');
        }
    }
    
    startNewTest() {
        this.loadRandomCode();
        this.resetTest();
    }
    
    // Utility functions
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new SpeedTyper();
});

// Security measures
document.addEventListener('keydown', (e) => {
    // Prevent developer tools shortcuts
    if (e.key === 'F12' || 
        (e.ctrlKey && e.shiftKey && e.key === 'I') ||
        (e.ctrlKey && e.shiftKey && e.key === 'C') ||
        (e.ctrlKey && e.shiftKey && e.key === 'J') ||
        (e.ctrlKey && e.key === 'U')) {
        e.preventDefault();
        return false;
    }
});

// Prevent right-click context menu
document.addEventListener('contextmenu', (e) => {
    e.preventDefault();
    return false;
});

// Disable text selection outside input areas
document.addEventListener('selectstart', (e) => {
    if (!e.target.matches('input, textarea')) {
        e.preventDefault();
        return false;
    }
});

// Prevent drag and drop
document.addEventListener('dragstart', (e) => {
    e.preventDefault();
    return false;
});

// Console warning
console.warn('🚫 Accessing developer tools may affect your test results!');
console.log('💻 SpeedTyper - Test your coding speed fairly!');