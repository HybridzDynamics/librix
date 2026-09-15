<?php

// API Documentation Page

require_once __DIR__ . "/../config/config.php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibriX API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .api-version {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            margin-top: 1rem;
            display: inline-block;
        }
        
        .nav {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav ul {
            list-style: none;
            display: flex;
            gap: 2rem;
            overflow-x: auto;
        }
        
        .nav a {
            color: #64748b;
            text-decoration: none;
            white-space: nowrap;
            padding: 0.5rem 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .nav a:hover, .nav a.active {
            color: #4f46e5;
            border-bottom-color: #4f46e5;
        }
        
        .section {
            background: white;
            margin: 2rem 0;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #1e293b;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .endpoint {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .endpoint-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .method {
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-weight: 600;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        
        .method.get { background: #10b981; color: white; }
        .method.post { background: #3b82f6; color: white; }
        .method.put { background: #f59e0b; color: white; }
        .method.delete { background: #ef4444; color: white; }
        
        .endpoint-url {
            font-family: monospace;
            background: #1e293b;
            color: #22d3ee;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        
        .endpoint-title {
            font-weight: 600;
            color: #1e293b;
        }
        
        .endpoint-description {
            color: #64748b;
            margin-bottom: 1rem;
        }
        
        .endpoint-auth {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 0.5rem 1rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }
        
        .endpoint-auth.admin {
            background: #fee2e2;
            border-color: #fca5a5;
            color: #991b1b;
        }
        
        .endpoint-params {
            margin-bottom: 1rem;
        }
        
        .endpoint-params h4 {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
        }
        
        .param-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.875rem;
        }
        
        .param-table th, .param-table td {
            padding: 0.5rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .param-table th {
            background: #f8fafc;
            font-weight: 600;
            color: #475569;
        }
        
        .param-table tr:last-child td {
            border-bottom: none;
        }
        
        .response-example {
            background: #1e293b;
            color: #22d3ee;
            padding: 1rem;
            border-radius: 0.375rem;
            font-family: monospace;
            font-size: 0.875rem;
            overflow-x: auto;
            margin-bottom: 1rem;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-public { background: #d1fae5; color: #065f46; }
        .badge-auth { background: #e0e7ff; color: #5b21b6; }
        .badge-admin { background: #fee2e2; color: #991b1b; }
        
        .toc {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin: 2rem 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .toc h3 {
            margin-bottom: 1rem;
            color: #1e293b;
        }
        
        .toc ul {
            list-style: none;
        }
        
        .toc li {
            margin-bottom: 0.5rem;
        }
        
        .toc a {
            color: #4f46e5;
            text-decoration: none;
        }
        
        .toc a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .nav ul {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .endpoint-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LibriX API Documentation</h1>
        <p>Complete REST API reference for the LibriX Library Management System</p>
        <div class="api-version">Version 1.0.0 | Base URL: /api/v1</div>
    </div>
    
    <div class="nav">
        <ul>
            <li><a href="#authentication" class="active">Authentication</a></li>
            <li><a href="#organizations">Organizations</a></li>
            <li><a href="#librarians">Librarians</a></li>
            <li><a href="#join-requests">Join Requests</a></li>
            <li><a href="#books">Books</a></li>
            <li><a href="#authors">Authors</a></li>
            <li><a href="#library">Library Operations</a></li>
            <li><a href="#users">Users</a></li>
            <li><a href="#admin">Admin</a></li>
            <li><a href="#readability">Readability</a></li>
        </ul>
    </div>
    
    <div class="container">
        <div class="toc">
            <h3>Quick Navigation</h3>
            <ul>
                <li><a href="#authentication">Authentication</a></li>
                <li><a href="#organizations">Organizations Management</a></li>
li>
                <li><a href="#librarians">Librarian Approval Workflow</a></li>
                <li><a href="#join-requests">Organization Join Requests</a></li>
                <li><a href="#books">Books Catalog</a></li>
                <li><a href="#library">Library Operations</a></li>
                <li><a href="#users">User Management</a></li>
                <li><a href="#admin">Admin Panel</a></li>
                <li><a href="#readability">Readability Analysis</a></li>
            </ul>
        </div>
        
        <div class="section" id="authentication">
            <h2>Authentication</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/auth/register</span>
                    </div>
                    <span class="badge badge-public">Public</span>
                </div>
                <div class="endpoint-description">Register a new user account</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>name</td><td>string</td><td>Yes</td><td>User's full name</td></tr>
                        <tr><td>email</td><td>string</td><td>Yes</td><td>User's email address</td></tr>
                        <tr><td>password</td><td>string</td><td>Yes</td><td>User's password (min 8 characters)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/auth/login</span>
                    </div>
                    <span class="badge badge-public">Public</span>
                </div>
                <div class="endpoint-description">Login with email and password</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>email</td><td>string</td><td>Yes</td><td>User's email address</td></tr>
                        <tr><td>password</td><td>string</td><td>Yes</td><td>User's password</td></tr>
                    </table>
                </div>
                <div class="response-example">
{
  "success": true,
  "data": {
    "token": "jwt_token_here",
    "expires_at": "2026-09-21 20:00:00",
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "status": "active"
    }
  },
  "message": "Login successful"
}
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/auth/logout</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Logout and invalidate token</div>
            </div>
        </div>
        
        <div class="section" id="organizations">
            <h2>Organizations Management</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/organizations</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Get all organizations (Admin sees all, regular users see public info)</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/organizations/{id}</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Get organization details by ID</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/organizations</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Create a new organization</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>name</td><td>string</td><td>Yes</td><td>Organization name</td></tr>
                        <tr><td>code</td><td>string</td><td>Yes</td><td>Unique organization code</td></tr>
                        <tr><td>description</td><td>string</td><td>No</td><td>Organization description</td></tr>
                        <tr><td>contact_email</td><td>string</td><td>No</td><td>Contact email</td></tr>
                        <tr><td>logo_url</td><td>string</td><td>No</td><td>Organization logo URL</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/organizations/{id}</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Update organization details</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method delete">DELETE</span>
                        <span class="endpoint-url">/organizations/{id}</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Delete organization (only if no books or users)</div>
            </div>
        </div>
        
        <div class="section" id="librarians">
            <h2>Librarian Approval Workflow</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/librarian/request</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Submit librarian approval request</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>library_name</td><td>string</td><td>Yes</td><td>Name of the library</td></tr>
                        <tr><td>library_address</td><td>string</td><td>No</td><td>Library address</td></tr>
                        <tr><td>library_phone</td><td>string</td><td>No</td><td>Library phone number</td></tr>
                        <tr><td>message</td><td>string</td><td>No</td><td>Additional message to admin</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/librarian/requests</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get all librarian requests (Admin only)</div>
                <div class="endpoint-params">
                    <h4>Query Parameters</h4>
                    <table class="param-table">
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                        <tr><td>status</td><td>string</td><td>Filter by status (pending, approved, rejected)</td></tr>
                        <tr><td>page</td><td>integer</td><td>Page number (default 1)</td></tr>
                        <tr><td>limit</td><td>integer</td><td>Items per page (default 20)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/librarian/approve</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Approve or reject librarian request</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>request_id</td><td>integer</td><td>Yes</td><td>Request ID to process</td></tr>
                        <tr><td>action</td><td>string</td><td>Yes</td><td>"approve" or "reject"</td></tr>
                        <tr><td>org_id</td><td>integer</td><td>No</td><td>Assign to existing organization (approve only)</td></tr>
                        <tr><td>admin_notes</td><td>string</td><td>No</td><td>Admin notes</td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="section" id="join-requests">
            <h2>Organization Join Requests</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/join/request</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
div>
                <div class="endpoint-description">Request to join an organization</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>org_code</td><td>string</td><td>Yes</td><td>Organization code to join</td></tr>
                        <tr><td>message</td><td>string</td><td>No</td><td>Message to organization admin</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/join/requests</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get all organization join requests (Admin only)</div>
                <div class="endpoint-params">
                    <h4>Query Parameters</h4>
                    <table class="param-table">
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                        <tr><td>status</td><td>string</td><td>Filter by status (pending, approved, rejected)</td></tr>
                        <tr><td>org_id</td><td>integer</td><td>Filter by organization ID</td></tr>
                        <tr><td>page</td><td>integer</td><td>Page number (default 1)</td></tr>
                        <tr><td>limit</td><td>integer</td><td>Items per page (default 20)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/join/approve</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Approve or reject join request</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>request_id</td><td>integer</td><td>Yes</td><td>Request ID to process</td></tr>
                        <tr><td>action</td><td>string</td><td>Yes</td><td>"approve" or "reject"</td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="section" id="books">
            <h2>Books Catalog</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/books</span>
                    </div>
                    <span class="badge badge-public">Public</span>
                </div>
                <div class="endpoint-description">Get books with search, filtering, and pagination</div>
                <div class="endpoint-params">
                    <h4>Query Parameters</h4>
                    <table class="param-table">
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                        <tr><td>search</td><td>string</td><td>Search in title, ISBN, author name</td></tr>
                        <tr><td>category</td><td>string</td><td>Filter by category</td></tr>
                        <tr><td>author_id</td><td>integer</td><td>Filter by author ID</td></tr>
                        <tr><td>available</td><td>boolean</td><td>Show only available copies</td></tr>
                        <tr><td>sort</td><td>string</td><td>Sort by (title, created_at, publication_year, id)</td></tr>
                        <tr><td>order</td><td>string</td><td>Sort order (asc, desc)</td></tr>
                        <tr><td>page</td><td>integer</td><td>Page number (default 1)</td></tr>
                        <tr><td>limit</td><td>integer</td><td>Items per page (default 20, max 100)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/books/{id}</span>
                    </div>
                    <span class="badge badge-public">Public</span>
                </div>
                <div class="endpoint-description">Get single book details</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/books</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Create new book</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/books/{id}</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Update book details</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method delete">DELETE</span>
                        <span class="endpoint-url">/books/{id}</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Delete book</div>
            </div>
        </div>
        
        <div class="section" id="library">
            <h2>Library Operations</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/library/issue</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Issue a book to user</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>book_id</td><td>integer</td><td>Yes</td><td>Book ID to issue</td></tr>
                        <tr><td>due_date</td><td>string</td><td>No</td><td>Due date (YYYY-MM-DD, default +14 days)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/library/return</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Return a borrowed book</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>issue_id</td><td>integer</td><td>Yes*</td><td>Issue ID to return</td></tr>
                        <tr><td>book_id</td><td>integer</td><td>Yes*</td><td>Book ID to return (alternative)</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/library/reserve</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Reserve a book</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>book_id</td><td>integer</td><td>Yes</td><td>Book ID to reserve</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/library/renew</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Renew a borrowed book</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/library/my-books</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Get user's library activity</div>
            </div>
        </div>
        
        <div class="section" id="users">
            <h2>User Management</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/users/profile</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Get user profile</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method put">PUT</span>
                        <span class="endpoint-url">/users/update</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
                <div class="endpoint-description">Update user profile</div>
            </div>
        </div>
        
        <div class="section" id="admin">
            <h2>Admin Panel</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/admin/users</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get all users with filtering</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/admin/statistics</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get system statistics</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/admin/books</span>
                    </div>
span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get books inventory report</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/admin/issues</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get all book issues</div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/admin/fines</span>
                    </div>
                    <span class="badge badge-admin">Admin Only</span>
                </div>
                <div class="endpoint-description">Get all fines</div>
            </div>
        </div>
        
        <div class="section" id="readability">
            <h2>Readability Analysis</h2>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method get">GET</span>
                        <span class="endpoint-url">/readability</span>
                    </div>
                    <span class="badge badge-public">Public</span>
                </div>
                <div class="endpoint-description">Get readability analysis for a book</div>
                <div class="endpoint-params">
                    <h4>Query Parameters</h4>
                    <table class="param-table">
                        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
                        <tr><td>book_id</td><td>integer</td><td>Book ID to analyze</td></tr>
                    </table>
                </div>
            </div>
            
            <div class="endpoint">
                <div class="endpoint-header">
                    <div>
                        <span class="method post">POST</span>
                        <span class="endpoint-url">/readability</span>
                    </div>
                    <span class="badge badge-auth">Auth Required</span>
                </div>
div>
                <div class="endpoint-description">Analyze text for readability metrics</div>
                <div class="endpoint-params">
                    <h4>Request Body</h4>
                    <table class="param-table">
                        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
                        <tr><td>book_id</td><td>integer</td><td>Yes</td><td>Book ID</td></tr>
                        <tr><td>text</td><td>string</td><td>Yes</td><td>Text sample to analyze</td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2>Response Format</h2>
            <p>All API responses follow this standard format:</p>
            <div class="response-example">
{
  "success": true,
  "data": { ... },
  "message": "Operation successful",
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 100,
    "total_pages": 5
  }
}
            </div>
            <p>Error responses:</p>
            <div class="response-example">
{
  "success": false,
  "error": "Error message",
  "errors": { ... }
}
            </div>
        </div>
        
        <div class="section">
            <h2>Authentication</h2>
            <p>Most endpoints require authentication via Bearer token in the Authorization header:</p>
            <div class="response-example">
Authorization: Bearer <token>
            </div>
            <p>Tokens are obtained via the login endpoint and are valid for 7 days by default.</p>
        </div>
        
        <div class="section">
            <h2>Rate Limiting</h2>
            <p>API endpoints are rate limited to prevent abuse:</p>
            <ul>
                <li>Login: 5 requests per 15 minutes</li>
                <li>Other auth endpoints: 10 requests per 15 minutes</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>Multi-Tenant Architecture</h2>
            <p>The system supports multiple organizations with the following features:</p>
            <ul>
                <li>Organizations can have their own books and users</li>
                <li>Librarians are approved by admins and assigned to organizations</li>
                <li>Users can request to join organizations via organization code</li>
                <li>Each organization's data is isolated</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Smooth scrolling for navigation
        document.querySelectorAll('.nav a').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href').substring(1);
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // Update active nav item on scroll
        window.addEventListener('scroll', () => {
            const sections = document.querySelectorAll('.section');
            const navLinks = document.querySelectorAll('.nav a');
            
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                if (pageYOffset >= sectionTop - 200) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href').substring(1) === current) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>