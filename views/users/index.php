<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Gestão de Usuários'; ?> - GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-users"></i> <?php echo $title ?? 'Gestão de Usuários'; ?></h1>
                <p class="text-muted">Gerir todos os usuários do sistema</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="?c=users&a=create" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Novo Usuário
                </a>
                <a href="?c=home&a=index" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Início
                </a>
            </div>
        </div>

        <!-- Mensagens de Feedback -->
        <?php if (isset($success) && $success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error) && $error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabela de Usuários -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-table"></i> Lista de Usuários
                    <span class="badge bg-secondary ms-2"><?php echo count($users ?? []); ?></span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (isset($users) && !empty($users)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Criado em</th>
                                    <th>Última Actualização</th>
                                    <th width="200">Acções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user->id); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user->username); ?></strong>
                                            <?php if ($user->id == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary">Você</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user->email); ?></td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y H:i', strtotime($user->updated_at)); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <!-- Visualizar -->
                                                <a href="?c=users&a=show&id=<?php echo $user->id; ?>" 
                                                   class="btn btn-sm btn-info" 
                                                   title="Visualizar">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <!-- Editar -->
                                                <a href="?c=users&a=edit&id=<?php echo $user->id; ?>" 
                                                   class="btn btn-sm btn-warning" 
                                                   title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                
                                                <!-- Eliminar (não permitir eliminar próprio usuário) -->
                                                <?php if ($user->id != $_SESSION['user_id']): ?>
                                                    <a href="?c=users&a=delete&id=<?php echo $user->id; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       title="Eliminar"
                                                       onclick="return confirm('Tem a certeza que deseja eliminar este usuário?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" 
                                                            title="Não pode eliminar o próprio usuário" 
                                                            disabled>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h4>Nenhum usuário encontrado</h4>
                        <p class="text-muted">Comece criando o primeiro usuário do sistema.</p>
                        <a href="?c=users&a=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Criar Primeiro Usuário
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo count($users ?? []); ?></h4>
                                <p>Total de Usuários</p>
                            </div>
                            <div>
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4><?php echo date('d/m/Y'); ?></h4>
                                <p>Data Actual</p>
                            </div>
                            <div>
                                <i class="fas fa-calendar fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4>Online</h4>
                                <p>Sistema Activo</p>
                            </div>
                            <div>
                                <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
