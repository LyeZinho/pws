# 🔐 Guia de Autenticação - Sistema de Login e Permissões

## 📋 Introdução ao Sistema de Autenticação

O sistema de autenticação do GEstufas é baseado em **sessões PHP** e utiliza **cookies** para manter o utilizador logado. O sistema inclui login, logout, registo, verificação de permissões e filtros de segurança.

---

## 🏗️ Arquitectura do Sistema

### **Componentes Principais**
```
Authentication System/
├── AuthController.php      # Controller de autenticação
├── Auth.php               # Model helper para autenticação
├── User.php               # Model de utilizador
├── Controller.php         # Filtros de autenticação base
└── Session Management     # Gestão de sessões PHP
```

### **Fluxo de Autenticação**
```
1. Utilizador acede a página protegida
2. authenticationFilter() verifica sessão
3. Se não autenticado → redireciona para login
4. Se autenticado → permite acesso
5. Logout → destroi sessão e cookies
```

---

## 👤 Model de Utilizador

### **Estrutura da Tabela Users**
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    role ENUM('user', 'admin', 'moderator') DEFAULT 'user',
    active BOOLEAN DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### **Model User.php Completo**
```php
<?php
/**
 * User Model - Modelo de utilizador com autenticação
 * 
 * Inclui métodos para autenticação, verificação de permissões,
 * gestão de passwords e perfis de utilizador.
 */
class User extends ActiveRecord\Model {
    
    // Nome da tabela
    static $table_name = 'users';
    
    // Validações
    static $validates_presence_of = array(
        array('username', 'message' => 'Username é obrigatório'),
        array('email', 'message' => 'Email é obrigatório'),
        array('password', 'message' => 'Password é obrigatória')
    );
    
    static $validates_uniqueness_of = array(
        array('username', 'message' => 'Username já existe'),
        array('email', 'message' => 'Email já está em uso')
    );
    
    static $validates_format_of = array(
        array('email', 'with' => '/\A[\w+\-.]+@[a-z\d\-.]+\.[a-z]+\z/i', 
              'message' => 'Email deve ter formato válido')
    );
    
    static $validates_length_of = array(
        array('username', 'minimum' => 3, 'maximum' => 50, 
              'message' => 'Username deve ter entre 3 e 50 caracteres'),
        array('password', 'minimum' => 6, 
              'message' => 'Password deve ter pelo menos 6 caracteres')
    );
    
    // Relacionamentos
    static $has_many = array(
        array('posts'),
        array('projects'),
        array('comments')
    );
    
    // Callbacks
    static $before_save = array('hash_password', 'format_data');
    static $before_create = array('set_defaults');
    
    /**
     * Hash da password antes de salvar
     */
    public function hash_password() {
        // Apenas fazer hash se a password foi alterada
        if (!empty($this->password) && $this->password_changed()) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    /**
     * Formatar dados antes de salvar
     */
    public function format_data() {
        $this->email = strtolower(trim($this->email));
        $this->username = trim($this->username);
        if (!empty($this->first_name)) {
            $this->first_name = ucfirst(strtolower(trim($this->first_name)));
        }
        if (!empty($this->last_name)) {
            $this->last_name = ucfirst(strtolower(trim($this->last_name)));
        }
    }
    
    /**
     * Definir valores padrão para novo utilizador
     */
    public function set_defaults() {
        if (empty($this->role)) {
            $this->role = 'user';
        }
        if (!isset($this->active)) {
            $this->active = 1;
        }
    }
    
    /**
     * Verificar se a password foi alterada
     */
    private function password_changed() {
        if ($this->is_new_record()) {
            return true;
        }
        
        $original = self::find($this->id);
        return $original->password !== $this->password;
    }
    
    /**
     * Verificar password
     */
    public function verifyPassword($password) {
        return password_verify($password, $this->password);
    }
    
    /**
     * Obter nome completo
     */
    public function getFullName() {
        $name = trim($this->first_name . ' ' . $this->last_name);
        return !empty($name) ? $name : $this->username;
    }
    
    /**
     * Verificar se é administrador
     */
    public function isAdmin() {
        return $this->role === 'admin';
    }
    
    /**
     * Verificar se é moderador
     */
    public function isModerator() {
        return $this->role === 'moderator' || $this->isAdmin();
    }
    
    /**
     * Verificar se pode editar um recurso
     */
    public function canEdit($resourceUserId) {
        return $this->id == $resourceUserId || $this->isAdmin();
    }
    
    /**
     * Verificar se pode moderar um recurso
     */
    public function canModerate() {
        return $this->isModerator();
    }
    
    /**
     * Actualizar último login
     */
    public function updateLastLogin() {
        $this->last_login = date('Y-m-d H:i:s');
        $this->save();
    }
    
    /**
     * Obter utilizadores activos
     */
    public static function active() {
        return self::find('all', array(
            'conditions' => array('active = ?', 1),
            'order' => 'username ASC'
        ));
    }
    
    /**
     * Obter administradores
     */
    public static function admins() {
        return self::find('all', array(
            'conditions' => array('role = ? AND active = ?', 'admin', 1),
            'order' => 'username ASC'
        ));
    }
    
    /**
     * Procurar utilizador por email
     */
    public static function findByEmail($email) {
        return self::find('first', array(
            'conditions' => array('email = ?', strtolower($email))
        ));
    }
    
    /**
     * Procurar utilizador por username
     */
    public static function findByUsername($username) {
        return self::find('first', array(
            'conditions' => array('username = ?', $username)
        ));
    }
    
    /**
     * Autenticar utilizador
     */
    public static function authenticate($login, $password) {
        // Tentar encontrar por email ou username
        $user = self::findByEmail($login);
        if (!$user) {
            $user = self::findByUsername($login);
        }
        
        if ($user && $user->active && $user->verifyPassword($password)) {
            $user->updateLastLogin();
            return $user;
        }
        
        return null;
    }
}
```

---

## 🔑 AuthController

### **Controller de Autenticação Completo**
```php
<?php
/**
 * AuthController - Controller para autenticação
 * 
 * Gere login, logout, registo e recuperação de password
 */
class AuthController extends Controller {
    
    /**
     * Página inicial de autenticação
     */
    public function index() {
        // Se já está logado, redirecionar para home
        if ($this->isLoggedIn()) {
            $this->redirectToRoute('home', 'index');
            return;
        }
        
        $this->redirectToRoute('auth', 'login');
    }
    
    /**
     * Página de login
     */
    public function login() {
        // Se já está logado, redirecionar
        if ($this->isLoggedIn()) {
            $this->redirectToRoute('home', 'index');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processLogin();
        } else {
            $this->showLoginForm();
        }
    }
    
    /**
     * Mostrar formulário de login
     */
    private function showLoginForm() {
        $data = [
            'title' => 'Login - Entrar no Sistema',
            'returnUrl' => $this->getHTTPGetParam('return_url') ?: ''
        ];
        
        $this->renderView('auth', 'login', $data);
    }
    
    /**
     * Processar login
     */
    private function processLogin() {
        try {
            // Obter dados do formulário
            $login = trim($this->getHTTPPostParam('login')); // Email ou username
            $password = $this->getHTTPPostParam('password');
            $rememberMe = $this->getHTTPPostParam('remember_me') ? true : false;
            $returnUrl = $this->getHTTPPostParam('return_url');
            
            // Validações básicas
            $errors = [];
            
            if (empty($login)) {
                $errors[] = 'Email ou username é obrigatório.';
            }
            
            if (empty($password)) {
                $errors[] = 'Password é obrigatória.';
            }
            
            // Se há erros, mostrar formulário novamente
            if (!empty($errors)) {
                $data = [
                    'title' => 'Login - Entrar no Sistema',
                    'errors' => $errors,
                    'formData' => ['login' => $login],
                    'returnUrl' => $returnUrl
                ];
                
                $this->renderView('auth', 'login', $data);
                return;
            }
            
            // Tentar autenticar
            $user = User::authenticate($login, $password);
            
            if ($user) {
                // Login bem-sucedido
                $this->createUserSession($user, $rememberMe);
                
                $_SESSION['success_message'] = "Bem-vindo, {$user->getFullName()}!";
                
                // Log da acção
                error_log("Login bem-sucedido - User ID: {$user->id}, IP: {$_SERVER['REMOTE_ADDR']}");
                
                // Redirecionar para URL de retorno ou home
                if (!empty($returnUrl)) {
                    header("Location: $returnUrl");
                } else {
                    $this->redirectToRoute('home', 'index');
                }
                
            } else {
                // Login falhado
                error_log("Tentativa de login falhada - Login: $login, IP: {$_SERVER['REMOTE_ADDR']}");
                
                $data = [
                    'title' => 'Login - Entrar no Sistema',
                    'errors' => ['Email/username ou password incorrectos.'],
                    'formData' => ['login' => $login],
                    'returnUrl' => $returnUrl
                ];
                
                $this->renderView('auth', 'login', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no login - " . $e->getMessage());
            
            $data = [
                'title' => 'Login - Entrar no Sistema',
                'errors' => ['Erro interno. Tente novamente.'],
                'formData' => ['login' => $login ?? ''],
                'returnUrl' => $returnUrl ?? ''
            ];
            
            $this->renderView('auth', 'login', $data);
        }
    }
    
    /**
     * Criar sessão do utilizador
     */
    private function createUserSession($user, $rememberMe = false) {
        // Regenerar ID da sessão por segurança
        session_regenerate_id(true);
        
        // Definir dados da sessão
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['login_time'] = time();
        
        // Cookie "Remember Me" (opcional)
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 60 * 60); // 30 dias
            
            setcookie('remember_token', $token, $expiry, '/', '', false, true);
            
            // Salvar token na base de dados (implementar tabela remember_tokens)
            // $this->saveRememberToken($user->id, $token, $expiry);
        }
    }
    
    /**
     * Logout
     */
    public function logout() {
        try {
            $userId = $_SESSION['user_id'] ?? 'N/A';
            
            // Destruir sessão
            session_start();
            session_destroy();
            
            // Remover cookies
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
                // Remover token da base de dados
                // $this->removeRememberToken($_COOKIE['remember_token']);
            }
            
            // Log da acção
            error_log("Logout realizado - User ID: $userId, IP: {$_SERVER['REMOTE_ADDR']}");
            
            // Redirecionar para login com mensagem
            $_SESSION['info_message'] = 'Logout realizado com sucesso.';
            $this->redirectToRoute('auth', 'login');
            
        } catch (Exception $e) {
            error_log("Erro no logout - " . $e->getMessage());
            $this->redirectToRoute('auth', 'login');
        }
    }
    
    /**
     * Página de registo
     */
    public function register() {
        // Se já está logado, redirecionar
        if ($this->isLoggedIn()) {
            $this->redirectToRoute('home', 'index');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processRegister();
        } else {
            $this->showRegisterForm();
        }
    }
    
    /**
     * Mostrar formulário de registo
     */
    private function showRegisterForm() {
        $data = [
            'title' => 'Registo - Criar Conta'
        ];
        
        $this->renderView('auth', 'register', $data);
    }
    
    /**
     * Processar registo
     */
    private function processRegister() {
        try {
            // Obter dados do formulário
            $username = trim($this->getHTTPPostParam('username'));
            $email = trim($this->getHTTPPostParam('email'));
            $password = $this->getHTTPPostParam('password');
            $confirmPassword = $this->getHTTPPostParam('confirm_password');
            $firstName = trim($this->getHTTPPostParam('first_name'));
            $lastName = trim($this->getHTTPPostParam('last_name'));
            $agreeTerms = $this->getHTTPPostParam('agree_terms') ? true : false;
            
            // Validações personalizadas
            $errors = [];
            
            if (empty($username)) {
                $errors[] = 'Username é obrigatório.';
            } elseif (strlen($username) < 3) {
                $errors[] = 'Username deve ter pelo menos 3 caracteres.';
            }
            
            if (empty($email)) {
                $errors[] = 'Email é obrigatório.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email deve ter formato válido.';
            }
            
            if (empty($password)) {
                $errors[] = 'Password é obrigatória.';
            } elseif (strlen($password) < 6) {
                $errors[] = 'Password deve ter pelo menos 6 caracteres.';
            }
            
            if ($password !== $confirmPassword) {
                $errors[] = 'Confirmação de password não confere.';
            }
            
            if (!$agreeTerms) {
                $errors[] = 'Deve concordar com os termos e condições.';
            }
            
            // Verificar se username/email já existem
            if (empty($errors)) {
                if (User::findByUsername($username)) {
                    $errors[] = 'Username já existe.';
                }
                
                if (User::findByEmail($email)) {
                    $errors[] = 'Email já está em uso.';
                }
            }
            
            // Se há erros, mostrar formulário
            if (!empty($errors)) {
                $data = [
                    'title' => 'Registo - Criar Conta',
                    'errors' => $errors,
                    'formData' => [
                        'username' => $username,
                        'email' => $email,
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]
                ];
                
                $this->renderView('auth', 'register', $data);
                return;
            }
            
            // Criar novo utilizador
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->password = $password; // Será feito hash automaticamente
            $user->first_name = $firstName;
            $user->last_name = $lastName;
            $user->role = 'user';
            $user->active = 1;
            
            if ($user->save()) {
                // Registo bem-sucedido
                error_log("Novo utilizador registado - ID: {$user->id}, Username: $username");
                
                // Login automático
                $this->createUserSession($user);
                
                $_SESSION['success_message'] = 'Conta criada com sucesso! Bem-vindo ao sistema.';
                $this->redirectToRoute('home', 'index');
                
            } else {
                // Erro na criação
                $data = [
                    'title' => 'Registo - Criar Conta',
                    'errors' => $user->errors->full_messages(),
                    'formData' => [
                        'username' => $username,
                        'email' => $email,
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ]
                ];
                
                $this->renderView('auth', 'register', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no registo - " . $e->getMessage());
            
            $data = [
                'title' => 'Registo - Criar Conta',
                'errors' => ['Erro interno. Tente novamente.'],
                'formData' => $_POST
            ];
            
            $this->renderView('auth', 'register', $data);
        }
    }
    
    /**
     * Verificar se utilizador está logado
     */
    private function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Recuperação de password (placeholder)
     */
    public function forgot_password() {
        $data = [
            'title' => 'Recuperar Password'
        ];
        
        $this->renderView('auth', 'forgot_password', $data);
    }
}
```

---

## 🛡️ Filtros de Segurança

### **Controller Base com Filtros**
```php
<?php
/**
 * Controller base com filtros de autenticação
 */
class Controller {
    
    /**
     * Filtro de autenticação básico
     * Redireciona para login se não autenticado
     */
    protected function authenticationFilter() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            // Salvar URL actual para retorno após login
            $returnUrl = $_SERVER['REQUEST_URI'];
            $_SESSION['error_message'] = 'Precisa de fazer login para aceder a esta página.';
            $this->redirectToRoute('auth', 'login', ['return_url' => $returnUrl]);
            exit;
        }
        
        // Verificar se utilizador ainda existe e está activo
        try {
            $user = User::find($_SESSION['user_id']);
            if (!$user || !$user->active) {
                $this->forceLogout('A sua conta foi desactivada.');
                exit;
            }
            
            // Actualizar dados da sessão se necessário
            if ($_SESSION['user_role'] !== $user->role) {
                $_SESSION['user_role'] = $user->role;
            }
            
        } catch (Exception $e) {
            error_log("Erro na verificação do utilizador - " . $e->getMessage());
            $this->forceLogout('Erro de autenticação.');
            exit;
        }
    }
    
    /**
     * Filtro para administradores
     */
    protected function adminFilter() {
        $this->authenticationFilter();
        
        if (!$this->isAdmin()) {
            $_SESSION['error_message'] = 'Acesso negado. Apenas administradores.';
            $this->redirectToRoute('home', 'index');
            exit;
        }
    }
    
    /**
     * Filtro para moderadores e administradores
     */
    protected function moderatorFilter() {
        $this->authenticationFilter();
        
        if (!$this->isModerator()) {
            $_SESSION['error_message'] = 'Acesso negado. Apenas moderadores.';
            $this->redirectToRoute('home', 'index');
            exit;
        }
    }
    
    /**
     * Filtro de propriedade - apenas dono ou admin
     */
    protected function ownerFilter($resourceUserId) {
        $this->authenticationFilter();
        
        if (!$this->canEdit($resourceUserId)) {
            $_SESSION['error_message'] = 'Não tem permissão para aceder a este recurso.';
            $this->redirectToRoute('home', 'index');
            exit;
        }
    }
    
    /**
     * Verificar se utilizador é admin
     */
    protected function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Verificar se utilizador é moderador
     */
    protected function isModerator() {
        return isset($_SESSION['user_role']) && 
               ($_SESSION['user_role'] === 'moderator' || $_SESSION['user_role'] === 'admin');
    }
    
    /**
     * Verificar se pode editar recurso
     */
    protected function canEdit($resourceUserId) {
        return $_SESSION['user_id'] == $resourceUserId || $this->isAdmin();
    }
    
    /**
     * Obter utilizador actual
     */
    protected function getCurrentUser() {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        
        try {
            return User::find($_SESSION['user_id']);
        } catch (Exception $e) {
            error_log("Erro ao obter utilizador actual - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Forçar logout
     */
    protected function forceLogout($message = 'Sessão expirada.') {
        session_start();
        session_destroy();
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        $_SESSION['error_message'] = $message;
        $this->redirectToRoute('auth', 'login');
    }
    
    /**
     * Verificar timeout de sessão (opcional)
     */
    protected function checkSessionTimeout($timeoutMinutes = 30) {
        if (isset($_SESSION['login_time'])) {
            $elapsed = time() - $_SESSION['login_time'];
            if ($elapsed > ($timeoutMinutes * 60)) {
                $this->forceLogout('Sessão expirou por inactividade.');
                exit;
            }
        }
    }
    
    // ... outros métodos do Controller base
}
```

---

## 📄 Views de Autenticação

### **View de Login**
```php
<?php
/**
 * View: auth/login.php
 * Formulário de login
 */

include_once 'views/layout/header.php';
?>

<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-6 col-lg-4">
            <!-- Logo/Brand -->
            <div class="text-center mb-4">
                <img src="public/img/logo-ipleiria.png" alt="GEstufas" class="mb-3" style="max-height: 80px;">
                <h2 class="text-primary">GEstufas</h2>
                <p class="text-muted">Sistema de Gestão</p>
            </div>
            
            <!-- Mensagens -->
            <?php include_once 'views/components/alerts.php'; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Formulário de Login -->
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Entrar no Sistema
                    </h5>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="index.php?c=auth&a=login" id="loginForm">
                        <?php if (!empty($returnUrl)): ?>
                            <input type="hidden" name="return_url" value="<?= htmlspecialchars($returnUrl) ?>">
                        <?php endif; ?>
                        
                        <!-- Email/Username -->
                        <div class="mb-3">
                            <label for="login" class="form-label">
                                <i class="fas fa-user me-2"></i>
                                Email ou Username
                            </label>
                            <input type="text" class="form-control" id="login" name="login" 
                                   value="<?= htmlspecialchars($formData['login'] ?? '') ?>" 
                                   required autofocus>
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock me-2"></i>
                                Password
                            </label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button type="button" class="btn btn-outline-secondary" 
                                        onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Remember Me -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember_me" 
                                   name="remember_me" value="1">
                            <label class="form-check-label" for="remember_me">
                                Manter-me logado
                            </label>
                        </div>
                        
                        <!-- Botão Login -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Entrar
                            </button>
                        </div>
                    </form>
                </div>
                
                <div class="card-footer text-center bg-light">
                    <small class="text-muted">
                        Não tem conta? 
                        <a href="index.php?c=auth&a=register" class="text-primary">
                            Registar-se
                        </a>
                    </small>
                    <br>
                    <small class="text-muted">
                        <a href="index.php?c=auth&a=forgot_password" class="text-secondary">
                            Esqueceu a password?
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form validation
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value;
    
    if (!login || !password) {
        e.preventDefault();
        alert('Por favor, preencha todos os campos.');
        return false;
    }
});
</script>

<?php include_once 'views/layout/footer.php'; ?>
```

---

## 🔒 Middleware de Segurança

### **Class Auth Helper**
```php
<?php
/**
 * Auth Helper - Métodos auxiliares para autenticação
 */
class Auth {
    
    /**
     * Verificar se utilizador está logado
     */
    public static function check() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obter utilizador actual
     */
    public static function user() {
        if (!self::check()) {
            return null;
        }
        
        try {
            return User::find($_SESSION['user_id']);
        } catch (Exception $e) {
            error_log("Erro ao obter utilizador - " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obter ID do utilizador actual
     */
    public static function id() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Verificar se é admin
     */
    public static function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    /**
     * Verificar se é moderador
     */
    public static function isModerator() {
        return isset($_SESSION['user_role']) && 
               ($_SESSION['user_role'] === 'moderator' || $_SESSION['user_role'] === 'admin');
    }
    
    /**
     * Login programático
     */
    public static function login(User $user, $remember = false) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['full_name'] = $user->getFullName();
        $_SESSION['login_time'] = time();
        
        $user->updateLastLogin();
        
        if ($remember) {
            self::setRememberCookie($user);
        }
        
        return true;
    }
    
    /**
     * Logout programático
     */
    public static function logout() {
        session_start();
        session_destroy();
        
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        return true;
    }
    
    /**
     * Definir cookie "Remember Me"
     */
    private static function setRememberCookie(User $user) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 dias
        
        setcookie('remember_token', $token, $expiry, '/', '', false, true);
        
        // Salvar token na base de dados (implementar)
        // RememberToken::create($user->id, $token, $expiry);
    }
    
    /**
     * Verificar permissão para recurso
     */
    public static function can($action, $resource = null) {
        if (!self::check()) {
            return false;
        }
        
        $user = self::user();
        if (!$user) {
            return false;
        }
        
        switch ($action) {
            case 'admin':
                return $user->isAdmin();
                
            case 'moderate':
                return $user->isModerator();
                
            case 'edit':
                if ($resource && isset($resource->user_id)) {
                    return $user->canEdit($resource->user_id);
                }
                return false;
                
            case 'delete':
                if ($resource && isset($resource->user_id)) {
                    return $user->canEdit($resource->user_id);
                }
                return false;
                
            default:
                return false;
        }
    }
    
    /**
     * Middleware para proteger rotas
     */
    public static function middleware($type = 'auth', $resource = null) {
        switch ($type) {
            case 'auth':
                if (!self::check()) {
                    self::redirectToLogin();
                }
                break;
                
            case 'admin':
                if (!self::check()) {
                    self::redirectToLogin();
                } elseif (!self::isAdmin()) {
                    self::accessDenied();
                }
                break;
                
            case 'moderator':
                if (!self::check()) {
                    self::redirectToLogin();
                } elseif (!self::isModerator()) {
                    self::accessDenied();
                }
                break;
                
            case 'owner':
                if (!self::check()) {
                    self::redirectToLogin();
                } elseif ($resource && !self::can('edit', $resource)) {
                    self::accessDenied();
                }
                break;
        }
    }
    
    /**
     * Redirecionar para login
     */
    private static function redirectToLogin() {
        $returnUrl = $_SERVER['REQUEST_URI'];
        $_SESSION['error_message'] = 'Precisa de fazer login para aceder a esta página.';
        header("Location: index.php?c=auth&a=login&return_url=" . urlencode($returnUrl));
        exit;
    }
    
    /**
     * Acesso negado
     */
    private static function accessDenied() {
        $_SESSION['error_message'] = 'Não tem permissão para aceder a este recurso.';
        header("Location: index.php?c=home&a=index");
        exit;
    }
}
```

---

## 🚀 Exemplos de Uso

### **No Controller**
```php
class PostController extends Controller {
    
    public function index() {
        // Página pública - sem autenticação
        $posts = Post::all();
        $this->renderView('posts', 'index', ['posts' => $posts]);
    }
    
    public function create() {
        // Requer autenticação
        $this->authenticationFilter();
        $this->renderView('posts', 'create');
    }
    
    public function edit() {
        // Requer ser dono ou admin
        $postId = $this->getHTTPGetParam('id');
        $post = Post::find($postId);
        
        if (!$post) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        $this->ownerFilter($post->user_id);
        $this->renderView('posts', 'edit', ['post' => $post]);
    }
    
    public function admin() {
        // Apenas administradores
        $this->adminFilter();
        $posts = Post::all();
        $this->renderView('posts', 'admin', ['posts' => $posts]);
    }
}
```

### **Nas Views**
```php
<!-- Mostrar diferentes conteúdos baseado na autenticação -->
<?php if (Auth::check()): ?>
    <p>Bem-vindo, <?= Auth::user()->getFullName() ?>!</p>
    
    <?php if (Auth::isAdmin()): ?>
        <a href="index.php?c=admin&a=index" class="btn btn-danger">
            Painel Admin
        </a>
    <?php endif; ?>
    
    <a href="index.php?c=auth&a=logout" class="btn btn-outline-secondary">
        Logout
    </a>
<?php else: ?>
    <a href="index.php?c=auth&a=login" class="btn btn-primary">
        Login
    </a>
    <a href="index.php?c=auth&a=register" class="btn btn-outline-primary">
        Registar
    </a>
<?php endif; ?>

<!-- Verificar permissões para mostrar botões -->
<?php if (Auth::can('edit', $post)): ?>
    <a href="index.php?c=posts&a=edit&id=<?= $post->id ?>" class="btn btn-warning">
        Editar
    </a>
<?php endif; ?>

<?php if (Auth::can('delete', $post)): ?>
    <button onclick="confirmDelete(<?= $post->id ?>)" class="btn btn-danger">
        Eliminar
    </button>
<?php endif; ?>
```

---

## 🛡️ Melhores Práticas de Segurança

### **1. Passwords**
- Usar `password_hash()` e `password_verify()`
- Mínimo 6-8 caracteres
- Validar força da password no cliente

### **2. Sessões**
- Regenerar ID após login
- Timeout de inactividade
- Verificar IP/User-Agent (opcional)

### **3. Cookies**
- HttpOnly e Secure flags
- Expiração adequada
- Invalidar no logout

### **4. Validações**
- Sempre validar no servidor
- Escape de HTML
- Verificar permissões

### **5. Logs**
- Registar tentativas de login
- Actividades sensíveis
- Erros de autenticação

---

Este guia fornece um sistema de autenticação completo e seguro para o GEstufas. Use os exemplos como base e adapte conforme necessário.
