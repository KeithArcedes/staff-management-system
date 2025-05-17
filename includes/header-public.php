<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' | ' : ''; ?><?php echo APP_NAME; ?></title>
    <!-- Bootstrap 5.3 CSS -->
    <link href="<?php echo APP_URL; ?>../assets/styles/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .navbar-brand {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4 fixed-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>"><?php echo APP_NAME; ?></a>
            <div class="navbar-nav">
                <a href="login.php" class="nav-link text-white">Staff Login</a>
            </div>
        </div>
    </nav>

    <main class="container my-4">