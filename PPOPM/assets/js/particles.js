function initParticles(containerSelector, options = {}) {
    const container = document.querySelector(containerSelector);
    if (!container) return;

    const oldCanvas = container.querySelector('.particles-canvas');
    if (oldCanvas) {
        oldCanvas.remove();
    }

    const canvas = document.createElement('canvas');
    canvas.classList.add('particles-canvas');
    canvas.style.position = 'absolute';
    canvas.style.top = '0';
    canvas.style.left = '0';
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    canvas.style.zIndex = '0'; // Di belakang konten (konten z-10)
    container.appendChild(canvas);

    const ctx = canvas.getContext('2d');

    function resizeCanvas() {
        if (container) {
            canvas.width = container.offsetWidth;
            canvas.height = container.offsetHeight;
        }
    }

    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    // Opsi yang bisa dikonfigurasi
    const count = options.count || 80;
    const colors = options.colors || ['#10b981', '#fbbf24', '#ffffff'];
    const speed = options.speed || 0.5;
    const connectDistance = options.connectDistance || 120;

    const particlesArray = [];

    for (let i = 0; i < count; i++) {
        const size = Math.random() * 3 + 1;
        const x = Math.random() * canvas.width;
        const y = Math.random() * canvas.height;
        const directionX = (Math.random() * 1.0) - 0.5;
        const directionY = (Math.random() * 1.0) - 0.5;

        particlesArray.push({
            x,
            y,
            size,
            directionX,
            directionY,
            color: colors[Math.floor(Math.random() * colors.length)]
        });
    }

    let animationFrameId;

    function animateParticles() {
        if (!ctx || !canvas) return; // Pastikan canvas masih ada
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        for (let i = 0; i < particlesArray.length; i++) {
            const particle = particlesArray[i];

            ctx.fillStyle = particle.color;
            ctx.globalAlpha = 0.8;
            ctx.beginPath();
            ctx.arc(particle.x, particle.y, particle.size, 0, Math.PI * 2);
            ctx.fill();

            particle.x += particle.directionX * speed;
            particle.y += particle.directionY * speed;

            if (particle.x < 0 || particle.x > canvas.width) {
                particle.directionX *= -1;
            }
            if (particle.y < 0 || particle.y > canvas.height) {
                particle.directionY *= -1;
            }

            connectParticles(particle, i);
        }

        animationFrameId = requestAnimationFrame(animateParticles);
    }

    function connectParticles(particle, index) {
        for (let j = index + 1; j < particlesArray.length; j++) {
            const otherParticle = particlesArray[j];
            const dx = particle.x - otherParticle.x;
            const dy = particle.y - otherParticle.y;
            const distance = Math.sqrt(dx * dx + dy * dy);

            if (distance < connectDistance) {
                const opacity = 1 - (distance / connectDistance);
                ctx.strokeStyle = `rgba(16, 185, 129, ${opacity * 0.3})`;
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(particle.x, particle.y);
                ctx.lineTo(otherParticle.x, otherParticle.y);
                ctx.stroke();
            }
        }
    }

    animateParticles();

    // Mengembalikan fungsi cleanup
    return () => {
        window.removeEventListener('resize', resizeCanvas);
        cancelAnimationFrame(animationFrameId);
        if (container && canvas) {
            try { container.removeChild(canvas); } catch(e) {}
        }
    };
}
