# ✅ Validações - Sistema de Validação de Dados

## Visão Geral

O sistema de validações do ActiveRecord garante a integridade dos dados antes de serem salvos na base de dados. As validações são executadas automaticamente durante operações de criação e atualização.

## 🔍 Tipos de Validação

### **1. Validações de Presença**
Garantem que campos obrigatórios não estejam vazios.

```php
class User extends ActiveRecord\Model {
    static $validates_presence_of = [
        ['name'],                    // Simples
        ['email'],                   // Múltiplos campos
        ['password'],
        ['name', 'message' => 'Nome é obrigatório'],  // Com mensagem personalizada
        ['email', 'message' => 'Email não pode estar vazio']
    ];
}
```

### **2. Validações de Unicidade**
Garantem que valores sejam únicos na tabela.

```php
class User extends ActiveRecord\Model {
    static $validates_uniqueness_of = [
        ['email'],                   // Email único
        ['username'],                // Username único
        ['email', 'message' => 'Este email já está em uso'],
        ['username', 'case_sensitive' => false]  // Ignorar maiúsculas/minúsculas
    ];
}
```

### **3. Validações de Formato**
Validam formato usando expressões regulares.

```php
class User extends ActiveRecord\Model {
    static $validates_format_of = [
        // Email válido
        ['email', 'with' => '/\A[^@\s]+@[^@\s]+\z/', 'message' => 'Email inválido'],
        
        // Telefone (formato português)
        ['phone', 'with' => '/^(\+351)?[0-9]{9}$/', 'message' => 'Telefone inválido'],
        
        // CEP/Código postal
        ['postal_code', 'with' => '/^\d{4}-\d{3}$/', 'message' => 'Código postal inválido (0000-000)'],
        
        // Username (apenas letras, números e underscore)
        ['username', 'with' => '/^[a-zA-Z0-9_]+$/', 'message' => 'Username só pode conter letras, números e underscore']
    ];
}
```

### **4. Validações de Tamanho**
Controlam o comprimento de strings.

```php
class Post extends ActiveRecord\Model {
    static $validates_length_of = [
        // Título entre 5 e 200 caracteres
        ['title', 'minimum' => 5, 'maximum' => 200],
        
        // Conteúdo mínimo de 50 caracteres
        ['content', 'minimum' => 50, 'message' => 'Conteúdo muito curto'],
        
        // Password mínima de 8 caracteres
        ['password', 'minimum' => 8, 'message' => 'Password deve ter pelo menos 8 caracteres'],
        
        // Descrição máxima de 500 caracteres
        ['description', 'maximum' => 500, 'too_long' => 'Descrição muito longa (máximo %d caracteres)'],
        
        // Exatamente 9 dígitos
        ['nif', 'is' => 9, 'message' => 'NIF deve ter exatamente 9 dígitos']
    ];
}
```

### **5. Validações de Inclusão**
Verificam se valor está numa lista de opções válidas.

```php
class User extends ActiveRecord\Model {
    static $validates_inclusion_of = [
        // Role deve ser uma das opções
        ['role', 'in' => ['user', 'admin', 'moderator']],
        
        // Status com mensagem personalizada
        ['status', 'in' => ['active', 'inactive', 'pending'], 'message' => 'Status inválido'],
        
        // Género
        ['gender', 'in' => ['M', 'F', 'O'], 'allow_blank' => true]  // Permite vazio
    ];
}
```

### **6. Validações de Exclusão**
Verificam se valor NÃO está numa lista de valores proibidos.

```php
class User extends ActiveRecord\Model {
    static $validates_exclusion_of = [
        // Username não pode ser admin, root, etc.
        ['username', 'in' => ['admin', 'root', 'administrator', 'system']],
        
        // Email não pode ser de domínios específicos
        ['email', 'in' => ['test@test.com', 'admin@admin.com']]
    ];
}
```

### **7. Validações Numéricas**
Controlam valores numéricos.

```php
class Product extends ActiveRecord\Model {
    static $validates_numericality_of = [
        // Preço deve ser numérico e positivo
        ['price', 'greater_than' => 0],
        
        // Idade deve ser inteiro entre 18 e 120
        ['age', 'only_integer' => true, 'greater_than_or_equal_to' => 18, 'less_than' => 120],
        
        // Desconto entre 0 e 100%
        ['discount', 'greater_than_or_equal_to' => 0, 'less_than_or_equal_to' => 100],
        
        // Quantidade deve ser inteiro
        ['quantity', 'only_integer' => true, 'message' => 'Quantidade deve ser um número inteiro']
    ];
}
```

## 🛠️ Validações Personalizadas

### **Método de Validação Personalizada**
```php
class User extends ActiveRecord\Model {
    static $validates_presence_of = [['name', 'email']];
    
    // Validação antes de salvar
    static $before_save = ['validate_email_domain', 'validate_password_strength'];
    
    // Validação personalizada de domínio de email
    public function validate_email_domain() {
        $allowedDomains = ['ipleiria.pt', 'gmail.com', 'outlook.com'];
        
        if ($this->email) {
            $domain = substr(strrchr($this->email, "@"), 1);
            if (!in_array($domain, $allowedDomains)) {
                $this->errors->add('email', 'deve ser de um domínio permitido (' . implode(', ', $allowedDomains) . ')');
            }
        }
    }
    
    // Validação de força da password
    public function validate_password_strength() {
        if ($this->password && $this->is_dirty('password')) {
            // Verificar se tem pelo menos uma maiúscula
            if (!preg_match('/[A-Z]/', $this->password)) {
                $this->errors->add('password', 'deve conter pelo menos uma letra maiúscula');
            }
            
            // Verificar se tem pelo menos um número
            if (!preg_match('/[0-9]/', $this->password)) {
                $this->errors->add('password', 'deve conter pelo menos um número');
            }
            
            // Verificar se tem pelo menos um carácter especial
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $this->password)) {
                $this->errors->add('password', 'deve conter pelo menos um carácter especial');
            }
        }
    }
}
```

### **Validação com Callback Específico**
```php
class Post extends ActiveRecord\Model {
    static $validates_presence_of = [['title', 'content']];
    
    // Callback específico para validação
    static $validate = ['validate_unique_title_per_user'];
    
    public function validate_unique_title_per_user() {
        // Verificar se já existe post com mesmo título para o mesmo utilizador
        $existingPost = Post::first([
            'conditions' => [
                'title = ? AND user_id = ? AND id != ?',
                $this->title,
                $this->user_id,
                $this->id ?: 0
            ]
        ]);
        
        if ($existingPost) {
            $this->errors->add('title', 'já existe um post com este título');
        }
    }
}
```

## 🎯 Validações Condicionais

### **Validação com Condições**
```php
class Order extends ActiveRecord\Model {
    static $validates_presence_of = [
        ['shipping_address', 'if' => 'requires_shipping'],
        ['credit_card_number', 'if' => 'payment_method_is_credit_card']
    ];
    
    // Método que determina se precisa de envio
    public function requires_shipping() {
        return $this->delivery_method === 'shipping';
    }
    
    // Método que verifica método de pagamento
    public function payment_method_is_credit_card() {
        return $this->payment_method === 'credit_card';
    }
}
```

### **Validação com Unless**
```php
class User extends ActiveRecord\Model {
    static $validates_presence_of = [
        ['password', 'unless' => 'is_oauth_user']
    ];
    
    public function is_oauth_user() {
        return !empty($this->oauth_provider);
    }
}
```

## 🔄 Callbacks de Validação

### **Before e After Validation**
```php
class User extends ActiveRecord\Model {
    static $before_validation = ['normalize_data'];
    static $after_validation = ['log_validation_result'];
    
    // Normalizar dados antes da validação
    public function normalize_data() {
        if ($this->email) {
            $this->email = strtolower(trim($this->email));
        }
        
        if ($this->name) {
            $this->name = ucwords(strtolower(trim($this->name)));
        }
        
        if ($this->phone) {
            // Remover espaços e caracteres especiais do telefone
            $this->phone = preg_replace('/[^0-9+]/', '', $this->phone);
        }
    }
    
    // Log do resultado da validação
    public function log_validation_result() {
        if ($this->errors->is_empty()) {
            error_log("Validação bem-sucedida para utilizador: " . $this->email);
        } else {
            error_log("Erros de validação para " . $this->email . ": " . implode(', ', $this->errors->full_messages()));
        }
    }
}
```

## 🚨 Tratamento de Erros

### **Verificar Validações**
```php
// Criar utilizador
$user = new User([
    'name' => 'João Silva',
    'email' => 'joao@example.com',
    'password' => '123'  // Password fraca
]);

// Tentar salvar
if ($user->save()) {
    echo "Utilizador criado com sucesso!";
} else {
    // Mostrar erros
    echo "Erros de validação:\n";
    foreach ($user->errors->full_messages() as $error) {
        echo "- $error\n";
    }
    
    // Erros por campo
    if ($user->errors->on('password')) {
        echo "Erro na password: " . $user->errors->on('password') . "\n";
    }
}
```

### **Validação Sem Salvar**
```php
$user = new User(['email' => 'email-invalido']);

// Apenas validar (sem salvar)
if ($user->is_valid()) {
    echo "Dados válidos";
} else {
    echo "Dados inválidos: " . implode(', ', $user->errors->full_messages());
}
```

### **Pular Validações (usar com cuidado)**
```php
$user = User::find(1);
$user->name = '';  // Violaria validação de presença

// Salvar sem validações
$user->save(['validate' => false]);

// Ou usar update_attribute (não executa validações nem callbacks)
$user->update_attribute('name', '');
```

## 📝 Exemplo Prático Completo

```php
<?php
class User extends ActiveRecord\Model {
    static $table_name = 'users';
    
    // Validações básicas
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['email', 'message' => 'Email é obrigatório'],
        ['password', 'if' => 'password_required?']
    ];
    
    // Unicidade
    static $validates_uniqueness_of = [
        ['email', 'message' => 'Este email já está em uso'],
        ['username', 'case_sensitive' => false, 'allow_blank' => true]
    ];
    
    // Formato
    static $validates_format_of = [
        ['email', 'with' => '/\A[^@\s]+@[^@\s]+\z/', 'message' => 'Email inválido'],
        ['phone', 'with' => '/^(\+351)?[0-9]{9}$/', 'allow_blank' => true]
    ];
    
    // Tamanho
    static $validates_length_of = [
        ['name', 'minimum' => 2, 'maximum' => 100],
        ['password', 'minimum' => 8, 'if' => 'password_required?'],
        ['bio', 'maximum' => 500, 'allow_blank' => true]
    ];
    
    // Inclusão
    static $validates_inclusion_of = [
        ['role', 'in' => ['user', 'admin', 'moderator']],
        ['status', 'in' => ['active', 'inactive', 'pending']]
    ];
    
    // Numérica
    static $validates_numericality_of = [
        ['age', 'only_integer' => true, 'greater_than' => 0, 'less_than' => 150, 'allow_blank' => true]
    ];
    
    // Callbacks de validação
    static $before_validation = ['normalize_data'];
    static $validate = ['validate_password_strength', 'validate_age_consistency'];
    
    public function password_required?() {
        return $this->is_new_record() || !empty($this->password);
    }
    
    public function normalize_data() {
        $this->email = strtolower(trim($this->email ?? ''));
        $this->name = trim($this->name ?? '');
        if ($this->phone) {
            $this->phone = preg_replace('/[^0-9+]/', '', $this->phone);
        }
    }
    
    public function validate_password_strength() {
        if ($this->password && $this->is_dirty('password')) {
            if (!preg_match('/[A-Z]/', $this->password)) {
                $this->errors->add('password', 'deve conter pelo menos uma letra maiúscula');
            }
            if (!preg_match('/[0-9]/', $this->password)) {
                $this->errors->add('password', 'deve conter pelo menos um número');
            }
        }
    }
    
    public function validate_age_consistency() {
        if ($this->age && $this->birth_date) {
            $calculatedAge = (new DateTime())->diff(new DateTime($this->birth_date))->y;
            if (abs($this->age - $calculatedAge) > 1) {
                $this->errors->add('age', 'não é consistente com a data de nascimento');
            }
        }
    }
}

// Uso com tratamento de erros
try {
    $user = User::create([
        'name' => 'João Silva',
        'email' => 'joao@ipleiria.pt',
        'password' => 'MinhaPassword123',
        'role' => 'user',
        'age' => 25
    ]);
    
    echo "Utilizador criado com ID: " . $user->id;
    
} catch (ActiveRecord\ValidationException $e) {
    echo "Erros de validação: " . $e->getMessage();
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
```

## 🔧 Dicas e Melhores Práticas

### **1. Validações no Model vs Controller**
```php
// ✅ BOM: Validações no model
class User extends ActiveRecord\Model {
    static $validates_presence_of = [['email']];
    static $validates_format_of = [['email', 'with' => '/email_regex/']];
}

// ❌ EVITAR: Validações apenas no controller
class UserController extends Controller {
    public function store() {
        if (empty($_POST['email'])) {
            // Validação só no controller é insuficiente
        }
    }
}
```

### **2. Mensagens de Erro Personalizadas**
```php
// Mensagens específicas por idioma
class User extends ActiveRecord\Model {
    static $validates_presence_of = [
        ['name', 'message' => 'Nome é obrigatório'],
        ['email', 'message' => 'Email é obrigatório']
    ];
    
    static $validates_format_of = [
        ['email', 'with' => '/email_regex/', 'message' => 'Formato de email inválido']
    ];
}
```

### **3. Performance em Validações**
```php
class User extends ActiveRecord\Model {
    // ✅ Validação eficiente
    static $validates_uniqueness_of = [
        ['email']  // ActiveRecord otimiza a query
    ];
    
    // ❌ Evitar validações custosas
    public function validate_complex_business_rule() {
        // Evitar queries complexas em validações
        // Considerar fazer em callbacks ou services
    }
}
```

---

O sistema de validações garante a integridade dos dados e fornece feedback claro aos utilizadores sobre problemas nos dados fornecidos. Use validações apropriadas para cada tipo de dado e sempre trate os erros adequadamente na interface do utilizador.
