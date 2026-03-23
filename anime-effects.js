/* ===== ANIME SHOP EFFECTS ===== */
/* 🌸 КАВАЙНЫЕ ЭФФЕКТЫ 🌸 */

// ===== ПАДАЮЩИЕ ЛЕПЕСТКИ САКУРЫ =====
function createSakuraPetals() {
    const petalsContainer = document.createElement('div');
    petalsContainer.id = 'sakuraPetals';
    petalsContainer.className = 'sakura-petals';
    document.body.appendChild(petalsContainer);

    const petalCount = 30;
    
    for (let i = 0; i < petalCount; i++) {
        const petal = document.createElement('div');
        petal.className = 'petal';
        petal.style.left = Math.random() * 100 + '%';
        petal.style.animationDelay = Math.random() * 15 + 's';
        petal.style.animationDuration = (Math.random() * 10 + 10) + 's';
        
        // Разные оттенки для лепестков
        const colors = [
            'linear-gradient(135deg, #ffb7c5, #ff69b4)',
            'linear-gradient(135deg, #ffcce0, #ff69b4)',
            'linear-gradient(135deg, #ffb7c5, #ff1493)',
            'linear-gradient(135deg, #ffdab9, #ff69b4)'
        ];
        petal.style.background = colors[Math.floor(Math.random() * colors.length)];
        
        petalsContainer.appendChild(petal);
    }
}

// ===== ЭФФЕКТ СЕРДЕЧЕК ПРИ КЛИКЕ =====
function createHeartEffect(e) {
    const hearts = ['💕', '💖', '💗', '💓', '💞', '💘', '❤️', '🌸'];
    
    for (let i = 0; i < 5; i++) {
        const heart = document.createElement('div');
        heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
        heart.style.position = 'fixed';
        heart.style.left = (e.clientX + (Math.random() - 0.5) * 100) + 'px';
        heart.style.top = (e.clientY + (Math.random() - 0.5) * 100) + 'px';
        heart.style.fontSize = (Math.random() * 20 + 20) + 'px';
        heart.style.pointerEvents = 'none';
        heart.style.zIndex = '99999';
        heart.style.animation = 'heartFloat 1.5s ease-out forwards';
        
        document.body.appendChild(heart);
        
        setTimeout(() => {
            heart.remove();
        }, 1500);
    }
}

// Добавляем анимацию сердечек
function addHeartAnimation() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes heartFloat {
            0% {
                transform: translateY(0) scale(1) rotate(0deg);
                opacity: 1;
            }
            100% {
                transform: translateY(-150px) scale(0.5) rotate(360deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ===== ЭФФЕКТ БЛИКА НА КНОПКАХ =====
function initButtonShine() {
    const buttons = document.querySelectorAll('.btn, .menu a');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', (e) => {
            const shine = document.createElement('span');
            shine.style.position = 'absolute';
            shine.style.top = '0';
            shine.style.left = '0';
            shine.style.width = '100%';
            shine.style.height = '100%';
            shine.style.background = 'linear-gradient(135deg, rgba(255,255,255,0.4) 0%, transparent 50%)';
            shine.style.borderRadius = 'inherit';
            shine.style.pointerEvents = 'none';
            shine.style.animation = 'buttonShine 0.6s ease-out forwards';
            
            button.style.position = 'relative';
            button.appendChild(shine);
            
            setTimeout(() => shine.remove(), 600);
        });
    });
}

// ===== КАЧАЮЩИЕСЯ ЗАГОЛОВКИ =====
function initWobbleEffect() {
    const headings = document.querySelectorAll('h1, h2');
    
    headings.forEach(heading => {
        heading.addEventListener('mouseenter', () => {
            heading.style.animation = 'wobble 0.5s ease-in-out';
            setTimeout(() => {
                heading.style.animation = '';
            }, 500);
        });
    });
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes wobble {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }
    `;
    document.head.appendChild(style);
}

// ===== ПЛАВАЮЩИЕ ЭМОДЗИ НА ФОНЕ =====
function initFloatingEmojis() {
    const emojis = ['🌸', '💕', '✨', '💖', '🎀', '💝', '🌺', '🦋'];
    
    setInterval(() => {
        const emoji = document.createElement('div');
        emoji.textContent = emojis[Math.floor(Math.random() * emojis.length)];
        emoji.style.position = 'fixed';
        emoji.style.left = Math.random() * 100 + '%';
        emoji.style.bottom = '-50px';
        emoji.style.fontSize = (Math.random() * 20 + 20) + 'px';
        emoji.style.pointerEvents = 'none';
        emoji.style.zIndex = '0';
        emoji.style.opacity = '0.6';
        emoji.style.animation = 'floatUp 8s linear forwards';
        
        document.body.appendChild(emoji);
        
        setTimeout(() => emoji.remove(), 8000);
    }, 500);
    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes floatUp {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ===== ЭФФЕКТ ПРИ НАВЕДЕНИИ НА КАРТОЧКИ =====
function initCardGlow() {
    const cards = document.querySelectorAll('.stat-card, .edit-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const glow = document.createElement('div');
            glow.style.position = 'absolute';
            glow.style.left = x + 'px';
            glow.style.top = y + 'px';
            glow.style.width = '150px';
            glow.style.height = '150px';
            glow.style.borderRadius = '50%';
            glow.style.background = 'radial-gradient(circle, rgba(255, 105, 180, 0.3), transparent)';
            glow.style.transform = 'translate(-50%, -50%)';
            glow.style.pointerEvents = 'none';
            glow.style.transition = 'opacity 0.3s';
            
            card.style.position = 'relative';
            card.appendChild(glow);
            
            setTimeout(() => {
                glow.style.opacity = '0';
                setTimeout(() => glow.remove(), 300);
            }, 100);
        });
    });
}

// ===== КАВАЙНЫЕ УВЕДОМЛЕНИЯ =====
function showCuteNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.position = 'fixed';
    notification.style.bottom = '30px';
    notification.style.right = '30px';
    notification.style.padding = '20px 30px';
    notification.style.background = type === 'success' 
        ? 'linear-gradient(145deg, #ffffff, #fff0f5)' 
        : 'linear-gradient(145deg, #ffe0e0, #ffc0c0)';
    notification.style.border = '3px solid ' + (type === 'success' ? '#ff69b4' : '#ff6b6b');
    notification.style.borderRadius = '25px';
    notification.style.boxShadow = '0 10px 40px rgba(255, 105, 180, 0.4)';
    notification.style.color = type === 'success' ? '#ff1493' : '#dc2626';
    notification.style.fontFamily = "'Fredoka', sans-serif";
    notification.style.fontWeight = '600';
    notification.style.fontSize = '1rem';
    notification.style.zIndex = '100000';
    notification.style.transform = 'translateX(150%)';
    notification.style.transition = 'transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
    notification.innerHTML = (type === 'success' ? '💖 ' : '⚠️ ') + message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    setTimeout(() => {
        notification.style.transform = 'translateX(150%)';
        setTimeout(() => notification.remove(), 500);
    }, 4000);
}

// ===== ЭФФЕКТ КОНФЕТТИ ДЛЯ ПОБЕДЫ =====
function createConfetti() {
    const colors = ['#ffb7c5', '#ff69b4', '#ff1493', '#e6e6fa', '#98ff98', '#ffdab9', '#dda0dd'];
    const shapes = ['🌸', '💕', '✨', '💖', '🎀'];
    
    for (let i = 0; i < 80; i++) {
        const confetti = document.createElement('div');
        confetti.textContent = shapes[Math.floor(Math.random() * shapes.length)];
        confetti.style.position = 'fixed';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.top = '-10px';
        confetti.style.fontSize = (Math.random() * 15 + 15) + 'px';
        confetti.style.zIndex = '100000';
        confetti.style.pointerEvents = 'none';
        
        document.body.appendChild(confetti);
        
        const animation = confetti.animate([
            { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
            { transform: 'translateY(100vh) rotate(720deg)', opacity: 0 }
        ], {
            duration: Math.random() * 2000 + 2000,
            easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
        });
        
        animation.onfinish = () => confetti.remove();
    }
}

// ===== РАДУЖНЫЙ ЭФФЕКТ ДЛЯ ПРИЗОВ =====
function initRainbowPrize() {
    const prizes = document.querySelectorAll('.result-prize');
    
    prizes.forEach(prize => {
        setInterval(() => {
            const hue = (Date.now() / 30) % 360;
            prize.style.filter = `drop-shadow(0 0 25px hsl(${hue}, 100%, 75%))`;
        }, 50);
    });
}

// ===== СЧЁТЧИК ДЛЯ ЧИСЕЛ С АНИМАЦИЕЙ =====
function animateNumber(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        
        if (current >= target) {
            element.textContent = target.toLocaleString();
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current).toLocaleString();
        }
    }, 16);
}

// ===== ЭФФЕКТ ПРИ СКРОЛЛЕ =====
function initScrollEffects() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                
                // Анимация чисел
                if (entry.target.classList.contains('stat-number')) {
                    const text = entry.target.textContent;
                    const target = parseInt(text.replace(/\D/g, ''));
                    if (!isNaN(target)) {
                        animateNumber(entry.target, target);
                    }
                }
            }
        });
    }, {
        threshold: 0.1
    });
    
    document.querySelectorAll('.stat-card, .tank-tab, .edit-card').forEach(el => {
        el.classList.add('scroll-animate');
        observer.observe(el);
    });
}

// ===== ДОБАВЛЯЕМ CSS ДЛЯ SCROLL АНИМАЦИИ =====
function addScrollAnimationCSS() {
    const style = document.createElement('style');
    style.textContent = `
        .scroll-animate {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }
        
        .scroll-animate.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .stat-number {
            transition: all 0.3s;
        }
        
        .stat-number:hover {
            transform: scale(1.1);
        }
    `;
    document.head.appendChild(style);
}

// ===== ИНИЦИАЛИЗАЦИЯ ВСЕХ ЭФФЕКТОВ =====
function initAllEffects() {
    // Создаем лепестки сакуры
    createSakuraPetals();
    
    // Добавляем анимацию сердечек
    addHeartAnimation();
    
    // Добавляем CSS для скролла
    addScrollAnimationCSS();
    
    // Инициализируем эффекты после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initButtonShine();
            initWobbleEffect();
            initFloatingEmojis();
            initCardGlow();
            initScrollEffects();
            initRainbowPrize();
        });
    } else {
        initButtonShine();
        initWobbleEffect();
        initFloatingEmojis();
        initCardGlow();
        initScrollEffects();
        initRainbowPrize();
    }
    
    // Эффект сердечек при клике
    document.addEventListener('click', createHeartEffect);
}

// ===== УТИЛИТЫ =====
function random(min, max) {
    return Math.random() * (max - min) + min;
}

function randomPinkColor() {
    const colors = ['#ffb7c5', '#ff69b4', '#ff1493', '#ffcce0', '#dda0dd'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// ===== ЗАПУСК =====
initAllEffects();

// ===== ЭКСПОРТ ФУНКЦИЙ =====
window.animeEffects = {
    createConfetti,
    showCuteNotification,
    randomPinkColor
};
