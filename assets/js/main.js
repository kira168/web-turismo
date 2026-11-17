document.addEventListener('DOMContentLoaded', function () {

    // ── Image fallback: replace broken images with a styled placeholder ──────
    const IMG_FALLBACKS = {
        'machu':    'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=600&q=80',
        'cusco':    'https://images.unsplash.com/photo-1580619305218-8423a7ef79b4?w=600&q=80',
        'amazon':   'https://images.unsplash.com/photo-1516026672322-bc52d61a55d5?w=600&q=80',
        'lima':     'https://images.unsplash.com/photo-1585464231875-d9ef1f5ad396?w=600&q=80',
        'puno':     'https://images.unsplash.com/photo-1548013146-72479768bada?w=600&q=80',
        'default':  'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=600&q=80'
    };

    function getFallbackUrl(img) {
        const alt = (img.alt || '').toLowerCase();
        const src = (img.src || '').toLowerCase();
        for (const [key, url] of Object.entries(IMG_FALLBACKS)) {
            if (alt.includes(key) || src.includes(key)) return url;
        }
        return IMG_FALLBACKS['default'];
    }

    function handleImgError(img) {
        if (img.dataset.fallbackTried) return; // prevent infinite loop
        img.dataset.fallbackTried = '1';
        const fallback = getFallbackUrl(img);
        if (img.src !== fallback) {
            img.src = fallback;
        } else {
            // Last resort: SVG gradient placeholder
            img.style.cssText = 'background:linear-gradient(135deg,#be0d2d,#8b0d1e);object-fit:cover;';
            img.removeAttribute('src');
        }
    }

    // Apply to all existing images
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', () => handleImgError(img));
        img.addEventListener('load', () => img.classList.add('loaded'));
        // Check already-broken images
        if (img.complete && img.naturalWidth === 0) handleImgError(img);
        // Mark already-loaded images
        if (img.complete && img.naturalWidth > 0) img.classList.add('loaded');
    });

    // Observe dynamically added images (admin panel, AJAX, etc.)
    const imgObserver = new MutationObserver(mutations => {
        mutations.forEach(m => m.addedNodes.forEach(node => {
            if (node.tagName === 'IMG') {
                node.addEventListener('error', () => handleImgError(node));
            } else if (node.querySelectorAll) {
                node.querySelectorAll('img').forEach(img => {
                    img.addEventListener('error', () => handleImgError(img));
                });
            }
        }));
    });
    imgObserver.observe(document.body, { childList: true, subtree: true });
    // ─────────────────────────────────────────────────────────────────────────

    // Hero search functionality
    const heroSearch = document.querySelector('.hero-search');
    if (heroSearch) {
        const searchInput = heroSearch.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    heroSearch.submit();
                }
            });
        }
    }

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Enhanced form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error-shake');
                    isValid = false;
                    setTimeout(() => field.classList.remove('error-shake'), 500);
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor, complete todos los campos requeridos.');
            }
        });
    });

    // Image lazy loading
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Package card hover effects
    const packageCards = document.querySelectorAll('.card-package');
    packageCards.forEach(card => {
        card.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-15px) rotate(1deg)';
        });

        card.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0) rotate(0deg)';
        });
    });

    // Testimonial carousel (if multiple testimonials)
    const testimonials = document.querySelectorAll('.testimonial-card');
    if (testimonials.length > 3) {
        let currentIndex = 0;
        const testimonialContainer = document.querySelector('.testimonials-grid');

        function showTestimonial(index) {
            testimonials.forEach((testimonial, i) => {
                testimonial.style.display = i === index ? 'block' : 'none';
            });
        }

        // Auto-rotate testimonials
        setInterval(() => {
            currentIndex = (currentIndex + 1) % testimonials.length;
            showTestimonial(currentIndex);
        }, 5000);
    }

    // Enhanced button interactions
    const buttons = document.querySelectorAll('.button-primary, .button-secondary');
    buttons.forEach(button => {
        button.addEventListener('mousedown', function () {
            this.style.transform = 'scale(0.95)';
        });

        button.addEventListener('mouseup', function () {
            this.style.transform = '';
        });

        button.addEventListener('mouseleave', function () {
            this.style.transform = '';
        });
    });

    // Loading states for forms
    const formsWithLoading = document.querySelectorAll('form');
    formsWithLoading.forEach(form => {
        form.addEventListener('submit', function () {
            if (this.checkValidity()) {
                const submitButton = this.querySelector('button[type="submit"], input[type="submit"]');
                if (submitButton) {
                    // Small delay to allow the form to submit before disabling
                    setTimeout(() => {
                        submitButton.innerHTML = '<span class="spinner"></span> Procesando...';
                        submitButton.disabled = true;
                    }, 50);
                }
            }
        });
    });

    // Success animations
    const successMessages = document.querySelectorAll('.success-message');
    successMessages.forEach(message => {
        message.classList.add('success-check');
    });

    // Mobile menu toggle (if exists)
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const navMenu = document.querySelector('.nav-menu');

    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function () {
            navMenu.classList.toggle('active');
            this.classList.toggle('active');
        });
    }

    // Asistente virtual
    const assistantOutput = document.getElementById('assistantOutput');
    const assistantButtons = document.querySelectorAll('[data-suggestion]');
    const assistantInput = document.getElementById('assistantInput');
    const assistantAsk = document.getElementById('assistantAsk');

    if (assistantOutput && assistantButtons.length) {
        const suggestions = {
            'avoid-rain': {
                title: 'Recomendación para evitar lluvia',
                message: 'Para evitar temporadas lluviosas, te recomendamos destinos de altura como Cusco y Machu Picchu. Haz clic para ver paquetes disponibles.',
                action: 'paquetes.php?category=Arqueológico&destino=Machu%20Picchu'
            },
            'culture': {
                title: 'Viaje cultural',
                message: 'Los mejores viajes culturales combinan historia y tradiciones. Te sugerimos explorar paquetes en Cusco y el Valle Sagrado.',
                action: 'paquetes.php?category=Cultural'
            },
            'adventure': {
                title: 'Aventura en la naturaleza',
                message: 'Si buscas naturaleza, elige destinos como Iquitos y la Amazonía. Te muestro paquetes con mucho contacto con la naturaleza.',
                action: 'paquetes.php?category=Naturaleza'
            },
            'beach': {
                title: 'Playa y relajación',
                message: 'Para un viaje de playa con buena climatología, revisa nuestros paquetes en Lima y la costa peruana.',
                action: 'paquetes.php?destino=Lima'
            }
        };

        const showAssistantResponse = (title, message, action) => {
            assistantOutput.innerHTML = `
                <strong>${title}</strong>
                <p>${message}</p>
                ${action ? `<a href="${action}" class="button button-primary">Ver paquetes</a>` : ''}
            `;
        };

        assistantButtons.forEach(button => {
            button.addEventListener('click', function () {
                const key = this.dataset.suggestion;
                const suggestion = suggestions[key];
                if (!suggestion) return;
                showAssistantResponse(suggestion.title, suggestion.message, suggestion.action);
                localStorage.setItem('travelPreference', key);
            });
        });

        if (assistantAsk && assistantInput) {
            assistantAsk.addEventListener('click', function () {
                const question = assistantInput.value.trim();
                if (!question) {
                    assistantOutput.innerHTML = '<strong>Escribe una pregunta para recibir recomendaciones de viaje.</strong>';
                    return;
                }

                let reply = '¡Buena pregunta! Revisa nuestros paquetes destacados y contáctanos si quieres una ruta personalizada.';
                let action = 'paquetes.php';

                if (question.match(/playa|relajación|mar|arena/i)) {
                    reply = 'Si buscas playa, te recomiendo nuestros paquetes en Lima y la costa norte. Disfruta sol, descanso y cultura costera.';
                    action = 'paquetes.php?destino=Lima';
                } else if (question.match(/cultura|historia|ruinas|museo/i)) {
                    reply = 'Para viajes culturales, Cusco y el Valle Sagrado son ideales. Encuentra paquetes con tours históricos y experiencias auténticas.';
                    action = 'paquetes.php?category=Cultural';
                } else if (question.match(/aventura|selva|amazonía|trek/i)) {
                    reply = 'Aventura total: descubre la Amazonía y destinos de trekking con paquetes hechos para experimentar la naturaleza.';
                    action = 'paquetes.php?category=Naturaleza';
                } else if (question.match(/familia|niños|familiar/i)) {
                    reply = 'Tenemos paquetes familiares cómodos y seguros para viajar con niños. Explora rutas con actividades para todos.';
                }

                showAssistantResponse('Tu asistente de viaje responde', reply, action);
            });
        }
    }

    // Registro de leads cuando el usuario completa datos en el formulario de reserva
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        let leadTrackTimeout;

        const trackLead = () => {
            clearTimeout(leadTrackTimeout);
            leadTrackTimeout = setTimeout(() => {
                const formData = new FormData();
                formData.append('paquete_id', bookingForm.dataset.packageId || '');
                formData.append('nombre', bookingForm.querySelector('input[name="nombre_cliente"]')?.value || '');
                formData.append('email', bookingForm.querySelector('input[name="email_cliente"]')?.value || '');
                formData.append('telefono', bookingForm.querySelector('input[name="telefono"]')?.value || '');
                formData.append('source', 'reserva_iniciada');

                fetch('lead.php', {
                    method: 'POST',
                    body: formData,
                }).catch(() => {});
            }, 1000);
        };

        const inputs = bookingForm.querySelectorAll('input[name="nombre_cliente"], input[name="email_cliente"], input[name="telefono"]');
        inputs.forEach(input => {
            input.addEventListener('change', trackLead);
            input.addEventListener('blur', trackLead);
        });
    }

    // Parallax effect for hero section
    const heroSection = document.querySelector('.hero');
    if (heroSection) {
        window.addEventListener('scroll', function () {
            const scrolled = window.pageYOffset;
            const rate = scrolled * -0.5;
            heroSection.style.transform = `translateY(${rate}px)`;
        });
    }
});
