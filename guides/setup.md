# ⚙️ Instalação e Configuração - Sistema GEstufas

## 🚀 Requisitos do Sistema

### **Requisitos Mínimos**
- **PHP:** 7.4+ (recomendado 8.0+)
- **MySQL:** 5.7+ ou MariaDB 10.2+
- **Apache/Nginx:** com mod_rewrite habilitado
- **Composer:** Para gestão de dependências

### **Extensões PHP Necessárias**
```bash
# Verificar extensões instaladas
php -m | grep -E "(pdo|pdo_mysql|session|json|mbstring)"
```

**Extensões obrigatórias:**
- `pdo`
- `pdo_mysql`
- `session`
- `json`
- `mbstring`

## 📥 Instalação

### 1. **Clone/Download do Projeto**
```bash
# Clone do repositório (se aplicável)
git clone [url-do-repositorio] gestufas
cd gestufas

# Ou extrair ficheiro ZIP
unzip gestufas.zip
cd gestufas
```

### 2. **Instalar Dependências**
```bash
# Instalar dependências via Composer
composer install

# Ou se não tiver composer globalmente
php composer.phar install
```

### 3. **Configurar Servidor Web**

#### **Apache (.htaccess)**
O projeto já inclui `.htaccess` configurado:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Configurações de segurança
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

<FilesMatch "\.(sql|log|md)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

#### **Nginx**
```nginx
server {
    listen 80;
    server_name gestufas.local;
    root /path/to/gestufas;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Bloquear acesso a ficheiros sensíveis
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(sql|log|md)$ {
        deny all;
    }
}
```

### 4. **Configurar Base de Dados**

#### **Criar Base de Dados**
```sql
-- MySQL/MariaDB
CREATE DATABASE gestufas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'gestufas_user'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT ALL PRIVILEGES ON gestufas.* TO 'gestufas_user'@'localhost';
FLUSH PRIVILEGES;
```

#### **Executar Scripts SQL**
```bash
# Navegar para o diretório de scripts
cd scripts/

# Executar script principal
mysql -u gestufas_user -p gestufas < posts-comments-schema.sql

# Executar scripts adicionais (se existirem)
mysql -u gestufas_user -p gestufas < add-tags-column.sql
```

### 5. **Configurar Aplicação**

#### **Configuração Principal (config/config.php)**
```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'database' => 'gestufas',
        'username' => 'gestufas_user',
        'password' => 'senha_segura',
        'charset' => 'utf8mb4',
        'port' => 3306
    ],
    
    'app' => [
        'name' => 'GEstufas',
        'debug' => true,  // false em produção
        'timezone' => 'Europe/Lisbon',
        'url' => 'http://localhost/gestufas',
    ],
    
    'session' => [
        'name' => 'gestufas_session',
        'lifetime' => 86400, // 24 horas em segundos
        'secure' => false,   // true em HTTPS
        'httponly' => true,
    ],
    
    'uploads' => [
        'path' => 'public/uploads/',
        'max_size' => 2 * 1024 * 1024, // 2MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif']
    ]
];
```

### 6. **Configurar Permissões**
```bash
# Linux/Mac
chmod -R 755 .
chmod -R 777 logs/
chmod -R 777 public/uploads/

# Criar diretórios necessários
mkdir -p logs public/uploads
```

## 🧪 Testar Instalação

### 1. **Testar Conexão à Base de Dados**
```bash
# Executar teste de conexão
php test_db.php
```

**Saída esperada:**
```
✅ Conexão à base de dados: OK
✅ Tabela users encontrada
✅ Tabela posts encontrada
✅ Tabela projects encontrada
✅ ActiveRecord configurado: OK
```

### 2. **Testar Aplicação Web**
```bash
# Servidor PHP built-in (para desenvolvimento)
php -S localhost:8000

# Ou usar WAMP/XAMPP/MAMP
```

**Acessar:** `http://localhost:8000` ou `http://localhost/gestufas`

### 3. **Verificar Debug**
```bash
# Executar script de debug
php debug.php
```

## 🔧 Configuração Avançada

### **Configuração de Produção**

#### **Desabilitar Debug**
```php
// config/config.php
'app' => [
    'debug' => false,
    'log_level' => 'error'
],
```

#### **Configurar HTTPS**
```php
// config/config.php
'session' => [
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'
],
```

#### **Configurar Cache**
```php
// config/config.php
'cache' => [
    'enabled' => true,
    'driver' => 'file',
    'path' => 'storage/cache/',
    'ttl' => 3600
]
```

### **Configuração de Email**
```php
// config/config.php
'mail' => [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'seu-email@gmail.com',
    'password' => 'sua-senha-app',
    'encryption' => 'tls',
    'from' => [
        'address' => 'noreply@gestufas.com',
        'name' => 'GEstufas System'
    ]
]
```

### **Configuração de Logs**
```php
// config/config.php
'logging' => [
    'enabled' => true,
    'level' => 'info', // debug, info, warning, error
    'file' => 'logs/app.log',
    'max_size' => 10 * 1024 * 1024, // 10MB
    'rotate' => true
]
```

## 🌍 Ambientes de Desenvolvimento

### **Ambiente Local (WAMP/XAMPP)**
```php
// config/environments/local.php
return [
    'database' => [
        'host' => 'localhost',
        'database' => 'gestufas',
        'username' => 'root',
        'password' => '',
    ],
    'app' => [
        'debug' => true,
        'url' => 'http://localhost/gestufas',
    ]
];
```

### **Ambiente de Teste**
```php
// config/environments/testing.php
return [
    'database' => [
        'host' => 'localhost',
        'database' => 'gestufas_test',
        'username' => 'test_user',
        'password' => 'test_password',
    ],
    'app' => [
        'debug' => true,
    ]
];
```

### **Ambiente de Produção**
```php
// config/environments/production.php
return [
    'database' => [
        'host' => getenv('DB_HOST'),
        'database' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
    ],
    'app' => [
        'debug' => false,
        'url' => getenv('APP_URL'),
    ]
];
```

### **Carregamento Dinâmico de Ambiente**
```php
// config/config.php
$environment = getenv('APP_ENV') ?: 'local';
$baseConfig = require __DIR__ . '/config.php';
$envConfig = require __DIR__ . "/environments/{$environment}.php";

return array_merge_recursive($baseConfig, $envConfig);
```

## 📋 Checklist de Instalação

### ✅ **Pré-instalação**
- [ ] PHP 7.4+ instalado
- [ ] MySQL/MariaDB instalado
- [ ] Composer instalado
- [ ] Servidor web configurado

### ✅ **Instalação**
- [ ] Projeto baixado/clonado
- [ ] `composer install` executado
- [ ] Base de dados criada
- [ ] Scripts SQL executados
- [ ] Configuração em `config/config.php`
- [ ] Permissões de ficheiros configuradas

### ✅ **Testes**
- [ ] `test_db.php` executado com sucesso
- [ ] Aplicação carrega sem erros
- [ ] Login/registo funcionam
- [ ] CRUD básico funciona

### ✅ **Produção (se aplicável)**
- [ ] Debug desabilitado
- [ ] HTTPS configurado
- [ ] Backups configurados
- [ ] Logs monitorizados

## 🆘 Resolução de Problemas

### **Erro: "Class not found"**
```bash
# Regenerar autoload
composer dump-autoload
```

### **Erro: "Connection refused"**
```bash
# Verificar serviço MySQL
sudo systemctl status mysql
sudo systemctl start mysql
```

### **Erro: "Permission denied"**
```bash
# Corrigir permissões
chmod -R 755 .
chmod -R 777 logs/ public/uploads/
```

### **Erro: "Headers already sent"**
- Verificar espaços em branco antes de `<?php`
- Verificar encoding de ficheiros (UTF-8 sem BOM)

### **ActiveRecord não funciona**
```php
// Verificar configuração em startup/config.php
require_once 'vendor/autoload.php';

ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory('models');
    $cfg->set_connections([
        'development' => 'mysql://user:pass@localhost/gestufas'
    ]);
});
```

---

## 📞 Suporte

Para problemas na instalação:
1. Verificar requisitos do sistema
2. Consultar logs em `logs/`
3. Executar `test_db.php` e `debug.php`
4. Verificar configuração em `config/config.php`

**Ficheiros de Log:**
- `logs/app.log` - Logs da aplicação
- `logs/php_errors.log` - Erros PHP
- Logs do servidor web (Apache/Nginx)

A instalação correta é fundamental para o bom funcionamento do sistema. Siga todos os passos e execute os testes para garantir que tudo está funcionando corretamente.
