<?php
require_once 'config.php';

// Set content type to HTML
header('Content-Type: text/html; charset=UTF-8');

// Get API documentation
$api_docs = [
    'title' => 'Library Management System API',
    'description' => 'RESTful API for managing library resources including books, users, and more.',
    'version' => '1.0.0',
    'base_url' => 'http://localhost/LibraryMS/api',
    'authentication' => [
        'type' => 'API Key',
        'header' => 'api_key: library-ms-api-key-2024',
        'description' => 'All API requests require authentication using an API key.'
    ],
    'endpoints' => [
        'books' => [
            'base_path' => '/books.php',
            'methods' => [
                'GET' => [
                    'description' => 'Get all books or specific book',
                    'parameters' => [
                        'isbn' => [
                            'type' => 'string',
                            'required' => false,
                            'description' => 'ISBN of the book to retrieve'
                        ],
                        'page' => [
                            'type' => 'integer',
                            'required' => false,
                            'default' => 1,
                            'description' => 'Page number for pagination'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'required' => false,
                            'default' => 10,
                            'description' => 'Number of items per page'
                        ]
                    ]
                ],
                'POST' => [
                    'description' => 'Create a new book',
                    'body' => [
                        'required' => [
                            'isbn' => 'string',
                            'title' => 'string',
                            'author' => 'string'
                        ],
                        'optional' => [
                            'category_id' => 'integer',
                            'status_id' => 'integer',
                            'publication_year' => 'integer',
                            'publisher' => 'string',
                            'summary' => 'string',
                            'pages' => 'integer',
                            'weight' => 'decimal',
                            'dimensions' => 'string'
                        ]
                    ]
                ],
                'PUT' => [
                    'description' => 'Update an existing book',
                    'parameters' => [
                        'isbn' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'ISBN of the book to update'
                        ]
                    ],
                    'body' => [
                        'optional' => [
                            'title' => 'string',
                            'author' => 'string',
                            'category_id' => 'integer',
                            'status_id' => 'integer',
                            'publication_year' => 'integer',
                            'publisher' => 'string',
                            'summary' => 'string',
                            'pages' => 'integer',
                            'weight' => 'decimal',
                            'dimensions' => 'string'
                        ]
                    ]
                ],
                'DELETE' => [
                    'description' => 'Delete a book',
                    'parameters' => [
                        'isbn' => [
                            'type' => 'string',
                            'required' => true,
                            'description' => 'ISBN of the book to delete'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'status_codes' => [
        '200' => 'Success',
        '201' => 'Created',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '500' => 'Internal Server Error'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - Library Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../custom.css" rel="stylesheet">
    <style>
        .endpoint-card {
            margin-bottom: 1.5rem;
            background: rgba(255,255,255,0.95);
            border-radius: 18px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(36, 50, 94, 0.10);
        }
        .method-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .method-get { 
            background: linear-gradient(90deg, #28a745 0%, #34c759 100%);
            color: white;
        }
        .method-post { 
            background: linear-gradient(90deg, #007bff 0%, #4f8cff 100%);
            color: white;
        }
        .method-put { 
            background: linear-gradient(90deg, #ffc107 0%, #ffd60a 100%);
            color: #222;
        }
        .method-delete { 
            background: linear-gradient(90deg, #dc3545 0%, #ff4d4d 100%);
            color: white;
        }
        .code-block {
            background: rgba(255,255,255,0.95);
            padding: 1.5rem;
            border-radius: 12px;
            margin: 1rem 0;
            box-shadow: 0 4px 16px rgba(36, 50, 94, 0.08);
            border: 1px solid #e3e8f0;
        }
        .code-block code {
            color: #4f8cff;
            font-weight: 600;
        }
        .parameter-table {
            font-size: 0.95rem;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            overflow: hidden;
        }
        .parameter-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #4f8cff;
        }
        .parameter-table td {
            vertical-align: middle;
        }
        .parameter-table code {
            color: #4f8cff;
            font-weight: 600;
            background: rgba(79, 140, 255, 0.08);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
        }
        .list-group-item {
            border: none;
            padding: 0.75rem 1.25rem;
            background: transparent;
            color: #222;
            font-weight: 500;
            transition: all 0.2s;
        }
        .list-group-item:hover, .list-group-item:focus {
            background: rgba(79, 140, 255, 0.08);
            color: #4f8cff;
        }
        .list-group-item.active {
            background: rgba(79, 140, 255, 0.12);
            color: #4f8cff;
            font-weight: 600;
        }
        .card-header {
            background: rgba(255,255,255,0.95);
            border-bottom: 1px solid #e3e8f0;
            padding: 1.25rem;
        }
        .card-header h2, .card-header h5 {
            color: #4f8cff;
            font-weight: 600;
            margin: 0;
        }
        .card-body {
            padding: 1.5rem;
        }
        .card-title {
            color: #4f8cff;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .lead {
            color: #666;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Library Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../books.php">Books</a></li>
                    <li class="nav-item"><a class="nav-link" href="../members.php">Members</a></li>
                    <li class="nav-item"><a class="nav-link" href="../borrowings.php">Borrowings</a></li>
                    <li class="nav-item"><a class="nav-link active" href="documentation.php">API Docs</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>API Endpoints</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#books" class="list-group-item list-group-item-action">Books</a>
                        <a href="#authentication" class="list-group-item list-group-item-action">Authentication</a>
                        <a href="#status-codes" class="list-group-item list-group-item-action">Status Codes</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card mb-4">
                    <div class="card-body">
                        <h1 class="card-title"><?php echo htmlspecialchars($api_docs['title']); ?></h1>
                        <p class="lead"><?php echo htmlspecialchars($api_docs['description']); ?></p>
                        <p><strong>Version:</strong> <?php echo htmlspecialchars($api_docs['version']); ?></p>
                        <p><strong>Base URL:</strong> <code><?php echo htmlspecialchars($api_docs['base_url']); ?></code></p>
                    </div>
                </div>

                <div id="authentication" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Authentication</h2>
                    </div>
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($api_docs['authentication']['description']); ?></p>
                        <div class="code-block">
                            <code><?php echo htmlspecialchars($api_docs['authentication']['header']); ?></code>
                        </div>
                    </div>
                </div>

                <div id="books" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Books Endpoint</h2>
                    </div>
                    <div class="card-body">
                        <p>Base Path: <code><?php echo htmlspecialchars($api_docs['endpoints']['books']['base_path']); ?></code></p>
                        
                        <?php foreach ($api_docs['endpoints']['books']['methods'] as $method => $details): ?>
                            <div class="endpoint-card">
                                <h3 class="h5">
                                    <span class="badge method-badge method-<?php echo strtolower($method); ?>">
                                        <?php echo $method; ?>
                                    </span>
                                    <?php echo htmlspecialchars($details['description']); ?>
                                </h3>
                                
                                <?php if (isset($details['parameters'])): ?>
                                    <h4 class="h6 mt-3">Parameters:</h4>
                                    <table class="table table-sm parameter-table">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Required</th>
                                                <th>Description</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($details['parameters'] as $param => $info): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($param); ?></code></td>
                                                    <td><?php echo htmlspecialchars($info['type']); ?></td>
                                                    <td><?php echo $info['required'] ? 'Yes' : 'No'; ?></td>
                                                    <td><?php echo htmlspecialchars($info['description']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>

                                <?php if (isset($details['body'])): ?>
                                    <h4 class="h6 mt-3">Request Body:</h4>
                                    <?php if (isset($details['body']['required'])): ?>
                                        <h5 class="h6">Required Fields:</h5>
                                        <ul>
                                            <?php foreach ($details['body']['required'] as $field => $type): ?>
                                                <li><code><?php echo htmlspecialchars($field); ?></code> (<?php echo htmlspecialchars($type); ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($details['body']['optional'])): ?>
                                        <h5 class="h6">Optional Fields:</h5>
                                        <ul>
                                            <?php foreach ($details['body']['optional'] as $field => $type): ?>
                                                <li><code><?php echo htmlspecialchars($field); ?></code> (<?php echo htmlspecialchars($type); ?>)</li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div id="status-codes" class="card mb-4">
                    <div class="card-header">
                        <h2 class="h4">Status Codes</h2>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Description</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($api_docs['status_codes'] as $code => $description): ?>
                                    <tr>
                                        <td><code><?php echo $code; ?></code></td>
                                        <td><?php echo htmlspecialchars($description); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 