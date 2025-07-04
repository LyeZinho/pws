<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - GEstufas</title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-container bg-white">
            <div class="logo">
                <h2 class="text-primary">GEstufas</h2>
                <p class="text-muted">Sistema de Gestão de Estufas</p>
            </div>
            
            <?php if (isset($data['error'])): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($data['error']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="?c=auth&a=login">
                <div class="mb-3">
                    <label for="username" class="form-label">Nome de usuário</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">Entrar</button>
            </form>
            
            <div class="text-center mt-3">
                <p>Não tem uma conta? <a href="?c=auth&a=register">Registre-se</a></p>
            </div>
        </div>
    </div>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>