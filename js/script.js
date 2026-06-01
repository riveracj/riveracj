document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // NAVIGATION
    // ==========================================
    const navbar = document.getElementById('navbar');
    const hamburger = document.getElementById('hamburger');
    const navMenu = document.getElementById('nav-menu');
    const navLinks = document.querySelectorAll('.nav-link');

    hamburger.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        const spans = hamburger.querySelectorAll('span');
        if (navMenu.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translateY(10px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translateY(-10px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            const spans = hamburger.querySelectorAll('span');
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        });
    });

    // ==========================================
    // HERO SCROLLING GALLERY
    // ==========================================
    (function() {
        const container = document.getElementById('heroGallery');
        const allImages = [
            'ai/aiclipper.png', 'ai/youtubetoviral.png', 'ai/cartoon_dev.png',
            'app/qrfast.png', 'app/filemanager.png', 'app/runapp.jpg', 'app/pjtravelntours.png',
            'web/2go-logistics.png', 'web/wearhause.png', 'web/wresletech.png',
            'web/schogms.png', 'web/gsms.png',
            'web/generator.png', 'web/qrbase.png'
        ];

        for (let c = 0; c < 3; c++) {
            const track = document.createElement('div');
            track.className = 'gallery-track' + (c % 2 === 0 ? ' fast' : ' slower');
            const rotated = [...allImages.slice(c), ...allImages.slice(0, c)];
            const items = [...rotated, ...rotated];
            items.forEach(src => {
                const div = document.createElement('div');
                div.className = 'gallery-thumb';
                div.style.backgroundImage = `url('images/${src}')`;
                track.appendChild(div);
            });
            container.appendChild(track);
        }
    })();

    // ==========================================
    // THEME TOGGLE
    // ==========================================
    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = themeToggle.querySelector('i');

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('theme', theme);
        themeIcon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
    }

    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const current = document.documentElement.getAttribute('data-theme');
        setTheme(current === 'dark' ? 'light' : 'dark');
    });

    // ==========================================
    // TYPING EFFECT
    // ==========================================
    function typeWriter(element, text, speed = 60, callback) {
        let i = 0;
        element.textContent = '';
        function type() {
            if (i < text.length) {
                element.textContent += text.charAt(i);
                i++;
                setTimeout(type, speed);
            } else if (callback) {
                callback();
            }
        }
        type();
    }

    const subtitleEl = document.getElementById('typed-text');
    const originalText = "Full-Stack Developer | Flutter Specialist | Cross-Platform Expert";

    setTimeout(() => {
        typeWriter(subtitleEl, originalText, 50);
    }, 1200);

    // ==========================================
    // PARTICLES
    // ==========================================
    function createParticles() {
        const hero = document.querySelector('.hero');
        const particleCount = 40;
        for (let i = 0; i < particleCount; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            Object.assign(particle.style, {
                top: `${Math.random() * 100}%`,
                left: `${Math.random() * 100}%`,
                animation: `float ${5 + Math.random() * 10}s ${Math.random() * 5}s infinite`,
                width: `${2 + Math.random() * 4}px`,
                height: `${2 + Math.random() * 4}px`
            });
            hero.appendChild(particle);
        }
    }
    createParticles();

    // ==========================================
    // CUSTOM CURSOR
    // ==========================================
    const cursor = document.createElement('div');
    cursor.className = 'custom-cursor';
    document.body.appendChild(cursor);

    let mouseX = 0, mouseY = 0, cursorX = 0, cursorY = 0;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    function animateCursor() {
        cursorX += (mouseX - cursorX) * 0.15;
        cursorY += (mouseY - cursorY) * 0.15;
        cursor.style.left = `${cursorX}px`;
        cursor.style.top = `${cursorY}px`;
        requestAnimationFrame(animateCursor);
    }
    animateCursor();

    document.querySelectorAll('a, button, .skill-card, .project-card, input, textarea').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.classList.add('hovering'));
        el.addEventListener('mouseleave', () => cursor.classList.remove('hovering'));
    });

    // ==========================================
    // NAVBAR SCROLL + ACTIVE LINK
    // ==========================================
    window.addEventListener('scroll', () => {
        if (window.scrollY > 100) {
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.2)';
        } else {
            navbar.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1)';
        }

        let current = '';
        const sections = document.querySelectorAll('section');
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (window.scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });

        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').slice(1) === current) {
                link.classList.add('active');
            }
        });
    });

    // ==========================================
    // BACK TO TOP
    // ==========================================
    const backToTopBtn = document.getElementById('back-to-top');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopBtn.classList.add('show');
        } else {
            backToTopBtn.classList.remove('show');
        }
    });

    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    // ==========================================
    // SCROLL REVEAL (Intersection Observer)
    // ==========================================
    const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -80px 0px' };

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
                revealObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.skill-card, .project-card, .about-content, .contact-content, .about-stats, .contact-details').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        revealObserver.observe(el);
    });

    // ==========================================
    // PROJECT MODAL (Private repo notification)
    // ==========================================
    const modal = document.getElementById('projectModal');
    const modalClose = document.getElementById('modalClose');

    document.querySelectorAll('.project-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.dataset.type === 'private') {
                e.preventDefault();
                modal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        });
    });

    modalClose.addEventListener('click', () => {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.classList.contains('active')) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // ==========================================
    // SMOOTH SCROLL
    // ==========================================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ==========================================
    // CONSOLE
    // ==========================================
    console.log('%c👋 Welcome to my portfolio!', 'color: #6366f1; font-size: 20px; font-weight: bold;');
    console.log('%cLike what you see? Let\'s work together!', 'color: #8b5cf6; font-size: 14px;');
    console.log('Portfolio loaded successfully! 🚀');

});
