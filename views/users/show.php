<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Detalhes do Usuário'; ?> - GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-user"></i> <?php echo $title ?? 'Detalhes do Usuário'; ?></h1>
                <p class="text-muted">Informações completas do usuário</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="?c=users&a=edit&id=<?php echo $user->id; ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="?c=users&a=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Informações do Usuário -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-id-card"></i> Informações Pessoais
                        </h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong><i class="fas fa-hashtag"></i> ID:</strong></td>
                                <td><?php echo htmlspecialchars($user->id); ?></td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-user"></i> Username:</strong></td>
                                <td>
                                    <?php echo htmlspecialchars($user->username); ?>
                                    <?php if ($user->id == $_SESSION['user_id']): ?>
                                        <span class="badge bg-primary">Você</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-envelope"></i> Email:</strong></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($user->email); ?>">
                                        <?php echo htmlspecialchars($user->email); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-calendar-plus"></i> Criado em:</strong></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($user->created_at)); ?></td>
                            </tr>
                            <tr>
                                <td><strong><i class="fas fa-calendar-check"></i> Última Actualização:</strong></td>
                                <td><?php echo date('d/m/Y H:i:s', strtotime($user->updated_at)); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar"></i> Estatísticas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="stat-box">
                                    <h3 class="text-primary"><?php echo count($posts ?? []); ?></h3>
                                    <p class="text-muted">Posts</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-box">
                                    <h3 class="text-success"><?php echo count($projects ?? []); ?></h3>
                                    <p class="text-muted">Projetos</p>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-box">
                                    <h3 class="text-info"><?php echo count($posts ?? []) + count($projects ?? []); ?></h3>
                                    <p class="text-muted">Total</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acções Rápidas -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-bolt"></i> Acções Rápidas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="?c=users&a=edit&id=<?php echo $user->id; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Editar Usuário
                            </a>
                            <?php if ($user->id != $_SESSION['user_id']): ?>
                                <a href="?c=users&a=delete&id=<?php echo $user->id; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Tem a certeza que deseja eliminar este usuário?')">
                                    <i class="fas fa-trash"></i> Eliminar Usuário
                                </a>
                            <?php endif; ?>
                            <a href="mailto:<?php echo htmlspecialchars($user->email); ?>" class="btn btn-info btn-sm">
                                <i class="fas fa-envelope"></i> Enviar Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Posts do Usuário -->
        <?php if (isset($posts) && !empty($posts)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-newspaper"></i> Posts do Usuário
                                <span class="badge bg-primary"><?php echo count($posts); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Título</th>
                                            <th>Criado em</th>
                                            <th>Acções</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($posts as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($post->id); ?></td>
                                                <td><?php echo htmlspecialchars($post->title); ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($post->created_at)); ?></td>
                                                <td>
                                                    <a href="?c=community&a=show&id=<?php echo $post->id; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Projetos do Usuário -->
        <?php if (isset($projects) && !empty($projects)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-project-diagram"></i> Projetos do Usuário
                                <span class="badge bg-success"><?php echo count($projects); ?></span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Nome</th>
                                            <th>Descrição</th>
                                            <th>Criado em</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($projects as $project): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($project->id); ?></td>
                                                <td><?php echo htmlspecialchars($project->name); ?></td>
                                                <td><?php echo htmlspecialchars(substr($project->description, 0, 50)) . '...'; ?></td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($project->created_at)); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Se não há posts nem projetos -->
        <?php if (empty($posts) && empty($projects)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Este usuário ainda não criou nenhum post ou projeto.
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="public/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .stat-box {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .stat-box h3 {
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .stat-box p {
            margin-bottom: 0;
            font-size: 0.9rem;
        }
    </style>
</body>
</html>
