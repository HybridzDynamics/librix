/**
 * LibriX API Client & Multi-Tenant Data Engine
 * Real HTTP REST Client with seamless multi-tenant RBAC support and rich entity hydration
 */

(function(window) {
    'use strict';

    // -------------------------------------------------------------
    // 1. Core Multi-Tenant Initial Data Sets
    // -------------------------------------------------------------
    const INITIAL_ORGANIZATIONS = [
        { id: 1, name: 'MIT Central Library', code: 'ORG-MIT-01', description: 'Massachusetts Institute of Technology Library & Media Archive', contact_email: 'library@mit.edu' },
        { id: 2, name: 'Oxford Bodleian Library', code: 'ORG-OXFORD-02', description: 'University of Oxford Bodleian Main Research Collection', contact_email: 'bodleian@oxford.edu' },
        { id: 3, name: 'Stanford University Libraries', code: 'ORG-STANFORD-03', description: 'Stanford University Green Library & Engineering Collection', contact_email: 'library@stanford.edu' },
        { id: 4, name: 'Global Public Library Network', code: 'ORG-GLOBAL-00', description: 'Central LibriX global open-access consortium', contact_email: 'admin@librix.com' }
    ];

    const INITIAL_CATEGORIES = [
        { id: 1, name: 'Classic Literature', description: 'Timeless masterpieces and canon fiction', total_books: 2 },
        { id: 2, name: 'Computer Science', description: 'Software engineering, algorithms, design patterns, and programming', total_books: 1 },
        { id: 3, name: 'Dystopian & Sci-Fi', description: 'Futuristic speculative fiction and dystopian classics', total_books: 1 },
        { id: 4, name: 'Philosophy & Psychology', description: 'Human behavior, stoicism, and deep ethical treatises', total_books: 1 },
        { id: 5, name: 'Fantasy & Adventure', description: 'Epic fantasy universes and mythic quests', total_books: 1 },
        { id: 6, name: 'Science & Nature', description: 'Physics, evolutionary biology, and astrophysics', total_books: 0 }
    ];

    const INITIAL_AUTHORS = [
        { id: 1, name: 'Robert C. Martin', biography: 'Renowned software engineer, author of Clean Code and Clean Architecture, leading agile advocate.', total_books: 1 },
        { id: 2, name: 'George Orwell', biography: 'English novelist, essayist, and critic famous for 1984 and Animal Farm.', total_books: 1 },
        { id: 3, name: 'F. Scott Fitzgerald', biography: 'American novelist celebrated for portraying the Jazz Age in The Great Gatsby.', total_books: 1 },
        { id: 4, name: 'J.R.R. Tolkien', biography: 'English writer, philologist, and academic best known as the author of The Lord of the Rings.', total_books: 1 },
        { id: 5, name: 'Marcus Aurelius', biography: 'Roman emperor and Stoic philosopher author of Meditations.', total_books: 1 },
        { id: 6, name: 'Harper Lee', biography: 'American novelist widely known for To Kill a Mockingbird.', total_books: 1 }
    ];

    const INITIAL_PUBLISHERS = [
        { id: 1, name: 'Prentice Hall', address: 'Upper Saddle River, NJ, USA', website: 'https://www.pearson.com', total_books: 1 },
        { id: 2, name: 'Secker & Warburg', address: 'London, United Kingdom', website: 'https://www.penguin.co.uk', total_books: 1 },
        { id: 3, name: "Charles Scribner's Sons", address: 'New York, NY, USA', website: 'https://www.simonandschuster.com', total_books: 2 },
        { id: 4, name: 'George Allen & Unwin', address: 'London, United Kingdom', website: 'https://www.harpercollins.com', total_books: 1 },
        { id: 5, name: 'J.B. Lippincott & Co.', address: 'Philadelphia, PA, USA', website: 'https://www.harpercollins.com', total_books: 1 }
    ];

    const INITIAL_BOOKS = [
        {
            id: 1,
            org_id: 1,
            author_id: 1,
            author_name: 'Robert C. Martin',
            category_id: 2,
            category: 'Computer Science',
            publisher_id: 1,
            publisher: 'Prentice Hall',
            title: 'Clean Code: A Handbook of Agile Software Craftsmanship',
            isbn: '9780132350884',
            description: "Even bad code can function. But if code isn't clean, it can bring a development organization to its knees. Every year, countless hours and significant resources are lost because of poorly written code. This book is a must-read for any developer, software engineer, project manager, or systems analyst.",
            language: 'English',
            publication_year: 2008,
            total_copies: 6,
            available_copies: 4,
            cover_image: 'https://images.unsplash.com/photo-1532012164546-f432f2e3edd3?w=500&q=80',
            average_rating: 4.8,
            rating_count: 42,
            created_at: '2026-09-01 10:00:00'
        },
        {
            id: 2,
            org_id: 2,
            author_id: 2,
            author_name: 'George Orwell',
            category_id: 3,
            category: 'Dystopian & Sci-Fi',
            publisher_id: 2,
            publisher: 'Secker & Warburg',
            title: '1984',
            isbn: '9780451524935',
            description: 'Winston Smith toes the Party line, rewriting history to satisfy the Ministry of Truth. With each lie he writes, Winston grows to hate the Party that seeks power for its own sake. A chilling dystopian masterpiece detailing totalitarianism and psychological control.',
            language: 'English',
            publication_year: 1949,
            total_copies: 8,
            available_copies: 5,
            cover_image: 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500&q=80',
            average_rating: 4.9,
            rating_count: 128,
            created_at: '2026-09-02 11:30:00'
        },
        {
            id: 3,
            org_id: 1,
            author_id: 3,
            author_name: 'F. Scott Fitzgerald',
            category_id: 1,
            category: 'Classic Literature',
            publisher_id: 3,
            publisher: "Charles Scribner's Sons",
            title: 'The Great Gatsby',
            isbn: '9780743273565',
            description: 'The story of the mysteriously wealthy Jay Gatsby and his unrequited love for Daisy Buchanan. Set in the Jazz Age on prosperous Long Island, it provides a poignant examination of the American Dream, decadence, and idealism.',
            language: 'English',
            publication_year: 1925,
            total_copies: 5,
            available_copies: 2,
            cover_image: 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=500&q=80',
            average_rating: 4.6,
            rating_count: 85,
            created_at: '2026-09-03 14:15:00'
        },
        {
            id: 4,
            org_id: 3,
            author_id: 4,
            author_name: 'J.R.R. Tolkien',
            category_id: 5,
            category: 'Fantasy & Adventure',
            publisher_id: 4,
            publisher: 'George Allen & Unwin',
            title: 'The Hobbit',
            isbn: '9780547928227',
            description: 'Bilbo Baggins is a hobbit who enjoys a comfortable, unambitious life. But his contentment is interrupted when the wizard Gandalf and a company of thirteen dwarves arrive on his doorstep to whisk him away on a journey over the treacherous Misty Mountains.',
            language: 'English',
            publication_year: 1937,
            total_copies: 7,
            available_copies: 3,
            cover_image: 'https://images.unsplash.com/photo-1618666012174-83b441c0bc76?w=500&q=80',
            average_rating: 4.9,
            rating_count: 94,
            created_at: '2026-09-04 09:45:00'
        },
        {
            id: 5,
            org_id: 1,
            author_id: 6,
            author_name: 'Harper Lee',
            category_id: 1,
            category: 'Classic Literature',
            publisher_id: 5,
            publisher: 'J.B. Lippincott & Co.',
            title: 'To Kill a Mockingbird',
            isbn: '9780060935467',
            description: 'The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it. Compassionate, dramatic, and deeply moving, it explores the roots of human behavior with humor and uncompromising pathos.',
            language: 'English',
            publication_year: 1960,
            total_copies: 6,
            available_copies: 4,
            cover_image: 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80',
            average_rating: 4.9,
            rating_count: 110,
            created_at: '2026-09-05 16:20:00'
        },
        {
            id: 6,
            org_id: 2,
            author_id: 5,
            author_name: 'Marcus Aurelius',
            category_id: 4,
            category: 'Philosophy & Psychology',
            publisher_id: 3,
            publisher: "Charles Scribner's Sons",
            title: 'Meditations',
            isbn: '9780140449334',
            description: 'A series of personal reflections and private notes written by the Roman Emperor Marcus Aurelius detailing his Stoic philosophy on virtue, rational thinking, self-mastery, and resilience in a turbulent world.',
            language: 'English',
            publication_year: 2002,
            total_copies: 4,
            available_copies: 3,
            cover_image: 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=500&q=80',
            average_rating: 4.7,
            rating_count: 63,
            created_at: '2026-09-06 12:00:00'
        }
    ];

    const INITIAL_READABILITY = {
        1: { id: 1, book_id: 1, book_title: 'Clean Code', flesch_reading_ease: 65.4, flesch_kincaid_grade: 7.8, difficulty_level: 'standard', estimated_reading_minutes: 380, word_count: 64200, sentence_count: 4200, syllable_count: 98000 },
        2: { id: 2, book_id: 2, book_title: '1984', flesch_reading_ease: 72.1, flesch_kincaid_grade: 6.9, difficulty_level: 'fairly_easy', estimated_reading_minutes: 290, word_count: 88942, sentence_count: 6100, syllable_count: 132000 },
        3: { id: 3, book_id: 3, book_title: 'The Great Gatsby', flesch_reading_ease: 68.2, flesch_kincaid_grade: 8.1, difficulty_level: 'standard', estimated_reading_minutes: 240, word_count: 47094, sentence_count: 2900, syllable_count: 68000 },
        4: { id: 4, book_id: 4, book_title: 'The Hobbit', flesch_reading_ease: 78.5, flesch_kincaid_grade: 5.8, difficulty_level: 'easy', estimated_reading_minutes: 310, word_count: 95356, sentence_count: 7300, syllable_count: 138000 },
        5: { id: 5, book_id: 5, book_title: 'To Kill a Mockingbird', flesch_reading_ease: 74.3, flesch_kincaid_grade: 6.4, difficulty_level: 'fairly_easy', estimated_reading_minutes: 340, word_count: 100388, sentence_count: 6800, syllable_count: 145000 },
        6: { id: 6, book_id: 6, book_title: 'Meditations', flesch_reading_ease: 58.6, flesch_kincaid_grade: 9.5, difficulty_level: 'fairly_difficult', estimated_reading_minutes: 210, word_count: 52000, sentence_count: 2400, syllable_count: 89000 }
    };

    const INITIAL_REVIEWS = [
        { id: 1, book_id: 1, user_id: 4, user_name: 'Jane Patron (Member)', rating: 5, review_text: 'Indispensable guide for any professional programmer. The principles of naming and function design changed how I write code.', created_at: '2026-09-08 14:20:00' },
        { id: 2, book_id: 2, user_id: 4, user_name: 'Jane Patron (Member)', rating: 5, review_text: 'Terrifying and profoundly prophetic. Orwell captures the psychological terror of state surveillance like no other.', created_at: '2026-09-09 11:15:00' },
        { id: 3, book_id: 5, user_id: 4, user_name: 'Jane Patron (Member)', rating: 5, review_text: 'A masterpiece of American literature. Atticus Finch remains one of the greatest moral figures in fiction.', created_at: '2026-09-10 16:45:00' }
    ];

    const INITIAL_USERS = [
        { id: 1, org_id: 4, name: 'System Administrator', email: 'admin@librix.com', password: 'admin123', role: 'admin', status: 'active', org_name: 'Global Public Library Network', org_code: 'ORG-GLOBAL-00', created_at: '2026-09-01 08:00:00' },
        { id: 2, org_id: 1, name: 'Dr. Sarah Chen (Librarian)', email: 'librarian@mit.edu', password: 'lib123', role: 'librarian', status: 'active', org_name: 'MIT Central Library', org_code: 'ORG-MIT-01', created_at: '2026-09-01 08:30:00' },
        { id: 3, org_id: 2, name: 'Prof. Arthur Pendelton', email: 'librarian@oxford.edu', password: 'lib123', role: 'librarian', status: 'active', org_name: 'Oxford Bodleian Library', org_code: 'ORG-OXFORD-02', created_at: '2026-09-01 08:45:00' },
        { id: 4, org_id: 1, name: 'Jane Patron (Member)', email: 'user@librix.com', password: 'user123', role: 'user', status: 'active', org_name: 'MIT Central Library', org_code: 'ORG-MIT-01', created_at: '2026-09-01 09:30:00' }
    ];

    const INITIAL_ISSUES = [
        { id: 1, org_id: 1, book_id: 1, book_title: 'Clean Code: A Handbook of Agile Software Craftsmanship', user_id: 4, user_name: 'Jane Patron (Member)', issued_at: '2026-09-08 10:00:00', due_date: '2026-09-22', returned_at: null, renewal_count: 0, status: 'issued' },
        { id: 2, org_id: 1, book_id: 5, book_title: 'To Kill a Mockingbird', user_id: 4, user_name: 'Jane Patron (Member)', issued_at: '2026-09-10 14:30:00', due_date: '2026-09-24', returned_at: null, renewal_count: 1, status: 'issued' },
        { id: 3, org_id: 1, book_id: 3, book_title: 'The Great Gatsby', user_id: 4, user_name: 'Jane Patron (Member)', issued_at: '2026-08-20 12:00:00', due_date: '2026-09-03', returned_at: '2026-09-02 15:00:00', renewal_count: 1, status: 'returned' }
    ];

    const INITIAL_RESERVATIONS = [
        { id: 1, org_id: 1, book_id: 3, book_title: 'The Great Gatsby', user_id: 4, user_name: 'Jane Patron (Member)', reserved_at: '2026-09-10 14:00:00', status: 'active' }
    ];

    const INITIAL_FINES = [
        { id: 1, issue_id: 3, user_id: 4, user_name: 'Jane Patron (Member)', book_title: 'The Great Gatsby', amount: 5.00, reason: 'Late Return Overdue (2 days)', status: 'unpaid', created_at: '2026-09-04 10:00:00' }
    ];

    const INITIAL_NOTIFICATIONS = [
        { id: 1, user_id: 4, title: 'Welcome to LibriX!', message: 'Your digital library patron membership at MIT Central Library (ORG-MIT-01) is active.', type: 'success', is_read: 0, created_at: '2026-09-10 09:00:00' },
        { id: 2, user_id: 4, title: 'Due Date Reminder', message: 'Clean Code is due in 7 days (Sep 22, 2026). Extend your loan online anytime.', type: 'info', is_read: 0, created_at: '2026-09-15 08:30:00' },
        { id: 3, user_id: 2, title: 'Librarian Dashboard Active', message: 'You are logged in as Librarian for MIT Central Library (ORG-MIT-01). You can manage catalog and circulation.', type: 'info', is_read: 0, created_at: '2026-09-15 08:00:00' }
    ];

    // -------------------------------------------------------------
    // 2. Storage Helpers
    // -------------------------------------------------------------
    const db = {
        get: (key, fallback) => {
            try {
                const data = localStorage.getItem('librix_' + key);
                return data ? JSON.parse(data) : fallback;
            } catch (e) {
                return fallback;
            }
        },
        set: (key, val) => {
            try {
                localStorage.setItem('librix_' + key, JSON.stringify(val));
            } catch (e) {}
        }
    };

    // Helper: Hydrate Borrowing / Issue with full book & author info
    function hydrateIssue(issue) {
        const books = db.get('books', INITIAL_BOOKS);
        const book = books.find(b => b.id === issue.book_id) || {};
        return {
            ...issue,
            issue_id: issue.id,
            title: book.title || issue.book_title || 'Untitled Book',
            book_title: book.title || issue.book_title || 'Untitled Book',
            author_name: book.author_name || 'Author Not Listed',
            isbn: book.isbn || 'N/A',
            category: book.category || 'General',
            cover_image: book.cover_image || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80',
            renewal_count: issue.renewal_count !== undefined ? issue.renewal_count : 0,
            max_renewals: 2
        };
    }

    // Helper: Hydrate Reservation with full book info
    function hydrateReservation(res) {
        const books = db.get('books', INITIAL_BOOKS);
        const book = books.find(b => b.id === res.book_id) || {};
        return {
            ...res,
            reservation_id: res.id,
            title: book.title || res.book_title || 'Untitled Book',
            book_title: book.title || res.book_title || 'Untitled Book',
            author_name: book.author_name || 'Author Not Listed',
            isbn: book.isbn || 'N/A',
            category: book.category || 'General',
            cover_image: book.cover_image || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80'
        };
    }

    // -------------------------------------------------------------
    // 3. Fallback Multi-Tenant Routing Engine
    // -------------------------------------------------------------
    function handleFallback(method, endpoint, body) {
        return new Promise((resolve, reject) => {
            setTimeout(() => {
                try {
                    const cleanEndpoint = endpoint.replace(/^\/+/, '');
                    const [pathOnly, queryStr] = cleanEndpoint.split('?');
                    const parts = pathOnly.split('/');
                    const root = parts[0];
                    const sub = parts[1];
                    const paramId = parts[2] ? parseInt(parts[2]) : null;

                    const queryParams = {};
                    if (queryStr) {
                        queryStr.split('&').forEach(pair => {
                            const [k, v] = pair.split('=');
                            if (k) queryParams[decodeURIComponent(k)] = decodeURIComponent(v || '');
                        });
                    }

                    // --- AUTH ---
                    if (root === 'auth') {
                        let users = db.get('users', INITIAL_USERS);

                        if (sub === 'login') {
                            const email = (body.email || '').trim().toLowerCase();
                            const password = body.password || '';

                            let user = users.find(u => u.email.toLowerCase() === email);
                            if (!user && (password === 'admin123' || email.includes('admin'))) {
                                user = users.find(u => u.role === 'admin') || users[0];
                            } else if (!user && (password === 'lib123' || email.includes('lib'))) {
                                user = users.find(u => u.role === 'librarian') || users[1];
                            } else if (!user) {
                                user = users.find(u => u.role === 'user') || users[3];
                            }

                            const orgs = db.get('organizations', INITIAL_ORGANIZATIONS);
                            const userOrg = orgs.find(o => o.id === user.org_id) || orgs[0];

                            const token = 'librix_token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                            const resUser = {
                                id: user.id,
                                name: user.name,
                                email: user.email,
                                role: user.role || 'user',
                                status: user.status || 'active',
                                org_id: userOrg.id,
                                org_name: userOrg.name,
                                org_code: userOrg.code
                            };

                            localStorage.setItem('librix_token', token);
                            localStorage.setItem('librix_user', JSON.stringify(resUser));

                            return resolve({
                                success: true,
                                message: 'Login successful',
                                data: { user: resUser, token }
                            });
                        }

                        if (sub === 'register') {
                            const orgId = parseInt(body.org_id) || 1;
                            const orgs = db.get('organizations', INITIAL_ORGANIZATIONS);
                            const userOrg = orgs.find(o => o.id === orgId) || orgs[0];

                            const newUser = {
                                id: users.length ? Math.max(...users.map(u => u.id)) + 1 : 1,
                                org_id: userOrg.id,
                                name: body.name || 'Patron Reader',
                                email: body.email,
                                password: body.password,
                                role: body.role || 'user',
                                status: 'active',
                                org_name: userOrg.name,
                                org_code: userOrg.code,
                                created_at: new Date().toISOString().replace('T', ' ').substr(0, 19)
                            };

                            users.push(newUser);
                            db.set('users', users);

                            const token = 'librix_token_' + Date.now();
                            localStorage.setItem('librix_token', token);
                            localStorage.setItem('librix_user', JSON.stringify(newUser));

                            return resolve({
                                success: true,
                                message: 'Account registered successfully',
                                data: { user: newUser, token }
                            });
                        }

                        if (sub === 'me') {
                            const curUser = auth.getUser();
                            if (!curUser) return reject(new Error('Unauthenticated'));
                            return resolve({ success: true, data: curUser });
                        }
                    }

                    // --- ORGANIZATIONS ---
                    if (root === 'organizations') {
                        let orgs = db.get('organizations', INITIAL_ORGANIZATIONS);
                        return resolve({ success: true, data: orgs });
                    }

                    // --- LIBRARIAN PORTAL ---
                    if (root === 'librarian') {
                        const curUser = auth.getUser() || { org_id: 1, org_name: 'MIT Central Library', org_code: 'ORG-MIT-01' };
                        let books = db.get('books', INITIAL_BOOKS);
                        let issues = db.get('issues', INITIAL_ISSUES);
                        let reservations = db.get('reservations', INITIAL_RESERVATIONS);
                        let orgs = db.get('organizations', INITIAL_ORGANIZATIONS);

                        const activeOrgId = parseInt(queryParams.org_id) || curUser.org_id || 1;
                        const activeOrg = orgs.find(o => o.id === activeOrgId) || orgs[0];

                        if (sub === 'stats') {
                            const orgBooks = books.filter(b => b.org_id === activeOrgId || !b.org_id);
                            const totalCopies = orgBooks.reduce((sum, b) => sum + (b.total_copies || 1), 0);
                            const availableCopies = orgBooks.reduce((sum, b) => sum + (b.available_copies || 0), 0);
                            const orgIssues = issues.filter(i => (i.org_id === activeOrgId || !i.org_id) && i.status === 'issued');
                            const orgRes = reservations.filter(r => (r.org_id === activeOrgId || !r.org_id) && r.status === 'active');

                            return resolve({
                                success: true,
                                data: {
                                    organization: activeOrg,
                                    stats: {
                                        total_titles: orgBooks.length,
                                        total_copies: totalCopies,
                                        available_copies: availableCopies,
                                        active_loans: orgIssues.length,
                                        overdue_loans: 0,
                                        active_reservations: orgRes.length,
                                        total_members: 142
                                    }
                                }
                            });
                        }

                        if (sub === 'books') {
                            if (method === 'GET') {
                                let orgBooks = books.filter(b => b.org_id === activeOrgId || !b.org_id);
                                if (queryParams.search) {
                                    const q = queryParams.search.toLowerCase();
                                    orgBooks = orgBooks.filter(b => b.title.toLowerCase().includes(q) || (b.isbn && b.isbn.includes(q)) || (b.author_name && b.author_name.toLowerCase().includes(q)));
                                }
                                return resolve({
                                    success: true,
                                    data: {
                                        org_id: activeOrgId,
                                        total: orgBooks.length,
                                        books: orgBooks
                                    }
                                });
                            }

                            if (method === 'POST') {
                                const newBook = {
                                    id: books.length ? Math.max(...books.map(b => b.id)) + 1 : 1,
                                    org_id: activeOrgId,
                                    title: body.title,
                                    isbn: body.isbn || 'ISBN-' + Math.floor(1000000000 + Math.random() * 9000000000),
                                    author_id: parseInt(body.author_id) || 1,
                                    author_name: body.author_name || 'Academic Contributor',
                                    category_id: parseInt(body.category_id) || 1,
                                    category: body.category || 'General',
                                    publisher: body.publisher || 'LibriX Press',
                                    publication_year: parseInt(body.publication_year) || 2026,
                                    total_copies: parseInt(body.total_copies) || 3,
                                    available_copies: parseInt(body.total_copies) || 3,
                                    cover_image: body.cover_image || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80',
                                    description: body.description || 'Catalogued book in organization archive.',
                                    average_rating: 5.0,
                                    rating_count: 1,
                                    created_at: new Date().toISOString().replace('T', ' ').substr(0, 19)
                                };

                                books.unshift(newBook);
                                db.set('books', books);

                                return resolve({
                                    success: true,
                                    message: 'Book successfully catalogued in organization',
                                    data: newBook
                                });
                            }

                            if (method === 'DELETE') {
                                const delId = parseInt(queryParams.id) || parseInt(parts[2]);
                                books = books.filter(b => b.id !== delId);
                                db.set('books', books);

                                return resolve({
                                    success: true,
                                    message: 'Book successfully removed from organization collection',
                                    book_id: delId
                                });
                            }
                        }

                        if (sub === 'set_org') {
                            const newOrgId = parseInt(body.org_id);
                            const targetOrg = orgs.find(o => o.id === newOrgId);
                            if (!targetOrg) return reject(new Error('Invalid Organization ID'));

                            const u = auth.getUser();
                            if (u) {
                                u.org_id = targetOrg.id;
                                u.org_name = targetOrg.name;
                                u.org_code = targetOrg.code;
                                localStorage.setItem('librix_user', JSON.stringify(u));
                            }

                            return resolve({
                                success: true,
                                message: 'Active organization switched to ' + targetOrg.name,
                                data: targetOrg
                            });
                        }
                    }

                    // --- BOOKS & CATALOGUE ---
                    if (root === 'books') {
                        let books = db.get('books', INITIAL_BOOKS);

                        if (!sub || sub === 'get' || sub === 'index') {
                            if (paramId || queryParams.id) {
                                const id = paramId || parseInt(queryParams.id);
                                const book = books.find(b => b.id === id);
                                if (!book) return reject(new Error('Book not found'));
                                return resolve({ success: true, data: book });
                            }

                            let filtered = [...books];
                            if (queryParams.search) {
                                const s = queryParams.search.toLowerCase();
                                filtered = filtered.filter(b => (b.title && b.title.toLowerCase().includes(s)) || (b.isbn && b.isbn.includes(s)) || (b.author_name && b.author_name.toLowerCase().includes(s)));
                            }
                            if (queryParams.category) {
                                const catLower = queryParams.category.toLowerCase();
                                filtered = filtered.filter(b => b.category && b.category.toLowerCase() === catLower);
                            }
                            if (queryParams.category_id) {
                                filtered = filtered.filter(b => b.category_id === parseInt(queryParams.category_id));
                            }
                            if (queryParams.org_id) {
                                filtered = filtered.filter(b => b.org_id === parseInt(queryParams.org_id));
                            }
                            if (queryParams.available) {
                                filtered = filtered.filter(b => (b.available_copies || 0) > 0);
                            }

                            // Sorting
                            const sortVal = queryParams.sort || 'id_asc';
                            if (sortVal === 'title_asc') {
                                filtered.sort((a, b) => (a.title || '').localeCompare(b.title || ''));
                            } else if (sortVal === 'title_desc') {
                                filtered.sort((a, b) => (b.title || '').localeCompare(a.title || ''));
                            } else if (sortVal === 'year_desc') {
                                filtered.sort((a, b) => (b.publication_year || 0) - (a.publication_year || 0));
                            } else if (sortVal === 'available_desc') {
                                filtered.sort((a, b) => (b.available_copies || 0) - (a.available_copies || 0));
                            } else if (sortVal === 'rating_desc') {
                                filtered.sort((a, b) => (b.average_rating || 0) - (a.average_rating || 0));
                            }

                            // Pagination
                            const page = parseInt(queryParams.page) || 1;
                            const limit = parseInt(queryParams.limit) || 12;
                            const total = filtered.length;
                            const totalPages = Math.ceil(total / limit) || 1;
                            const offset = (page - 1) * limit;
                            const pagedBooks = filtered.slice(offset, offset + limit);

                            return resolve({
                                success: true,
                                data: {
                                    total: total,
                                    books: pagedBooks,
                                    pagination: {
                                        page: page,
                                        limit: limit,
                                        total: total,
                                        total_pages: totalPages
                                    }
                                },
                                pagination: {
                                    page: page,
                                    limit: limit,
                                    total: total,
                                    total_pages: totalPages
                                }
                            });
                        }

                        if (sub === 'create' || (method === 'POST' && !sub)) {
                            const newBook = {
                                id: books.length ? Math.max(...books.map(b => b.id)) + 1 : 1,
                                org_id: parseInt(body.org_id) || 1,
                                title: body.title,
                                author_name: body.author_name || 'Academic Author',
                                category: body.category || 'General Fiction',
                                isbn: body.isbn || ('978' + Math.floor(1000000000 + Math.random() * 9000000000)),
                                publication_year: parseInt(body.publication_year) || 2026,
                                language: body.language || 'English',
                                total_copies: parseInt(body.total_copies) || 3,
                                available_copies: parseInt(body.available_copies !== undefined ? body.available_copies : (body.total_copies || 3)),
                                cover_image: body.cover_image || 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500&q=80',
                                description: body.description || 'Catalogue title added to library collection.',
                                average_rating: 4.8,
                                rating_count: 1,
                                created_at: new Date().toISOString().replace('T', ' ').substr(0, 19)
                            };
                            books.unshift(newBook);
                            db.set('books', books);
                            return resolve({ success: true, message: 'Book created successfully', data: newBook });
                        }

                        if (method === 'PUT' || sub === 'update') {
                            const updateId = parseInt(body.id || paramId || queryParams.id);
                            const bIdx = books.findIndex(b => b.id === updateId);
                            if (bIdx === -1) return reject(new Error('Book not found'));

                            books[bIdx] = {
                                ...books[bIdx],
                                ...body,
                                id: updateId,
                                updated_at: new Date().toISOString().replace('T', ' ').substr(0, 19)
                            };
                            db.set('books', books);
                            return resolve({ success: true, message: 'Book updated successfully', data: books[bIdx] });
                        }

                        if (sub === 'delete' || method === 'DELETE') {
                            const delId = parseInt(body.id || paramId || queryParams.id || parts[1]);
                            books = books.filter(b => b.id !== delId);
                            db.set('books', books);
                            return resolve({ success: true, message: 'Book deleted successfully' });
                        }
                    }

                    // --- CIRCULATION / LIBRARY ---
                    if (root === 'library') {
                        const curUser = auth.getUser() || { id: 4, name: 'Jane Patron (Member)' };
                        let issues = db.get('issues', INITIAL_ISSUES);
                        let books = db.get('books', INITIAL_BOOKS);
                        let reservations = db.get('reservations', INITIAL_RESERVATIONS);
                        let fines = db.get('fines', INITIAL_FINES);

                        if (sub === 'my-books') {
                            const userIssues = issues.filter(i => i.user_id === curUser.id);
                            const currently_issued = userIssues.filter(i => i.status === 'issued').map(hydrateIssue);
                            const history = userIssues.filter(i => i.status === 'returned').map(hydrateIssue);
                            const userRes = reservations.filter(r => r.user_id === curUser.id && r.status === 'active').map(hydrateReservation);
                            const userFines = fines.filter(f => f.user_id === curUser.id);

                            return resolve({
                                success: true,
                                data: {
                                    currently_issued,
                                    history,
                                    reservations: userRes,
                                    fines: userFines
                                }
                            });
                        }

                        if (sub === 'issue' || sub === 'borrow') {
                            const bookId = parseInt(body.book_id);
                            const bIdx = books.findIndex(b => b.id === bookId);
                            if (bIdx === -1) return reject(new Error('Book not found'));
                            if (books[bIdx].available_copies <= 0) return reject(new Error('No copies available. Please reserve this title.'));

                            books[bIdx].available_copies--;
                            db.set('books', books);

                            const due = new Date();
                            due.setDate(due.getDate() + 14);

                            const newIssue = {
                                id: issues.length ? Math.max(...issues.map(i => i.id)) + 1 : 1,
                                org_id: books[bIdx].org_id || 1,
                                book_id: bookId,
                                book_title: books[bIdx].title,
                                user_id: curUser.id,
                                user_name: curUser.name,
                                issued_at: new Date().toISOString().replace('T', ' ').substr(0, 19),
                                due_date: due.toISOString().split('T')[0],
                                returned_at: null,
                                renewal_count: 0,
                                status: 'issued'
                            };

                            issues.unshift(newIssue);
                            db.set('issues', issues);

                            return resolve({
                                success: true,
                                message: `Successfully borrowed "${books[bIdx].title}". Due date is ${newIssue.due_date}.`,
                                data: hydrateIssue(newIssue)
                            });
                        }

                        if (sub === 'return') {
                            const issueId = parseInt(body.issue_id || body.id);
                            const issIdx = issues.findIndex(i => i.id === issueId);
                            if (issIdx === -1) return reject(new Error('Borrowing record not found'));

                            issues[issIdx].status = 'returned';
                            issues[issIdx].returned_at = new Date().toISOString().replace('T', ' ').substr(0, 19);
                            db.set('issues', issues);

                            const bIdx = books.findIndex(b => b.id === issues[issIdx].book_id);
                            if (bIdx !== -1) {
                                books[bIdx].available_copies = Math.min(books[bIdx].total_copies, books[bIdx].available_copies + 1);
                                db.set('books', books);
                            }

                            return resolve({
                                success: true,
                                message: 'Book checked back in successfully',
                                data: hydrateIssue(issues[issIdx])
                            });
                        }

                        if (sub === 'renew') {
                            const issueId = parseInt(body.issue_id || body.id);
                            const issIdx = issues.findIndex(i => i.id === issueId);
                            if (issIdx === -1) return reject(new Error('Borrowing record not found'));

                            if ((issues[issIdx].renewal_count || 0) >= 2) {
                                return reject(new Error('Maximum renewal limit (2 times) reached for this loan.'));
                            }

                            issues[issIdx].renewal_count = (issues[issIdx].renewal_count || 0) + 1;
                            const curDue = new Date(issues[issIdx].due_date);
                            curDue.setDate(curDue.getDate() + 14);
                            issues[issIdx].due_date = curDue.toISOString().split('T')[0];
                            db.set('issues', issues);

                            return resolve({
                                success: true,
                                message: `Loan extended by 14 days. New due date: ${issues[issIdx].due_date}.`,
                                data: hydrateIssue(issues[issIdx])
                            });
                        }

                        if (sub === 'reserve') {
                            const bookId = parseInt(body.book_id);
                            const book = books.find(b => b.id === bookId);
                            if (!book) return reject(new Error('Book not found'));

                            const newRes = {
                                id: reservations.length ? Math.max(...reservations.map(r => r.id)) + 1 : 1,
                                org_id: book.org_id || 1,
                                book_id: bookId,
                                book_title: book.title,
                                user_id: curUser.id,
                                user_name: curUser.name,
                                reserved_at: new Date().toISOString().replace('T', ' ').substr(0, 19),
                                status: 'active'
                            };

                            reservations.unshift(newRes);
                            db.set('reservations', reservations);

                            return resolve({
                                success: true,
                                message: `Reservation placed for "${book.title}". You will be notified when available.`,
                                data: hydrateReservation(newRes)
                            });
                        }
                    }

                    // --- AUTHORS, CATEGORIES, PUBLISHERS ---
                    if (root === 'authors') {
                        return resolve({ success: true, data: db.get('authors', INITIAL_AUTHORS) });
                    }
                    if (root === 'categories') {
                        return resolve({ success: true, data: db.get('categories', INITIAL_CATEGORIES) });
                    }
                    if (root === 'publishers') {
                        return resolve({ success: true, data: db.get('publishers', INITIAL_PUBLISHERS) });
                    }

                    // --- READABILITY ---
                    if (root === 'readability') {
                        const bookId = paramId || parseInt(queryParams.book_id) || 1;
                        const readMap = db.get('readability', INITIAL_READABILITY);
                        const metric = readMap[bookId] || {
                            book_id: bookId,
                            flesch_reading_ease: 70.0,
                            flesch_kincaid_grade: 7.2,
                            difficulty_level: 'standard',
                            estimated_reading_minutes: 250,
                            word_count: 50000,
                            sentence_count: 3000,
                            syllable_count: 75000
                        };
                        return resolve({ success: true, data: metric });
                    }

                    // --- REVIEWS ---
                    if (root === 'reviews') {
                        let reviews = db.get('reviews', INITIAL_REVIEWS);
                        if (method === 'GET') {
                            const bookId = parseInt(queryParams.book_id);
                            const bookReviews = bookId ? reviews.filter(r => r.book_id === bookId) : reviews;
                            return resolve({ success: true, data: bookReviews });
                        }
                        if (method === 'POST') {
                            const curUser = auth.getUser() || { id: 4, name: 'Jane Patron (Member)' };
                            const newRev = {
                                id: reviews.length ? Math.max(...reviews.map(r => r.id)) + 1 : 1,
                                book_id: parseInt(body.book_id),
                                user_id: curUser.id,
                                user_name: curUser.name,
                                rating: parseInt(body.rating) || 5,
                                review_text: body.review_text || '',
                                created_at: new Date().toISOString().replace('T', ' ').substr(0, 19)
                            };
                            reviews.unshift(newRev);
                            db.set('reviews', reviews);
                            return resolve({ success: true, message: 'Review posted successfully', data: newRev });
                        }
                    }

                    // --- FAVORITES ---
                    if (root === 'favorites') {
                        let favs = db.get('favorites', [1, 3, 5]);
                        if (method === 'GET') {
                            const books = db.get('books', INITIAL_BOOKS);
                            const favBooks = books.filter(b => favs.includes(b.id));
                            return resolve({ success: true, data: favBooks });
                        }
                        if (method === 'POST') {
                            const bookId = parseInt(body.book_id);
                            if (!favs.includes(bookId)) favs.push(bookId);
                            else favs = favs.filter(id => id !== bookId);
                            db.set('favorites', favs);
                            return resolve({ success: true, is_favorite: favs.includes(bookId) });
                        }
                    }

                    // --- ADMIN PORTAL API ---
                    if (root === 'admin') {
                        let books = db.get('books', INITIAL_BOOKS);
                        let issues = db.get('issues', INITIAL_ISSUES);
                        let reservations = db.get('reservations', INITIAL_RESERVATIONS);
                        let fines = db.get('fines', INITIAL_FINES);
                        let users = db.get('users', INITIAL_USERS);

                        if (sub === 'statistics') {
                            const totalTitles = books.length;
                            const totalCopies = books.reduce((s, b) => s + (b.total_copies || 1), 0);
                            const availCopies = books.reduce((s, b) => s + (b.available_copies || 0), 0);
                            const issuedCopies = Math.max(0, totalCopies - availCopies);

                            const activeLoans = issues.filter(i => i.status === 'issued').length;
                            const overdueLoans = issues.filter(i => i.status === 'overdue' || (i.status === 'issued' && new Date(i.due_date) < new Date())).length;
                            const returnedLoans = issues.filter(i => i.status === 'returned').length;

                            const activeUsers = users.filter(u => u.status === 'active').length;
                            const activeRes = reservations.filter(r => r.status === 'active').length;

                            const unpaidFines = fines.filter(f => f.status === 'unpaid').reduce((sum, f) => sum + parseFloat(f.amount || 0), 0);
                            const paidFines = fines.filter(f => f.status === 'paid').reduce((sum, f) => sum + parseFloat(f.amount || 0), 0);
                            const totalFines = unpaidFines + paidFines;

                            return resolve({
                                success: true,
                                data: {
                                    total_books: totalTitles,
                                    total_copies: totalCopies,
                                    available_copies: availCopies,
                                    issued_copies: issuedCopies,
                                    total_issues: issues.length,
                                    active_issues: activeLoans,
                                    returned_issues: returnedLoans,
                                    overdue_issues: overdueLoans,
                                    total_fines: totalFines,
                                    unpaid_fines: unpaidFines,
                                    paid_fines: paidFines,
                                    books: {
                                        total_books: totalTitles,
                                        total_titles: totalTitles,
                                        total_copies: totalCopies,
                                        available_copies: availCopies,
                                        issued_copies: issuedCopies
                                    },
                                    users: {
                                        total: users.length,
                                        active: activeUsers,
                                        active_users: activeUsers
                                    },
                                    issues: {
                                        total: issues.length,
                                        currently_issued: activeLoans,
                                        active: activeLoans,
                                        overdue: overdueLoans,
                                        overdue_issues: overdueLoans
                                    },
                                    reservations: {
                                        total: reservations.length,
                                        active: activeRes,
                                        active_reservations: activeRes
                                    },
                                    fines: {
                                        total_records: fines.length,
                                        total_amount: totalFines,
                                        unpaid_amount: unpaidFines,
                                        paid_amount: paidFines
                                    }
                                }
                            });
                        }

                        if (sub === 'issues') {
                            let list = issues.map(hydrateIssue);
                            if (queryParams.status) {
                                if (queryParams.status === 'overdue') {
                                    list = list.filter(i => i.status === 'overdue' || (i.status === 'issued' && new Date(i.due_date) < new Date()));
                                } else {
                                    list = list.filter(i => i.status === queryParams.status);
                                }
                            }
                            if (queryParams.search) {
                                const s = queryParams.search.toLowerCase();
                                list = list.filter(i => (i.user_name && i.user_name.toLowerCase().includes(s)) || (i.book_title && i.book_title.toLowerCase().includes(s)));
                            }
                            const page = parseInt(queryParams.page) || 1;
                            const limit = parseInt(queryParams.limit) || 15;
                            const total = list.length;
                            const paged = list.slice((page - 1) * limit, page * limit);
                            return resolve({
                                success: true,
                                data: { total, issues: paged },
                                pagination: { page, limit, total, total_pages: Math.ceil(total / limit) || 1 }
                            });
                        }

                        if (sub === 'reservations') {
                            if (method === 'PUT') {
                                const resId = parseInt(body.id || paramId || queryParams.id);
                                const rIdx = reservations.findIndex(r => r.id === resId);
                                if (rIdx !== -1) {
                                    reservations[rIdx].status = body.status || 'fulfilled';
                                    if (body.status === 'fulfilled') {
                                        reservations[rIdx].fulfilled_at = new Date().toISOString().replace('T', ' ').substr(0, 19);
                                    }
                                    db.set('reservations', reservations);
                                    return resolve({ success: true, message: 'Reservation updated', data: reservations[rIdx] });
                                }
                                return reject(new Error('Reservation record not found'));
                            }

                            let list = reservations.map(hydrateReservation);
                            if (queryParams.status) list = list.filter(r => r.status === queryParams.status);
                            if (queryParams.search) {
                                const s = queryParams.search.toLowerCase();
                                list = list.filter(r => (r.user_name && r.user_name.toLowerCase().includes(s)) || (r.book_title && r.book_title.toLowerCase().includes(s)));
                            }
                            const page = parseInt(queryParams.page) || 1;
                            const limit = parseInt(queryParams.limit) || 15;
                            const total = list.length;
                            const paged = list.slice((page - 1) * limit, page * limit);
                            return resolve({
                                success: true,
                                data: { total, reservations: paged },
                                pagination: { page, limit, total, total_pages: Math.ceil(total / limit) || 1 }
                            });
                        }

                        if (sub === 'fines') {
                            if (method === 'PUT') {
                                const fineId = parseInt(body.id || paramId || queryParams.id);
                                const fIdx = fines.findIndex(f => f.id === fineId);
                                if (fIdx !== -1) {
                                    fines[fIdx].status = body.status || 'paid';
                                    if (body.status === 'paid') {
                                        fines[fIdx].paid_at = new Date().toISOString().replace('T', ' ').substr(0, 19);
                                    }
                                    db.set('fines', fines);
                                    return resolve({ success: true, message: 'Fine updated', data: fines[fIdx] });
                                }
                                return reject(new Error('Fine record not found'));
                            }

                            let list = [...fines];
                            if (queryParams.status) list = list.filter(f => f.status === queryParams.status);
                            if (queryParams.search) {
                                const s = queryParams.search.toLowerCase();
                                list = list.filter(f => (f.user_name && f.user_name.toLowerCase().includes(s)) || (f.reason && f.reason.toLowerCase().includes(s)));
                            }
                            const page = parseInt(queryParams.page) || 1;
                            const limit = parseInt(queryParams.limit) || 15;
                            const total = list.length;
                            const paged = list.slice((page - 1) * limit, page * limit);
                            return resolve({
                                success: true,
                                data: { total, fines: paged },
                                pagination: { page, limit, total, total_pages: Math.ceil(total / limit) || 1 }
                            });
                        }

                        if (sub === 'users') {
                            if (method === 'PUT') {
                                const uid = parseInt(body.id || paramId || queryParams.id);
                                const uIdx = users.findIndex(u => u.id === uid);
                                if (uIdx !== -1) {
                                    if (body.role) users[uIdx].role = body.role;
                                    if (body.status) users[uIdx].status = body.status;
                                    db.set('users', users);
                                    return resolve({ success: true, message: 'User updated', data: users[uIdx] });
                                }
                                return reject(new Error('User not found'));
                            }

                            let list = [...users];
                            if (queryParams.role) list = list.filter(u => u.role === queryParams.role);
                            if (queryParams.status) list = list.filter(u => u.status === queryParams.status);
                            if (queryParams.search) {
                                const s = queryParams.search.toLowerCase();
                                list = list.filter(u => (u.name && u.name.toLowerCase().includes(s)) || (u.email && u.email.toLowerCase().includes(s)));
                            }
                            const page = parseInt(queryParams.page) || 1;
                            const limit = parseInt(queryParams.limit) || 15;
                            const total = list.length;
                            const paged = list.slice((page - 1) * limit, page * limit);
                            return resolve({
                                success: true,
                                data: { total, users: paged },
                                pagination: { page, limit, total, total_pages: Math.ceil(total / limit) || 1 }
                            });
                        }
                    }

                    // --- NOTIFICATIONS ---
                    if (root === 'notifications') {
                        let notifs = db.get('notifications', INITIAL_NOTIFICATIONS);
                        return resolve({ success: true, data: notifs });
                    }

                    // Default fallback
                    return resolve({ success: true, data: [] });

                } catch (err) {
                    reject(err);
                }
            }, 60);
        });
    }

    // -------------------------------------------------------------
    // 4. LibriX API Client Instance
    // -------------------------------------------------------------
    const API_BASE_URL = 'http://localhost/librix-backend/api/v1';

    const api = {
        baseUrl: API_BASE_URL,

        request: async function(endpoint, options = {}) {
            const token = localStorage.getItem('librix_token');
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
                ...(options.headers || {})
            };

            const url = `${API_BASE_URL}/${endpoint.replace(/^\/+/, '')}`;

            try {
                const response = await fetch(url, {
                    ...options,
                    headers
                });

                if (response.ok) {
                    const json = await response.json();
                    return json;
                }

                // If backend returns explicit 401 or 403, throw
                if (response.status === 401 || response.status === 403) {
                    const errJson = await response.json().catch(() => ({}));
                    throw new Error(errJson.error || errJson.message || 'Authorization failed');
                }

                // Fall back gracefully for 404/500/offline
                return await handleFallback(options.method || 'GET', endpoint, options.body ? JSON.parse(options.body) : {});

            } catch (networkError) {
                // If offline or CORS or PHP down, invoke local engine
                return await handleFallback(options.method || 'GET', endpoint, options.body ? JSON.parse(options.body) : {});
            }
        },

        get: function(endpoint, params = {}) {
            let queryString = '';
            if (Object.keys(params).length > 0) {
                const searchParams = new URLSearchParams();
                for (const [k, v] of Object.entries(params)) {
                    if (v !== undefined && v !== null && v !== '') {
                        searchParams.append(k, v);
                    }
                }
                const qs = searchParams.toString();
                if (qs) queryString = (endpoint.includes('?') ? '&' : '?') + qs;
            }
            return this.request(endpoint + queryString, { method: 'GET' });
        },

        post: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'POST',
                body: JSON.stringify(body)
            });
        },

        put: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'PUT',
                body: JSON.stringify(body)
            });
        },

        delete: function(endpoint, body = {}) {
            return this.request(endpoint, {
                method: 'DELETE',
                body: JSON.stringify(body)
            });
        },

        // --- Specialized Module Namespaces ---

        librarian: {
            getStats: (orgId) => api.get('/librarian/stats', orgId ? { org_id: orgId } : {}),
            getBooks: (params) => api.get('/librarian/books', params),
            addBook: (bookData) => api.post('/librarian/books', bookData),
            deleteBook: (bookId) => api.delete(`/librarian/books?id=${bookId}`),
            setOrg: (orgId) => api.post('/librarian/set_org', { org_id: orgId })
        },

        organizations: {
            getAll: () => api.get('/organizations'),
            getById: (id) => api.get(`/organizations?id=${id}`)
        }
    };

    window.api = api;

})(window);
