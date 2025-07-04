<!-- views/layout/default.php -->
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GEstufas' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="public/img/logo-ipleiria.png" alt="Logo" height="30">
                GEstufas
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="index.php?c=community&a=index">Comunidade</a>
                    <a class="nav-link" href="index.php?c=profile&a=index">Perfil</a>
                    <a class="nav-link" href="index.php?c=auth&a=logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="index.php?c=auth&a=index">Login</a>
                    <a class="nav-link" href="index.php?c=auth&a=register">Registar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container mt-4">
        <?php require_once($viewPath); ?>
    </main>
    
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2025 GEstufas - Sistema de Gestão de Estufas</p>
        </div>
    </footer>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>