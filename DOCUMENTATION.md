# 📚 Documentação Técnica - Sistema GEstufas

## 🏗️ Arquitetura da Aplicação

### Padrão MVC (Model-View-Controller)

O sistema GEstufas foi desenvolvido seguindo rigorosamente o padrão **MVC**, que separa a aplicação em três camadas principais:

#### 📊 **Models** (`/models/`)
Os models representam a camada de dados e lógica de negócio:

- **User.php**: Modelo de utilizadores
  - Validações: username único, email único, password obrigatória
  - Relacionamentos: tem muitos posts e projetos
  - Métodos especiais: autenticação, verificação de permissões

- **Post.php**: Modelo de posts/artigos
  - Validações: título e conteúdo obrigatórios
  - Relacionamentos: pertence a um utilizador, tem muitos comentários
  - Funcionalidades: sistema de tags, contagem de comentários

- **Project.php**: Modelo de projetos
  - Validações: nome e descrição obrigatórios
  - Relacionamentos: pertence a um utilizador criador
  - Funcionalidades: sistema de membros, status do projeto

- **Comment.php**: Modelo de comentários
  - Validações: conteúdo mínimo de 5 caracteres
  - Relacionamentos: pertence a um post e utilizador
  - Funcionalidades: verificação de permissões para edição/remoção

- **Auth.php**: Modelo de autenticação
  - Métodos: login, logout, verificação de sessão
  - Segurança: hash MD5 de passwords, validação de sessões

#### 🎮 **Controllers** (`/controllers/`)
Os controllers processam requisições e coordenam models/views:

- **Controller.php**: Classe base com métodos comuns
  - `redirectToRoute()`: Redirecionamento entre páginas
  - `renderView()`: Renderização de views com dados
  - `authenticationFilter()`: Verificação de autenticação
  - `getHTTPPostParam()` / `getHTTPGetParam()`: Obtenção de parâmetros

- **HomeController.php**: Página inicial e dashboard
  - Estatísticas do sistema
  - Posts recentes
  - Actividades da comunidade

- **AuthController.php**: Autenticação de utilizadores
  - Login/logout
  - Registo de novos utilizadores
  - Validação de credenciais

- **UserController.php**: CRUD completo de utilizadores
  - Listagem paginada com filtros
  - Criação com validações
  - Edição e actualização
  - Remoção com confirmação

- **PostController.php**: CRUD completo de posts
  - Listagem com paginação
  - Criação com sistema de tags
  - Visualização com comentários
  - Edição/remoção (apenas pelo autor)
  - Sistema de comentários

- **ProjectController.php**: CRUD completo de projetos
  - Listagem com filtros por status
  - Criação com validações
  - Sistema de membros (join/leave)
  - Edição/remoção (apenas pelo criador)

- **CommunityController.php**: Gestão da comunidade
  - Feed de posts
  - Interações sociais
  - Moderação de conteúdo

- **ProfileController.php**: Perfil do utilizador
  - Visualização de dados pessoais
  - Histórico de posts e projetos
  - Edição de informações

#### 🖼️ **Views** (`/views/`)
As views são responsáveis pela apresentação:

- **layout/**: Templates base
  - `header.php`: Cabeçalho com navegação responsiva
  - `footer.php`: Rodapé com informações
  - `default.php`: Layout principal da aplicação

- **auth/**: Autenticação
  - `login.php`: Formulário de login
  - `register.php`: Formulário de registo

- **users/**: Gestão de utilizadores
  - `index.php`: Listagem com paginação e filtros
  - `create.php`: Formulário de criação
  - `edit.php`: Formulário de edição
  - `show.php`: Detalhes do utilizador

- **posts/**: Gestão de posts
  - `index.php`: Listagem com paginação
  - `create.php`: Editor de posts com tags
  - `edit.php`: Edição de posts
  - `show.php`: Visualização com comentários

- **projects/**: Gestão de projetos
  - `index.php`: Listagem com filtros
  - `create.php`: Criação de projetos
  - `edit.php`: Edição de projetos
  - `show.php`: Detalhes e gestão de membros

---

## 🔄 Fluxo de Requisições

### 1. **Ponto de Entrada** (`index.php`)
```php
Utilizador -> URL (?c=posts&a=index) -> index.php
```

O `index.php` é o **single entry point** que:
- Carrega configurações (`startup/config.php`)
- Inicializa o router (`framework/Router.php`)
- Processa a requisição HTTP
- Chama o controller/action apropriado

### 2. **Sistema de Rotas** (`routes.php`)
```php
// Exemplo de rota
'posts' => [
    'index' => ['GET', 'PostController', 'index'],
    'create' => ['GET|POST', 'PostController', 'create'],
    'show' => ['GET', 'PostController', 'show'],
    // ...
]
```

O router:
- Analisa parâmetros `c` (controller) e `a` (action)
- Verifica métodos HTTP permitidos
- Instancia o controller correto
- Chama o método especificado

### 3. **Processamento no Controller**
```php
class PostController extends Controller {
    public function index() {
        // 1. Verificar autenticação
        $this->authenticationFilter();
        
        // 2. Processar lógica de negócio
        $posts = Post::find('all', [...]);
        
        // 3. Preparar dados
        $data = ['posts' => $posts];
        
        // 4. Renderizar view
        $this->renderView('posts', 'index', $data);
    }
}
```

### 4. **Renderização da View**
```php
// Controller chama:
$this->renderView('posts', 'index', $data);

// Que carrega:
views/posts/index.php
```

---

## 🗄️ Base de Dados e ORM

### **PHP ActiveRecord**
O sistema utiliza **PHP ActiveRecord** como ORM:

```php
// Configuração (startup/config.php)
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('models');
    $cfg->set_connections(array(
        'development' => 'mysql://root:@localhost/gestufas_db'
    ));
    $cfg->set_default_connection('development');
});
```

### **Relacionamentos**
```php
class User extends ActiveRecord\Model {
    static $has_many = array(
        array('posts'),
        array('projects')
    );
}

class Post extends ActiveRecord\Model {
    static $belongs_to = array(
        array('user')
    );
    static $has_many = array(
        array('comments')
    );
}
```

### **Operações CRUD**
```php
// Create
$user = new User();
$user->username = 'joao';
$user->save();

// Read
$users = User::all();
$user = User::find(1);

// Update
$user->username = 'joao_silva';
$user->save();

// Delete
$user->delete();
```

---

## 🔐 Sistema de Autenticação

### **Fluxo de Login**
1. Utilizador acede a `?c=auth&a=login`
2. `AuthController::login()` processa credenciais
3. Validação contra base de dados
4. Criação de sessão PHP
5. Redirecionamento para área autenticada

### **Verificação de Autenticação**
```php
protected function authenticationFilter() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $this->redirectToRoute('auth', 'login');
        exit;
    }
}
```

### **Permissões**
- **Posts**: Apenas o autor pode editar/deletar
- **Projetos**: Apenas o criador pode editar/deletar
- **Comentários**: Apenas o autor pode editar/deletar
- **Utilizadores**: Apenas admins podem gerir

---

## 📱 Interface e UX

### **Design Responsivo**
- **Framework**: Bootstrap 5
- **Icons**: Font Awesome 6
- **Layout**: Mobile-first
- **Componentes**: Cards, modals, alerts, pagination

### **Navegação**
```php
// Header com navegação dinâmica
<nav class="navbar navbar-expand-lg">
    <div class="navbar-nav">
        <a class="nav-link" href="?c=home&a=index">Início</a>
        <a class="nav-link" href="?c=posts&a=index">Posts</a>
        <a class="nav-link" href="?c=projects&a=index">Projetos</a>
        <!-- ... -->
    </div>
</nav>
```

### **Feedback ao Utilizador**
- **Mensagens de sucesso**: `$_SESSION['success_message']`
- **Mensagens de erro**: `$_SESSION['error_message']`
- **Validação em tempo real**: JavaScript
- **Loading states**: Spinners e placeholders

---

## 🛡️ Segurança

### **Validação de Dados**
```php
// Exemplo de validação
$errors = [];
if (empty($title)) {
    $errors[] = 'O título é obrigatório.';
}
if (strlen($title) < 3) {
    $errors[] = 'O título deve ter pelo menos 3 caracteres.';
}
```

### **Escape de HTML**
```php
// Nas views
echo htmlspecialchars($post->title);
```

### **Proteção CSRF** (a implementar)
- Tokens CSRF em formulários
- Validação server-side

### **Sanitização**
```php
$title = trim($this->getHTTPPostParam('title'));
$content = strip_tags($this->getHTTPPostParam('content'));
```

---

## 📊 Performance e Otimização

### **Paginação**
```php
// Implementada em todos os listagens
$page = intval($this->getHTTPGetParam('page')) ?: 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

$posts = Post::find('all', array(
    'limit' => $perPage,
    'offset' => $offset
));
```

### **Lazy Loading**
```php
// Carregamento de relacionamentos apenas quando necessário
$post = Post::find($id, array(
    'include' => array('user', 'comments')
));
```

### **Cache** (a implementar)
- Cache de queries frequentes
- Cache de views renderizadas
- Cache de sessões

---

## 🔧 Configuração e Deploy

### **Configurações** (`config/`)
- `app.php`: Configurações gerais
- `config.php`: Configurações por ambiente
- `database.php`: Configurações de BD

### **Autoload** (`startup/config.php`)
```php
spl_autoload_register(function ($class) {
    $directories = [
        'controllers/',
        'models/',
        'core/',
        'framework/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});
```

### **Logs e Debug**
```php
// Função de debug
function debug($data, $label = 'Debug') {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<pre><strong>$label:</strong>\n";
        print_r($data);
        echo "</pre>";
    }
}

// Log de erros
function logError($message, $level = 'ERROR') {
    $logFile = __DIR__ . '/../logs/app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
```

---

## 🧪 Testes

### **Teste de Conexão** (`test_db.php`)
```php
// Testa conexão e operações básicas
$connection = ActiveRecord\ConnectionManager::get_connection();
$testUser = new User();
$testUser->username = 'teste_' . time();
// ...
```

### **Debug de Rotas** (`debug.php`)
- Informações do servidor
- Parâmetros da requisição
- Estado das classes
- Conexão com BD

---

## 📈 Métricas e Monitoring

### **Estatísticas do Sistema**
```php
// HomeController::index()
$totalUsers = User::count();
$totalPosts = Post::count();
$totalProjects = Project::count();
$recentActivity = $this->getRecentActivity();
```

### **Performance**
```php
// Medição de tempo de execução
$startTime = microtime(true);
// ... código ...
$executionTime = microtime(true) - $startTime;
```

---

## 🚀 Funcionalidades Implementadas

### ✅ **Completas**
- [x] Sistema de autenticação (login/logout/registo)
- [x] CRUD completo de utilizadores
- [x] CRUD completo de posts
- [x] CRUD completo de projetos
- [x] Sistema de comentários
- [x] Interface responsiva
- [x] Paginação
- [x] Validações
- [x] Permissões básicas
- [x] Logs e debug

### 🔄 **Em Desenvolvimento**
- [ ] Sistema de tags avançado
- [ ] Notificações em tempo real
- [ ] Upload de ficheiros
- [ ] API REST
- [ ] Testes automatizados
- [ ] Cache system
- [ ] Proteção CSRF
- [ ] Rate limiting

---

## 📝 Exemplo de Utilização

### **Criar um Post**
1. Utilizador acede a `?c=posts&a=create`
2. Preenche formulário (título, conteúdo, tags)
3. `PostController::store()` valida dados
4. Novo post é criado na BD
5. Utilizador é redirecionado para visualização

### **Comentar um Post**
1. Utilizador acede a `?c=posts&a=show&id=1`
2. Preenche formulário de comentário
3. `PostController::comment()` processa
4. Comentário é adicionado
5. Página é recarregada com novo comentário

---

## 🛠️ Manutenção

### **Logs**
- `logs/app.log`: Log geral da aplicação
- `logs/php_errors.log`: Erros PHP
- `logs/debug.log`: Informações de debug

### **Backup**
- Base de dados: `scripts/backup.sql`
- Ficheiros: backup da pasta `uploads/`

### **Updates**
- Composer: `composer update`
- Base de dados: scripts em `scripts/`

---

Esta documentação é **viva** e deve ser actualizada conforme o sistema evolui. 🚀
