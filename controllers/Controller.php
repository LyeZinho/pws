<?php
/**
 * Controller - Classe base para todos os controllers
 * 
 * Fornece métodos comuns que todos os controllers podem usar:
 * - Redirecionamento entre páginas
 * - Renderização de views
 * - Obtenção de parâmetros HTTP
 * - Controle de autenticação
 */
class Controller
{
    /**
     * redirectToRoute() - Redireciona para outra rota
     * 
     * @param string $controllerPrefix - Nome do controller (ex: 'auth', 'home')
     * @param string $action - Nome da ação (ex: 'index', 'login')
     * @param array $params - Parâmetros adicionais para a URL
     */
    protected function redirectToRoute($controllerPrefix, $action, $params = []) {
        // Constrói a URL no formato: ?c=controller&a=action
        $url = "?c={$controllerPrefix}&a={$action}";
        
        // Adiciona parâmetros adicionais se existirem
        if (!empty($params)) {
            $url .= '&' . http_build_query($params);
        }
        
        // Executa o redirecionamento e para a execução
        header("Location: {$url}");
        exit;
    }

    /**
     * renderView() - Renderiza uma view com dados
     * 
     * @param string $controllerPrefix - Pasta da view (ex: 'auth', 'home')
     * @param string $viewName - Nome do arquivo da view (ex: 'login', 'index')
     * @param array $data - Dados para passar para a view
     * @param string $layout - Layout a ser usado (futuro)
     */
    protected function renderView($controllerPrefix, $viewName, $data = [], $layout = 'default') {
        // Constrói o caminho para o arquivo da view
        $viewPath = __DIR__ . "/../views/{$controllerPrefix}/{$viewName}.php";
        
        // Verifica se o arquivo existe
        if (file_exists($viewPath)) {
            // Extrai dados do array para variáveis (ex: $data['title'] vira $title)
            extract($data);
            
            // Inclui o arquivo da view
            include $viewPath;
        } else {
            // Arquivo não encontrado - lança exceção
            throw new Exception("View não encontrada: {$viewPath}");
        }
    }

    /**
     * getHTTPPostParam() - Obtém parâmetro POST
     * 
     * @param string $key - Nome do parâmetro
     * @return mixed - Valor do parâmetro ou null se não existir
     */
    protected function getHTTPPostParam($key) {
        return isset($_POST[$key]) ? $_POST[$key] : null;
    }

    /**
     * getHTTPGetParam() - Obtém parâmetro GET
     * 
     * @param string $key - Nome do parâmetro
     * @return mixed - Valor do parâmetro ou null se não existir
     */
    protected function getHTTPGetParam($key) {
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }

    /**
     * authenticationFilter() - Verifica se o usuário está autenticado
     * 
     * Se não estiver autenticado, redireciona para login
     * Deve ser chamado em métodos que requerem autenticação
     */
    protected function authenticationFilter() {
        // Verifica se existe sessão de usuário
        if (!isset($_SESSION['user_id'])) {
            // Não autenticado - redireciona para login
            $this->redirectToRoute('auth', 'login');
        }
    }
}