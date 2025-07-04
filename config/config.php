<?php
// config/config.php - Configurações específicas do ambiente

// Configurações de ambiente
define('ENVIRONMENT', 'development'); // development, staging, production

// Configurações de base de dados baseadas no ambiente
switch (ENVIRONMENT) {
    case 'development':
        define('DB_HOST', 'localhost');
        define('DB_NAME', 'gestufas_db');
        define('DB_USER', 'root');
        define('DB_PASS', '');
        define('DB_CHARSET', 'utf8');
        
        // Configurações de debug para desenvolvimento
        define('APP_DEBUG', true);
        define('DISPLAY_ERRORS', true);
        define('LOG_ERRORS', true);
        break;
        
    case 'staging':
        define('DB_HOST', 'staging-db-host');
        define('DB_NAME', 'gestufas_staging');
        define('DB_USER', 'staging_user');
        define('DB_PASS', 'staging_password');
        define('DB_CHARSET', 'utf8');
        
        define('APP_DEBUG', false);
        define('DISPLAY_ERRORS', false);
        define('LOG_ERRORS', true);
        break;
        
    case 'production':
        define('DB_HOST', 'production-db-host');
        define('DB_NAME', 'gestufas_production');
        define('DB_USER', 'production_user');
        define('DB_PASS', 'production_password');
        define('DB_CHARSET', 'utf8');
        
        define('APP_DEBUG', false);
        define('DISPLAY_ERRORS', false);
        define('LOG_ERRORS', true);
        break;
        
    default:
        throw new Exception('Ambiente não definido corretamente');
}

// Configurações de segurança
define('HASH_ALGORITHM', 'sha256');
define('HASH_SALT', 'gestufas_salt_2025_' . ENVIRONMENT);

// Configurações de aplicação
define('APP_NAME', 'GEstufas');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/pws');

// Configurações de timezone
date_default_timezone_set('Europe/Lisbon');