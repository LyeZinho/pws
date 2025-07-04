<?php
/**
 * ProfileController - Controller para gerenciar perfil do usuário
 * 
 * Este controller gerencia:
 * - Visualização do perfil do usuário
 * - Edição de dados do perfil
 * - Histórico de posts do usuário
 */
class ProfileController extends Controller {
    
    /**
     * Método index() - Mostra o perfil do usuário atual
     * Exibe informações do usuário e seus posts
     */
    public function index() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obtém usuário atual
        $user = User::find($_SESSION['user_id']);
        
        // Busca posts do usuário
        $posts = Post::find('all', array(
            'conditions' => array('user_id = ?', $_SESSION['user_id']),
            'order' => 'created_at DESC'
        ));
        
        // Prepara dados para a view
        $data = [
            'title' => 'Meu Perfil',
            'user' => $user,
            'posts' => $posts
        ];
        
        $this->renderView('profile', 'index', $data);
    }
    
    /**
     * Método edit() - Editar dados do perfil
     * GET: Mostra formulário de edição
     * POST: Processa e salva alterações
     */
    public function edit() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obtém usuário atual
        $user = User::find($_SESSION['user_id']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Processar edição do perfil
            $user->username = $this->getHTTPPostParam('username');
            $user->email = $this->getHTTPPostParam('email');
            
            // Só atualiza senha se foi fornecida
            $newPassword = $this->getHTTPPostParam('password');
            if (!empty($newPassword)) {
                $user->password = md5($newPassword);
            }
            
            // Tenta salvar alterações
            if ($user->save()) {
                // Sucesso - redireciona para perfil
                $this->redirectToRoute('profile', 'index');
            } else {
                // Erro - mostra formulário com erros
                $data = [
                    'title' => 'Editar Perfil',
                    'user' => $user,
                    'errors' => $user->errors
                ];
                $this->renderView('profile', 'edit', $data);
            }
        } else {
            // GET request - mostra formulário
            $data = [
                'title' => 'Editar Perfil',
                'user' => $user
            ];
            $this->renderView('profile', 'edit', $data);
        }
    }
}