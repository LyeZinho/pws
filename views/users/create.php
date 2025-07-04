<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Criar Usuário'; ?> - GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <!-- Cabeçalho -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-user-plus"></i> <?php echo $title ?? 'Criar Usuário'; ?></h1>
                <p class="text-muted">Adicionar um novo usuário ao sistema</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="?c=users&a=index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Voltar à Lista
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

        <!-- Formulário de Criação -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-form"></i> Dados do Novo Usuário
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="?c=users&a=store" novalidate>
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
                                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
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
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
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

                            <div class="row">
                                <!-- Password -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">
                                            <i class="fas fa-lock"></i> Senha *
                                        </label>
                                        <input type="password" 
                                               class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>" 
                                               id="password" 
                                               name="password" 
                                               required
                                               placeholder="Digite a senha">
                                        <div class="form-text">
                                            Senha deve ter pelo menos 6 caracteres
                                        </div>
                                        <?php if (isset($errors['password'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo is_array($errors['password']) ? implode(', ', $errors['password']) : $errors['password']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Confirmar Password -->
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">
                                            <i class="fas fa-lock"></i> Confirmar Senha *
                                        </label>
                                        <input type="password" 
                                               class="form-control" 
                                               id="confirm_password" 
                                               name="confirm_password" 
                                               required
                                               placeholder="Confirme a senha">
                                        <div class="form-text">
                                            Repita a senha para confirmação
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botões de Acção -->
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="reset" class="btn btn-outline-secondary me-md-2">
                                            <i class="fas fa-undo"></i> Limpar
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Criar Usuário
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
                <div class="card bg-light">
                    <div class="card-body">
                        <h6><i class="fas fa-info-circle"></i> Informações Importantes:</h6>
                        <ul class="mb-0">
                            <li>Todos os campos marcados com * são obrigatórios</li>
                            <li>O username deve ser único no sistema</li>
                            <li>O email deve ser válido e único</li>
                            <li>A senha será encriptada antes de ser armazenada</li>
                            <li>O usuário receberá as credenciais por email (funcionalidade futura)</li>
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
            
            if (password !== confirmPassword) {
                this.setCustomValidity('As senhas não coincidem');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
