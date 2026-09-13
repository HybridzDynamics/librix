/**
 * LibriX Utility Functions
 * Toasts, Modals, Formatters, Badges, Helpers using Lucide Icons
 */

const utils = {
    /**
     * Display a floating toast notification
     */
    toast: function(title, message = '', type = 'info', duration = 4000) {
        let container = document.getElementById('toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        let iconSvg = '';
        if (window.lucide) {
            if (type === 'success') iconSvg = lucide.render('check-circle', { size: 20, color: 'var(--success)' });
            else if (type === 'error') iconSvg = lucide.render('x-circle', { size: 20, color: 'var(--danger)' });
            else if (type === 'warning') iconSvg = lucide.render('alert-triangle', { size: 20, color: 'var(--warning)' });
            else iconSvg = lucide.render('info', { size: 20, color: 'var(--info)' });
        } else {
            iconSvg = '•';
        }

        const closeIcon = window.lucide ? lucide.render('x', { size: 16 }) : '&times;';

        toast.innerHTML = `
            <div class="toast-icon">${iconSvg}</div>
            <div class="toast-content">
                <div class="toast-title">${utils.escapeHtml(title)}</div>
                ${message ? `<div class="toast-message">${utils.escapeHtml(message)}</div>` : ''}
            </div>
            <button class="toast-close" aria-label="Close">${closeIcon}</button>
        `;

        const closeBtn = toast.querySelector('.toast-close');
        const dismiss = () => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(30px)';
            setTimeout(() => toast.remove(), 250);
        };

        closeBtn.addEventListener('click', dismiss);
        container.appendChild(toast);

        if (duration > 0) {
            setTimeout(dismiss, duration);
        }
    },

    /**
     * Modal Controller
     */
    modal: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return { open: () => {}, close: () => {} };

        return {
            open: function() {
                modal.classList.add('is-active');
                document.body.style.overflow = 'hidden';

                if (window.lucide) {
                    lucide.createIcons(modal);
                }

                const closeElements = modal.querySelectorAll('[data-modal-close], .modal-close');
                closeElements.forEach(el => {
                    el.onclick = () => utils.modal(modalId).close();
                });

                modal.onclick = (e) => {
                    if (e.target === modal) {
                        utils.modal(modalId).close();
                    }
                };

                const escHandler = (e) => {
                    if (e.key === 'Escape') {
                        utils.modal(modalId).close();
                        window.removeEventListener('keydown', escHandler);
                    }
                };
                window.addEventListener('keydown', escHandler);
            },
            close: function() {
                modal.classList.remove('is-active');
                document.body.style.overflow = '';
            }
        };
    },

    /**
     * Format standard date
     */
    formatDate: function(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    /**
     * Relative time string
     */
    timeAgo: function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        const minutes = Math.floor(seconds / 60);
        if (minutes < 60) return `${minutes}m ago`;
        const hours = Math.floor(minutes / 60);
        if (hours < 24) return `${hours}h ago`;
        const days = Math.floor(hours / 24);
        if (days < 30) return `${days}d ago`;
        return utils.formatDate(dateStr);
    },

    /**
     * Star rating with Lucide Star icons
     */
    renderStars: function(rating = 0) {
        const fullStars = Math.floor(rating);
        let html = '<span class="stars" style="display: inline-flex; align-items: center; gap: 2px;">';
        for (let i = 1; i <= 5; i++) {
            if (window.lucide) {
                if (i <= fullStars) {
                    html += lucide.render('star', { size: 14, fill: '#f59e0b', color: '#f59e0b' });
                } else {
                    html += lucide.render('star', { size: 14, fill: 'none', color: '#cbd5e1' });
                }
            } else {
                html += (i <= fullStars) ? '★' : '<span class="stars-inactive">★</span>';
            }
        }
        html += ` <span style="font-size: 12px; color: var(--text-muted); font-weight: 600; margin-left: 4px;">(${rating.toFixed(1)})</span></span>`;
        return html;
    },

    /**
     * Readability Difficulty Badge with Lucide Book-Open Icon
     */
    getReadabilityBadge: function(difficulty, easeScore) {
        const labels = {
            very_easy: 'Very Easy (5th Grade)',
            easy: 'Easy (6th Grade)',
            fairly_easy: 'Fairly Easy (7th Grade)',
            standard: 'Standard (8th-9th)',
            fairly_difficult: 'Fairly Difficult (10th-12th)',
            difficult: 'Difficult (College)',
            very_difficult: 'Very Difficult (Grad)'
        };

        const cssClass = `badge-ease-${(difficulty || 'standard').replace('_', '-')}`;
        const label = labels[difficulty] || 'Standard';
        const scoreText = easeScore !== undefined && easeScore !== null ? ` • ${easeScore}` : '';
        const iconSvg = window.lucide ? lucide.render('book-open', { size: 13, className: 'badge-icon' }) : '';

        return `<span class="badge ${cssClass}">${iconSvg} <span>${label}${scoreText}</span></span>`;
    },

    /**
     * Debounce utility
     */
    debounce: function(func, delay = 300) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    },

    /**
     * Escape HTML
     */
    escapeHtml: function(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
};

window.utils = utils;
