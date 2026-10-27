/**
 * ============================================================================
 * Vaccination Management System (VMS) - Aptech eProject
 * File: assets/js/animations.js
 * Description: IntersectionObserver Scroll Animations & Count-Up Engine
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ------------------------------------------------------------------------
    // 1. IntersectionObserver: Scroll-Triggered Fade-In Elements
    // ------------------------------------------------------------------------
    const scrollElements = document.querySelectorAll('.fade-in-on-scroll');

    if ('IntersectionObserver' in window && !prefersReducedMotion) {
        const scrollObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.15,
            rootMargin: '0px 0px -40px 0px'
        });

        scrollElements.forEach(function (el) {
            scrollObserver.observe(el);
        });
    } else {
        // Reduced motion fallback: show elements immediately
        scrollElements.forEach(function (el) {
            el.classList.add('is-visible');
        });
    }

    // ------------------------------------------------------------------------
    // 2. IntersectionObserver: Count-Up Statistics Engine
    // ------------------------------------------------------------------------
    const statCounters = document.querySelectorAll('[data-count-to]');

    if ('IntersectionObserver' in window && !prefersReducedMotion) {
        const counterObserver = new IntersectionObserver(function (entries, observer) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animateCountUp(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.2
        });

        statCounters.forEach(function (counter) {
            counterObserver.observe(counter);
        });
    } else {
        // Fallback for reduced motion or legacy browsers: immediately display target number
        statCounters.forEach(function (counter) {
            counter.textContent = counter.getAttribute('data-count-to') || '0';
        });
    }
    // ------------------------------------------------------------------------
    // 3. Parent Review Shuffle Carousel Engine
    // ------------------------------------------------------------------------
    initReviewCarousel(prefersReducedMotion);
});

/**
 * Initializes the stacked review shuffle carousel with auto-rotation and pause-on-hover/focus.
 *
 * @param {boolean} prefersReducedMotion True if user requested reduced motion
 */
function initReviewCarousel(prefersReducedMotion) {
    const carousel = document.querySelector('.review-carousel-wrap');
    if (!carousel) return;

    const slides = carousel.querySelectorAll('.review-slide');
    const dots = carousel.querySelectorAll('.carousel-dot');
    const prevBtn = carousel.querySelector('#carouselPrevBtn');
    const nextBtn = carousel.querySelector('#carouselNextBtn');

    if (slides.length <= 1) return;

    let currentIndex = 0;
    let timer = null;
    const intervalTime = 5500;

    function showSlide(index) {
        slides.forEach(function (slide, idx) {
            if (idx === index) {
                slide.classList.add('is-active');
            } else {
                slide.classList.remove('is-active');
            }
        });

        dots.forEach(function (dot, idx) {
            if (idx === index) {
                dot.classList.add('is-active');
            } else {
                dot.classList.remove('is-active');
            }
        });

        currentIndex = index;
    }

    function nextSlide() {
        let next = (currentIndex + 1) % slides.length;
        showSlide(next);
    }

    function prevSlide() {
        let prev = (currentIndex - 1 + slides.length) % slides.length;
        showSlide(prev);
    }

    function startAutoRotate() {
        if (!prefersReducedMotion && !timer) {
            timer = setInterval(nextSlide, intervalTime);
        }
    }

    function stopAutoRotate() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Attach button controls
    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            nextSlide();
            stopAutoRotate();
            startAutoRotate();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function () {
            prevSlide();
            stopAutoRotate();
            startAutoRotate();
        });
    }

    // Attach dot controls
    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            const index = parseInt(dot.getAttribute('data-slide-to'), 10);
            showSlide(index);
            stopAutoRotate();
            startAutoRotate();
        });
    });

    // Pause on Hover & Focus
    carousel.addEventListener('mouseenter', stopAutoRotate);
    carousel.addEventListener('mouseleave', startAutoRotate);
    carousel.addEventListener('focusin', stopAutoRotate);
    carousel.addEventListener('focusout', startAutoRotate);

    // Start auto-rotation
    startAutoRotate();
}

/**
 * Smoothly animates an integer count-up from 0 to the target number.
 *
 * @param {HTMLElement} element Target counter element with data-count-to attribute
 */
function animateCountUp(element) {
    const target = parseInt(element.getAttribute('data-count-to'), 10) || 0;
    const duration = 1400; // Animation duration in milliseconds
    const startTime = performance.now();

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        // Ease-out cubic formula for organic settling
        const easeOutProgress = 1 - Math.pow(1 - progress, 3);
        const currentCount = Math.floor(easeOutProgress * target);

        element.textContent = currentCount.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = target.toLocaleString();
        }
    }

    requestAnimationFrame(updateCounter);
}

