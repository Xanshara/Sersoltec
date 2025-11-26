/**
 * SERSOLTEC v2.4 - Product Reviews Frontend
 * Sprint 2.3: Reviews System
 * 
 * Handles all frontend interactions for product reviews:
 * - Display reviews
 * - Submit new review
 * - Mark reviews as helpful
 * - Star rating widget
 * - Sort and pagination
 */

(function() {
    'use strict';
    
    // Configuration
    const API_URL = '../api/reviews-api.php';
    let currentProductId = null;
    let currentPage = 1;
    let currentSort = 'newest';
    
    /**
     * Initialize reviews module
     */
    window.Reviews = {
        
        /**
         * Initialize for a specific product
         */
        init: function(productId) {
            currentProductId = productId;
            this.loadStats();
            this.loadReviews();
            this.initFormHandlers();
            this.initSortHandler();
        },
        
        /**
         * Load review statistics
         */
        loadStats: async function() {
            try {
                const response = await fetch(`${API_URL}?action=stats&product_id=${currentProductId}`);
                const result = await response.json();
                
                if (result.success) {
                    this.displayStats(result.data);
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        },
        
        /**
         * Display review statistics
         */
        displayStats: function(data) {
            const container = document.getElementById('review-stats');
            if (!container) return;
            
            const { total_reviews, average_rating, rating_distribution } = data;
            
            let html = `
                <div class="review-stats-summary">
                    <div class="average-rating">
                        <div class="rating-number">${average_rating}</div>
                        <div class="rating-stars">${this.renderStars(average_rating)}</div>
                        <div class="total-reviews">${total_reviews} ${total_reviews === 1 ? 'opinia' : 'opinii'}</div>
                    </div>
                    <div class="rating-breakdown">
            `;
            
            rating_distribution.forEach(dist => {
                html += `
                    <div class="rating-bar">
                        <span class="rating-label">${dist.rating} ★</span>
                        <div class="bar-container">
                            <div class="bar-fill" style="width: ${dist.percentage}%"></div>
                        </div>
                        <span class="rating-count">${dist.count}</span>
                    </div>
                `;
            });
            
            html += `
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        },
        
        /**
         * Load reviews list
         */
        loadReviews: async function(page = 1, sort = currentSort) {
            currentPage = page;
            currentSort = sort;
            
            const container = document.getElementById('reviews-list');
            if (!container) return;
            
            // Show loading
            container.innerHTML = '<div class="loading">Ładowanie opinii...</div>';
            
            try {
                const response = await fetch(
                    `${API_URL}?action=list&product_id=${currentProductId}&page=${page}&limit=10&sort=${sort}`
                );
                const result = await response.json();
                
                if (result.success) {
                    this.displayReviews(result.data.reviews, result.data.pagination);
                } else {
                    container.innerHTML = '<div class="error">Błąd podczas ładowania opinii.</div>';
                }
            } catch (error) {
                console.error('Error loading reviews:', error);
                container.innerHTML = '<div class="error">Błąd podczas ładowania opinii.</div>';
            }
        },
        
        /**
         * Display reviews list
         */
        displayReviews: function(reviews, pagination) {
            const container = document.getElementById('reviews-list');
            if (!container) return;
            
            if (reviews.length === 0) {
                container.innerHTML = '<div class="no-reviews">Brak opinii dla tego produktu. Bądź pierwszy!</div>';
                return;
            }
            
            let html = '<div class="reviews-container">';
            
            reviews.forEach(review => {
                html += this.renderReview(review);
            });
            
            html += '</div>';
            
            // Add pagination
            if (pagination.pages > 1) {
                html += this.renderPagination(pagination);
            }
            
            container.innerHTML = html;
            
            // Attach event listeners
            this.attachReviewListeners();
        },
        
        /**
         * Render single review
         */
        renderReview: function(review) {
            const date = new Date(review.created_at);
            const dateStr = date.toLocaleDateString('pl-PL', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            const helpfulClass = review.is_helpful ? 'active' : '';
            const helpfulText = review.is_helpful ? 'Pomocne ✓' : 'Pomocne?';
            
            return `
                <div class="review-item" data-review-id="${review.id}">
                    <div class="review-header">
                        <div class="review-rating">${this.renderStars(review.rating)}</div>
                        <div class="review-meta">
                            <span class="review-author">${this.escapeHtml(review.user_name)}</span>
                            <span class="review-date">${dateStr}</span>
                        </div>
                    </div>
                    <div class="review-body">
                        <h4 class="review-title">${this.escapeHtml(review.title)}</h4>
                        <p class="review-text">${this.escapeHtml(review.review_text)}</p>
                    </div>
                    <div class="review-footer">
                        <button class="btn-helpful ${helpfulClass}" data-review-id="${review.id}">
                            ${helpfulText} (${review.helpful_count})
                        </button>
                        <button class="btn-report" data-review-id="${review.id}">
                            Zgłoś
                        </button>
                    </div>
                </div>
            `;
        },
        
        /**
         * Render star rating
         */
        renderStars: function(rating, interactive = false) {
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 >= 0.5;
            const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
            
            let html = '<span class="stars' + (interactive ? ' interactive' : '') + '">';
            
            // Full stars
            for (let i = 0; i < fullStars; i++) {
                html += interactive 
                    ? `<span class="star" data-rating="${i + 1}">★</span>`
                    : '<span class="star filled">★</span>';
            }
            
            // Half star
            if (hasHalfStar) {
                html += '<span class="star half">★</span>';
            }
            
            // Empty stars
            for (let i = 0; i < emptyStars; i++) {
                const ratingValue = fullStars + (hasHalfStar ? 1 : 0) + i + 1;
                html += interactive
                    ? `<span class="star" data-rating="${ratingValue}">★</span>`
                    : '<span class="star">★</span>';
            }
            
            html += '</span>';
            return html;
        },
        
        /**
         * Render pagination
         */
        renderPagination: function(pagination) {
            const { page, pages } = pagination;
            
            let html = '<div class="pagination">';
            
            // Previous button
            if (page > 1) {
                html += `<button class="page-btn" data-page="${page - 1}">« Poprzednia</button>`;
            }
            
            // Page numbers
            for (let i = 1; i <= pages; i++) {
                if (
                    i === 1 || 
                    i === pages || 
                    (i >= page - 2 && i <= page + 2)
                ) {
                    const activeClass = i === page ? 'active' : '';
                    html += `<button class="page-btn ${activeClass}" data-page="${i}">${i}</button>`;
                } else if (i === page - 3 || i === page + 3) {
                    html += '<span class="page-dots">...</span>';
                }
            }
            
            // Next button
            if (page < pages) {
                html += `<button class="page-btn" data-page="${page + 1}">Następna »</button>`;
            }
            
            html += '</div>';
            return html;
        },
        
        /**
         * Attach event listeners to reviews
         */
        attachReviewListeners: function() {
            // Helpful buttons
            document.querySelectorAll('.btn-helpful').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const reviewId = e.target.dataset.reviewId;
                    this.markHelpful(reviewId, e.target);
                });
            });
            
            // Report buttons
            document.querySelectorAll('.btn-report').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const reviewId = e.target.dataset.reviewId;
                    this.reportReview(reviewId);
                });
            });
            
            // Pagination
            document.querySelectorAll('.page-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const page = parseInt(e.target.dataset.page);
                    this.loadReviews(page, currentSort);
                    // Scroll to reviews section
                    document.getElementById('reviews-section').scrollIntoView({ behavior: 'smooth' });
                });
            });
        },
        
        /**
         * Initialize review form handlers
         */
        initFormHandlers: function() {
            const form = document.getElementById('review-form');
            if (!form) return;
            
            // Star rating selection
            const starContainer = document.getElementById('rating-stars');
            if (starContainer) {
                starContainer.innerHTML = this.renderStars(0, true);
                
                const stars = starContainer.querySelectorAll('.star');
                stars.forEach(star => {
                    star.addEventListener('click', (e) => {
                        const rating = parseInt(e.target.dataset.rating);
                        document.getElementById('rating-input').value = rating;
                        
                        // Update visual
                        stars.forEach((s, i) => {
                            if (i < rating) {
                                s.classList.add('filled');
                            } else {
                                s.classList.remove('filled');
                            }
                        });
                    });
                    
                    // Hover effect
                    star.addEventListener('mouseenter', (e) => {
                        const rating = parseInt(e.target.dataset.rating);
                        stars.forEach((s, i) => {
                            if (i < rating) {
                                s.classList.add('hover');
                            } else {
                                s.classList.remove('hover');
                            }
                        });
                    });
                });
                
                starContainer.addEventListener('mouseleave', () => {
                    stars.forEach(s => s.classList.remove('hover'));
                });
            }
            
            // Form submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.submitReview();
            });
        },
        
        /**
         * Initialize sort handler
         */
        initSortHandler: function() {
            const sortSelect = document.getElementById('review-sort');
            if (!sortSelect) return;
            
            sortSelect.addEventListener('change', (e) => {
                this.loadReviews(1, e.target.value);
            });
        },
        
        /**
         * Submit new review
         */
        submitReview: async function() {
            const form = document.getElementById('review-form');
            const submitBtn = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('review-message');
            
            // Get form data
            const rating = parseInt(document.getElementById('rating-input').value);
            const title = document.getElementById('review-title').value.trim();
            const reviewText = document.getElementById('review-text').value.trim();
            
            // Validation
            if (!rating || rating < 1 || rating > 5) {
                this.showMessage('Proszę wybrać ocenę (1-5 gwiazdek)', 'error', messageDiv);
                return;
            }
            
            if (!title || title.length < 3) {
                this.showMessage('Tytuł musi mieć minimum 3 znaki', 'error', messageDiv);
                return;
            }
            
            if (!reviewText || reviewText.length < 10) {
                this.showMessage('Opinia musi mieć minimum 10 znaków', 'error', messageDiv);
                return;
            }
            
            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wysyłanie...';
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'add',
                        product_id: currentProductId,
                        rating: rating,
                        title: title,
                        review_text: reviewText
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showMessage(
                        'Dziękujemy za opinię! Zostanie ona opublikowana po weryfikacji.',
                        'success',
                        messageDiv
                    );
                    
                    // Reset form
                    form.reset();
                    document.getElementById('rating-input').value = '';
                    document.querySelectorAll('#rating-stars .star').forEach(s => {
                        s.classList.remove('filled');
                    });
                    
                    // Reload stats
                    setTimeout(() => {
                        this.loadStats();
                    }, 1000);
                } else {
                    this.showMessage(result.message || 'Wystąpił błąd podczas wysyłania opinii', 'error', messageDiv);
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                this.showMessage('Wystąpił błąd podczas wysyłania opinii', 'error', messageDiv);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Wyślij opinię';
            }
        },
        
        /**
         * Mark review as helpful
         */
        markHelpful: async function(reviewId, button) {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'helpful',
                        review_id: reviewId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const { is_helpful, helpful_count } = result.data;
                    
                    // Update button
                    if (is_helpful) {
                        button.classList.add('active');
                        button.textContent = `Pomocne ✓ (${helpful_count})`;
                    } else {
                        button.classList.remove('active');
                        button.textContent = `Pomocne? (${helpful_count})`;
                    }
                } else {
                    if (response.status === 401) {
                        alert('Musisz być zalogowany, aby oznaczyć opinię jako pomocną.');
                    } else {
                        alert(result.message || 'Wystąpił błąd');
                    }
                }
            } catch (error) {
                console.error('Error marking helpful:', error);
                alert('Wystąpił błąd');
            }
        },
        
        /**
         * Report review
         */
        reportReview: async function(reviewId) {
            const reason = prompt('Dlaczego zgłaszasz tę opinię?\n\n(np. język nienawiści, spam, fake review)');
            
            if (!reason || reason.trim() === '') {
                return;
            }
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'report',
                        review_id: reviewId,
                        reason: reason.trim()
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Dziękujemy za zgłoszenie. Sprawdzimy tę opinię.');
                } else {
                    if (response.status === 401) {
                        alert('Musisz być zalogowany, aby zgłaszać opinie.');
                    } else {
                        alert(result.message || 'Wystąpił błąd');
                    }
                }
            } catch (error) {
                console.error('Error reporting review:', error);
                alert('Wystąpił błąd');
            }
        },
        
        /**
         * Show message
         */
        showMessage: function(message, type, container) {
            container.innerHTML = `<div class="message ${type}">${message}</div>`;
            container.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                container.style.display = 'none';
            }, 5000);
        },
        
        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };
    
})();