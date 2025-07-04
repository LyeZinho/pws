<?php
// config/app.php - Configurações adicionais da aplicação

// Configurações de PHP para melhor compatibilidade
ini_set('error_reporting', E_ALL & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Configurações de timezone
date_default_timezone_set('Europe/Lisbon');

// Configurações de encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // Definir como 1 em HTTPS

// Configurações de segurança
ini_set('expose_php', 0);

// Configurações de upload
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '10M');
ini_set('max_execution_time', '300');
ini_set('memory_limit', '256M');

// Configurações de cache
ini_set('opcache.enable', 1);
ini_set('opcache.memory_consumption', 128);
ini_set('opcache.max_accelerated_files', 4000);
ini_set('opcache.revalidate_freq', 60);

// Função para limpar output buffer e evitar warnings de headers
function cleanOutputBuffer() {
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
}

// Configurações de base de dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestufas_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8');

// Configurações de aplicação
define('APP_NAME', 'GEstufas');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true);
define('APP_URL', 'http://localhost/pws');

// Configurações de criptografia
define('HASH_ALGORITHM', 'sha256');
define('HASH_SALT', 'gestufas_salt_2025');

// Configurações de e-mail
define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', '');
define('MAIL_PASSWORD', '');
define('MAIL_FROM', 'noreply@gestufas.com');
define('MAIL_FROM_NAME', 'GEstufas System');

// Configurações de upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB
define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx']);

// Configurações de logs
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO');
