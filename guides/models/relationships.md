# 🔗 Relacionamentos - Guia Completo

## Visão Geral

Os relacionamentos no ActiveRecord permitem definir e gerir associações entre diferentes models (tabelas) de forma simples e intuitiva. Este guia cobre todos os tipos de relacionamentos disponíveis no sistema GEstufas.

## 📊 Tipos de Relacionamentos

### 1. **Has Many (Um para Muitos)**
Um utilizador tem muitos posts, um post pertence a um utilizador.

#### **Configuração Básica**
```php
// models/User.php
class User extends ActiveRecord\Model {
    static $has_many = [
        ['posts'],           // Simples
        ['comments'],        // Múltiplos relacionamentos
        ['projects']
    ];
}

// models/Post.php
class Post extends ActiveRecord\Model {
    static $belongs_to = [
        ['user']
    ];
}
```

#### **Configuração Avançada**
```php
class User extends ActiveRecord\Model {
    static $has_many = [
        [
            'posts',
            'foreign_key' => 'author_id',  // Chave estrangeira personalizada
            'class_name' => 'BlogPost',     // Classe diferente
            'conditions' => 'published = 1', // Condições
            'order' => 'created_at DESC',   // Ordenação
            'limit' => 10                   // Limite
        ],
        [
            'recent_posts',
            'class_name' => 'Post',
            'conditions' => 'created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)',
            'order' => 'created_at DESC'
        ]
    ];
}
```

#### **Uso Prático**
```php
$user = User::find(1);

// Buscar todos os posts
$posts = $user->posts;
echo "Utilizador tem " . count($posts) . " posts";

// Iterar sobre posts
foreach ($user->posts as $post) {
    echo "Post: " . $post->title . "\n";
}

// Criar novo post associado
$newPost = $user->posts->create([
    'title' => 'Novo Post',
    'content' => 'Conteúdo do post'
]);

// Adicionar post existente
$existingPost = Post::find(5);
$user->posts[] = $existingPost;
$user->save();
```

### 2. **Belongs To (Muitos para Um)**
Muitos posts pertencem a um utilizador.

#### **Configuração**
```php
class Post extends ActiveRecord\Model {
    static $belongs_to = [
        ['user'],                    // Simples
        ['category'],                // Categoria do post
        ['author', 'class_name' => 'User', 'foreign_key' => 'author_id']
    ];
}
```

#### **Uso Prático**
```php
$post = Post::find(1);

// Aceder ao utilizador
echo "Autor: " . $post->user->name;
echo "Email: " . $post->user->email;

// Verificar se existe utilizador
if ($post->user) {
    echo "Post tem autor";
}

// Alterar utilizador
$newUser = User::find(2);
$post->user = $newUser;
$post->save();

// Ou usando ID
$post->user_id = 2;
$post->save();
```

### 3. **Has One (Um para Um)**
Um utilizador tem um perfil, um perfil pertence a um utilizador.

#### **Configuração**
```php
class User extends ActiveRecord\Model {
    static $has_one = [
        ['profile'],
        ['settings', 'class_name' => 'UserSettings']
    ];
}

class Profile extends ActiveRecord\Model {
    static $belongs_to = [
        ['user']
    ];
}
```

#### **Uso Prático**
```php
$user = User::find(1);

// Aceder ao perfil
if ($user->profile) {
    echo "Bio: " . $user->profile->bio;
}

// Criar perfil
$user->create_profile([
    'bio' => 'Biografia do utilizador',
    'website' => 'https://example.com'
]);

// Ou criar manualmente
$profile = new Profile([
    'bio' => 'Nova bio',
    'user_id' => $user->id
]);
$profile->save();
```

### 4. **Has Many Through (Muitos para Muitos)**
Utilizadores têm muitas tags através de posts.

#### **Configuração**
```php
// models/User.php
class User extends ActiveRecord\Model {
    static $has_many = [
        ['posts'],
        ['tags', 'through' => 'posts']  // Através de posts
    ];
}

// models/Post.php
class Post extends ActiveRecord\Model {
    static $belongs_to = [['user']];
    static $has_many = [
        ['post_tags'],
        ['tags', 'through' => 'post_tags']
    ];
}

// models/Tag.php
class Tag extends ActiveRecord\Model {
    static $has_many = [
        ['post_tags'],
        ['posts', 'through' => 'post_tags']
    ];
}

// models/PostTag.php (tabela de junção)
class PostTag extends ActiveRecord\Model {
    static $belongs_to = [
        ['post'],
        ['tag']
    ];
}
```

#### **Estrutura da Base de Dados**
```sql
-- Tabela de junção
CREATE TABLE post_tags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_tag (post_id, tag_id)
);
```

#### **Uso Prático**
```php
$post = Post::find(1);

// Buscar todas as tags do post
$tags = $post->tags;
foreach ($tags as $tag) {
    echo "Tag: " . $tag->name . "\n";
}

// Adicionar tag ao post
$tag = Tag::find(1);
$postTag = new PostTag([
    'post_id' => $post->id,
    'tag_id' => $tag->id
]);
$postTag->save();

// Ou criar através do relacionamento
$post->tags[] = $tag;
$post->save();

// Buscar posts de uma tag
$tag = Tag::find(1);
$posts = $tag->posts;

// Utilizador tem muitas tags através de posts
$user = User::find(1);
$userTags = $user->tags; // Tags de todos os posts do utilizador
```

## 🔧 Métodos de Relacionamento

### **Métodos Automáticos**
```php
class User extends ActiveRecord\Model {
    static $has_many = [['posts']];
    static $has_one = [['profile']];
}

$user = User::find(1);

// Has Many - métodos gerados automaticamente
$posts = $user->posts;              // Buscar todos
$user->posts->create([...]);        // Criar novo
$user->posts->build([...]);         // Construir (não salvar)
$user->posts->size();               // Contar
$user->posts->empty();              // Verificar se vazio

// Has One - métodos gerados
$profile = $user->profile;          // Buscar
$user->create_profile([...]);       // Criar
$user->build_profile([...]);        // Construir
```

### **Métodos Personalizados**
```php
class User extends ActiveRecord\Model {
    static $has_many = [['posts']];
    
    // Método personalizado para posts publicados
    public function getPublishedPosts() {
        return Post::all([
            'conditions' => [
                'user_id = ? AND published = 1',
                $this->id
            ],
            'order' => 'created_at DESC'
        ]);
    }
    
    // Contar posts por status
    public function countPostsByStatus($published = true) {
        return Post::count([
            'conditions' => [
                'user_id = ? AND published = ?',
                $this->id,
                $published ? 1 : 0
            ]
        ]);
    }
    
    // Posts recentes
    public function getRecentPosts($days = 7) {
        return Post::all([
            'conditions' => [
                'user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? DAY)',
                $this->id,
                $days
            ]
        ]);
    }
}
```

## 📈 Lazy vs Eager Loading

### **Lazy Loading (Padrão)**
```php
// Problema N+1: Uma query para posts, uma query para cada utilizador
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name; // Query individual para cada user
}
```

### **Eager Loading (Recomendado)**
```php
// Solução: Carregar utilizadores numa só query adicional
$posts = Post::all(['include' => ['user']]);
foreach ($posts as $post) {
    echo $post->user->name; // User já carregado, sem query adicional
}

// Múltiplos relacionamentos
$posts = Post::all(['include' => ['user', 'comments', 'tags']]);

// Relacionamentos aninhados
$posts = Post::all(['include' => ['user' => ['profile']]]);
```

## 🔍 Consultas com Relacionamentos

### **Filtrar por Relacionamento**
```php
// Posts de um utilizador específico
$userPosts = Post::all([
    'conditions' => ['user_id = ?', 1],
    'include' => ['user']
]);

// Posts com comentários
$postsWithComments = Post::all([
    'joins' => 'INNER JOIN comments ON posts.id = comments.post_id',
    'group' => 'posts.id'
]);

// Utilizadores com posts publicados
$activeUsers = User::all([
    'joins' => 'INNER JOIN posts ON users.id = posts.user_id',
    'conditions' => 'posts.published = 1',
    'group' => 'users.id'
]);
```

### **Contar Relacionamentos**
```php
// Contar posts por utilizador
$users = User::find_by_sql("
    SELECT users.*, COUNT(posts.id) as posts_count
    FROM users
    LEFT JOIN posts ON users.id = posts.user_id
    GROUP BY users.id
");

foreach ($users as $user) {
    echo $user->name . " tem " . $user->posts_count . " posts\n";
}
```

## 📝 Exemplos Práticos Avançados

### **Sistema de Blog Completo**
```php
// models/User.php
class User extends ActiveRecord\Model {
    static $has_many = [
        ['posts'],
        ['comments'],
        ['likes'],
        ['followers', 'class_name' => 'Follow', 'foreign_key' => 'followed_id'],
        ['following', 'class_name' => 'Follow', 'foreign_key' => 'follower_id']
    ];
    
    public function getFollowersCount() {
        return count($this->followers);
    }
    
    public function isFollowing($userId) {
        return Follow::exists([
            'conditions' => [
                'follower_id = ? AND followed_id = ?',
                $this->id,
                $userId
            ]
        ]);
    }
}

// models/Post.php
class Post extends ActiveRecord\Model {
    static $belongs_to = [['user']];
    static $has_many = [
        ['comments'],
        ['likes'],
        ['post_tags'],
        ['tags', 'through' => 'post_tags']
    ];
    
    public function getLikesCount() {
        return count($this->likes);
    }
    
    public function isLikedBy($userId) {
        return Like::exists([
            'conditions' => [
                'post_id = ? AND user_id = ?',
                $this->id,
                $userId
            ]
        ]);
    }
    
    public function getCommentsCount() {
        return count($this->comments);
    }
}

// models/Comment.php
class Comment extends ActiveRecord\Model {
    static $belongs_to = [
        ['post'],
        ['user']
    ];
    static $has_many = [
        ['replies', 'class_name' => 'Comment', 'foreign_key' => 'parent_id'],
    ];
    static $belongs_to_additional = [
        ['parent', 'class_name' => 'Comment']
    ];
}

// models/Like.php
class Like extends ActiveRecord\Model {
    static $belongs_to = [
        ['user'],
        ['post']
    ];
}

// models/Follow.php
class Follow extends ActiveRecord\Model {
    static $belongs_to = [
        ['follower', 'class_name' => 'User'],
        ['followed', 'class_name' => 'User']
    ];
}
```

### **Uso do Sistema**
```php
// Criar post com tags
$user = User::find(1);
$post = $user->posts->create([
    'title' => 'Meu Post',
    'content' => 'Conteúdo do post'
]);

// Adicionar tags
$tags = ['php', 'mvc', 'tutorial'];
foreach ($tags as $tagName) {
    $tag = Tag::first(['conditions' => ['name = ?', $tagName]]) 
           ?? Tag::create(['name' => $tagName]);
    
    PostTag::create([
        'post_id' => $post->id,
        'tag_id' => $tag->id
    ]);
}

// Adicionar comentário
$comment = $post->comments->create([
    'content' => 'Ótimo post!',
    'user_id' => 2
]);

// Adicionar like
$like = $post->likes->create(['user_id' => 3]);

// Seguir utilizador
$follow = Follow::create([
    'follower_id' => 2,
    'followed_id' => 1
]);

// Dashboard do utilizador
$user = User::find(1, ['include' => ['posts' => ['comments', 'likes']]]);
echo "Posts: " . count($user->posts) . "\n";
echo "Seguidores: " . $user->getFollowersCount() . "\n";

foreach ($user->posts as $post) {
    echo "Post: " . $post->title . "\n";
    echo "  Likes: " . $post->getLikesCount() . "\n";
    echo "  Comentários: " . $post->getCommentsCount() . "\n";
}
```

## ⚠️ Cuidados e Limitações

### **Performance**
```php
// ❌ Evitar - Problema N+1
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->user->name;
}

// ✅ Preferir - Eager Loading
$posts = Post::all(['include' => ['user']]);
foreach ($posts as $post) {
    echo $post->user->name;
}
```

### **Validações em Relacionamentos**
```php
class Post extends ActiveRecord\Model {
    static $belongs_to = [['user']];
    static $validates_presence_of = [['user_id']];
    
    // Validação personalizada
    static $before_save = ['validate_user_exists'];
    
    public function validate_user_exists() {
        if ($this->user_id && !User::exists($this->user_id)) {
            $this->errors->add('user_id', 'utilizador não existe');
        }
    }
}
```

### **Eliminação em Cascata**
```php
// Configurar na base de dados
CREATE TABLE posts (
    id INT PRIMARY KEY,
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

// Ou gerir no model
class User extends ActiveRecord\Model {
    static $has_many = [['posts']];
    static $before_delete = ['delete_associated_posts'];
    
    public function delete_associated_posts() {
        foreach ($this->posts as $post) {
            $post->delete();
        }
    }
}
```

---

Os relacionamentos são uma das funcionalidades mais poderosas do ActiveRecord, permitindo modelar relações complexas entre dados de forma simples e intuitiva. Use eager loading para evitar problemas de performance e sempre considere a integridade referencial dos dados.
