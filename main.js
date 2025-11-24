/**
 * SERSOLTEC - Main JavaScript
 * Ogólne funkcjonalności i interakcje
 */

// ========================================
// RESET STATE ON PAGE LOAD
// ========================================

window.addEventListener('load', function() {
    const mainNav = document.getElementById('mainNav');
    if (mainNav) {
        mainNav.classList.add('hidden');
    }
});

// ========================================
// MOBILE MENU
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    
    if (menuToggle && mainNav) {
        // Na załadowaniu - menu schowane na mobile
        if (window.innerWidth <= 768) {
            mainNav.classList.add('hidden');
        }
        
        // Toggle menu na klikniecie hamburgera
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            mainNav.classList.toggle('hidden');
        });
        
        // Zamknij menu po kliknięciu na link
        mainNav.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                mainNav.classList.add('hidden');
            });
        });
        
        // Zamknij menu klikając poza nim
        document.addEventListener('click', function(e) {
            if (!menuToggle.contains(e.target) && !mainNav.contains(e.target)) {
                mainNav.classList.add('hidden');
            }
        });
        
        // Resetuj menu przy resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                mainNav.classList.remove('hidden');
            } else {
                mainNav.classList.add('hidden');
            }
        });
    }
});

// ========================================
// SMOOTH SCROLL
// ========================================

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({ behavior: 'smooth' });
        }
    });
});

// ========================================
// FORM VALIDATION
// ========================================

function validateForm(form) {
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
        } else {
            input.classList.remove('error');
        }
    });
    
    return isValid;
}

document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
        }
    });
});

// ========================================
// LANGUAGE SWITCHER
// ========================================

document.querySelectorAll('.lang-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (this.tagName === 'A') {
            // Przenieś język do URL jako query parameter
            const lang = this.getAttribute('href').split('lang=')[1];
            if (lang) {
                const url = new URL(window.location);
                url.searchParams.set('lang', lang);
                window.location = url.toString();
            }
        }
    });
});

// ========================================
// IMAGE LAZY LOADING
// ========================================

if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    observer.unobserve(img);
                }
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

// ========================================
// RESPONSIVE TABLE
// ========================================

function makeTablesResponsive() {
    document.querySelectorAll('table').forEach(table => {
        table.classList.add('responsive-table');
        table.addEventListener('scroll', function() {
            // Przesunięcie w boczną
        });
    });
}

makeTablesResponsive();

// ========================================
// PRODUCT FILTER
// ========================================

function initProductFilters() {
    const filters = document.querySelectorAll('.filter-select, .filter-input');
    
    filters.forEach(filter => {
        filter.addEventListener('change', function() {
            this.form.submit();
        });
    });
}

initProductFilters();

// ========================================
// ADD TO CART / INQUIRY
// ========================================

function addToInquiry(productId, productName) {
    const inquiry = JSON.parse(localStorage.getItem('inquiry') || '{}');
    
    if (!inquiry.items) {
        inquiry.items = [];
    }
    
    // Sprawdź czy produkt już jest w zapytaniu
    const exists = inquiry.items.some(item => item.id === productId);
    
    if (!exists) {
        inquiry.items.push({
            id: productId,
            name: productName,
            addedAt: new Date().toISOString()
        });
        
        localStorage.setItem('inquiry', JSON.stringify(inquiry));
        showNotification('Produkt dodany do zapytania');
    } else {
        showNotification('Produkt już w zapytaniu');
    }
}

function getInquiry() {
    return JSON.parse(localStorage.getItem('inquiry') || '{}');
}

function clearInquiry() {
    localStorage.removeItem('inquiry');
}

// ========================================
// NOTIFICATIONS
// ========================================

function showNotification(message, type = 'success', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background-color: ${type === 'success' ? '#4caf50' : '#f44336'};
        color: white;
        border-radius: 4px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideIn 0.3s ease-out;
        font-weight: 500;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out';
        setTimeout(() => notification.remove(), 300);
    }, duration);
}

// ========================================
// ANIMATIONS
// ========================================

const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
`;
document.head.appendChild(style);

// ========================================
// UTILITY FUNCTIONS
// ========================================

/**
 * Format ceny
 */
function formatPrice(price) {
    return new Intl.NumberFormat('pl-PL', {
        style: 'currency',
        currency: 'EUR'
    }).format(price);
}

/**
 * Debounce function
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Get URL query parameter
 */
function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

/**
 * Scroll to top
 */
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Dodaj przycisk scroll to top
window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        let scrollBtn = document.getElementById('scrollToTopBtn');
        if (!scrollBtn) {
            scrollBtn = document.createElement('button');
            scrollBtn.id = 'scrollToTopBtn';
            scrollBtn.className = 'btn btn-primary';
            scrollBtn.textContent = '↑';
            scrollBtn.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                z-index: 999;
                width: 50px;
                height: 50px;
                padding: 0;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                font-size: 1.5rem;
                transition: opacity 0.3s;
            `;
            scrollBtn.addEventListener('click', scrollToTop);
            document.body.appendChild(scrollBtn);
        }
    }
});

// ========================================
// INIT
// ========================================

console.log('✓ Sersoltec JS loaded');
