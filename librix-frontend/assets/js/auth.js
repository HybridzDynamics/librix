const auth = {

    getToken: function() {
        return localStorage.getItem('librix_token');
    },

    getUser: function() {
        const userStr = localStorage.getItem('librix_user');
        if (!userStr) return null;
        try {
            return JSON.parse(userStr);
        } catch (e) {
            return null;
        }
    },

    isLoggedIn: function() {
        return !!this.getToken() && !!this.getUser();
    },

    isAdmin: function() {
        const user = this.getUser();
        return user && user.role === 'admin';
    },

    isLibrarian: function() {
        const user = this.getUser();
        return user && (user.role === 'librarian' || user.role === 'admin');
    },

    getOrgId: function() {
        const user = this.getUser();
        return user ? (user.org_id || 1) : 1;
    },

    getOrgName: function() {
        const user = this.getUser();
        return user ? (user.org_name || 'Your Library') : 'Your Library';
    },

    getOrgCode: function() {
        const user = this.getUser();
        return user ? (user.org_code || 'org-code') : 'org-code';
    },

    _pathPrefix: function() {
        const path = window.location.pathname;
        if (path.indexOf('/user/') !== -1 || path.indexOf('/admin/') !== -1 || path.indexOf('/librarian/') !== -1) {
            return '../';
        }
        if (path.indexOf('/pages/') !== -1) {
            return '';
        }
        return 'pages/';
    },

    _loginUrl: function() {
        return this._pathPrefix() + 'login.html';
    },

    _dashboardUrl: function() {
        return this._pathPrefix() + 'user/dashboard.html';
    },

    _redirect: function(url, params) {
        if (typeof utils !== 'undefined' && utils.navigate) {
            utils.navigate(url, params);
        } else {
            window.location.href = url;
        }
    },

    register: function(name, email, password, orgId) {
        orgId = orgId || 1;

        return api.post('/auth/register', {
            name: name,
            email: email,
            password: password,
            org_id: orgId
        }).then(function(response) {
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Registration failed');
        });
    },

    login: function(email, password) {
        return api.post('/auth/login', { email: email, password: password }).then(function(response) {
            if (response.success && response.data) {
                localStorage.setItem('librix_token', response.data.token);
                localStorage.setItem('librix_user', JSON.stringify(response.data.user));
                return response.data;
            }
            throw new Error(response.message || 'Login failed');
        });
    },

    logout: function() {
        const token = this.getToken();

        const finish = function() {
            localStorage.removeItem('librix_token');
            localStorage.removeItem('librix_user');

            const path = window.location.pathname;
            const isRoot = path.substring(path.length - 10) === 'index.html' || path.charAt(path.length - 1) === '/';
            if (isRoot) {
                window.location.reload();
            } else {
                auth._redirect(auth._loginUrl());
            }
        };

        if (token) {
            return api.post('/auth/logout').then(finish, finish);
        }

        finish();
    },

    requireAuth: function() {
        if (!this.isLoggedIn()) {
            this._redirect(this._loginUrl(), { redirect: window.location.pathname });
            return false;
        }
        return true;
    },

    requireLibrarian: function() {
        if (!this.requireAuth()) return false;
        if (!this.isLibrarian()) {
            if (typeof utils !== 'undefined') {
                utils.toast('Access Denied', 'Librarian privileges are required for this section.', 'danger');
            }
            this._redirect(this._dashboardUrl());
            return false;
        }
        return true;
    },

    requireAdmin: function() {
        if (!this.requireAuth()) return false;
        if (!this.isAdmin()) {
            if (typeof utils !== 'undefined') {
                utils.toast('Access Denied', 'Administrator privileges are required.', 'danger');
            }
            this._redirect(this._dashboardUrl());
            return false;
        }
        return true;
    },

    updateNav: function() {
        const navAuth = document.querySelector('.nav-auth') || document.getElementById('nav-auth-container');
        if (!navAuth) return;

        const prefix = this._pathPrefix();

        if (!this.isLoggedIn()) {
            navAuth.innerHTML =
                '<a href="' + prefix + 'login.html" class="btn btn-outline btn-sm">Login</a>' +
                '<a href="' + prefix + 'register.html" class="btn btn-primary btn-sm">Sign Up</a>';
            if (window.lucide) {
                lucide.createIcons();
            }
            return;
        }

        const user = this.getUser();
        let dashboardUrl = prefix + 'user/dashboard.html';
        let roleBadge = '';

        if (user.role === 'admin') {
            dashboardUrl = prefix + 'admin/dashboard.html';
            roleBadge = '<span class="badge badge-primary" style="font-size: 10px; padding: 1px 6px;">ADMIN</span>';
        } else if (user.role === 'librarian') {
            dashboardUrl = prefix + 'librarian/dashboard.html';
            roleBadge = '<span class="badge badge-accent" style="font-size: 10px; padding: 1px 6px; background:#f3e8ff; color:#7e22ce;">LIBRARIAN</span>';
        } else {
            dashboardUrl = prefix + 'user/dashboard.html';
        }

        let profileImage = '';
        if (user.profile_picture_url) {
            profileImage = '<img src="' + user.profile_picture_url + '" alt="Profile" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 2px solid var(--primary);">';
        } else if (window.lucide) {
            profileImage = lucide.render('user', { size: 14 });
        }

        navAuth.innerHTML =
            '<div style="display: flex; align-items: center; gap: 12px;">' +
                '<a href="' + dashboardUrl + '" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;">' +
                    profileImage +
                    '<span>' + utils.escapeHtml(user.name.split(' ')[0]) + '</span>' +
                    roleBadge +
                '</a>' +
                '<button id="nav-logout-btn" class="btn btn-outline btn-sm" style="border-color: var(--border);">Logout</button>' +
            '</div>';

        const logoutBtn = document.getElementById('nav-logout-btn');
        if (logoutBtn) {
            logoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                utils.confirm({
                    title: 'Sign Out Confirmation',
                    message: 'Are you sure you want to log out of your session?',
                    confirmText: 'Sign Out',
                    icon: 'log-out'
                }).then(function(confirmed) {
                    if (confirmed) {
                        auth.logout();
                    }
                });
            });
        }

        if (window.lucide) {
            lucide.createIcons();
        }
    }
};

window.auth = auth;
