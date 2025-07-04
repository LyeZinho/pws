# 🧪 Guia de Testes com PHP, MySQL e ActiveRecord

Este guia aborda estratégias e práticas para testar aplicações PHP que usam MySQL e ActiveRecord de forma eficaz.

## 📋 Índice
- [Configuração de Ambiente de Testes](#configuração-de-ambiente-de-testes)
- [Testes de Models](#testes-de-models)
- [Testes de Base de Dados](#testes-de-base-de-dados)
- [Fixtures e Factories](#fixtures-e-factories)
- [Mocking e Stubs](#mocking-e-stubs)
- [Testes de Integração](#testes-de-integração)
- [Testes de Performance](#testes-de-performance)
- [Testes de Migração](#testes-de-migração)
- [CI/CD Testing](#cicd-testing)
- [Debugging de Testes](#debugging-de-testes)

---

## ⚙️ Configuração de Ambiente de Testes

### **Estrutura de Diretórios**

```
tests/
├── unit/
│   ├── models/
│   ├── controllers/
│   └── helpers/
├── integration/
│   ├── database/
│   └── api/
├── fixtures/
│   ├── users.php
│   └── posts.php
├── factories/
│   ├── UserFactory.php
│   └── PostFactory.php
├── support/
│   ├── TestCase.php
│   ├── DatabaseTestCase.php
│   └── helpers.php
└── bootstrap.php
```

### **Configuração Base de Testes**

```php
// tests/bootstrap.php
<?php

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar ambiente de teste
$_ENV['APP_ENV'] = 'testing';

// Configurar ActiveRecord para testes
ActiveRecord\Config::initialize(function($cfg) {
    $cfg->set_model_directory(__DIR__ . '/../models');
    $cfg->set_connections([
        'testing' => 'mysql://root:@localhost/app_test'
    ]);
    $cfg->set_default_connection('testing');
    
    // Desabilitar logs em testes
    $cfg->set_logging(false);
});

// Configurações globais de teste
date_default_timezone_set('UTC');
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### **Classe Base para Testes**

```php
// tests/support/TestCase.php
abstract class TestCase extends PHPUnit\Framework\TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        $this->setUpApplication();
    }
    
    protected function tearDown(): void {
        $this->tearDownApplication();
        parent::tearDown();
    }
    
    protected function setUpApplication() {
        // Configurações específicas para cada teste
    }
    
    protected function tearDownApplication() {
        // Limpeza após cada teste
    }
    
    protected function assertArrayHasKeys(array $keys, array $array, string $message = '') {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, $message);
        }
    }
    
    protected function assertDatabaseHas($table, array $data) {
        $connection = ActiveRecord\Connection::instance();
        
        $conditions = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "$key = ?";
            $values[] = $value;
        }
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $conditions);
        $result = $connection->query($sql, $values);
        
        $this->assertGreaterThan(0, $result[0]['count'], 
            "Failed asserting that table [$table] contains matching record.");
    }
    
    protected function assertDatabaseMissing($table, array $data) {
        $connection = ActiveRecord\Connection::instance();
        
        $conditions = [];
        $values = [];
        
        foreach ($data as $key => $value) {
            $conditions[] = "$key = ?";
            $values[] = $value;
        }
        
        $sql = "SELECT COUNT(*) as count FROM $table WHERE " . implode(' AND ', $conditions);
        $result = $connection->query($sql, $values);
        
        $this->assertEquals(0, $result[0]['count'], 
            "Failed asserting that table [$table] does not contain matching record.");
    }
    
    protected function assertDatabaseCount($table, $count) {
        $connection = ActiveRecord\Connection::instance();
        $result = $connection->query("SELECT COUNT(*) as count FROM $table");
        
        $this->assertEquals($count, $result[0]['count'],
            "Failed asserting that table [$table] contains [$count] records.");
    }
}
```

### **Classe para Testes de Base de Dados**

```php
// tests/support/DatabaseTestCase.php
abstract class DatabaseTestCase extends TestCase {
    
    protected $useTransactions = true;
    protected $connection;
    
    protected function setUp(): void {
        parent::setUp();
        $this->connection = ActiveRecord\Connection::instance();
        
        if ($this->useTransactions) {
            $this->connection->query("START TRANSACTION");
        }
        
        $this->seedDatabase();
    }
    
    protected function tearDown(): void {
        if ($this->useTransactions) {
            $this->connection->query("ROLLBACK");
        } else {
            $this->cleanDatabase();
        }
        
        parent::tearDown();
    }
    
    protected function seedDatabase() {
        // Override em subclasses para seeding específico
    }
    
    protected function cleanDatabase() {
        // Limpar todas as tabelas
        $tables = $this->getAllTables();
        
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            if ($table !== 'schema_migrations') {
                $this->connection->query("TRUNCATE TABLE $table");
            }
        }
        
        $this->connection->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    private function getAllTables() {
        $result = $this->connection->query("SHOW TABLES");
        return array_column($result, 'Tables_in_app_test');
    }
    
    protected function createTestDatabase() {
        $connection = new PDO('mysql://root:@localhost');
        $connection->query("DROP DATABASE IF EXISTS app_test");
        $connection->query("CREATE DATABASE app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    
    protected function runMigrations() {
        // Executar migrações para configurar schema de teste
        $migrationManager = new MigrationManager('migrations');
        $migrationManager->migrate();
    }
}
```

---

## 🏗️ Testes de Models

### **Testes de Validação**

```php
// tests/unit/models/UserTest.php
class UserTest extends DatabaseTestCase {
    
    public function testUserCreationWithValidData() {
        $user = new User([
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'senha123'
        ]);
        
        $this->assertTrue($user->save());
        $this->assertNotNull($user->id);
        $this->assertEquals('João Silva', $user->name);
    }
    
    public function testUserValidationRequiresName() {
        $user = new User([
            'email' => 'teste@exemplo.com',
            'password' => 'senha123'
        ]);
        
        $this->assertFalse($user->save());
        $this->assertContains('Name can\'t be blank', $user->errors->full_messages());
    }
    
    public function testEmailMustBeUnique() {
        // Criar primeiro usuário
        User::create([
            'name' => 'Primeiro',
            'email' => 'teste@exemplo.com',
            'password' => 'senha123'
        ]);
        
        // Tentar criar segundo com mesmo email
        $user = new User([
            'name' => 'Segundo',
            'email' => 'teste@exemplo.com',
            'password' => 'senha123'
        ]);
        
        $this->assertFalse($user->save());
        $this->assertContains('Email has already been taken', $user->errors->full_messages());
    }
    
    public function testEmailValidation() {
        $invalidEmails = [
            'invalid-email',
            '@domain.com',
            'user@',
            'user space@domain.com'
        ];
        
        foreach ($invalidEmails as $email) {
            $user = new User([
                'name' => 'Test User',
                'email' => $email,
                'password' => 'senha123'
            ]);
            
            $this->assertFalse($user->save(), "Email '$email' should be invalid");
        }
    }
    
    public function testPasswordHashing() {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@exemplo.com',
            'password' => 'plaintext_password'
        ]);
        
        $this->assertNotEquals('plaintext_password', $user->password_hash);
        $this->assertTrue(password_verify('plaintext_password', $user->password_hash));
    }
}
```

### **Testes de Relacionamentos**

```php
// tests/unit/models/RelationshipTest.php
class RelationshipTest extends DatabaseTestCase {
    
    public function testUserHasManyPosts() {
        $user = UserFactory::create();
        $posts = PostFactory::createMany(3, ['user_id' => $user->id]);
        
        $this->assertCount(3, $user->posts);
        $this->assertInstanceOf(Post::class, $user->posts[0]);
    }
    
    public function testPostBelongsToUser() {
        $user = UserFactory::create();
        $post = PostFactory::create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(User::class, $post->user);
        $this->assertEquals($user->id, $post->user->id);
    }
    
    public function testUserHasOneProfile() {
        $user = UserFactory::create();
        $profile = UserProfileFactory::create(['user_id' => $user->id]);
        
        $this->assertInstanceOf(UserProfile::class, $user->profile);
        $this->assertEquals($profile->id, $user->profile->id);
    }
    
    public function testManyToManyRelationship() {
        $post = PostFactory::create();
        $tags = TagFactory::createMany(3);
        
        // Associar tags ao post
        foreach ($tags as $tag) {
            PostTag::create(['post_id' => $post->id, 'tag_id' => $tag->id]);
        }
        
        $this->assertCount(3, $post->tags);
        
        // Testar relacionamento inverso
        $this->assertContains($post->id, array_column($tags[0]->posts->to_array(), 'id'));
    }
    
    public function testEagerLoading() {
        $users = UserFactory::createMany(5);
        foreach ($users as $user) {
            PostFactory::createMany(3, ['user_id' => $user->id]);
        }
        
        // Sem eager loading (deveria gerar N+1 queries)
        $usersWithoutEager = User::find('all');
        
        // Com eager loading
        $usersWithEager = User::find('all', ['include' => ['posts']]);
        
        $this->assertCount(5, $usersWithEager);
        $this->assertCount(3, $usersWithEager[0]->posts);
    }
}
```

### **Testes de Callbacks**

```php
// tests/unit/models/CallbackTest.php
class CallbackTest extends DatabaseTestCase {
    
    public function testBeforeSaveCallback() {
        $user = new User([
            'name' => 'Test User',
            'email' => 'TEST@EXEMPLO.COM', // Email em maiúscula
            'password' => 'senha123'
        ]);
        
        $user->save();
        
        // Verificar se callback normalizou o email
        $this->assertEquals('test@exemplo.com', $user->email);
    }
    
    public function testAfterCreateCallback() {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@exemplo.com',
            'password' => 'senha123'
        ]);
        
        // Verificar se callback criou perfil automaticamente
        $this->assertNotNull($user->profile);
        $this->assertEquals($user->id, $user->profile->user_id);
    }
    
    public function testBeforeDestroyCallback() {
        $user = UserFactory::create();
        $posts = PostFactory::createMany(3, ['user_id' => $user->id]);
        
        $user->delete();
        
        // Verificar se posts foram removidos
        $this->assertDatabaseCount('posts', 0);
    }
    
    public function testValidationCallbacks() {
        $user = new User([
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123' // Muito curta
        ]);
        
        $this->assertFalse($user->is_valid());
        $this->assertGreaterThan(0, count($user->errors->full_messages()));
    }
}
```

---

## 🗃️ Fixtures e Factories

### **Sistema de Fixtures**

```php
// tests/fixtures/users.php
return [
    'john' => [
        'name' => 'John Doe',
        'email' => 'john@exemplo.com',
        'password_hash' => password_hash('password123', PASSWORD_DEFAULT),
        'created_at' => '2025-01-01 10:00:00',
        'updated_at' => '2025-01-01 10:00:00'
    ],
    'jane' => [
        'name' => 'Jane Smith',
        'email' => 'jane@exemplo.com',
        'password_hash' => password_hash('password456', PASSWORD_DEFAULT),
        'created_at' => '2025-01-01 11:00:00',
        'updated_at' => '2025-01-01 11:00:00'
    ]
];

// tests/support/FixtureLoader.php
class FixtureLoader {
    private static $loadedFixtures = [];
    
    public static function load($fixtureName) {
        if (isset(self::$loadedFixtures[$fixtureName])) {
            return self::$loadedFixtures[$fixtureName];
        }
        
        $fixturePath = __DIR__ . "/../fixtures/$fixtureName.php";
        
        if (!file_exists($fixturePath)) {
            throw new Exception("Fixture não encontrada: $fixtureName");
        }
        
        $data = require $fixturePath;
        $records = [];
        
        // Determinar classe do model baseada no nome do fixture
        $modelClass = ucfirst(rtrim($fixtureName, 's'));
        
        foreach ($data as $key => $attributes) {
            $record = $modelClass::create($attributes);
            $records[$key] = $record;
        }
        
        self::$loadedFixtures[$fixtureName] = $records;
        return $records;
    }
    
    public static function get($fixtureName, $key) {
        $fixtures = self::load($fixtureName);
        
        if (!isset($fixtures[$key])) {
            throw new Exception("Fixture key não encontrada: $fixtureName.$key");
        }
        
        return $fixtures[$key];
    }
    
    public static function clear() {
        self::$loadedFixtures = [];
    }
}
```

### **Factory Pattern**

```php
// tests/factories/UserFactory.php
class UserFactory {
    
    public static function create($attributes = []) {
        $defaults = [
            'name' => self::randomName(),
            'email' => self::randomEmail(),
            'password' => 'password123',
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ];
        
        $attributes = array_merge($defaults, $attributes);
        
        return User::create($attributes);
    }
    
    public static function createMany($count, $attributes = []) {
        $records = [];
        
        for ($i = 0; $i < $count; $i++) {
            $records[] = self::create($attributes);
        }
        
        return $records;
    }
    
    public static function build($attributes = []) {
        $defaults = [
            'name' => self::randomName(),
            'email' => self::randomEmail(),
            'password' => 'password123'
        ];
        
        $attributes = array_merge($defaults, $attributes);
        
        return new User($attributes);
    }
    
    public static function states() {
        return [
            'admin' => ['role' => 'admin'],
            'inactive' => ['status' => 'inactive'],
            'verified' => ['email_verified_at' => new DateTime()]
        ];
    }
    
    public static function admin($attributes = []) {
        return self::create(array_merge(self::states()['admin'], $attributes));
    }
    
    public static function inactive($attributes = []) {
        return self::create(array_merge(self::states()['inactive'], $attributes));
    }
    
    private static function randomName() {
        $firstNames = ['João', 'Maria', 'Pedro', 'Ana', 'Carlos', 'Fernanda'];
        $lastNames = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Costa', 'Pereira'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
    
    private static function randomEmail() {
        return 'user' . uniqid() . '@exemplo.com';
    }
}

// tests/factories/PostFactory.php
class PostFactory {
    
    public static function create($attributes = []) {
        $defaults = [
            'title' => self::randomTitle(),
            'content' => self::randomContent(),
            'user_id' => UserFactory::create()->id,
            'status' => 'published',
            'created_at' => new DateTime(),
            'updated_at' => new DateTime()
        ];
        
        $attributes = array_merge($defaults, $attributes);
        
        return Post::create($attributes);
    }
    
    public static function createMany($count, $attributes = []) {
        $records = [];
        
        for ($i = 0; $i < $count; $i++) {
            $records[] = self::create($attributes);
        }
        
        return $records;
    }
    
    public static function draft($attributes = []) {
        return self::create(array_merge(['status' => 'draft'], $attributes));
    }
    
    public static function published($attributes = []) {
        return self::create(array_merge(['status' => 'published'], $attributes));
    }
    
    private static function randomTitle() {
        $titles = [
            'Como programar em PHP',
            'Guia de MySQL',
            'ActiveRecord em ação',
            'Desenvolvimento web moderno',
            'Testes automatizados'
        ];
        
        return $titles[array_rand($titles)] . ' ' . uniqid();
    }
    
    private static function randomContent() {
        return 'Este é um conteúdo de exemplo para o post de teste. ' . 
               'Contém informações úteis e relevantes sobre o tópico abordado.';
    }
}
```

### **Base Factory**

```php
// tests/factories/BaseFactory.php
abstract class BaseFactory {
    
    protected static function fake() {
        static $faker = null;
        
        if ($faker === null) {
            $faker = new FakeDataGenerator();
        }
        
        return $faker;
    }
    
    abstract public static function create($attributes = []);
    abstract public static function build($attributes = []);
    
    public static function createMany($count, $attributes = []) {
        $records = [];
        
        for ($i = 0; $i < $count; $i++) {
            $records[] = static::create($attributes);
        }
        
        return $records;
    }
}

// Gerador simples de dados falsos
class FakeDataGenerator {
    
    public function name() {
        $firstNames = ['João', 'Maria', 'Pedro', 'Ana', 'Carlos', 'Fernanda', 'Lucas', 'Juliana'];
        $lastNames = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Costa', 'Pereira', 'Rodrigues', 'Almeida'];
        
        return $firstNames[array_rand($firstNames)] . ' ' . $lastNames[array_rand($lastNames)];
    }
    
    public function email() {
        return 'user' . uniqid() . '@exemplo.com';
    }
    
    public function text($words = 10) {
        $lorem = 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua';
        $wordsArray = explode(' ', $lorem);
        
        $selectedWords = array_slice($wordsArray, 0, $words);
        return implode(' ', $selectedWords) . '.';
    }
    
    public function sentence() {
        return $this->text(rand(5, 15));
    }
    
    public function paragraph() {
        return $this->text(rand(20, 50));
    }
    
    public function number($min = 1, $max = 100) {
        return rand($min, $max);
    }
    
    public function boolean() {
        return (bool) rand(0, 1);
    }
    
    public function date($format = 'Y-m-d H:i:s') {
        $timestamp = rand(strtotime('-1 year'), time());
        return date($format, $timestamp);
    }
}
```

---

## 🎭 Mocking e Stubs

### **Mocking de ActiveRecord**

```php
// tests/unit/controllers/UserControllerTest.php
class UserControllerTest extends TestCase {
    
    public function testIndexReturnsUsers() {
        // Mock do model User
        $userMock = $this->createMock(User::class);
        $userMock->method('find')
                 ->with('all')
                 ->willReturn([
                     (object) ['id' => 1, 'name' => 'John'],
                     (object) ['id' => 2, 'name' => 'Jane']
                 ]);
        
        // Substituir classe User por mock
        $controller = new UserController();
        $controller->setUserModel($userMock);
        
        $result = $controller->index();
        
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]->name);
    }
    
    public function testCreateUserSuccess() {
        $userMock = $this->createMock(User::class);
        $userMock->method('save')->willReturn(true);
        $userMock->method('is_valid')->willReturn(true);
        $userMock->id = 1;
        
        $controller = new UserController();
        $controller->setUserModel($userMock);
        
        $result = $controller->create(['name' => 'John', 'email' => 'john@test.com']);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['user_id']);
    }
    
    public function testCreateUserValidationFailure() {
        $userMock = $this->createMock(User::class);
        $userMock->method('save')->willReturn(false);
        $userMock->method('is_valid')->willReturn(false);
        
        $errorsMock = $this->createMock(ActiveRecord\Errors::class);
        $errorsMock->method('full_messages')->willReturn(['Name can\'t be blank']);
        $userMock->errors = $errorsMock;
        
        $controller = new UserController();
        $controller->setUserModel($userMock);
        
        $result = $controller->create(['email' => 'john@test.com']);
        
        $this->assertFalse($result['success']);
        $this->assertContains('Name can\'t be blank', $result['errors']);
    }
}
```

### **Stub para Conexões de Banco**

```php
// tests/support/DatabaseStub.php
class DatabaseStub {
    private $queries = [];
    private $results = [];
    
    public function addExpectedQuery($sql, $result) {
        $this->results[$sql] = $result;
    }
    
    public function query($sql, $params = []) {
        $this->queries[] = ['sql' => $sql, 'params' => $params];
        
        if (isset($this->results[$sql])) {
            return $this->results[$sql];
        }
        
        return [];
    }
    
    public function getExecutedQueries() {
        return $this->queries;
    }
    
    public function assertQueryExecuted($expectedSql) {
        foreach ($this->queries as $query) {
            if ($query['sql'] === $expectedSql) {
                return true;
            }
        }
        
        throw new Exception("Query não foi executada: $expectedSql");
    }
    
    public function assertQueryCount($expectedCount) {
        $actualCount = count($this->queries);
        
        if ($actualCount !== $expectedCount) {
            throw new Exception("Esperado $expectedCount queries, mas $actualCount foram executadas");
        }
    }
    
    public function reset() {
        $this->queries = [];
        $this->results = [];
    }
}

// Uso em testes
class DatabaseStubTest extends TestCase {
    
    public function testQueryExecution() {
        $dbStub = new DatabaseStub();
        
        // Configurar resultado esperado
        $dbStub->addExpectedQuery(
            'SELECT * FROM users WHERE id = ?',
            [['id' => 1, 'name' => 'John', 'email' => 'john@test.com']]
        );
        
        // Injetar stub no ActiveRecord (requer modificação na implementação)
        ActiveRecord\Connection::setInstance($dbStub);
        
        // Executar operação
        $user = User::find(1);
        
        // Verificar comportamento
        $dbStub->assertQueryExecuted('SELECT * FROM users WHERE id = ?');
        $dbStub->assertQueryCount(1);
        
        $this->assertEquals('John', $user->name);
    }
}
```

---

## 🔗 Testes de Integração

### **Testes de API**

```php
// tests/integration/api/UserApiTest.php
class UserApiTest extends DatabaseTestCase {
    
    protected function setUp(): void {
        parent::setUp();
        $this->startOutputBuffering();
    }
    
    protected function tearDown(): void {
        $this->endOutputBuffering();
        parent::tearDown();
    }
    
    public function testGetUsersEndpoint() {
        // Criar dados de teste
        UserFactory::createMany(3);
        
        // Simular requisição
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/users';
        
        ob_start();
        require __DIR__ . '/../../../api/users.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertIsArray($response);
        $this->assertCount(3, $response);
        $this->assertArrayHasKeys(['id', 'name', 'email'], $response[0]);
    }
    
    public function testCreateUserEndpoint() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_POST = [
            'name' => 'John Doe',
            'email' => 'john@exemplo.com',
            'password' => 'password123'
        ];
        
        ob_start();
        require __DIR__ . '/../../../api/users.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@exemplo.com'
        ]);
    }
    
    public function testCreateUserValidationError() {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/api/users';
        $_POST = [
            'name' => '',
            'email' => 'invalid-email'
        ];
        
        ob_start();
        require __DIR__ . '/../../../api/users.php';
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertFalse($response['success']);
        $this->assertArrayHasKey('errors', $response);
        $this->assertDatabaseCount('users', 0);
    }
    
    private function startOutputBuffering() {
        ob_start();
    }
    
    private function endOutputBuffering() {
        if (ob_get_level()) {
            ob_end_clean();
        }
    }
}
```

### **Testes de Workflow Completo**

```php
// tests/integration/WorkflowTest.php
class WorkflowTest extends DatabaseTestCase {
    
    public function testCompleteUserRegistrationWorkflow() {
        // 1. Registrar usuário
        $userData = [
            'name' => 'João Silva',
            'email' => 'joao@exemplo.com',
            'password' => 'senha123'
        ];
        
        $user = User::create($userData);
        $this->assertNotNull($user->id);
        
        // 2. Verificar criação automática de perfil
        $this->assertNotNull($user->profile);
        $this->assertEquals($user->id, $user->profile->user_id);
        
        // 3. Criar posts
        $post1 = Post::create([
            'title' => 'Primeiro Post',
            'content' => 'Conteúdo do primeiro post',
            'user_id' => $user->id
        ]);
        
        $post2 = Post::create([
            'title' => 'Segundo Post',
            'content' => 'Conteúdo do segundo post',
            'user_id' => $user->id
        ]);
        
        // 4. Verificar relacionamentos
        $this->assertCount(2, $user->posts);
        $this->assertEquals($user->id, $post1->user->id);
        
        // 5. Adicionar comentários
        $comment = Comment::create([
            'content' => 'Excelente post!',
            'post_id' => $post1->id,
            'user_id' => $user->id
        ]);
        
        $this->assertCount(1, $post1->comments);
        
        // 6. Testar exclusão em cascata
        $user->delete();
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('posts', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('comments', ['user_id' => $user->id]);
    }
    
    public function testBlogPostPublicationWorkflow() {
        $author = UserFactory::create(['role' => 'author']);
        $editor = UserFactory::create(['role' => 'editor']);
        
        // 1. Autor cria rascunho
        $post = Post::create([
            'title' => 'Novo Artigo',
            'content' => 'Conteúdo do artigo...',
            'user_id' => $author->id,
            'status' => 'draft'
        ]);
        
        $this->assertEquals('draft', $post->status);
        
        // 2. Autor submete para revisão
        $post->status = 'pending_review';
        $post->save();
        
        // 3. Editor aprova
        $post->status = 'published';
        $post->published_at = new DateTime();
        $post->reviewed_by = $editor->id;
        $post->save();
        
        // 4. Verificar estado final
        $this->assertEquals('published', $post->status);
        $this->assertNotNull($post->published_at);
        $this->assertEquals($editor->id, $post->reviewed_by);
        
        // 5. Verificar que post aparece em listagem pública
        $publishedPosts = Post::find('all', [
            'conditions' => ['status = ?', 'published']
        ]);
        
        $this->assertContains($post->id, array_column($publishedPosts->to_array(), 'id'));
    }
}
```

---

## ⚡ Testes de Performance

### **Benchmarking de Queries**

```php
// tests/performance/QueryPerformanceTest.php
class QueryPerformanceTest extends DatabaseTestCase {
    
    protected function setUp(): void {
        parent::setUp();
        $this->createLargeDataset();
    }
    
    private function createLargeDataset() {
        // Criar dataset grande para testes de performance
        UserFactory::createMany(1000);
        
        foreach (User::find('all') as $user) {
            PostFactory::createMany(5, ['user_id' => $user->id]);
        }
    }
    
    public function testQueryPerformanceWithoutEagerLoading() {
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        $users = User::find('all', ['limit' => 100]);
        foreach ($users as $user) {
            $postCount = count($user->posts); // N+1 problem
        }
        
        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();
        
        $executionTime = $endTime - $startTime;
        $queryCount = $endQueries - $startQueries;
        
        echo "Sem eager loading: {$executionTime}s, $queryCount queries\n";
        
        // Deve ser lento devido ao N+1
        $this->assertGreaterThan(100, $queryCount);
    }
    
    public function testQueryPerformanceWithEagerLoading() {
        $startTime = microtime(true);
        $startQueries = $this->getQueryCount();
        
        $users = User::find('all', [
            'limit' => 100,
            'include' => ['posts']
        ]);
        
        foreach ($users as $user) {
            $postCount = count($user->posts); // Sem queries extras
        }
        
        $endTime = microtime(true);
        $endQueries = $this->getQueryCount();
        
        $executionTime = $endTime - $startTime;
        $queryCount = $endQueries - $startQueries;
        
        echo "Com eager loading: {$executionTime}s, $queryCount queries\n";
        
        // Deve ser eficiente
        $this->assertLessThan(5, $queryCount);
    }
    
    public function testBulkInsertPerformance() {
        $data = [];
        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'name' => "User $i",
                'email' => "user$i@exemplo.com",
                'password_hash' => password_hash('password', PASSWORD_DEFAULT)
            ];
        }
        
        // Teste de inserção individual
        $startTime = microtime(true);
        foreach (array_slice($data, 0, 100) as $userData) {
            User::create($userData);
        }
        $individualTime = microtime(true) - $startTime;
        
        // Teste de inserção em lote
        $startTime = microtime(true);
        $this->bulkInsert('users', array_slice($data, 100, 100));
        $bulkTime = microtime(true) - $startTime;
        
        echo "Inserção individual: {$individualTime}s\n";
        echo "Inserção em lote: {$bulkTime}s\n";
        
        $this->assertLessThan($individualTime, $bulkTime);
    }
    
    private function bulkInsert($table, $data) {
        $connection = ActiveRecord\Connection::instance();
        
        if (empty($data)) return;
        
        $columns = array_keys($data[0]);
        $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
        $values = [];
        
        foreach ($data as $row) {
            foreach ($columns as $column) {
                $values[] = $row[$column];
            }
        }
        
        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES " .
               implode(',', array_fill(0, count($data), $placeholders));
        
        $connection->query($sql, $values);
    }
    
    private function getQueryCount() {
        // Implementar contador de queries
        static $count = 0;
        return ++$count;
    }
    
    public function testMemoryUsage() {
        $startMemory = memory_get_usage();
        
        // Carregar muitos registros
        $users = User::find('all');
        
        $endMemory = memory_get_usage();
        $memoryUsed = $endMemory - $startMemory;
        
        echo "Memória usada: " . round($memoryUsed / 1024 / 1024, 2) . "MB\n";
        
        // Verificar se não excede limite razoável
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed); // 50MB
    }
}
```

---

## 🐛 Debugging de Testes

### **Helper de Debug**

```php
// tests/support/DebugHelper.php
class DebugHelper {
    
    public static function dumpQueries() {
        echo "\n=== Queries Executadas ===\n";
        
        $queries = ActiveRecord\Connection::instance()->getLastQueries();
        
        foreach ($queries as $i => $query) {
            echo ($i + 1) . ". " . $query . "\n";
        }
        
        echo "=========================\n\n";
    }
    
    public static function dumpModel($model) {
        echo "\n=== Debug do Model ===\n";
        echo "Classe: " . get_class($model) . "\n";
        echo "ID: " . ($model->id ?? 'null') . "\n";
        echo "Atributos:\n";
        
        foreach ($model->attributes() as $key => $value) {
            $displayValue = is_object($value) ? get_class($value) : $value;
            echo "  $key: $displayValue\n";
        }
        
        if ($model->errors && !$model->errors->is_empty()) {
            echo "Erros:\n";
            foreach ($model->errors->full_messages() as $error) {
                echo "  - $error\n";
            }
        }
        
        echo "======================\n\n";
    }
    
    public static function dumpDatabase($table = null) {
        $connection = ActiveRecord\Connection::instance();
        
        if ($table) {
            $tables = [$table];
        } else {
            $result = $connection->query("SHOW TABLES");
            $tables = array_column($result, 'Tables_in_app_test');
        }
        
        echo "\n=== Estado da Base de Dados ===\n";
        
        foreach ($tables as $tableName) {
            $count = $connection->query("SELECT COUNT(*) as count FROM $tableName")[0]['count'];
            echo "$tableName: $count registros\n";
            
            if ($count > 0 && $count <= 5) {
                $records = $connection->query("SELECT * FROM $tableName LIMIT 5");
                foreach ($records as $record) {
                    echo "  " . json_encode($record) . "\n";
                }
            }
        }
        
        echo "===============================\n\n";
    }
    
    public static function enableQueryLogging() {
        ActiveRecord\Config::initialize(function($cfg) {
            $cfg->set_logging(true);
            $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) {
                echo "[SQL] $sql\n";
            }));
        });
    }
    
    public static function measureTime($callback, $description = 'Operação') {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = round($endTime - $startTime, 4);
        $memoryUsed = round(($endMemory - $startMemory) / 1024, 2);
        
        echo "$description: {$executionTime}s, {$memoryUsed}KB\n";
        
        return $result;
    }
    
    public static function assertNoNPlusOne($callback) {
        $queryCount = 0;
        
        ActiveRecord\Config::initialize(function($cfg) use (&$queryCount) {
            $cfg->set_logging(true);
            $cfg->set_logger(new ActiveRecord\CallbackLogger(function($sql) use (&$queryCount) {
                $queryCount++;
            }));
        });
        
        $callback();
        
        // Verificar se não há muitas queries (indicativo de N+1)
        if ($queryCount > 10) {
            throw new Exception("Possível problema N+1 detectado: $queryCount queries executadas");
        }
        
        return $queryCount;
    }
}
```

### **Trait para Testes com Debug**

```php
// tests/support/DebuggableTest.php
trait DebuggableTest {
    
    protected function debug($message) {
        if ($this->isDebugMode()) {
            echo "\n[DEBUG] $message\n";
        }
    }
    
    protected function debugModel($model) {
        if ($this->isDebugMode()) {
            DebugHelper::dumpModel($model);
        }
    }
    
    protected function debugDatabase($table = null) {
        if ($this->isDebugMode()) {
            DebugHelper::dumpDatabase($table);
        }
    }
    
    protected function debugQueries() {
        if ($this->isDebugMode()) {
            DebugHelper::dumpQueries();
        }
    }
    
    private function isDebugMode() {
        return isset($_ENV['TEST_DEBUG']) && $_ENV['TEST_DEBUG'] === 'true';
    }
    
    protected function assertNoNPlusOne($callback) {
        $queryCount = DebugHelper::assertNoNPlusOne($callback);
        $this->debug("Queries executadas: $queryCount");
    }
    
    protected function measureAndAssertTime($callback, $maxTime = 1.0, $description = 'Operação') {
        $startTime = microtime(true);
        $result = $callback();
        $executionTime = microtime(true) - $startTime;
        
        $this->debug("$description: {$executionTime}s");
        $this->assertLessThan($maxTime, $executionTime, 
            "$description demorou mais que {$maxTime}s");
        
        return $result;
    }
}

// Uso em testes
class UserModelTest extends DatabaseTestCase {
    use DebuggableTest;
    
    public function testUserCreation() {
        $this->debug('Testando criação de usuário');
        
        $user = UserFactory::create(['name' => 'Test User']);
        
        $this->debugModel($user);
        $this->debugDatabase('users');
        
        $this->assertNotNull($user->id);
    }
}
```

---

## 📊 Relatórios de Teste

### **Gerador de Relatórios**

```php
// tests/support/TestReporter.php
class TestReporter {
    private $results = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    public function addResult($testName, $status, $duration, $memory, $details = []) {
        $this->results[] = [
            'test' => $testName,
            'status' => $status,
            'duration' => $duration,
            'memory' => $memory,
            'details' => $details
        ];
    }
    
    public function generateReport($format = 'html') {
        switch ($format) {
            case 'html':
                return $this->generateHtmlReport();
            case 'json':
                return $this->generateJsonReport();
            case 'xml':
                return $this->generateXmlReport();
            default:
                return $this->generateTextReport();
        }
    }
    
    private function generateHtmlReport() {
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($r) { return $r['status'] === 'pass'; }));
        $failedTests = $totalTests - $passedTests;
        $totalDuration = round(microtime(true) - $this->startTime, 2);
        
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Relatório de Testes</title>\n";
        $html .= "<style>\n";
        $html .= "body { font-family: Arial, sans-serif; margin: 20px; }\n";
        $html .= ".summary { background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px; }\n";
        $html .= ".pass { color: green; }\n";
        $html .= ".fail { color: red; }\n";
        $html .= "table { border-collapse: collapse; width: 100%; }\n";
        $html .= "th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }\n";
        $html .= "th { background-color: #f2f2f2; }\n";
        $html .= "</style>\n</head>\n<body>\n";
        
        $html .= "<h1>Relatório de Testes</h1>\n";
        $html .= "<div class='summary'>\n";
        $html .= "<h2>Resumo</h2>\n";
        $html .= "<p>Total de testes: $totalTests</p>\n";
        $html .= "<p class='pass'>Passou: $passedTests</p>\n";
        $html .= "<p class='fail'>Falhou: $failedTests</p>\n";
        $html .= "<p>Tempo total: {$totalDuration}s</p>\n";
        $html .= "</div>\n";
        
        $html .= "<h2>Detalhes dos Testes</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Teste</th><th>Status</th><th>Duração</th><th>Memória</th><th>Detalhes</th></tr>\n";
        
        foreach ($this->results as $result) {
            $statusClass = $result['status'] === 'pass' ? 'pass' : 'fail';
            $statusText = $result['status'] === 'pass' ? '✅ Passou' : '❌ Falhou';
            $memory = round($result['memory'] / 1024, 2) . ' KB';
            $details = !empty($result['details']) ? implode(', ', $result['details']) : '';
            
            $html .= "<tr>\n";
            $html .= "<td>{$result['test']}</td>\n";
            $html .= "<td class='$statusClass'>$statusText</td>\n";
            $html .= "<td>{$result['duration']}s</td>\n";
            $html .= "<td>$memory</td>\n";
            $html .= "<td>$details</td>\n";
            $html .= "</tr>\n";
        }
        
        $html .= "</table>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    private function generateJsonReport() {
        return json_encode([
            'summary' => [
                'total' => count($this->results),
                'passed' => count(array_filter($this->results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($this->results, function($r) { return $r['status'] === 'fail'; })),
                'duration' => round(microtime(true) - $this->startTime, 2)
            ],
            'results' => $this->results
        ], JSON_PRETTY_PRINT);
    }
    
    private function generateTextReport() {
        $output = "=== Relatório de Testes ===\n\n";
        
        $totalTests = count($this->results);
        $passedTests = count(array_filter($this->results, function($r) { return $r['status'] === 'pass'; }));
        $failedTests = $totalTests - $passedTests;
        $totalDuration = round(microtime(true) - $this->startTime, 2);
        
        $output .= "Total de testes: $totalTests\n";
        $output .= "Passou: $passedTests\n";
        $output .= "Falhou: $failedTests\n";
        $output .= "Tempo total: {$totalDuration}s\n\n";
        
        $output .= "Detalhes:\n";
        $output .= str_repeat("-", 80) . "\n";
        
        foreach ($this->results as $result) {
            $status = $result['status'] === 'pass' ? '✅' : '❌';
            $memory = round($result['memory'] / 1024, 2);
            
            $output .= "$status {$result['test']} ({$result['duration']}s, {$memory}KB)\n";
            
            if (!empty($result['details'])) {
                $output .= "   " . implode(', ', $result['details']) . "\n";
            }
        }
        
        return $output;
    }
    
    public function saveReport($filename, $format = 'html') {
        $content = $this->generateReport($format);
        file_put_contents($filename, $content);
        echo "Relatório salvo em: $filename\n";
    }
}
```

---

## 💡 Resumo de Boas Práticas

### ✅ **Fazer**
- Usar base de dados separada para testes
- Implementar fixtures e factories consistentes
- Testar tanto casos de sucesso quanto de falha
- Usar transações para isolar testes
- Mockar dependências externas
- Medir performance em testes críticos
- Automatizar testes no CI/CD
- Documentar testes complexos

### ❌ **Evitar**
- Testes que dependem de dados de produção
- Testes que modificam estado global
- Testes muito longos ou complexos
- Não limpar dados entre testes
- Ignorar testes que falham esporadicamente
- Testes sem assertivas claras
- Duplicação de lógica de teste

### 🎯 **Checklist de Testes**
- [ ] Ambiente de teste configurado
- [ ] Fixtures e factories implementadas
- [ ] Testes de models com validações
- [ ] Testes de relacionamentos
- [ ] Testes de integração de API
- [ ] Testes de performance básicos
- [ ] CI/CD executando testes
- [ ] Coverage de código adequado
- [ ] Documentação de testes atualizada

---

**🧪 Lembre-se:** Testes são uma parte essencial do desenvolvimento. Invista tempo em criar uma boa infraestrutura de testes para garantir a qualidade e confiabilidade da sua aplicação!
