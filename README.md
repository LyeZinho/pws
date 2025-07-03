# GEstufas - Sistema de Gestão de Estufas

## 📋 Sobre o Projeto

O GEstufas é um sistema web desenvolvido em PHP que utiliza o padrão MVC (Model-View-Controller) para gestão de estufas. O projeto implementa um ORM (Object-Relational Mapping) utilizando a biblioteca PHP ActiveRecord para facilitar as operações com a base de dados.

## 🏗️ Arquitetura do Sistema

### Estrutura de Pastas

```
📁 projeto/
├── 📁 config/           # Configurações do sistema
├── 📁 controllers/      # Controllers do padrão MVC
├── 📁 core/            # Classes base do sistema
├── 📁 framework/       # Framework customizado (Router)
├── 📁 models/          # Modelos ActiveRecord
├── 📁 public/          # Recursos públicos (CSS, JS, imagens)
├── 📁 scripts/         # Scripts SQL da base de dados
├── 📁 startup/         # Configurações de inicialização
├── 📁 vendor/          # Dependências do Composer
├── 📁 views/           # Templates/Views
├── composer.json       # Configuração do Composer
├── index.php          # Ponto de entrada da aplicação
└── routes.php         # Definição das rotas
```

## 🔧 Tecnologias Utilizadas

- **PHP 7.4+** - Linguagem principal
- **MySQL** - Sistema de gestão de base de dados
- **PHP ActiveRecord** - ORM para mapeamento objeto-relacional
- **Bootstrap 5** - Framework CSS para interface responsiva
- **Composer** - Gerenciador de dependências
- **Carbon** - Biblioteca para manipulação de datas

## 🚀 Passos para Implementação do Projeto

### 📋 Fase 1: Configuração Inicial do Ambiente

#### 1.1 Pré-requisitos
- **XAMPP** (ou WAMP/LAMP) instalado
- **PHP 7.4+**
- **MySQL 5.7+**
- **Composer** instalado globalmente

#### 1.2 Configuração do Servidor
```bash
# Iniciar serviços XAMPP
- Apache
- MySQL
```

#### 1.3 Instalação de Dependências
```bash
# Na pasta do projeto
composer install

# Verificar instalação
composer show
```

### 📊 Fase 2: Configuração da Base de Dados

#### 2.1 Criar Base de Dados
```sql
CREATE DATABASE gestufas_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestufas_db;
```

#### 2.2 Executar Scripts SQL
```sql
-- Executar posts-comments-schema.sql
-- Executar add-tags-column.sql
```

#### 2.3 Configurar Ligação à Base de Dados
```php
// startup/config.php
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('./models');
    $cfg->set_connections(array(
        'development' => 'mysql://root:password@localhost/gestufas_db',
    ));
});
```

### 🏗️ Fase 3: Implementação dos Modelos (ActiveRecord)

#### 3.1 Criar Modelo User
```php
<?php
// models/User.php
class User extends ActiveRecord\Model {
    static $table_name = 'users';
    
    static $validates_presence_of = array(
        array('username', 'message' => 'Username é obrigatório'),
        array('email', 'message' => 'Email é obrigatório'),
        array('password', 'message' => 'Password é obrigatório')
    );
    
    static $validates_uniqueness_of = array(
        array('username', 'message' => 'Username já existe'),
        array('email', 'message' => 'Email já existe')
    );
    
    static $has_many = array(
        array('posts'),
        array('projects')
    );
    
    // Relacionamentos
    public function posts() {
        return $this->has_many('Post');
    }
    
    public function projects() {
        return $this->has_many('Project');
    }
}
```

#### 3.2 Criar Modelo Post
```php
<?php
// models/Post.php
class Post extends ActiveRecord\Model {
    static $table_name = 'posts';
    
    static $validates_presence_of = array(
        array('title', 'message' => 'Título é obrigatório'),
        array('content', 'message' => 'Conteúdo é obrigatório'),
        array('user_id', 'message' => 'Utilizador é obrigatório')
    );
    
    static $belongs_to = array(
        array('user')
    );
    
    static $has_many = array(
        array('comments')
    );
    
    // Relacionamentos
    public function user() {
        return $this->belongs_to('User');
    }
    
    public function comments() {
        return $this->has_many('Comment');
    }
}
```

#### 3.3 Criar Modelo Project
```php
<?php
// models/Project.php
class Project extends ActiveRecord\Model {
    static $table_name = 'projects';
    
    static $validates_presence_of = array(
        array('name', 'message' => 'Nome é obrigatório'),
        array('description', 'message' => 'Descrição é obrigatória'),
        array('user_id', 'message' => 'Utilizador é obrigatório')
    );
    
    static $belongs_to = array(
        array('user')
    );
    
    public function user() {
        return $this->belongs_to('User');
    }
}
```

### 🎮 Fase 4: Implementação dos Controllers

#### 4.1 Criar HomeController
```php
<?php
// controllers/HomeController.php
class HomeController extends Controller {
    
    public function index() {
        $recentPosts = Post::find('all', array(
            'order' => 'created_at DESC',
            'limit' => 5
        ));
        
        $data = [
            'title' => 'Página Inicial - GEstufas',
            'posts' => $recentPosts
        ];
        
        $this->renderView('home', 'index', $data);
    }
}
```

#### 4.2 Implementar AuthController
```php
<?php
// controllers/AuthController.php
class AuthController extends Controller {
    
    public function index() {
        $this->renderView('auth', 'login');
    }
    
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $this->getHTTPPostParam('username');
            $password = $this->getHTTPPostParam('password');
            
            $user = User::find('first', array(
                'conditions' => array('username = ? AND password = ?', $username, md5($password))
            ));
            
            if ($user) {
                session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $this->redirectToRoute('home', 'index');
            } else {
                $data = ['error' => 'Credenciais inválidas'];
                $this->renderView('auth', 'login', $data);
            }
        } else {
            $this->renderView('auth', 'login');
        }
    }
    
    public function logout() {
        session_start();
        session_destroy();
        $this->redirectToRoute('auth', 'index');
    }
    
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User();
            $user->username = $this->getHTTPPostParam('username');
            $user->email = $this->getHTTPPostParam('email');
            $user->password = md5($this->getHTTPPostParam('password'));
            
            if ($user->save()) {
                $this->redirectToRoute('auth', 'index');
            } else {
                $data = ['errors' => $user->errors];
                $this->renderView('auth', 'register', $data);
            }
        } else {
            $this->renderView('auth', 'register');
        }
    }
}
```

#### 4.3 Implementar CommunityController
```php
<?php
// controllers/CommunityController.php
class CommunityController extends Controller {
    
    public function index() {
        $this->authenticationFilter();
        
        $posts = Post::find('all', array(
            'order' => 'created_at DESC',
            'include' => array('user')
        ));
        
        $data = [
            'title' => 'Comunidade',
            'posts' => $posts
        ];
        
        $this->renderView('community', 'index', $data);
    }
    
    public function create() {
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();
            $post = new Post();
            $post->title = $this->getHTTPPostParam('title');
            $post->content = $this->getHTTPPostParam('content');
            $post->user_id = $_SESSION['user_id'];
            
            if ($post->save()) {
                $this->redirectToRoute('community', 'index');
            } else {
                $data = ['errors' => $post->errors];
                $this->renderView('community', 'create', $data);
            }
        } else {
            $this->renderView('community', 'create');
        }
    }
    
    public function show() {
        $this->authenticationFilter();
        
        $id = $this->getHTTPGetParam('id');
        $post = Post::find($id, array('include' => array('user', 'comments')));
        
        if ($post) {
            $data = [
                'title' => $post->title,
                'post' => $post
            ];
            $this->renderView('community', 'show', $data);
        } else {
            $this->redirectToRoute('community', 'index');
        }
    }
}
```

### 🎨 Fase 5: Implementação das Views

#### 5.1 Criar Layout Principal
```php
<!-- views/layout/default.php -->
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'GEstufas' ?></title>
    <link href="public/css/bootstrap.min.css" rel="stylesheet">
    <link href="public/css/custom.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="public/img/logo-ipleiria.png" alt="Logo" height="30">
                GEstufas
            </a>
            <div class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="index.php?c=community&a=index">Comunidade</a>
                    <a class="nav-link" href="index.php?c=profile&a=index">Perfil</a>
                    <a class="nav-link" href="index.php?c=auth&a=logout">Logout</a>
                <?php else: ?>
                    <a class="nav-link" href="index.php?c=auth&a=index">Login</a>
                    <a class="nav-link" href="index.php?c=auth&a=register">Registar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container mt-4">
        <?php require_once($viewPath); ?>
    </main>
    
    <footer class="bg-light mt-5 py-3">
        <div class="container text-center">
            <p>&copy; 2025 GEstufas - Sistema de Gestão de Estufas</p>
        </div>
    </footer>
    
    <script src="public/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

#### 5.2 Criar Views de Autenticação
```php
<!-- views/auth/login.php -->
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Login</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-success">Entrar</button>
                    <a href="index.php?c=auth&a=register" class="btn btn-link">Registar</a>
                </form>
            </div>
        </div>
    </div>
</div>
```

### 🛣️ Fase 6: Configuração das Rotas

#### 6.1 Expandir routes.php
```php
<?php
// routes.php
return [
    'defaultRoute' => ['GET', 'HomeController', 'index'],
    'home' => [
        'index' => ['GET', 'HomeController', 'index'],
    ],
    'auth' => [
        'index' => ['GET', 'AuthController', 'index'],
        'login' => ['GET|POST', 'AuthController', 'login'],
        'logout' => ['GET', 'AuthController', 'logout'],
        'register' => ['GET|POST', 'AuthController', 'register'],
    ],
    'community' => [
        'index' => ['GET', 'CommunityController', 'index'],
        'create' => ['GET|POST', 'CommunityController', 'create'],
        'show' => ['GET', 'CommunityController', 'show'],
    ],
    'profile' => [
        'index' => ['GET', 'ProfileController', 'index'],
        'edit' => ['GET|POST', 'ProfileController', 'edit'],
    ]
];
```

### 🔒 Fase 7: Implementação do Sistema de Autenticação

#### 7.1 Criar Classe Auth
```php
<?php
// models/Auth.php
class Auth {
    
    public function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return User::find($_SESSION['user_id']);
        }
        return null;
    }
    
    public function login($username, $password) {
        $user = User::find('first', array(
            'conditions' => array('username = ? AND password = ?', $username, md5($password))
        ));
        
        if ($user) {
            session_start();
            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            return true;
        }
        return false;
    }
    
    public function logout() {
        session_start();
        session_destroy();
    }
}
```

### 🗃️ Fase 8: Scripts SQL da Base de Dados

#### 8.1 Schema Principal
```sql
-- scripts/posts-comments-schema.sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT NOT NULL,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 🧪 Fase 9: Testes e Validação

#### 9.1 Testes de Funcionalidade
- [ ] Registo de utilizador
- [ ] Login e logout
- [ ] Criação de posts
- [ ] Visualização de posts
- [ ] Sistema de navegação
- [ ] Responsive design

#### 9.2 Testes de Segurança
- [ ] Validação de dados
- [ ] Proteção contra SQL Injection
- [ ] Controlo de acesso
- [ ] Sanitização de inputs

### 📈 Fase 10: Funcionalidades Avançadas

#### 10.1 Sistema de Comentários
```php
// Implementar comentários em posts
// Adicionar validações
// Criar views para comentários
```

#### 10.2 Upload de Imagens
```php
// Sistema de upload para projetos
// Validação de tipos de ficheiro
// Redimensionamento automático
```

#### 10.3 Sistema de Tags
```sql
-- scripts/add-tags-column.sql
ALTER TABLE posts ADD COLUMN tags VARCHAR(255);
CREATE INDEX idx_posts_tags ON posts(tags);
```

## 🔧 Comandos Úteis

### Composer
```bash
composer install          # Instalar dependências
composer update           # Atualizar dependências
composer dump-autoload    # Regenerar autoload
```

### Git
```bash
git init                  # Inicializar repositório
git add .                 # Adicionar ficheiros
git commit -m "Initial commit"  # Primeiro commit
```

### Base de Dados
```sql
-- Backup da base de dados
mysqldump -u root -p gestufas_db > backup.sql

-- Restaurar backup
mysql -u root -p gestufas_db < backup.sql
```

## 🚀 Funcionalidades Implementadas

### Módulos Principais

1. **Sistema de Autenticação** (`AuthController`)
   - Login e logout de utilizadores
   - Gestão de sessões
   - Controlo de acesso

2. **Gestão de Comunidade** (`CommunityController`)
   - Publicação de posts
   - Interação entre utilizadores
   - Sistema de comentários

3. **Gestão de Perfil** (`ProfileController`)
   - Edição de perfil do utilizador
   - Preferências pessoais

## 📊 Modelos de Dados

### Principais Entidades

- **User** - Utilizadores do sistema
- **Post** - Publicações da comunidade
- **Project** - Projetos de estufas

### Relacionamentos

O sistema utiliza o PHP ActiveRecord para gerir os relacionamentos entre entidades:

```php
// Exemplo de relacionamento
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
}
```

## 🔄 Sistema de Roteamento

O sistema utiliza um router personalizado que mapeia URLs para controllers e actions:

```php
// Exemplo de rota
'auth' => [
    'login' => ['GET|POST', 'AuthController', 'login'],
    'logout' => ['GET', 'AuthController', 'logout'],
]
```

### Padrão de URL
```
index.php?c=controller&a=action&param=value
```

## 🎨 Interface do Utilizador

- **Design Responsivo** - Utiliza Bootstrap 5 para compatibilidade móvel
- **Layout Modular** - Sistema de layouts e views reutilizáveis
- **Componentes Reutilizáveis** - Headers, footers e componentes comuns

## 🗄️ Base de Dados

### Configuração
```php
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('./models');
    $cfg->set_connections(array(
        'development' => 'mysql://root@localhost/gestufas_db',
    ));
});
```

### Scripts SQL
- `posts-comments-schema.sql` - Schema para posts e comentários
- `add-tags-column.sql` - Script para adicionar sistema de tags

## 📦 Instalação e Configuração

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Composer
- Servidor web (Apache/Nginx)

### Passos de Instalação

1. **Clonar o repositório**
   ```bash
   git clone [url-do-repositorio]
   cd gestufas
   ```

2. **Instalar dependências**
   ```bash
   composer install
   ```

3. **Configurar base de dados**
   - Criar base de dados MySQL
   - Executar scripts SQL da pasta `scripts/`
   - Configurar ligação em `startup/config.php`

4. **Configurar ambiente**
   ```php
   // startup/config.php
   $cfg->set_connections(array(
       'development' => 'mysql://utilizador:password@localhost/gestufas_db',
   ));
   ```

5. **Configurar servidor web**
   - Apontar document root para a pasta do projeto
   - Ativar mod_rewrite (Apache)

## 🔒 Segurança

### Medidas Implementadas
- Validação de parâmetros HTTP
- Controlo de acesso baseado em sessões
- Sanitização de dados de entrada
- Proteção contra SQL Injection via ActiveRecord

## 🧪 Desenvolvimento

### Padrões de Código
- **PSR-4** - Autoloading de classes
- **MVC** - Separação de responsabilidades
- **ActiveRecord** - Padrão de acesso a dados

### Estrutura de um Controller
```php
class ExampleController extends Controller {
    public function index() {
        $data = ['title' => 'Página Inicial'];
        $this->renderView('example', 'index', $data);
    }
}
```

### Estrutura de um Model
```php
class Example extends ActiveRecord\Model {
    static $table_name = 'examples';
    static $validates_presence_of = array(
        array('name', 'message' => 'Nome é obrigatório')
    );
}
```

## 📈 Funcionalidades Futuras

### Planejamento
- [ ] API REST para integração móvel
- [ ] Sistema de notificações
- [ ] Dashboard de analytics
- [ ] Sistema de relatórios
- [ ] Integração com sensores IoT
- [ ] Sistema de backup automático

### Melhorias Técnicas
- [ ] Implementação de cache
- [ ] Logging estruturado
- [ ] Testes unitários
- [ ] CI/CD pipeline
- [ ] Containerização com Docker

## 🤝 Contribuição

### Como Contribuir
1. Fork do projeto
2. Criar branch para funcionalidade (`git checkout -b feature/nova-funcionalidade`)
3. Commit das alterações (`git commit -m 'Adicionar nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Criar Pull Request

### Padrões de Commit
- `feat:` - Nova funcionalidade
- `fix:` - Correção de bug
- `docs:` - Documentação
- `style:` - Formatação de código
- `refactor:` - Refatoração
- `test:` - Testes

## 📄 Licença

Este projeto está sob a licença MIT. Consulte o arquivo `LICENSE` para mais detalhes.

## 👥 Equipa de Desenvolvimento

- **Desenvolvedor Principal** - [Seu Nome]
- **Arquiteto de Software** - [Nome]
- **Designer UI/UX** - [Nome]

## 📞 Suporte

Para suporte técnico ou dúvidas sobre o projeto:
- Email: [email@projeto.com]
- Issues: [GitHub Issues]
- Documentação: [Link para documentação]

---

**Versão:** 1.0.0  
**Última Atualização:** Janeiro 2025  
**Status:** Em Desenvolvimento Ativo
