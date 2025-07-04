# 📊 Guia de Models - ActiveRecord ORM

## 📋 Introdução aos Models

Os **Models** no sistema GEstufas são classes que representam dados e lógica de negócio. Utilizamos o **PHP ActiveRecord** como ORM (Object-Relational Mapping) para facilitar operações com a base de dados.

---

## 🏗️ Estrutura Básica de um Model

### **Exemplo Simples**
```php
<?php
/**
 * Model: Product
 * Tabela: products
 * 
 * Representa um produto no sistema
 */
class Product extends ActiveRecord\Model {
    
    // Nome da tabela na base de dados (opcional se seguir convenção)
    static $table_name = 'products';
    
    // Validações obrigatórias
    static $validates_presence_of = array(
        array('name', 'message' => 'Nome é obrigatório'),
        array('price', 'message' => 'Preço é obrigatório')
    );
    
    // Validações de formato
    static $validates_format_of = array(
        array('email', 'with' => '/\A[\w+\-.]+@[a-z\d\-.]+\.[a-z]+\z/i', 
              'message' => 'Email inválido')
    );
    
    // Validações numéricas
    static $validates_numericality_of = array(
        array('price', 'greater_than' => 0, 'message' => 'Preço deve ser maior que 0')
    );
    
    // Relacionamentos
    static $belongs_to = array(
        array('category'),  // Pertence a uma categoria
        array('user')       // Pertence a um utilizador
    );
    
    static $has_many = array(
        array('reviews'),   // Tem muitas avaliações
        array('orders')     // Tem muitas encomendas
    );
}
```

---

## 🔗 Relacionamentos

### **Belongs To (Pertence a)**
```php
class Post extends ActiveRecord\Model {
    // Post pertence a um User
    static $belongs_to = array(
        array('user')
    );
    
    // Utilização
    public function getAuthor() {
        return $this->user; // Retorna o objeto User
    }
}
```

### **Has Many (Tem Muitos)**
```php
class User extends ActiveRecord\Model {
    // User tem muitos Posts
    static $has_many = array(
        array('posts'),
        array('comments')
    );
    
    // Utilização
    public function getAllPosts() {
        return $this->posts; // Retorna array de Posts
    }
    
    public function getPostCount() {
        return count($this->posts);
    }
}
```

### **Has One (Tem Um)**
```php
class User extends ActiveRecord\Model {
    // User tem um Profile
    static $has_one = array(
        array('profile')
    );
    
    // Utilização
    public function getUserProfile() {
        return $this->profile; // Retorna objeto Profile
    }
}
```

### **Many to Many (Muitos para Muitos)**
```php
class User extends ActiveRecord\Model {
    // Utilizadores pertencem a muitos grupos
    static $has_many = array(
        array('user_groups'),
        array('groups', 'through' => 'user_groups')
    );
}

class Group extends ActiveRecord\Model {
    // Grupos têm muitos utilizadores
    static $has_many = array(
        array('user_groups'),
        array('users', 'through' => 'user_groups')
    );
}

// Tabela de ligação: user_groups
class UserGroup extends ActiveRecord\Model {
    static $belongs_to = array(
        array('user'),
        array('group')
    );
}
```

---

## ✅ Sistema de Validações

### **Validações de Presença**
```php
static $validates_presence_of = array(
    array('name', 'message' => 'Nome é obrigatório'),
    array('email', 'message' => 'Email é obrigatório'),
    array('password', 'message' => 'Password é obrigatória')
);
```

### **Validações de Unicidade**
```php
static $validates_uniqueness_of = array(
    array('email', 'message' => 'Email já existe'),
    array('username', 'message' => 'Username já está em uso')
);
```

### **Validações de Comprimento**
```php
static $validates_length_of = array(
    array('password', 'minimum' => 6, 'message' => 'Password deve ter pelo menos 6 caracteres'),
    array('name', 'maximum' => 100, 'message' => 'Nome não pode ter mais de 100 caracteres'),
    array('description', 'within' => array(10, 500), 'message' => 'Descrição deve ter entre 10 e 500 caracteres')
);
```

### **Validações de Formato**
```php
static $validates_format_of = array(
    array('email', 'with' => '/\A[\w+\-.]+@[a-z\d\-.]+\.[a-z]+\z/i', 
          'message' => 'Email deve ter formato válido'),
    array('phone', 'with' => '/^\+?[1-9]\d{1,14}$/', 
          'message' => 'Telefone deve ter formato válido')
);
```

### **Validações Numéricas**
```php
static $validates_numericality_of = array(
    array('age', 'greater_than' => 0, 'less_than' => 150, 
          'message' => 'Idade deve estar entre 1 e 149'),
    array('price', 'greater_than_or_equal_to' => 0, 
          'message' => 'Preço não pode ser negativo')
);
```

### **Validações Personalizadas**
```php
class User extends ActiveRecord\Model {
    
    static $validates_presence_of = array(
        array('email'),
        array('password')
    );
    
    // Validação personalizada
    static $validate = array(
        array('custom_validation')
    );
    
    /**
     * Validação personalizada para verificar se email é corporativo
     */
    public function custom_validation() {
        if (!empty($this->email) && !str_ends_with($this->email, '@empresa.com')) {
            $this->errors->add('email', 'deve ser um email corporativo (@empresa.com)');
        }
        
        if (!empty($this->password) && strlen($this->password) < 8) {
            $this->errors->add('password', 'deve ter pelo menos 8 caracteres');
        }
    }
}
```

---

## 💾 Operações CRUD

### **Create (Criar)**
```php
// Método 1: Criar e salvar
$user = new User();
$user->name = 'João Silva';
$user->email = 'joao@example.com';
$user->password = md5('password123');

if ($user->save()) {
    echo "Utilizador criado com ID: " . $user->id;
} else {
    // Mostrar erros de validação
    foreach ($user->errors->full_messages() as $error) {
        echo "Erro: $error\n";
    }
}

// Método 2: Criar com array
$user = User::create(array(
    'name' => 'Maria Santos',
    'email' => 'maria@example.com',
    'password' => md5('password456')
));
```

### **Read (Ler)**
```php
// Encontrar por ID
$user = User::find(1);

// Encontrar o primeiro
$user = User::first();

// Encontrar o último  
$user = User::last();

// Encontrar todos
$users = User::all();

// Encontrar com condições
$users = User::find('all', array(
    'conditions' => array('active = ?', true),
    'order' => 'name ASC',
    'limit' => 10
));

// Encontrar um com condições
$user = User::find('first', array(
    'conditions' => array('email = ?', 'joao@example.com')
));

// Contar registros
$totalUsers = User::count();
$activeUsers = User::count(array(
    'conditions' => array('active = ?', true)
));
```

### **Update (Actualizar)**
```php
// Método 1: Encontrar e actualizar
$user = User::find(1);
$user->name = 'João Silva Santos';
$user->email = 'joao.santos@example.com';

if ($user->save()) {
    echo "Utilizador actualizado!";
} else {
    foreach ($user->errors->full_messages() as $error) {
        echo "Erro: $error\n";
    }
}

// Método 2: Actualizar directamente
User::update(1, array(
    'name' => 'João Silva Santos',
    'email' => 'joao.santos@example.com'
));

// Método 3: Actualizar múltiplos
User::update_all(
    "active = 0", 
    "last_login < '2024-01-01'"
);
```

### **Delete (Eliminar)**
```php
// Método 1: Encontrar e eliminar
$user = User::find(1);
$user->delete();

// Método 2: Eliminar por ID
User::destroy(1);

// Método 3: Eliminar múltiplos por ID
User::destroy(array(1, 2, 3));

// Método 4: Eliminar com condições
User::delete_all("active = 0 AND last_login < '2024-01-01'");
```

---

## 🔍 Consultas Avançadas

### **Condições Complexas**
```php
// AND
$users = User::find('all', array(
    'conditions' => array(
        'active = ? AND age >= ? AND city = ?', 
        true, 18, 'Lisboa'
    )
));

// OR usando SQL
$users = User::find('all', array(
    'conditions' => "age < 18 OR age > 65"
));

// IN
$users = User::find('all', array(
    'conditions' => array(
        'city IN (?)', 
        array('Lisboa', 'Porto', 'Coimbra')
    )
));

// LIKE
$users = User::find('all', array(
    'conditions' => array('name LIKE ?', '%Silva%')
));
```

### **Ordenação e Limitação**
```php
// Ordenação simples
$users = User::find('all', array(
    'order' => 'name ASC'
));

// Ordenação múltipla
$users = User::find('all', array(
    'order' => 'city ASC, name ASC'
));

// Limitação
$users = User::find('all', array(
    'limit' => 10,
    'offset' => 20  // Para paginação
));
```

### **Agrupamento**
```php
// Agrupar e contar
$cityCounts = User::find('all', array(
    'select' => 'city, COUNT(*) as user_count',
    'group' => 'city',
    'order' => 'user_count DESC'
));
```

### **Joins**
```php
// Inner Join
$posts = Post::find('all', array(
    'joins' => 'INNER JOIN users ON posts.user_id = users.id',
    'conditions' => 'users.active = 1'
));

// Left Join
$posts = Post::find('all', array(
    'joins' => 'LEFT JOIN comments ON posts.id = comments.post_id',
    'select' => 'posts.*, COUNT(comments.id) as comment_count',
    'group' => 'posts.id'
));
```

---

## 🛠️ Métodos Personalizados

### **Scopes (Consultas Nomeadas)**
```php
class User extends ActiveRecord\Model {
    
    /**
     * Utilizadores ativos
     */
    public static function active() {
        return self::find('all', array(
            'conditions' => array('active = ?', true)
        ));
    }
    
    /**
     * Utilizadores por cidade
     */
    public static function byCity($city) {
        return self::find('all', array(
            'conditions' => array('city = ?', $city),
            'order' => 'name ASC'
        ));
    }
    
    /**
     * Utilizadores registados este mês
     */
    public static function thisMonth() {
        return self::find('all', array(
            'conditions' => array(
                'created_at >= ?', 
                date('Y-m-01 00:00:00')
            )
        ));
    }
}

// Utilização
$activeUsers = User::active();
$lisboaUsers = User::byCity('Lisboa');
$newUsers = User::thisMonth();
```

### **Métodos de Instância**
```php
class User extends ActiveRecord\Model {
    
    /**
     * Nome completo do utilizador
     */
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    /**
     * Verificar se o utilizador é administrador
     */
    public function isAdmin() {
        return $this->role === 'admin';
    }
    
    /**
     * Verificar se o utilizador pode editar um post
     */
    public function canEdit($post) {
        return $this->id == $post->user_id || $this->isAdmin();
    }
    
    /**
     * Obter posts recentes do utilizador
     */
    public function getRecentPosts($limit = 5) {
        return Post::find('all', array(
            'conditions' => array('user_id = ?', $this->id),
            'order' => 'created_at DESC',
            'limit' => $limit
        ));
    }
    
    /**
     * Avatar do utilizador (com fallback)
     */
    public function getAvatar() {
        if (!empty($this->avatar)) {
            return 'uploads/avatars/' . $this->avatar;
        }
        return 'public/img/default-avatar.png';
    }
}

// Utilização
$user = User::find(1);
echo $user->getFullName();
if ($user->isAdmin()) {
    echo "Utilizador é administrador";
}
$recentPosts = $user->getRecentPosts(3);
```

---

## 🎯 Callbacks (Gatilhos)

### **Callbacks de Validação**
```php
class User extends ActiveRecord\Model {
    
    static $before_validation = array('prepare_data');
    static $after_validation = array('log_validation');
    
    public function prepare_data() {
        // Limpar e formatar dados antes da validação
        $this->email = strtolower(trim($this->email));
        $this->name = ucwords(strtolower(trim($this->name)));
    }
    
    public function log_validation() {
        if ($this->errors->is_empty()) {
            error_log("Validação bem-sucedida para utilizador: " . $this->email);
        }
    }
}
```

### **Callbacks de Gravação**
```php
class User extends ActiveRecord\Model {
    
    static $before_save = array('hash_password', 'set_created_at');
    static $after_save = array('send_welcome_email');
    static $before_create = array('generate_token');
    static $after_create = array('create_profile');
    
    public function hash_password() {
        if (!empty($this->password) && $this->password_changed()) {
            $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        }
    }
    
    public function set_created_at() {
        if ($this->is_new_record()) {
            $this->created_at = date('Y-m-d H:i:s');
        }
    }
    
    public function generate_token() {
        $this->token = bin2hex(random_bytes(32));
    }
    
    public function send_welcome_email() {
        // Enviar email de boas-vindas
        mail($this->email, 'Bem-vindo!', 'Obrigado por se registar!');
    }
    
    public function create_profile() {
        // Criar perfil automático
        $profile = new Profile();
        $profile->user_id = $this->id;
        $profile->save();
    }
}
```

---

## 📚 Exemplo Completo: Model Product

```php
<?php
/**
 * Model: Product
 * 
 * Representa um produto no sistema de e-commerce
 * 
 * Relacionamentos:
 * - Pertence a uma Category
 * - Pertence a um User (vendedor)
 * - Tem muitas Reviews
 * - Tem muitas OrderItems
 * 
 * @package Models
 */
class Product extends ActiveRecord\Model {
    
    // Nome da tabela
    static $table_name = 'products';
    
    // Validações
    static $validates_presence_of = array(
        array('name', 'message' => 'Nome do produto é obrigatório'),
        array('price', 'message' => 'Preço é obrigatório'),
        array('category_id', 'message' => 'Categoria é obrigatória'),
        array('user_id', 'message' => 'Vendedor é obrigatório')
    );
    
    static $validates_numericality_of = array(
        array('price', 'greater_than' => 0, 'message' => 'Preço deve ser maior que 0'),
        array('stock', 'greater_than_or_equal_to' => 0, 'message' => 'Stock não pode ser negativo')
    );
    
    static $validates_length_of = array(
        array('name', 'maximum' => 255, 'message' => 'Nome não pode ter mais de 255 caracteres'),
        array('description', 'maximum' => 2000, 'message' => 'Descrição não pode ter mais de 2000 caracteres')
    );
    
    // Relacionamentos
    static $belongs_to = array(
        array('category'),
        array('user', 'foreign_key' => 'user_id') // Vendedor
    );
    
    static $has_many = array(
        array('reviews'),
        array('order_items'),
        array('product_images')
    );
    
    // Callbacks
    static $before_save = array('format_data');
    static $after_create = array('log_creation');
    
    /**
     * Formatar dados antes de salvar
     */
    public function format_data() {
        $this->name = trim($this->name);
        $this->slug = $this->generateSlug();
        $this->updated_at = date('Y-m-d H:i:s');
    }
    
    /**
     * Log da criação do produto
     */
    public function log_creation() {
        error_log("Novo produto criado: {$this->name} (ID: {$this->id})");
    }
    
    /**
     * Gerar slug para URL amigável
     */
    private function generateSlug() {
        $slug = strtolower($this->name);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    
    /**
     * Produtos ativos (em stock)
     */
    public static function active() {
        return self::find('all', array(
            'conditions' => array('active = ? AND stock > ?', true, 0),
            'order' => 'created_at DESC'
        ));
    }
    
    /**
     * Produtos por categoria
     */
    public static function byCategory($categoryId) {
        return self::find('all', array(
            'conditions' => array('category_id = ? AND active = ?', $categoryId, true),
            'include' => array('category', 'user'),
            'order' => 'name ASC'
        ));
    }
    
    /**
     * Produtos em promoção
     */
    public static function onSale() {
        return self::find('all', array(
            'conditions' => array('sale_price IS NOT NULL AND sale_price > 0'),
            'order' => 'sale_price ASC'
        ));
    }
    
    /**
     * Preço final (com desconto se houver)
     */
    public function getFinalPrice() {
        if (!empty($this->sale_price) && $this->sale_price > 0) {
            return $this->sale_price;
        }
        return $this->price;
    }
    
    /**
     * Percentagem de desconto
     */
    public function getDiscountPercentage() {
        if (!empty($this->sale_price) && $this->sale_price > 0) {
            return round(($this->price - $this->sale_price) / $this->price * 100);
        }
        return 0;
    }
    
    /**
     * Verificar se está em stock
     */
    public function inStock() {
        return $this->stock > 0;
    }
    
    /**
     * Obter avaliação média
     */
    public function getAverageRating() {
        $reviews = $this->reviews;
        if (empty($reviews)) {
            return 0;
        }
        
        $total = 0;
        foreach ($reviews as $review) {
            $total += $review->rating;
        }
        
        return round($total / count($reviews), 1);
    }
    
    /**
     * URL do produto
     */
    public function getUrl() {
        return "index.php?c=products&a=show&id={$this->id}&slug={$this->slug}";
    }
    
    /**
     * Imagem principal do produto
     */
    public function getMainImage() {
        $images = $this->product_images;
        if (!empty($images)) {
            return $images[0]->image_path;
        }
        return 'public/img/no-image.png';
    }
    
    /**
     * Verificar se o utilizador pode editar este produto
     */
    public function canEdit($userId) {
        return $this->user_id == $userId;
    }
}
```

---

## 🚀 Dicas e Melhores Práticas

### **1. Nomenclatura**
- Models em **PascalCase** singular: `User`, `Post`, `Product`
- Tabelas em **snake_case** plural: `users`, `posts`, `products`
- Campos em **snake_case**: `first_name`, `created_at`, `user_id`

### **2. Validações**
- Sempre validar dados de entrada
- Usar mensagens de erro claras em português
- Combinar validações do ActiveRecord com validações personalizadas

### **3. Relacionamentos**
- Definir foreign keys explicitamente quando necessário
- Usar `include` para evitar N+1 queries
- Implementar métodos auxiliares para relacionamentos complexos

### **4. Performance**
- Usar `limit` e `offset` para paginação
- Evitar `find('all')` sem condições em tabelas grandes
- Usar `select` para limitar campos retornados
- Implementar cache para consultas frequentes

### **5. Segurança**
- Sempre usar prepared statements (ActiveRecord faz automaticamente)
- Validar e sanitizar dados
- Não expor dados sensíveis em métodos públicos
- Usar callbacks para encriptar dados sensíveis

### **6. Organização**
- Agrupar métodos por funcionalidade
- Documentar métodos complexos
- Usar callbacks para lógica automática
- Implementar scopes para consultas frequentes

---

Este guia fornece uma base sólida para trabalhar com Models no sistema GEstufas. Para exemplos mais específicos, consulte os outros guias na pasta `examples/`.
