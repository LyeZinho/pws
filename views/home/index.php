<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'GEstufas - Sistema de Gestão de Estufas'; ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }
        .feature-icon {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .stats-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .footer-section {
            background-color: #2c3e50;
            color: white;
            padding: 40px 0;
        }
    </style>
</head>
<body>
    <!-- Navegação -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="?c=home&a=index">
                <i class="fas fa-seedling"></i> GEstufas
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="?c=home&a=index">
                            <i class="fas fa-home"></i> Início
                        </a>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?c=community&a=index">
                                <i class="fas fa-users"></i> Comunidade
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?c=users&a=index">
                                <i class="fas fa-user-cog"></i> Usuários
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($currentUser->username ?? 'Usuário'); ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?c=profile&a=index">
                                    <i class="fas fa-user-circle"></i> Meu Perfil
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?c=auth&a=logout">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="?c=auth&a=login">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="?c=auth&a=register">
                                <i class="fas fa-user-plus"></i> Registrar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Seção Hero -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold mb-4">
                        <?php echo $title ?? 'GEstufas'; ?>
                    </h1>
                    <p class="lead mb-4">
                        <?php echo $message ?? 'Bem-vindo ao sistema de gestão de estufas!'; ?>
                    </p>
                    <?php if (!$isLoggedIn): ?>
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="?c=auth&a=register" class="btn btn-light btn-lg me-md-2">
                                <i class="fas fa-user-plus"></i> Começar Agora
                            </a>
                            <a href="?c=auth&a=login" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Fazer Login
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-grid gap-2 d-md-flex">
                            <a href="?c=community&a=index" class="btn btn-light btn-lg me-md-2">
                                <i class="fas fa-users"></i> Ver Comunidade
                            </a>
                            <a href="?c=users&a=index" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-cog"></i> Gerir Sistema
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-seedling" style="font-size: 8rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Estatísticas -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <i class="fas fa-users feature-icon"></i>
                            <h3><?php echo $stats['totalUsers'] ?? 0; ?></h3>
                            <p class="text-muted">Usuários</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <i class="fas fa-newspaper feature-icon"></i>
                            <h3><?php echo $stats['totalPosts'] ?? 0; ?></h3>
                            <p class="text-muted">Posts</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <i class="fas fa-project-diagram feature-icon"></i>
                            <h3><?php echo $stats['totalProjects'] ?? 0; ?></h3>
                            <p class="text-muted">Projetos</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="card stats-card text-center">
                        <div class="card-body">
                            <i class="fas fa-comments feature-icon"></i>
                            <h3><?php echo $stats['totalComments'] ?? 0; ?></h3>
                            <p class="text-muted">Comentários</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Posts Recentes -->
    <?php if (!empty($recentPosts)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">
                <i class="fas fa-newspaper"></i> Posts Recentes
            </h2>
            <div class="row">
                <?php foreach (array_slice($recentPosts, 0, 3) as $post): ?>
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($post->title); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(substr($post->content, 0, 150)) . '...'; ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Por <?php echo htmlspecialchars($post->user->username); ?>
                                    </small>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($post->created_at)); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="?c=community&a=show&id=<?php echo $post->id; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Ler Mais
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center">
                <a href="?c=community&a=index" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-right"></i> Ver Todos os Posts
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Funcionalidades -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">
                <i class="fas fa-star"></i> Funcionalidades
            </h2>
            <div class="row">
                <div class="col-lg-4 text-center mb-4">
                    <i class="fas fa-users feature-icon"></i>
                    <h4>Gestão de Usuários</h4>
                    <p class="text-muted">Sistema completo de CRUD para gerir usuários do sistema com validações e segurança.</p>
                </div>
                <div class="col-lg-4 text-center mb-4">
                    <i class="fas fa-comments feature-icon"></i>
                    <h4>Comunidade</h4>
                    <p class="text-muted">Plataforma para partilhar posts, comentários e interagir com outros utilizadores.</p>
                </div>
                <div class="col-lg-4 text-center mb-4">
                    <i class="fas fa-chart-bar feature-icon"></i>
                    <h4>Relatórios</h4>
                    <p class="text-muted">Dashboard com estatísticas detalhadas e relatórios de atividade do sistema.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <h5><i class="fas fa-seedling"></i> GEstufas</h5>
                    <p>Sistema de gestão de estufas desenvolvido com PHP, MySQL e Bootstrap.</p>
                </div>
                <div class="col-lg-6">
                    <h5>Links Úteis</h5>
                    <ul class="list-unstyled">
                        <li><a href="?c=home&a=index" class="text-light"><i class="fas fa-home"></i> Início</a></li>
                        <?php if ($isLoggedIn): ?>
                            <li><a href="?c=community&a=index" class="text-light"><i class="fas fa-users"></i> Comunidade</a></li>
                            <li><a href="?c=users&a=index" class="text-light"><i class="fas fa-user-cog"></i> Usuários</a></li>
                        <?php else: ?>
                            <li><a href="?c=auth&a=login" class="text-light"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                            <li><a href="?c=auth&a=register" class="text-light"><i class="fas fa-user-plus"></i> Registrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p>&copy; 2025 GEstufas. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
