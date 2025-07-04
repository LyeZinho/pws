<?php
/**
 * Arquivo de Configuração Principal do Sistema GEstufas
 * 
 * Este arquivo é responsável por:
 * 1. Carregar todas as configurações da aplicação
 * 2. Configurar o autoload de classes
 * 3. Inicializar o PHP ActiveRecord (ORM)
 * 4. Configurar a conexão com a base de dados
 * 5. Inicializar a sessão PHP
 * 
 * @package GEstufas
 * @version 1.0.0
 * @author Sistema GEstufas
 * @since 2025
 */

// Incluir configurações adicionais da aplicação
require_once __DIR__ . '/../config/app.php';

// Limpar output buffer para evitar warnings de headers
cleanOutputBuffer();

/**
 * Configuração do Autoload de Classes
 * 
 * O autoload permite carregar automaticamente as classes quando são utilizadas,
 * sem necessidade de fazer require_once manual para cada classe.
 * 
 * Directórios incluídos:
 * - controllers/: Todos os controllers do sistema
 * - models/: Todos os modelos (User, Post, Project, Comment)
 * - framework/: Classes do framework (Router)
 * - core/: Classes base do sistema
 */
spl_autoload_register(function ($class) {
    // Directórios onde procurar as classes
    $directories = [
        __DIR__ . '/../controllers/',  // Controllers (AuthController, UserController, etc.)
        __DIR__ . '/../models/',       // Models (User, Post, Project, Comment)
        __DIR__ . '/../framework/',    // Framework (Router)
        __DIR__ . '/../core/',         // Core (Controller base)
    ];
    
    // Tentar carregar a classe em cada directório
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return; // Classe encontrada, parar a procura
        }
    }
});

/**
 * Configuração do Composer
 * 
 * Carrega todas as dependências externas instaladas via Composer:
 * - PHP ActiveRecord: ORM para base de dados
 * - Carbon: Biblioteca para manipulação de datas
 */
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Configuração do PHP ActiveRecord (ORM)
 * 
 * O ActiveRecord é um padrão de design que permite trabalhar com a base de dados
 * de forma orientada a objetos, mapeando tabelas para classes.
 * 
 * Configurações:
 * - model_directory: Directório onde estão os modelos
 * - connections: Configurações de conexão com diferentes ambientes
 * - default_connection: Conexão padrão a usar
 */
ActiveRecord\Config::initialize(function($cfg) {
    // Definir o directório dos modelos
    $cfg->set_model_directory(__DIR__ . '/../models');
    
    // Configurar conexões com a base de dados
    $cfg->set_connections([
        // Ambiente de desenvolvimento
        'development' => 'mysql://root:@localhost/gestufas_db?charset=utf8',
        
        // Ambiente de produção (configurar quando necessário)
        'production' => 'mysql://user:password@localhost/gestufas_prod?charset=utf8'
    ]);
    
    // Configuração adicional para melhor compatibilidade
    $cfg->set_default_connection('development');
});

/**
 * Inicialização da Sessão PHP
 * 
 * As sessões são utilizadas para:
 * - Manter o usuário autenticado
 * - Armazenar mensagens de feedback (success/error)
 * - Manter dados temporários entre requisições
 * 
 * Verifica se a sessão já foi iniciada para evitar conflitos
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Configurações Adicionais de Segurança
 * 
 * Definir cabeçalhos de segurança para proteger a aplicação
 */
if (!headers_sent()) {
    // Proteger contra ataques XSS
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // Configurar charset padrão
    header('Content-Type: text/html; charset=UTF-8');
}

/**
 * Função auxiliar para debug (apenas em desenvolvimento)
 * 
 * @param mixed $data Dados para debug
 * @param string $label Rótulo para identificar o debug
 */
function debug($data, $label = 'Debug') {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px; border-left: 4px solid #007cba;'>";
        echo "<strong>$label:</strong><br>";
        echo "<pre>" . print_r($data, true) . "</pre>";
        echo "</div>";
    }
}

/**
 * Função para log de erros personalizado
 * 
 * @param string $message Mensagem de erro
 * @param string $level Nível do erro (INFO, WARNING, ERROR)
 */
function logError($message, $level = 'ERROR') {
    $logFile = __DIR__ . '/../logs/application.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Criar directório de logs se não existir
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    // Escrever no arquivo de log
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Tratamento de Erros Globais
 * 
 * Captura erros não tratados e logs para análise
 */
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    $errorMsg = "PHP Error: $message in $file on line $line";
    logError($errorMsg, 'PHP_ERROR');
    
    // Em desenvolvimento, mostrar o erro
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 10px; border-left: 4px solid #c62828;'>";
        echo "<strong>PHP Error:</strong> $message<br>";
        echo "<strong>File:</strong> $file<br>";
        echo "<strong>Line:</strong> $line";
        echo "</div>";
    }
    
    return true;
});

/**
 * Tratamento de Excepções Globais
 * 
 * Captura excepções não tratadas
 */
set_exception_handler(function($exception) {
    $errorMsg = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    logError($errorMsg, 'EXCEPTION');
    
    // Em desenvolvimento, mostrar a excepção
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 10px; margin: 10px; border-left: 4px solid #c62828;'>";
        echo "<strong>Uncaught Exception:</strong> " . $exception->getMessage() . "<br>";
        echo "<strong>File:</strong> " . $exception->getFile() . "<br>";
        echo "<strong>Line:</strong> " . $exception->getLine() . "<br>";
        echo "<strong>Trace:</strong><pre>" . $exception->getTraceAsString() . "</pre>";
        echo "</div>";
    } else {
        // Em produção, mostrar mensagem genérica
        echo "<h1>Erro do Sistema</h1>";
        echo "<p>Ocorreu um erro interno. Por favor, tente novamente mais tarde.</p>";
    }
});

// Log de inicialização do sistema
logError('Sistema GEstufas iniciado com sucesso', 'INFO');