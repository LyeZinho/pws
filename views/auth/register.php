<!-- 
    View para registro de novos usuários
    Formulário HTML com campos para username, email e password
    Exibe erros de validação se houver
-->
    

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Registrar Novo Usuário</h4>
            </div>
            <div class="card-body">
                <!-- Exibe erros de validação se existirem -->
                <?php if (isset($errors) && !empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $field => $fieldErrors): ?>
                                <?php foreach ($fieldErrors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Formulário de registro -->
                <form method="POST" action="?c=auth&a=register">
                    <div class="mb-3">
                        <label for="username" class="form-label">Nome de Usuário</label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               required
                               placeholder="Digite seu nome de usuário"
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               required
                               placeholder="Digite seu email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required
                               placeholder="Digite sua senha">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmar Senha</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirm" 
                               name="password_confirm" 
                               required
                               placeholder="Confirme sua senha">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-success">Registrar</button>
                    </div>
                </form>
                
                <!-- Link para login -->
                <div class="text-center mt-3">
                    <p>Já tem uma conta? 
                        <a href="?c=auth&a=login" class="text-decoration-none">Faça login aqui</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>