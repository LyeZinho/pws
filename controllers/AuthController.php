<?php
/**
 * AuthController - Controller para gerenciar autenticação de usuários
 * 
 * Este controller é responsável por todas as operações relacionadas à autenticação:
 * - Login de usuários
 * - Logout de usuários  
 * - Registro de novos usuários
 * - Validação de credenciais
 */
class AuthController extends Controller {
    
    /**
     * Método index() - Página inicial de autenticação
     * Redireciona para a página de login como padrão
     */
    public function index() {
        $this->renderView('auth', 'login');
    }
    
    /**
     * Método login() - Processa o login do usuário
     * GET: Exibe o formulário de login
     * POST: Valida credenciais e inicia sessão
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Obtém dados do formulário POST
            $username = $this->getHTTPPostParam('username');
            $password = $this->getHTTPPostParam('password');
            
            // Busca usuário no banco com as credenciais fornecidas
            // NOTA: md5() é inseguro, usar password_hash() em produção
            $user = User::find('first', array(
                'conditions' => array('username = ? AND password = ?', $username, md5($password))
            ));
            
            // Se usuário encontrado, inicia sessão
            if ($user) {
                session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $this->redirectToRoute('home', 'index');
            } else {
                // Credenciais inválidas - mostra erro
                $data = ['error' => 'Credenciais inválidas'];
                $this->renderView('auth', 'login', $data);
            }
        } else {
            // GET request - apenas mostra formulário
            $this->renderView('auth', 'login');
        }
    }
    
    /**
     * Método logout() - Termina a sessão do usuário
     * Destroi a sessão e redireciona para login
     */
    public function logout() {
        session_start();
        session_destroy(); // Remove todos os dados da sessão
        $this->redirectToRoute('auth', 'index');
    }
    
    /**
     * Método register() - Registra um novo usuário
     * GET: Mostra formulário de registro
     * POST: Processa dados e cria novo usuário
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Cria nova instância do modelo User
            $user = new User();
            $user->username = $this->getHTTPPostParam('username');
            $user->email = $this->getHTTPPostParam('email');
            // NOTA: md5() é inseguro, usar password_hash() em produção
            $user->password = md5($this->getHTTPPostParam('password'));
            
            // Tenta salvar no banco de dados
            if ($user->save()) {
                // Sucesso - redireciona para login
                $this->redirectToRoute('auth', 'index');
            } else {
                // Erro - mostra formulário com erros
                $data = ['errors' => $user->errors];
                $this->renderView('auth', 'register', $data);
            }
        } else {
            // GET request - mostra formulário de registro
            $this->renderView('auth', 'register');
        }
    }
}