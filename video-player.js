/* ===== VIDEO PLAYER FUNCTIONALITY ===== */
/* 🎬 ВИДЕО-ПЛЕЕР С АВТОВОСПРОИЗВЕДЕНИЕМ 🎬 */

let videoPlayer = {
    isOpen: false,
    isMuted: false,
    currentVideo: null,
    
    init() {
        // Получаем активное видео с сервера
        this.fetchActiveVideo();
        
        // Создаем HTML плеера
        this.createPlayer();
        
        // Загружаем состояние из localStorage
        this.loadPlayerState();
    },
    
    async fetchActiveVideo() {
        try {
            const response = await fetch('get_active_video.php');
            const data = await response.json();
            
            if (data.success && data.video) {
                this.currentVideo = data.video;
                this.loadVideo(data.video.video_url);
            }
        } catch (error) {
            console.log('Нет активного видео или ошибка:', error);
        }
    },
    
    createPlayer() {
        const container = document.createElement('div');
        container.id = 'videoPlayerContainer';
        container.className = 'video-player-container closed';
        container.innerHTML = `
            <div class="video-player-window">
                <div class="video-player-header">
                    <div class="video-player-title">
                        <span class="live-dot"></span>
                        🎬 ANIME TV
                    </div>
                    <div class="video-player-controls">
                        <button class="video-player-btn" onclick="videoPlayer.toggleMute()" title="Звук">
                            🔊
                        </button>
                        <button class="video-player-btn" onclick="videoPlayer.minimize()" title="Свернуть">
                            🗕
                        </button>
                        <button class="video-player-btn close-btn" onclick="videoPlayer.close()" title="Закрыть">
                            ✕
                        </button>
                    </div>
                </div>
                <div class="video-player-body" id="videoPlayerBody">
                    <div class="video-player-overlay" id="videoPlayerOverlay">
                        <button class="video-player-overlay-btn" onclick="videoPlayer.togglePlay()">
                            ▶️ Воспроизвести
                        </button>
                        <button class="video-player-overlay-btn" onclick="videoPlayer.toggleMute()">
                            🔊 Включить звук
                        </button>
                    </div>
                </div>
                <div class="video-player-footer">
                    <div class="video-indicator">
                        <span class="live-dot"></span>
                        <span>LIVE</span>
                    </div>
                    <span>💕 ANIME SHOP</span>
                </div>
            </div>
        `;
        
        document.body.appendChild(container);
        
        // Кнопка открытия
        const openBtn = document.createElement('div');
        openBtn.id = 'videoPlayerOpenBtn';
        openBtn.className = 'video-player-open-btn';
        openBtn.innerHTML = '🎬';
        openBtn.onclick = () => this.open();
        document.body.appendChild(openBtn);
        
        // Подключаем стили
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'video-player.css';
        document.head.appendChild(link);
    },
    
    loadVideo(url) {
        const playerBody = document.getElementById('videoPlayerBody');
        if (!playerBody) return;
        
        let html = '';
        
        // Определяем тип видео
        if (url.includes('youtube.com') || url.includes('youtu.be')) {
            const videoId = this.getYoutubeId(url);
            html = `<iframe 
                src="https://www.youtube.com/embed/${videoId}?autoplay=1&mute=0&loop=1&playlist=${videoId}&controls=1" 
                allow="autoplay; encrypted-media" 
                allowfullscreen>
            </iframe>`;
        } else if (url.includes('vimeo.com')) {
            const videoId = url.split('/').pop();
            html = `<iframe 
                src="https://player.vimeo.com/video/${videoId}?autoplay=1&loop=1" 
                allow="autoplay; fullscreen" 
                allowfullscreen>
            </iframe>`;
        } else if (url.match(/\.(mp4|webm|ogg)$/i)) {
            html = `<video 
                autoplay 
                loop 
                controls 
                playsinline>
                <source src="${url}" type="video/mp4">
                Ваш браузер не поддерживает видео.
            </video>`;
        } else {
            // Пытаемся как iframe
            html = `<iframe src="${url}" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
        }
        
        // Сохраняем оверлей для управления
        const overlay = document.getElementById('videoPlayerOverlay');
        playerBody.innerHTML = html;
        playerBody.appendChild(overlay);
        
        // Показываем плеер
        this.show();
    },
    
    getYoutubeId(url) {
        const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        const match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    },
    
    show() {
        const container = document.getElementById('videoPlayerContainer');
        const openBtn = document.getElementById('videoPlayerOpenBtn');
        
        if (container) {
            container.classList.remove('closed');
            container.classList.remove('minimized');
            this.isOpen = true;
        }
        
        if (openBtn) {
            openBtn.classList.remove('show');
        }
        
        // Сохраняем состояние
        localStorage.setItem('videoPlayerClosed', 'false');
    },
    
    open() {
        this.show();
    },
    
    close() {
        const container = document.getElementById('videoPlayerContainer');
        const openBtn = document.getElementById('videoPlayerOpenBtn');
        
        if (container) {
            container.classList.add('closed');
            container.classList.remove('minimized');
            this.isOpen = false;
        }
        
        if (openBtn) {
            openBtn.classList.add('show');
        }
        
        // Сохраняем состояние
        localStorage.setItem('videoPlayerClosed', 'true');
        
        // Эффект сердечек
        this.createCloseHearts();
    },
    
    minimize() {
        const container = document.getElementById('videoPlayerContainer');
        
        if (container) {
            container.classList.toggle('minimized');
        }
    },
    
    toggleMute() {
        const playerBody = document.getElementById('videoPlayerBody');
        if (!playerBody) return;
        
        const video = playerBody.querySelector('video');
        const iframe = playerBody.querySelector('iframe');
        
        if (video) {
            video.muted = !video.muted;
            this.isMuted = video.muted;
        }
        
        if (iframe) {
            // Для YouTube/Vimeo нужно использовать postMessage
            this.isMuted = !this.isMuted;
        }
        
        // Обновляем иконку
        const btn = document.querySelector('button[onclick="videoPlayer.toggleMute()"]');
        if (btn) {
            btn.textContent = this.isMuted ? '🔇' : '🔊';
        }
    },
    
    togglePlay() {
        const playerBody = document.getElementById('videoPlayerBody');
        if (!playerBody) return;
        
        const video = playerBody.querySelector('video');
        if (video) {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }
        
        // Скрываем оверлей
        const overlay = document.getElementById('videoPlayerOverlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                overlay.style.pointerEvents = 'none';
            }, 300);
        }
    },
    
    loadPlayerState() {
        const isClosed = localStorage.getItem('videoPlayerClosed');
        
        if (isClosed === 'false' || !isClosed) {
            // Плеер должен быть открыт
            setTimeout(() => {
                if (this.currentVideo) {
                    this.show();
                }
            }, 1000);
        }
    },
    
    createCloseHearts() {
        const hearts = ['💕', '💖', '💗', '💓', '💞'];
        
        for (let i = 0; i < 10; i++) {
            const heart = document.createElement('div');
            heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
            heart.style.position = 'fixed';
            heart.style.left = (window.innerWidth - 100 + (Math.random() - 0.5) * 100) + 'px';
            heart.style.top = (window.innerHeight - 100 + (Math.random() - 0.5) * 100) + 'px';
            heart.style.fontSize = (Math.random() * 20 + 20) + 'px';
            heart.style.pointerEvents = 'none';
            heart.style.zIndex = '99999';
            heart.style.animation = 'heartFloat 1.5s ease-out forwards';
            
            document.body.appendChild(heart);
            
            setTimeout(() => heart.remove(), 1500);
        }
    }
};

// Инициализируем плеер при загрузке
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => videoPlayer.init());
} else {
    videoPlayer.init();
}
