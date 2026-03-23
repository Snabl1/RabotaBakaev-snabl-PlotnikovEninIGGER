/* ===== CYBERPUNK PARTICLES & EFFECTS ===== */
/* 🔥 САМЫЕ БЕЗУМНЫЕ ЭФФЕКТЫ 🔥 */

// ===== ЧАСТИЦЫ =====
function createParticles() {
    const particlesContainer = document.createElement('div');
    particlesContainer.id = 'particlesContainer';
    particlesContainer.className = 'particles';
    document.body.appendChild(particlesContainer);

    const particleCount = 50;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDelay = Math.random() * 15 + 's';
        particle.style.animationDuration = (Math.random() * 10 + 10) + 's';
        
        // Случайный цвет для частиц
        const colors = [
            'var(--neon-pink)',
            'var(--neon-blue)',
            'var(--neon-green)',
            'var(--neon-yellow)',
            'var(--neon-purple)',
            'var(--neon-orange)'
        ];
        const randomColor = colors[Math.floor(Math.random() * colors.length)];
        particle.style.background = randomColor;
        particle.style.boxShadow = `0 0 10px ${randomColor}, 0 0 20px ${randomColor}`;
        
        particlesContainer.appendChild(particle);
    }
}

// ===== ЭФФЕКТ ПАРАЛЛАКСА ДЛЯ МЫШИ =====
function initParallax() {
    document.addEventListener('mousemove', (e) => {
        const mouseX = e.clientX / window.innerWidth;
        const mouseY = e.clientY / window.innerHeight;
        
        // Двигаем частицы
        const particles = document.querySelectorAll('.particle');
        particles.forEach((particle, index) => {
            const speed = (index % 5 + 1) * 0.5;
            const x = (mouseX - 0.5) * speed * 20;
            const y = (mouseY - 0.5) * speed * 20;
            particle.style.transform = `translate(${x}px, ${y}px)`;
        });
        
        // Двигаем фон
        const bg = document.body::before;
        if (bg) {
            document.body.style.backgroundPosition = `${mouseX * 20}px ${mouseY * 20}px`;
        }
    });
}

// ===== ЭФФЕКТ ПРИ КЛИКЕ =====
function createClickEffect(e) {
    const clickCircle = document.createElement('div');
    clickCircle.style.position = 'fixed';
    clickCircle.style.left = e.clientX + 'px';
    clickCircle.style.top = e.clientY + 'px';
    clickCircle.style.width = '20px';
    clickCircle.style.height = '20px';
    clickCircle.style.borderRadius = '50%';
    clickCircle.style.background = 'radial-gradient(circle, var(--neon-blue), transparent)';
    clickCircle.style.boxShadow = '0 0 20px var(--neon-blue), 0 0 40px var(--neon-blue)';
    clickCircle.style.transform = 'translate(-50%, -50%)';
    clickCircle.style.pointerEvents = 'none';
    clickCircle.style.zIndex = '99999';
    clickCircle.style.animation = 'clickExpand 0.6s ease-out forwards';
    
    document.body.appendChild(clickCircle);
    
    setTimeout(() => {
        clickCircle.remove();
    }, 600);
}

// Добавляем анимацию клика в CSS
function addClickAnimation() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes clickExpand {
            0% {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
            100% {
                transform: translate(-50%, -50%) scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

// ===== ЭФФЕКТ ПРИ НАВЕДЕНИИ НА КНОПКИ =====
function initButtonEffects() {
    const buttons = document.querySelectorAll('.btn, .menu a');
    
    buttons.forEach(button => {
        button.addEventListener('mouseenter', (e) => {
            const rect = button.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            const glow = document.createElement('div');
            glow.style.position = 'absolute';
            glow.style.left = x + 'px';
            glow.style.top = y + 'px';
            glow.style.width = '100px';
            glow.style.height = '100px';
            glow.style.borderRadius = '50%';
            glow.style.background = 'radial-gradient(circle, rgba(0, 255, 255, 0.3), transparent)';
            glow.style.transform = 'translate(-50%, -50%)';
            glow.style.pointerEvents = 'none';
            glow.style.transition = 'all 0.3s';
            
            button.appendChild(glow);
            
            setTimeout(() => {
                glow.style.opacity = '0';
                setTimeout(() => glow.remove(), 300);
            }, 100);
        });
    });
}

// ===== ЭФФЕКТ ПЕЧАТНОЙ МАШИНКИ ДЛЯ ЗАГОЛОВКОВ =====
function typeWriterEffect(element, text, speed = 50) {
    let i = 0;
    element.textContent = '';
    
    function type() {
        if (i < text.length) {
            element.textContent += text.charAt(i);
            i++;
            setTimeout(type, speed);
        }
    }
    
    type();
}

// ===== ЭФФЕКТ ГЛИТЧА ДЛЯ ТЕКСТА =====
function initGlitchEffect() {
    const glitchElements = document.querySelectorAll('.glitch');
    
    glitchElements.forEach(element => {
        const originalText = element.textContent;
        
        setInterval(() => {
            if (Math.random() > 0.95) {
                const chars = '!@#$%^&*()_+-=[]{}|;:,.<>?';
                let glitched = '';
                
                for (let i = 0; i < originalText.length; i++) {
                    if (Math.random() > 0.7) {
                        glitched += chars[Math.floor(Math.random() * chars.length)];
                    } else {
                        glitched += originalText[i];
                    }
                }
                
                element.textContent = glitched;
                
                setTimeout(() => {
                    element.textContent = originalText;
                }, 100);
            }
        }, 100);
    });
}

// ===== ЭФФЕКТ ВОЛНЫ ДЛЯ ТАБЛИЦ =====
function initTableWave() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tr');
        
        rows.forEach((row, index) => {
            row.style.transitionDelay = (index * 0.05) + 's';
        });
    });
}

// ===== ЭФФЕКТ СЧЁТЧИКА ДЛЯ ЧИСЕЛ =====
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
                    const target = parseInt(entry.target.textContent.replace(/\D/g, ''));
                    if (!isNaN(target)) {
                        animateNumber(entry.target, target);
                    }
                }
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Наблюдаем за элементами
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
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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

// ===== ЭФФЕКТ РАДУГИ ДЛЯ ПРИЗОВ =====
function initRainbowEffect() {
    const prizes = document.querySelectorAll('.result-prize');
    
    prizes.forEach(prize => {
        setInterval(() => {
            const hue = (Date.now() / 20) % 360;
            prize.style.filter = `drop-shadow(0 0 20px hsl(${hue}, 100%, 50%))`;
        }, 50);
    });
}

// ===== ЗВУКОВЫЕ ЭФФЕКТЫ (опционально) =====
const sounds = {
    hover: null,
    click: null,
    win: null
};

function initSounds() {
    // Можно добавить звуковые эффекты при желании
    // sounds.hover = new Audio('sounds/hover.mp3');
    // sounds.click = new Audio('sounds/click.mp3');
    // sounds.win = new Audio('sounds/win.mp3');
}

// ===== ИНИЦИАЛИЗАЦИЯ ВСЕХ ЭФФЕКТОВ =====
function initAllEffects() {
    // Создаем частицы
    createParticles();
    
    // Добавляем CSS для клика
    addClickAnimation();
    
    // Добавляем CSS для скролла
    addScrollAnimationCSS();
    
    // Инициализируем эффекты после загрузки DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initButtonEffects();
            initTableWave();
            initScrollEffects();
            initGlitchEffect();
            initRainbowEffect();
            initSounds();
        });
    } else {
        initButtonEffects();
        initTableWave();
        initScrollEffects();
        initGlitchEffect();
        initRainbowEffect();
        initSounds();
    }
    
    // Эффект клика
    document.addEventListener('click', createClickEffect);
}

// ===== УТИЛИТЫ =====
// Случайное число
function random(min, max) {
    return Math.random() * (max - min) + min;
}

// Случайный цвет из палитры
function randomNeonColor() {
    const colors = [
        '#ff00ff', '#00ffff', '#00ff00', '#ffff00', '#ff6600', '#bf00ff', '#ff0066'
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// ===== ЗАПУСК =====
initAllEffects();

// ===== ДОПОЛНИТЕЛЬНЫЕ ФИЧИ ДЛЯ СПИННЕРА =====
function updateSpinUI() {
    const spinCounter = document.getElementById('spinCounter');
    const levelCounter = document.getElementById('levelCounter');
    const xpCounter = document.getElementById('xpCounter');
    
    if (spinCounter) {
        const spins = parseInt(localStorage.getItem('totalSpins') || '0');
        spinCounter.textContent = spins;
    }
    
    if (levelCounter) {
        const level = parseInt(localStorage.getItem('playerLevel') || '1');
        levelCounter.textContent = level;
    }
    
    if (xpCounter) {
        const xp = parseInt(localStorage.getItem('playerXP') || '0');
        const nextLevel = parseInt(localStorage.getItem('nextLevelXP') || '100');
        xpCounter.textContent = `${xp}/${nextLevel}`;
    }
}

// Обновляем UI спиннера при загрузке
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateSpinUI);
} else {
    updateSpinUI();
}

// ===== ЭФФЕКТ КОНФЕТТИ ДЛЯ ПОБЕДЫ =====
function createConfetti() {
    const colors = ['#ff00ff', '#00ffff', '#00ff00', '#ffff00', '#ff6600', '#bf00ff', '#ff0066'];
    
    for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.style.position = 'fixed';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.top = '-10px';
        confetti.style.width = random(8, 15) + 'px';
        confetti.style.height = random(8, 15) + 'px';
        confetti.style.background = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
        confetti.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
        confetti.style.boxShadow = '0 0 10px currentColor';
        confetti.style.zIndex = '100000';
        confetti.style.pointerEvents = 'none';
        
        document.body.appendChild(confetti);
        
        const animation = confetti.animate([
            { transform: `translateY(0) rotate(0deg)`, opacity: 1 },
            { transform: `translateY(100vh) rotate(${Math.random() * 720}deg)`, opacity: 0 }
        ], {
            duration: random(2000, 4000),
            easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
        });
        
        animation.onfinish = () => confetti.remove();
    }
}

// ===== ЭФФЕКТ ВЗРЫВА ДЛЯ СПИННЕРА =====
function createExplosion(x, y) {
    const colors = ['#ff00ff', '#00ffff', '#00ff00', '#ffff00', '#ff6600'];
    
    for (let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.style.position = 'fixed';
        particle.style.left = x + 'px';
        particle.style.top = y + 'px';
        particle.style.width = random(5, 12) + 'px';
        particle.style.height = random(5, 12) + 'px';
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];
        particle.style.borderRadius = '50%';
        particle.style.boxShadow = '0 0 15px currentColor';
        particle.style.zIndex = '100000';
        particle.style.pointerEvents = 'none';
        
        document.body.appendChild(particle);
        
        const angle = (Math.PI * 2 * i) / 30;
        const velocity = random(100, 200);
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;
        
        const animation = particle.animate([
            { transform: 'translate(0, 0) scale(1)', opacity: 1 },
            { transform: `translate(${vx}px, ${vy}px) scale(0)`, opacity: 0 }
        ], {
            duration: random(800, 1500),
            easing: 'cubic-bezier(0, 0.9, 0.57, 1)'
        });
        
        animation.onfinish = () => particle.remove();
    }
}

// Экспортируем функции для использования в других скриптах
window.cyberEffects = {
    createConfetti,
    createExplosion,
    updateSpinUI,
    randomNeonColor
};
