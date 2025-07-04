<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Editar Usuário'; ?> - GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-user-edit"></i> <?php echo $title ?? 'Editar Usuário'; ?></h1>
                <p class="text-muted">Actualizar informações do usuário: <strong><?php echo htmlspecialchars($user->username); ?></strong></p>
            </div>
            <div class="col-md-4 text-end">
                <a href="?c=users&a=show&id=<?php echo $user->id; ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> Visualizar
                </a>
                <a href="?c=users&a=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
            </div>
        </div>

        <!-- Mensagens de Erro -->
        <?php if (isset($errors) && !empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h6><i class="fas fa-exclamation-triangle"></i> Erros encontrados:</h6>
                <ul class="mb-0">
                    <?php foreach ($errors as $field => $fieldErrors): ?>
                        <?php if (is_array($fieldErrors)): ?>
                            <?php foreach ($fieldErrors as $error): ?>
                                <li><strong><?php echo ucfirst($field); ?>:</strong> <?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li><strong><?php echo ucfirst($field); ?>:</strong> <?php echo htmlspecialchars($fieldErrors); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Formulário de Edição -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-form"></i> Actualizar Dados do Usuário
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="?c=users&a=update" novalidate>
                            <!-- Campo oculto para ID -->
                            <input type="hidden" name="id" value="<?php echo $user->id; ?>">
                            
                            <div class="row">
                                <!-- Username -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">
                                            <i class="fas fa-user"></i> Username *
                                        </label>
                                        <input type="text" 
                                               class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>" 
                                               id="username" 
                                               name="username" 
                                               value="<?php echo htmlspecialchars($user->username); ?>"
                                               required
                                               placeholder="Digite o username">
                                        <div class="form-text">
                                            Nome único para identificação do usuário
                                        </div>
                                        <?php if (isset($errors['username'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo is_array($errors['username']) ? implode(', ', $errors['username']) : $errors['username']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope"></i> Email *
                                        </label>
                                        <input type="email" 
                                               class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                               id="email" 
                                               name="email" 
                                               value="<?php echo htmlspecialchars($user->email); ?>"
                                               required
                                               placeholder="Digite o email">
                                        <div class="form-text">
                                            Email válido para contacto
                                        </div>
                                        <?php if (isset($errors['email'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo is_array($errors['email']) ? implode(', ', $errors['email']) : $errors['email']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Separador -->
                            <hr class="my-4">
                            <h6><i class="fas fa-lock"></i> Alterar Senha (opcional)</h6>
                            <p class="text-muted">Deixe em branco se não desejar alterar a senha</p>

                            <div class="row">
                                <!-- Nova Password -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock"></i> Nova Senha
                                        </label>
                                        <input type="password" 
                                               class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                               id="password" 
                                               name="password" 
                                               placeholder="Digite a nova senha">
                                        <div class="form-text">
                                            Deixe em branco para manter a senha actual
                                        </div>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo is_array($errors['password']) ? implode(', ', $errors['password']) : $errors['password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Confirmar Nova Password -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock"></i> Confirmar Nova Senha
                                        </label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               placeholder="Confirme a nova senha">
                                        <div class="form-text">
                                            Repita a nova senha para confirmação
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Informações do Usuário -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6><i class="fas fa-info-circle"></i> Informações do Usuário</h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <small class="text-muted">ID:</small><br>
                                                    <strong><?php echo htmlspecialchars($user->id); ?></strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Criado em:</small><br>
                                                    <strong><?php echo date('d/m/Y H:i', strtotime($user->created_at)); ?></strong>
                                                </div>
                                                <div class="col-md-4">
                                                    <small class="text-muted">Última Actualização:</small><br>
                                                    <strong><?php echo date('d/m/Y H:i', strtotime($user->updated_at)); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de Acção -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <a href="?c=users&a=show&id=<?php echo $user->id; ?>" class="btn btn-outline-secondary me-md-2">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar Usuário
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-warning">
                    <div class="card-body">
                        <h6><i class="fas fa-exclamation-triangle"></i> Atenção:</h6>
                        <ul class="mb-0">
                            <li>Alterar o username pode afectar a identificação do usuário no sistema</li>
                            <li>Certifique-se de que o email é válido e acessível</li>
                            <li>A nova senha será encriptada antes de ser armazenada</li>
                            <li>Se deixar os campos de senha em branco, a senha actual será mantida</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="public/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para validação do formulário -->
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password && confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm_password');
            if (this.value && confirmPassword.value && this.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('As senhas não coincidem');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
