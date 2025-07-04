# Guia MVC com Autenticação - Sistema de Login Simples

## Índice
- [Introdução](#introdução)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Sistema de Autenticação](#sistema-de-autenticação)
- [Middleware de Autenticação](#middleware-de-autenticação)
- [Controllers de Auth](#controllers-de-auth)
- [Views de Autenticação](#views-de-autenticação)
- [Proteção de Rotas](#proteção-de-rotas)
- [Gerenciamento de Sessões](#gerenciamento-de-sessões)
- [Setup do Ambiente](#setup-do-ambiente)
- [Resolução de Problemas](#resolução-de-problemas)

## Introdução

Este guia implementa um sistema completo de autenticação no padrão MVC, incluindo login, logout, registro de usuários, proteção de rotas e gerenciamento de sessões.

### Recursos Implementados:
- Sistema de login/logout
- Registro de novos usuários
- Proteção de rotas por middleware
- Verificação de permissões
- Lembrar login (Remember Me)
- Recuperação de senha
- Validação de dados de autenticação

## Estrutura do Projeto

```
projeto-mvc-auth/
├── app/
│   ├── Controllers/
│   │   ├── BaseController.php
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── UserController.php
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   └── Session.php
│   ├── Middleware/
│   │   ├── AuthMiddleware.php
│   │   ├── GuestMiddleware.php
│   │   └── RoleMiddleware.php
│   └── Views/
│       ├── layouts/
│       │   ├── main.php
│       │   └── auth.php
│       ├── auth/
│       │   ├── login.php
│       │   ├── register.php
│       │   ├── forgot-password.php
│       │   └── reset-password.php
│       ├── dashboard/
│       │   └── index.php
│       └── users/
│           └── profile.php
├── core/
│   ├── Application.php
│   ├── Router.php
│   ├── Controller.php
│   ├── Model.php
│   ├── Auth.php
│   ├── Session.php
│   └── Middleware.php
├── config/
│   ├── auth.php
│   └── database.php
├── public/
│   └── index.php
└── .htaccess
```

## Sistema de Autenticação

### core/Auth.php
```php
<?php
namespace Core;

use App\Models\User;

class Auth {
    private static $user = null;
    private static $session;
    
    public static function init() {
        self::$session = new Session();
        self::loadUser();
    }
    
    public static function attempt($email, $password, $remember = false) {
        $user = User::findByEmail($email);
        
        if (!$user || !$user->verifyPassword($password)) {
            return false;
        }
        
        if (!$user->isActive()) {
            return false;
        }
        
        self::login($user, $remember);
        return true;
    }
    
    public static function login(User $user, $remember = false) {
        self::$user = $user;
        
        // Armazenar na sessão
        self::$session->set('user_id', $user->id);
        self::$session->set('user_email', $user->email);
        self::$session->set('logged_in', true);
        
        // Regenerar ID da sessão por segurança
        self::$session->regenerate();
        
        // Remember me
        if ($remember) {
            self::setRememberToken($user);
        }
        
        // Atualizar último login
        $user->updateLastLogin();
        
        // Log da atividade
        self::logActivity('login', $user->id);
    }
    
    public static function logout() {
        if (self::$user) {
            self::logActivity('logout', self::$user->id);
        }
        
        // Limpar remember token
        self::clearRememberToken();
        
        // Limpar sessão
        self::$session->destroy();
        
        self::$user = null;
    }
    
    public static function check() {
        return self::$user !== null;
    }
    
    public static function guest() {
        return self::$user === null;
    }
    
    public static function user() {
        return self::$user;
    }
    
    public static function id() {
        return self::$user ? self::$user->id : null;
    }
    
    private static function loadUser() {
        $userId = self::$session->get('user_id');
        
        if ($userId) {
            self::$user = User::find($userId);
            
            // Verificar se usuário ainda é válido
            if (!self::$user || !self::$user->isActive()) {
                self::logout();
            }
        } else {
            // Tentar carregar via remember token
            self::loadUserFromRememberToken();
        }
    }
    
    private static function setRememberToken(User $user) {
        $token = self::generateToken();
        $expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Salvar token no banco
        $user->updateRememberToken($token, $expiry);
        
        // Salvar cookie
        setcookie(
            'remember_token',
            $token,
            time() + (30 * 24 * 60 * 60), // 30 dias
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true // HttpOnly
        );
    }
    
    private static function loadUserFromRememberToken() {
        if (!isset($_COOKIE['remember_token'])) {
            return;
        }
        
        $token = $_COOKIE['remember_token'];
        $user = User::findByRememberToken($token);
        
        if ($user && $user->isRememberTokenValid()) {
            self::login($user, true); // Renovar remember token
        } else {
            self::clearRememberToken();
        }
    }
    
    private static function clearRememberToken() {
        if (self::$user) {
            self::$user->clearRememberToken();
        }
        
        setcookie(
            'remember_token',
            '',
            time() - 3600,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }
    
    private static function generateToken() {
        return bin2hex(random_bytes(32));
    }
    
    private static function logActivity($action, $userId) {
        // Log simples - em produção usar tabela específica
        error_log("Auth activity: {$action} for user {$userId} from IP " . $_SERVER['REMOTE_ADDR']);
    }
    
    public static function validatePasswordReset($token, $email) {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return false;
        }
        
        return $user->isPasswordResetTokenValid($token);
    }
    
    public static function resetPassword($token, $email, $newPassword) {
        if (!self::validatePasswordReset($token, $email)) {
            return false;
        }
        
        $user = User::findByEmail($email);
        $user->password = $newPassword;
        $user->clearPasswordResetToken();
        
        return $user->save() === true;
    }
}
```

### core/Session.php
```php
<?php
namespace Core;

class Session {
    
    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            $this->configureSession();
            session_start();
        }
    }
    
    private function configureSession() {
        // Configurações de segurança
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
        ini_set('session.cookie_samesite', 'Strict');
        
        // Tempo de vida da sessão (2 horas)
        ini_set('session.gc_maxlifetime', 7200);
        ini_set('session.cookie_lifetime', 7200);
    }
    
    public function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    public function get($key, $default = null) {
        return $_SESSION[$key] ?? $default;
    }
    
    public function has($key) {
        return isset($_SESSION[$key]);
    }
    
    public function remove($key) {
        unset($_SESSION[$key]);
    }
    
    public function flash($key, $value = null) {
        if ($value === null) {
            // Recuperar e remover
            $value = $this->get("flash.{$key}");
            $this->remove("flash.{$key}");
            return $value;
        } else {
            // Definir
            $this->set("flash.{$key}", $value);
        }
    }
    
    public function regenerate() {
        session_regenerate_id(true);
    }
    
    public function destroy() {
        session_unset();
        session_destroy();
        
        // Limpar cookie da sessão
        if (isset($_COOKIE[session_name()])) {
            setcookie(
                session_name(),
                '',
                time() - 3600,
                '/',
                '',
                isset($_SERVER['HTTPS']),
                true
            );
        }
    }
    
    public function all() {
        return $_SESSION;
    }
}
```

## Middleware de Autenticação

### core/Middleware.php
```php
<?php
namespace Core;

abstract class Middleware {
    abstract public function handle($request, $next);
    
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    protected function abort($code = 403) {
        http_response_code($code);
        echo "Acesso negado";
        exit;
    }
}
```

### app/Middleware/AuthMiddleware.php
```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Auth;

class AuthMiddleware extends Middleware {
    
    public function handle($request, $next) {
        if (Auth::guest()) {
            // Salvar URL de destino para redirect após login
            if (isset($_GET['url'])) {
                $_SESSION['intended_url'] = $_GET['url'];
            }
            
            $this->redirect('/login');
        }
        
        return $next($request);
    }
}
```

### app/Middleware/GuestMiddleware.php
```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Auth;

class GuestMiddleware extends Middleware {
    
    public function handle($request, $next) {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        
        return $next($request);
    }
}
```

### app/Middleware/RoleMiddleware.php
```php
<?php
namespace App\Middleware;

use Core\Middleware;
use Core\Auth;

class RoleMiddleware extends Middleware {
    private $requiredRole;
    
    public function __construct($requiredRole) {
        $this->requiredRole = $requiredRole;
    }
    
    public function handle($request, $next) {
        if (Auth::guest()) {
            $this->redirect('/login');
        }
        
        $user = Auth::user();
        
        if (!$user->hasRole($this->requiredRole)) {
            $this->abort(403);
        }
        
        return $next($request);
    }
}
```

## Controllers de Auth

### app/Controllers/AuthController.php
```php
<?php
namespace App\Controllers;

use Core\Auth;
use App\Models\User;

class AuthController extends BaseController {
    
    public function showLogin() {
        // Middleware guest aplicado via route
        $this->render('auth/login', [
            'title' => 'Login',
            'layout' => 'auth'
        ]);
    }
    
    public function login() {
        $input = $this->getInput();
        
        $errors = $this->validate($input, [
            'email' => 'required|email',
            'password' => 'required'
        ]);
        
        if (!empty($errors)) {
            $this->render('auth/login', [
                'title' => 'Login',
                'layout' => 'auth',
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }
        
        $remember = isset($input['remember']) && $input['remember'] === '1';
        
        if (Auth::attempt($input['email'], $input['password'], $remember)) {
            // Redirect para URL pretendida ou dashboard
            $intendedUrl = $_SESSION['intended_url'] ?? '/dashboard';
            unset($_SESSION['intended_url']);
            
            $this->redirect($intendedUrl);
        } else {
            $this->render('auth/login', [
                'title' => 'Login',
                'layout' => 'auth',
                'errors' => ['auth' => 'Credenciais inválidas'],
                'old' => $input
            ]);
        }
    }
    
    public function showRegister() {
        $this->render('auth/register', [
            'title' => 'Registro',
            'layout' => 'auth'
        ]);
    }
    
    public function register() {
        $input = $this->getInput();
        
        $errors = $this->validate($input, [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);
        
        // Validar confirmação de senha
        if ($input['password'] !== $input['password_confirmation']) {
            $errors['password_confirmation'] = 'Confirmação de senha não confere';
        }
        
        if (!empty($errors)) {
            $this->render('auth/register', [
                'title' => 'Registro',
                'layout' => 'auth',
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }
        
        try {
            $user = new User();
            $user->fill([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'role' => 'user',
                'status' => 'active'
            ]);
            
            $result = $user->save();
            
            if ($result === true) {
                // Auto-login após registro
                Auth::login($user);
                
                $this->setMessage('success', 'Conta criada com sucesso!');
                $this->redirect('/dashboard');
            } else {
                $this->render('auth/register', [
                    'title' => 'Registro',
                    'layout' => 'auth',
                    'errors' => $result,
                    'old' => $input
                ]);
            }
            
        } catch (\Exception $e) {
            $this->render('auth/register', [
                'title' => 'Registro',
                'layout' => 'auth',
                'errors' => ['general' => 'Erro ao criar conta: ' . $e->getMessage()],
                'old' => $input
            ]);
        }
    }
    
    public function logout() {
        Auth::logout();
        $this->setMessage('success', 'Logout realizado com sucesso');
        $this->redirect('/');
    }
    
    public function showForgotPassword() {
        $this->render('auth/forgot-password', [
            'title' => 'Recuperar Senha',
            'layout' => 'auth'
        ]);
    }
    
    public function sendPasswordReset() {
        $input = $this->getInput();
        
        $errors = $this->validate($input, [
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $this->render('auth/forgot-password', [
                'title' => 'Recuperar Senha',
                'layout' => 'auth',
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }
        
        $user = User::findByEmail($input['email']);
        
        if ($user) {
            $token = $user->generatePasswordResetToken();
            $this->sendPasswordResetEmail($user, $token);
        }
        
        // Sempre mostrar mensagem de sucesso (segurança)
        $this->setMessage('success', 'Se o email existir, você receberá instruções para redefinir sua senha');
        $this->redirect('/login');
    }
    
    public function showResetPassword($token) {
        $email = $_GET['email'] ?? '';
        
        if (!Auth::validatePasswordReset($token, $email)) {
            $this->setMessage('error', 'Token de redefinição inválido ou expirado');
            $this->redirect('/forgot-password');
            return;
        }
        
        $this->render('auth/reset-password', [
            'title' => 'Redefinir Senha',
            'layout' => 'auth',
            'token' => $token,
            'email' => $email
        ]);
    }
    
    public function resetPassword($token) {
        $input = $this->getInput();
        
        $errors = $this->validate($input, [
            'password' => 'required|min:6',
            'password_confirmation' => 'required'
        ]);
        
        if ($input['password'] !== $input['password_confirmation']) {
            $errors['password_confirmation'] = 'Confirmação de senha não confere';
        }
        
        if (!empty($errors)) {
            $this->render('auth/reset-password', [
                'title' => 'Redefinir Senha',
                'layout' => 'auth',
                'errors' => $errors,
                'token' => $token,
                'email' => $input['email'] ?? ''
            ]);
            return;
        }
        
        if (Auth::resetPassword($token, $input['email'], $input['password'])) {
            $this->setMessage('success', 'Senha redefinida com sucesso!');
            $this->redirect('/login');
        } else {
            $this->setMessage('error', 'Erro ao redefinir senha');
            $this->redirect('/forgot-password');
        }
    }
    
    private function sendPasswordResetEmail($user, $token) {
        $resetUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . 
                   "/reset-password/{$token}?email=" . urlencode($user->email);
        
        // Em produção, usar biblioteca de email adequada
        $subject = "Redefinição de senha";
        $message = "Clique no link para redefinir sua senha: {$resetUrl}";
        
        // mail($user->email, $subject, $message);
        
        // Para desenvolvimento, apenas loggar
        error_log("Password reset email sent to {$user->email}: {$resetUrl}");
    }
}
```

### app/Controllers/DashboardController.php
```php
<?php
namespace App\Controllers;

use Core\Auth;
use App\Models\User;

class DashboardController extends BaseController {
    
    public function __construct() {
        parent::__construct();
        // Middleware auth aplicado via route
    }
    
    public function index() {
        $user = Auth::user();
        
        $stats = [
            'total_users' => User::count(),
            'user_since' => $user->created_at,
            'last_login' => $user->last_login_at
        ];
        
        $this->render('dashboard/index', [
            'title' => 'Dashboard',
            'user' => $user,
            'stats' => $stats
        ]);
    }
    
    public function profile() {
        $user = Auth::user();
        
        $this->render('users/profile', [
            'title' => 'Meu Perfil',
            'user' => $user
        ]);
    }
    
    public function updateProfile() {
        $user = Auth::user();
        $input = $this->getInput();
        
        $errors = $this->validate($input, [
            'name' => 'required|min:2',
            'email' => 'required|email'
        ]);
        
        if (!empty($errors)) {
            $this->render('users/profile', [
                'title' => 'Meu Perfil',
                'user' => $user,
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }
        
        // Verificar se email não está sendo usado por outro usuário
        $existingUser = User::findByEmail($input['email']);
        if ($existingUser && $existingUser->id !== $user->id) {
            $errors['email'] = 'Este email já está sendo usado';
            $this->render('users/profile', [
                'title' => 'Meu Perfil',
                'user' => $user,
                'errors' => $errors,
                'old' => $input
            ]);
            return;
        }
        
        $user->fill([
            'name' => $input['name'],
            'email' => $input['email']
        ]);
        
        // Atualizar senha se fornecida
        if (!empty($input['password'])) {
            if (strlen($input['password']) < 6) {
                $errors['password'] = 'Senha deve ter pelo menos 6 caracteres';
                $this->render('users/profile', [
                    'title' => 'Meu Perfil',
                    'user' => $user,
                    'errors' => $errors,
                    'old' => $input
                ]);
                return;
            }
            $user->password = $input['password'];
        }
        
        $result = $user->save();
        
        if ($result === true) {
            $this->setMessage('success', 'Perfil atualizado com sucesso!');
            $this->redirect('/profile');
        } else {
            $this->render('users/profile', [
                'title' => 'Meu Perfil',
                'user' => $user,
                'errors' => $result,
                'old' => $input
            ]);
        }
    }
}
```

## Views de Autenticação

### app/Views/layouts/auth.php
```php
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Autenticação' ?> - MVC Framework</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .auth-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group input.error {
            border-color: #e74c3c;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #5a6fd8;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
        }
        
        .auth-links a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .auth-divider {
            margin: 15px 0;
            text-align: center;
            color: #999;
        }
        
        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <?php if (isset($_SESSION['flash'])): ?>
            <?php foreach ($_SESSION['flash'] as $type => $message): ?>
                <div class="alert <?= $type ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endforeach; ?>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
        
        <?= $content ?>
    </div>
</body>
</html>
```

### app/Views/auth/login.php
```php
<div class="auth-header">
    <h1>Entrar</h1>
    <p>Acesse sua conta para continuar</p>
</div>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <?php if (isset($errors['auth'])): ?>
            <?= htmlspecialchars($errors['auth']) ?>
        <?php else: ?>
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               class="<?= isset($errors['email']) ? 'error' : '' ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="password">Senha:</label>
        <input type="password" 
               id="password" 
               name="password"
               class="<?= isset($errors['password']) ? 'error' : '' ?>"
               required>
    </div>
    
    <div class="checkbox-group">
        <input type="checkbox" id="remember" name="remember" value="1">
        <label for="remember">Lembrar-me</label>
    </div>
    
    <button type="submit" class="btn">Entrar</button>
</form>

<div class="auth-links">
    <a href="/forgot-password">Esqueceu sua senha?</a>
    
    <div class="auth-divider">ou</div>
    
    <a href="/register">Criar nova conta</a>
</div>
```

### app/Views/auth/register.php
```php
<div class="auth-header">
    <h1>Criar Conta</h1>
    <p>Preencha os dados para se cadastrar</p>
</div>

<?php if (isset($errors) && !empty($errors)): ?>
    <div class="alert error">
        <ul style="margin: 0; padding-left: 20px;">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/register">
    <div class="form-group">
        <label for="name">Nome:</label>
        <input type="text" 
               id="name" 
               name="name" 
               value="<?= htmlspecialchars($old['name'] ?? '') ?>"
               class="<?= isset($errors['name']) ? 'error' : '' ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" 
               id="email" 
               name="email" 
               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
               class="<?= isset($errors['email']) ? 'error' : '' ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="password">Senha:</label>
        <input type="password" 
               id="password" 
               name="password"
               class="<?= isset($errors['password']) ? 'error' : '' ?>"
               required>
    </div>
    
    <div class="form-group">
        <label for="password_confirmation">Confirmar Senha:</label>
        <input type="password" 
               id="password_confirmation" 
               name="password_confirmation"
               class="<?= isset($errors['password_confirmation']) ? 'error' : '' ?>"
               required>
    </div>
    
    <button type="submit" class="btn">Criar Conta</button>
</form>

<div class="auth-links">
    <a href="/login">Já tem uma conta? Entrar</a>
</div>
```

### app/Views/dashboard/index.php
```php
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h1>Dashboard</h1>
    <div>
        <span>Olá, <?= htmlspecialchars($user->name) ?>!</span>
        <a href="/logout" style="margin-left: 15px; color: #dc3545;">Sair</a>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff;">
        <h3 style="margin: 0 0 10px 0; color: #333;">Total de Usuários</h3>
        <p style="font-size: 24px; font-weight: bold; margin: 0; color: #007bff;">
            <?= number_format($stats['total_users']) ?>
        </p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
        <h3 style="margin: 0 0 10px 0; color: #333;">Membro desde</h3>
        <p style="font-size: 16px; margin: 0; color: #28a745;">
            <?= date('d/m/Y', strtotime($stats['user_since'])) ?>
        </p>
    </div>
    
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107;">
        <h3 style="margin: 0 0 10px 0; color: #333;">Último Login</h3>
        <p style="font-size: 16px; margin: 0; color: #856404;">
            <?= $stats['last_login'] ? date('d/m/Y H:i', strtotime($stats['last_login'])) : 'Primeiro acesso' ?>
        </p>
    </div>
</div>

<div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
    <h2 style="margin-bottom: 20px;">Menu Rápido</h2>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="/profile" 
           style="display: block; padding: 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
            Meu Perfil
        </a>
        
        <a href="/users" 
           style="display: block; padding: 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
            Gerenciar Usuários
        </a>
        
        <a href="/posts" 
           style="display: block; padding: 15px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
            Posts
        </a>
        
        <a href="/settings" 
           style="display: block; padding: 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; text-align: center;">
            Configurações
        </a>
    </div>
</div>

<div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
    <h3>Informações da Sessão (Debug)</h3>
    <p><strong>ID do Usuário:</strong> <?= $user->id ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($user->email) ?></p>
    <p><strong>Role:</strong> <?= htmlspecialchars($user->role ?? 'user') ?></p>
    <p><strong>Status:</strong> <?= htmlspecialchars($user->status ?? 'active') ?></p>
</div>
```

## Proteção de Rotas

### Atualização do Router para Middleware

Atualize o `core/Router.php` para suportar middleware:

```php
public function addRoute($pattern, $controller, $action = 'index', $method = 'GET', $middleware = []) {
    $this->routes[] = [
        'pattern' => $pattern,
        'controller' => $controller,
        'action' => $action,
        'method' => strtoupper($method),
        'middleware' => $middleware
    ];
}

private function runMiddleware($middleware) {
    foreach ($middleware as $middlewareClass) {
        if (is_string($middlewareClass)) {
            $middleware = new $middlewareClass();
        } else {
            $middleware = $middlewareClass;
        }
        
        $middleware->handle($_REQUEST, function() {
            return true;
        });
    }
}
```

### public/index.php atualizado com rotas protegidas
```php
<?php
session_start();

require_once '../core/Application.php';
require_once '../core/Auth.php';

use Core\Application;
use Core\Auth;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;

// Inicializar Auth
Auth::init();

try {
    $app = new Application();
    $router = $app->getRouter();
    
    // Rotas públicas
    $router->get('/', 'Home', 'index');
    
    // Rotas de guest (apenas não logados)
    $router->get('/login', 'Auth', 'showLogin', 'GET', [GuestMiddleware::class]);
    $router->post('/login', 'Auth', 'login', 'POST', [GuestMiddleware::class]);
    $router->get('/register', 'Auth', 'showRegister', 'GET', [GuestMiddleware::class]);
    $router->post('/register', 'Auth', 'register', 'POST', [GuestMiddleware::class]);
    $router->get('/forgot-password', 'Auth', 'showForgotPassword', 'GET', [GuestMiddleware::class]);
    $router->post('/forgot-password', 'Auth', 'sendPasswordReset', 'POST', [GuestMiddleware::class]);
    $router->get('/reset-password/{token}', 'Auth', 'showResetPassword', 'GET', [GuestMiddleware::class]);
    $router->post('/reset-password/{token}', 'Auth', 'resetPassword', 'POST', [GuestMiddleware::class]);
    
    // Rotas protegidas (apenas logados)
    $router->get('/dashboard', 'Dashboard', 'index', 'GET', [AuthMiddleware::class]);
    $router->get('/profile', 'Dashboard', 'profile', 'GET', [AuthMiddleware::class]);
    $router->post('/profile', 'Dashboard', 'updateProfile', 'POST', [AuthMiddleware::class]);
    $router->get('/logout', 'Auth', 'logout', 'GET', [AuthMiddleware::class]);
    
    // Rotas de usuários (protegidas)
    $router->get('/users', 'User', 'index', 'GET', [AuthMiddleware::class]);
    $router->get('/users/create', 'User', 'create', 'GET', [AuthMiddleware::class]);
    $router->post('/users', 'User', 'store', 'POST', [AuthMiddleware::class]);
    $router->get('/users/{id}', 'User', 'show', 'GET', [AuthMiddleware::class]);
    $router->get('/users/{id}/edit', 'User', 'edit', 'GET', [AuthMiddleware::class]);
    $router->post('/users/{id}', 'User', 'update', 'POST', [AuthMiddleware::class]);
    $router->get('/users/{id}/delete', 'User', 'destroy', 'GET', [AuthMiddleware::class]);
    
    $app->run();
    
} catch (Exception $e) {
    if (defined('DEBUG') && DEBUG) {
        echo "Erro: " . $e->getMessage();
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        echo "Erro interno do servidor";
        error_log($e->getMessage());
    }
}
```

## Setup do Ambiente

### 1. Estrutura do Banco Atualizada

```sql
-- Atualizar tabela users para autenticação
ALTER TABLE users ADD COLUMN role VARCHAR(20) DEFAULT 'user';
ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active';
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN remember_token VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN remember_token_expires_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN password_reset_token VARCHAR(100) NULL;
ALTER TABLE users ADD COLUMN password_reset_expires_at TIMESTAMP NULL;

-- Índices para performance
CREATE INDEX idx_users_remember_token ON users(remember_token);
CREATE INDEX idx_users_password_reset_token ON users(password_reset_token);
CREATE INDEX idx_users_status ON users(status);
```

### 2. Model User Atualizado

```php
// Adicionar ao app/Models/User.php

public function isActive() {
    return $this->status === 'active';
}

public function hasRole($role) {
    return $this->role === $role;
}

public function updateLastLogin() {
    $this->last_login_at = date('Y-m-d H:i:s');
    return parent::save();
}

public function updateRememberToken($token, $expiry) {
    $this->remember_token = $token;
    $this->remember_token_expires_at = $expiry;
    return parent::save();
}

public function clearRememberToken() {
    $this->remember_token = null;
    $this->remember_token_expires_at = null;
    return parent::save();
}

public static function findByRememberToken($token) {
    $sql = "SELECT * FROM " . static::getTableName() . " WHERE remember_token = ? LIMIT 1";
    $result = static::$db->fetch($sql, [$token]);
    
    return $result ? static::newFromBuilder($result) : null;
}

public function isRememberTokenValid() {
    return $this->remember_token_expires_at && 
           strtotime($this->remember_token_expires_at) > time();
}

public function generatePasswordResetToken() {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    $this->password_reset_token = $token;
    $this->password_reset_expires_at = $expiry;
    parent::save();
    
    return $token;
}

public function isPasswordResetTokenValid($token) {
    return $this->password_reset_token === $token &&
           $this->password_reset_expires_at &&
           strtotime($this->password_reset_expires_at) > time();
}

public function clearPasswordResetToken() {
    $this->password_reset_token = null;
    $this->password_reset_expires_at = null;
    return parent::save();
}

public static function count() {
    $sql = "SELECT COUNT(*) as count FROM " . static::getTableName();
    $result = static::$db->fetch($sql);
    return (int) $result['count'];
}
```

## Resolução de Problemas

### Problema 1: Sessão não mantém login
**Sintomas**: Usuário faz login mas é redirecionado para login novamente
**Soluções**:
```php
// Verificar se session_start() está sendo chamado
// Verificar configurações de sessão
ini_set('session.gc_maxlifetime', 7200);

// Debug da sessão
var_dump($_SESSION);
var_dump(session_id());

// Verificar se Auth::init() está sendo chamado
```

### Problema 2: Remember token não funciona
**Sintomas**: Checkbox "Lembrar-me" não mantém usuário logado
**Soluções**:
```php
// Verificar se cookies estão sendo salvos
var_dump($_COOKIE);

// Verificar configurações de cookie
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.cookie_httponly', 1);

// Verificar se token está sendo salvo no banco
```

### Problema 3: Middleware não executa
**Sintomas**: Rotas protegidas são acessíveis sem login
**Soluções**:
```php
// Verificar se middleware está sendo executado
// Adicionar debug no middleware
public function handle($request, $next) {
    error_log("AuthMiddleware executado");
    error_log("Auth::check(): " . (Auth::check() ? 'true' : 'false'));
    
    if (Auth::guest()) {
        $this->redirect('/login');
    }
    
    return $next($request);
}
```

### Problema 4: Validação de email duplicado falha
**Sintomas**: Sistema permite cadastro com email já existente
**Soluções**:
```php
// Verificar método emailExists()
protected function emailExists() {
    $sql = "SELECT COUNT(*) as count FROM " . static::getTableName() . " WHERE email = ?";
    
    // Debug
    error_log("Checking email: " . $this->email);
    
    if ($this->exists) {
        $sql .= " AND " . static::$primaryKey . " != ?";
        $result = static::$db->fetch($sql, [$this->email, $this->getKey()]);
    } else {
        $result = static::$db->fetch($sql, [$this->email]);
    }
    
    error_log("Email exists count: " . $result['count']);
    
    return $result['count'] > 0;
}
```

### Problema 5: Redirect loop
**Sintomas**: Página fica em loop de redirecionamento
**Soluções**:
```php
// Verificar se não há redirect dentro de redirect
// Usar exit após redirect

protected function redirect($url) {
    error_log("Redirecting to: " . $url);
    header("Location: {$url}");
    exit; // IMPORTANTE
}

// Verificar lógica de middleware
```

### Debug Helper para Auth

```php
// Adicionar ao BaseController
protected function debugAuth() {
    echo "<div style='background: #f8f9fa; padding: 10px; margin: 10px 0; border: 1px solid #ddd;'>";
    echo "<strong>Debug Auth:</strong><br>";
    echo "Logged in: " . (Auth::check() ? 'Yes' : 'No') . "<br>";
    echo "User ID: " . (Auth::id() ?? 'None') . "<br>";
    echo "Session ID: " . session_id() . "<br>";
    echo "Session data: " . json_encode($_SESSION) . "<br>";
    echo "Cookies: " . json_encode($_COOKIE) . "<br>";
    echo "</div>";
}
```

---

**Importante**: Este sistema de autenticação é adequado para aprendizado e pequenos projetos. Para produção, considere usar bibliotecas estabelecidas como Firebase Auth, Auth0, ou frameworks como Laravel que incluem sistemas de autenticação robustos e testados.
