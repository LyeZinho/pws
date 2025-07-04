<?php
/**
 * Arquivo Principal da Aplicação GEstufas
 * 
 * Este é o ponto de entrada único da aplicação (Single Entry Point).
 * Todas as requisições HTTP passam por este arquivo, que:
 * 
 * 1. Carrega as configurações do sistema
 * 2. Inicializa o router
 * 3. Processa a requisição HTTP
 * 4. Chama o controller e action apropriados
 * 
 * Padrão MVC (Model-View-Controller):
 * - Model: Representa os dados (User, Post, Project, Comment)
 * - View: Representa a interface (templates HTML)
 * - Controller: Controla a lógica da aplicação
 * 
 * Fluxo da Requisição:
 * 1. Utilizador acede a uma URL (ex: ?c=users&a=index)
 * 2. index.php carrega as configurações
 * 3. Router analisa a URL e determina qual controller/action chamar
 * 4. Controller processa a lógica e carrega dados
 * 5. View é renderizada com os dados
 * 6. HTML é enviado para o navegador
 * 
 * @package GEstufas
 * @version 1.0.0
 * @author Sistema GEstufas
 * @since 2025
 */

// Iniciar medição de tempo de execução (para performance)
$startTime = microtime(true);

try {
    /**
     * Carregar Configurações do Sistema
     * 
     * Este arquivo configura:
     * - Autoload de classes
     * - Conexão com base de dados
     * - Inicialização da sessão
     * - Tratamento de erros
     */
    require_once 'startup/config.php';
    
    /**
     * Carregar e Inicializar o Router
     * 
     * O Router é responsável por:
     * - Analisar a URL (parâmetros c= e a=)
     * - Determinar qual controller chamar
     * - Verificar se o método HTTP é permitido
     * - Instanciar o controller e chamar o método
     */
    require_once 'framework/Router.php';
    $router = new Router();
    
    /**
     * Processar a Requisição
     * 
     * Este é o ponto onde a mágica acontece:
     * - Router lê os parâmetros da URL
     * - Consulta o arquivo routes.php
     * - Instancia o controller apropriado
     * - Chama o método correspondente
     */
    $router->route();
    
} catch (Exception $e) {
    /**
     * Tratamento de Erros Globais
     * 
     * Se algo der errado no processamento da requisição,
     * capturamos o erro e mostramos uma mensagem apropriada
     */
    
    // Log do erro para análise
    logError('Erro crítico na aplicação: ' . $e->getMessage(), 'CRITICAL');
    
    // Em desenvolvimento, mostrar detalhes do erro
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<div style='background: #ffebee; color: #c62828; padding: 20px; margin: 20px; border-left: 4px solid #c62828;'>";
        echo "<h2>🚨 Erro Crítico da Aplicação</h2>";
        echo "<p><strong>Mensagem:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>Stack Trace:</strong></p>";
        echo "<pre style='background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;'>";
        echo htmlspecialchars($e->getTraceAsString());
        echo "</pre>";
        echo "</div>";
    } else {
        // Em produção, mostrar mensagem genérica
        echo "<!DOCTYPE html>";
        echo "<html lang='pt-BR'>";
        echo "<head>";
        echo "<meta charset='UTF-8'>";
        echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
        echo "<title>Erro - GEstufas</title>";
        echo "<link href='public/css/bootstrap.min.css' rel='stylesheet'>";
        echo "</head>";
        echo "<body>";
        echo "<div class='container mt-5'>";
        echo "<div class='row justify-content-center'>";
        echo "<div class='col-md-6'>";
        echo "<div class='alert alert-danger text-center'>";
        echo "<h1>🚨 Erro do Sistema</h1>";
        echo "<p>Ocorreu um erro interno no sistema. Por favor, tente novamente mais tarde.</p>";
        echo "<p>Se o problema persistir, contacte o administrador.</p>";
        echo "<a href='?c=home&a=index' class='btn btn-primary'>Voltar ao Início</a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</body>";
        echo "</html>";
    }
    
    // Enviar código de erro HTTP apropriado
    http_response_code(500);
    exit;
}

/**
 * Medição de Performance (apenas em desenvolvimento)
 * 
 * Calcula e exibe o tempo de execução da página
 */
if (defined('APP_DEBUG') && APP_DEBUG) {
    $endTime = microtime(true);
    $executionTime = ($endTime - $startTime) * 1000; // Converter para milissegundos
    
    echo "<!-- Tempo de execução: " . round($executionTime, 2) . " ms -->";
    echo "<!-- Memória utilizada: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB -->";
}