/* =========================================
   FEEDLEARN UI INTERACTIONS
   ========================================= */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const toggleBtn = document.querySelector('.mobile-toggle');
    const nav = document.querySelector('.site-nav');
    
    if (toggleBtn && nav) {
        toggleBtn.addEventListener('click', () => {
            const isOpen = nav.classList.contains('active');
            
            nav.classList.toggle('active');
            
            // Toggle icon or state if needed
            toggleBtn.setAttribute('aria-expanded', !isOpen);
            toggleBtn.innerHTML = isOpen ? '☰' : '✕';
        });

        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!nav.contains(e.target) && !toggleBtn.contains(e.target) && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggleBtn.innerHTML = '☰';
            }
        });
    }

    // 2. Add Fade-in Animation to Cards (Staggered) -- REMOVED to prevent visibility issues
    /*
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        // card.style.opacity = '0'; // CAUSES INVISIBILITY if animation fails
        // card.style.animation = `fadeIn 0.5s ease-out forwards ${index * 0.1}s`;
    });
    */

    // 3. Table Row Hover Highlight (JS Fallback for complex tables)
    // Most handled by CSS, but we can add click ripples if we want later.

    // 4. Form Input Focus Glow (visual enhancement)
    const inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(input => {
        input.addEventListener('focus', () => {
            input.parentElement.classList.add('input-focused');
        });
        input.addEventListener('blur', () => {
            input.parentElement.classList.remove('input-focused');
        });
    });

    // 5. Toast / Alert dismiss
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // If we had a close button
        const close = alert.querySelector('.close');
        if (close) {
            close.addEventListener('click', () => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }
    });
});
