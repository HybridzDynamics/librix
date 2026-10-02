/**
 * LibriX Authentication & Role-Based Access Control (RBAC) Module
 * Supports Admin, Librarian, and Patron Roles with Multi-Tenant Organization Scoping
 */

const auth = {
    /**
     * Get stored authentication token
     */
    getToken: function() {
        return localStorage.getItem('librix_token');
    },

    /**
     * Get stored user profile
     */
    getUser: function() {
        const userStr = localStorage.getItem('librix_user');
        if (!userStr) return null;
        try {
            return JSON.parse(userStr);
        } catch (e) {
            return null;
        }
    },

    /**
     * Check if user is logged in
     */
    isLoggedIn: function() {
        return !!this.getToken() && !!this.getUser();
    },

    /**
     * Check if current user is an admin
     */
    isAdmin: function() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    /**
     * Check if current user is a librarian (or admin)
     */
    isLibrarian: function() {
        const user = this.getUser();
        return user && (user.role === 'librarian' || user.role === 'admin');
    },

    /**
     * Get active Organization ID
     */
    getOrgId: function() {
        const user = this.getUser();
        return user ? (user.org_id || 1) : 1;
    },

    getOrgName: function() {
        const user = this.getUser();
        return user ? (user.org_name || 'MIT Central Library') : 'MIT Central Library';
    },

    getOrgCode: function() {
        const user = this.getUser();
        return user ? (user.org_code || 'ORG-MIT-01') : 'ORG-MIT-01';
    },

    /**
     * Authenticate with email and password
     */
    login: async function(email, password) {
        try {
            const response = await api.post('/auth/login', { email, password });
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Login failed');
        } catch (error) {
            throw error;
        }
    },

    /**
     * Register a new user
     */
    register: async function(name, email, password, orgId = 1, role = 'user') {
        try {
            const response = await api.post('/auth/register', { name, email, password, org_id: orgId, role });
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Registration failed');
        } catch (error) {
            throw error;
        }
    },

    /**
     * Log out current session
     */
    logout: async function() {
        const token = this.getToken();
        if (token) {
            try {
                await api.post('/auth/logout');
            } catch (e) {}
        }
        localStorage.removeItem('librix_token');
        localStorage.removeItem('librix_user');
        
        // Redirect to login or home using dynamic navigation
        const isRoot = window.location.pathname.endsWith('index.html') || window.location.pathname.endsWith('/');
        if (isRoot) {
            window.location.reload();
        } else {
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/librarian/');
            const target = isInSub ? '../login.html' : (isInPages ? 'login.html' : 'pages/login.html');
            if (typeof utils !== 'undefined' && utils.navigate) {
                utils.navigate(target);
            } else {
                window.location.href = target;
            }
        }
    },

    /**
     * Require authentication on protected pages
     */
    requireAuth: function(redirectUrl = null) {
        if (!this.isLoggedIn()) {
            const currentPath = window.location.pathname;
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/librarian/');
            const target = isInSub ? '../login.html' : (isInPages ? 'login.html' : 'pages/login.html');
            
            // Store redirect in sessionStorage
            if (typeof utils !== 'undefined' && utils.navigate) {
                utils.navigate(target, {redirect: currentPath});
            } else {
                window.location.href = target;
            }
            return false;
        }
        return true;
    },

    /**
     * Require librarian access
     */
    requireLibrarian: function(redirectUrl = null) {
        if (!this.requireAuth()) return false;
        if (!this.isLibrarian()) {
            if (typeof utils !== 'undefined') {
                utils.toast('Access Denied', 'Librarian privileges are required for this section.', 'danger');
            }
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/librarian/');
            const target = isInSub ? '../user/dashboard.html' : (isInPages ? 'user/dashboard.html' : 'pages/user/dashboard.html');
            if (typeof utils !== 'undefined' && utils.navigate) {
                utils.navigate(target);
            } else {
                window.location.href = target;
            }
            return false;
        }
        return true;
    },

    /**
     * Require admin access
     */
    requireAdmin: function(redirectUrl = null) {
        if (!this.requireAuth()) return false;
        if (!this.isAdmin()) {
            if (typeof utils !== 'undefined') {
                utils.toast('Access Denied', 'Administrator privileges are required.', 'danger');
            }
            const isInPages = window.location.pathname.includes('/pages/');
            const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/librarian/');
            const target = isInSub ? '../user/dashboard.html' : (isInPages ? 'user/dashboard.html' : 'pages/user/dashboard.html');
            if (typeof utils !== 'undefined' && utils.navigate) {
                utils.navigate(target);
            } else {
                window.location.href = target;
            }
            return false;
        }
        return true;
    },

    /**
     * Dynamically update navigation bar links based on auth state
     */
    updateNav: function() {
        const navAuth = document.querySelector('.nav-auth') || document.getElementById('nav-auth-container');
        if (!navAuth) return;

        const isInPages = window.location.pathname.includes('/pages/');
        const isInSub = window.location.pathname.includes('/user/') || window.location.pathname.includes('/admin/') || window.location.pathname.includes('/librarian/');
        
        const pathPrefix = isInSub ? '../' : (isInPages ? '' : 'pages/');

        if (this.isLoggedIn()) {
            const user = this.getUser();
            let dashboardUrl = `${pathPrefix}user/dashboard.html`;
            let roleBadge = '';

            if (user.role === 'admin') {
                dashboardUrl = `${pathPrefix}admin/dashboard.html`;
                roleBadge = '<span class="badge badge-primary" style="font-size: 10px; padding: 1px 6px;">ADMIN</span>';
            } else if (user.role === 'librarian') {
                dashboardUrl = `${pathPrefix}librarian/dashboard.html`;
                roleBadge = '<span class="badge badge-accent" style="font-size: 10px; padding: 1px 6px; background:#f3e8ff; color:#7e22ce;">LIBRARIAN</span>';
            }

            // Profile picture or fallback icon
            let profileImage = '';
            if (user.profile_picture_url) {
                profileImage = `<img src="${user.profile_picture_url}" alt="Profile" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">`;
            } else {
                profileImage = window.lucide ? lucide.render('user', { size: 14 }) : '';
            }

            navAuth.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="${dashboardUrl}" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;">
                        ${profileImage}
                        <span>${utils.escapeHtml(user.name.split(' ')[0])}</span>
                        ${roleBadge}
                    </a>
                    <button id="nav-logout-btn" class="btn btn-outline btn-sm" style="border-color: var(--border);">Logout</button>
                </div>
            `;

            const logoutBtn = document.getElementById('nav-logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const confirmed = await utils.confirm({
                        title: 'Sign Out Confirmation',
                        message: 'Are you sure you want to log out of your session?',
                        confirmText: 'Sign Out',
                        icon: 'log-out'
                    });
                    if (confirmed) {
                        auth.logout();
                    }
                });
            }
        } else {
            navAuth.innerHTML = `
                <a href="${pathPrefix}login.html" class="btn btn-outline btn-sm">Login</a>
                <a href="${pathPrefix}register.html" class="btn btn-primary btn-sm">Sign Up</a>
            `;
        }

        if (window.lucide) {
            lucide.createIcons();
        }
    }
};

window.auth = auth;
