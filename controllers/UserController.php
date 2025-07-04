<?php
/**
 * UserController - Controller para operações CRUD de usuários
 * 
 * Este controller implementa todas as operações CRUD (Create, Read, Update, Delete):
 * - index(): Lista todos os usuários
 * - show(): Mostra um usuário específico
 * - create(): Formulário para criar novo usuário
 * - store(): Salva novo usuário na base de dados
 * - edit(): Formulário para editar usuário existente
 * - update(): Actualiza usuário na base de dados
 * - delete(): Remove usuário da base de dados
 */
class UserController extends Controller {
    
    /**
     * index() - Lista todos os usuários do sistema
     * 
     * @return void
     */
    public function index() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Buscar todos os usuários ordenados por nome
            $users = User::find('all', array(
                'order' => 'username ASC'
            ));
            
            // Preparar dados para a view
            $data = [
                'title' => 'Gestão de Usuários',
                'users' => $users,
                'success' => $_SESSION['success'] ?? null,
                'error' => $_SESSION['error'] ?? null
            ];
            
            // Limpar mensagens da sessão
            unset($_SESSION['success'], $_SESSION['error']);
            
            // Renderizar a view
            $this->renderView('users', 'index', $data);
            
        } catch (Exception $e) {
            // Tratar erro e mostrar mensagem
            $_SESSION['error'] = 'Erro ao carregar usuários: ' . $e->getMessage();
            $this->renderView('users', 'index', ['users' => [], 'error' => $_SESSION['error']]);
        }
    }
    
    /**
     * show() - Mostra detalhes de um usuário específico
     * 
     * @return void
     */
    public function show() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do usuário da URL
        $id = $this->getHTTPGetParam('id');
        
        if (!$id) {
            $_SESSION['error'] = 'ID do usuário não fornecido';
            $this->redirectToRoute('users', 'index');
            return;
        }
        
        try {
            // Buscar usuário com seus posts e projetos
            $user = User::find($id, array(
                'include' => array('posts', 'projects')
            ));
            
            if (!$user) {
                $_SESSION['error'] = 'Usuário não encontrado';
                $this->redirectToRoute('users', 'index');
                return;
            }
            
            // Preparar dados para a view
            $data = [
                'title' => 'Detalhes do Usuário',
                'user' => $user,
                'posts' => $user->posts,
                'projects' => $user->projects
            ];
            
            // Renderizar a view
            $this->renderView('users', 'show', $data);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar usuário: ' . $e->getMessage();
            $this->redirectToRoute('users', 'index');
        }
    }
    
    /**
     * create() - Mostra formulário para criar novo usuário
     * 
     * @return void
     */
    public function create() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Preparar dados para a view
        $data = [
            'title' => 'Criar Novo Usuário',
            'errors' => $_SESSION['errors'] ?? null
        ];
        
        // Limpar erros da sessão
        unset($_SESSION['errors']);
        
        // Renderizar a view
        $this->renderView('users', 'create', $data);
    }
    
    /**
     * store() - Salva novo usuário na base de dados
     * 
     * @return void
     */
    public function store() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('users', 'create');
            return;
        }
        
        try {
            // Criar nova instância do usuário
            $user = new User();
            
            // Obter dados do formulário
            $user->username = trim($this->getHTTPPostParam('username'));
            $user->email = trim($this->getHTTPPostParam('email'));
            $password = $this->getHTTPPostParam('password');
            $confirmPassword = $this->getHTTPPostParam('confirm_password');
            
            // Validar senhas
            if ($password !== $confirmPassword) {
                $_SESSION['errors'] = ['password' => ['As senhas não coincidem']];
                $this->redirectToRoute('users', 'create');
                return;
            }
            
            // Criptografar senha (usar password_hash em produção)
            $user->password = md5($password);
            
            // Tentar salvar o usuário
            if ($user->save()) {
                $_SESSION['success'] = 'Usuário criado com sucesso!';
                $this->redirectToRoute('users', 'index');
            } else {
                $_SESSION['errors'] = $user->errors->get_raw_errors();
                $this->redirectToRoute('users', 'create');
            }
            
        } catch (Exception $e) {
            $_SESSION['errors'] = ['system' => ['Erro ao criar usuário: ' . $e->getMessage()]];
            $this->redirectToRoute('users', 'create');
        }
    }
    
    /**
     * edit() - Mostra formulário para editar usuário existente
     * 
     * @return void
     */
    public function edit() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do usuário da URL
        $id = $this->getHTTPGetParam('id');
        
        if (!$id) {
            $_SESSION['error'] = 'ID do usuário não fornecido';
            $this->redirectToRoute('users', 'index');
            return;
        }
        
        try {
            // Buscar usuário
            $user = User::find($id);
            
            if (!$user) {
                $_SESSION['error'] = 'Usuário não encontrado';
                $this->redirectToRoute('users', 'index');
                return;
            }
            
            // Preparar dados para a view
            $data = [
                'title' => 'Editar Usuário',
                'user' => $user,
                'errors' => $_SESSION['errors'] ?? null
            ];
            
            // Limpar erros da sessão
            unset($_SESSION['errors']);
            
            // Renderizar a view
            $this->renderView('users', 'edit', $data);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar usuário: ' . $e->getMessage();
            $this->redirectToRoute('users', 'index');
        }
    }
    
    /**
     * update() - Actualiza usuário na base de dados
     * 
     * @return void
     */
    public function update() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Verificar se é uma requisição POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('users', 'index');
            return;
        }
        
        // Obter ID do usuário
        $id = $this->getHTTPPostParam('id');
        
        if (!$id) {
            $_SESSION['error'] = 'ID do usuário não fornecido';
            $this->redirectToRoute('users', 'index');
            return;
        }
        
        try {
            // Buscar usuário
            $user = User::find($id);
            
            if (!$user) {
                $_SESSION['error'] = 'Usuário não encontrado';
                $this->redirectToRoute('users', 'index');
                return;
            }
            
            // Actualizar dados do usuário
            $user->username = trim($this->getHTTPPostParam('username'));
            $user->email = trim($this->getHTTPPostParam('email'));
            
            // Verificar se uma nova senha foi fornecida
            $password = $this->getHTTPPostParam('password');
            if (!empty($password)) {
                $confirmPassword = $this->getHTTPPostParam('confirm_password');
                
                if ($password !== $confirmPassword) {
                    $_SESSION['errors'] = ['password' => ['As senhas não coincidem']];
                    $this->redirectToRoute('users', 'edit', ['id' => $id]);
                    return;
                }
                
                $user->password = md5($password);
            }
            
            // Tentar salvar as alterações
            if ($user->save()) {
                $_SESSION['success'] = 'Usuário actualizado com sucesso!';
                $this->redirectToRoute('users', 'index');
            } else {
                $_SESSION['errors'] = $user->errors->get_raw_errors();
                $this->redirectToRoute('users', 'edit', ['id' => $id]);
            }
            
        } catch (Exception $e) {
            $_SESSION['errors'] = ['system' => ['Erro ao actualizar usuário: ' . $e->getMessage()]];
            $this->redirectToRoute('users', 'edit', ['id' => $id]);
        }
    }
    
    /**
     * delete() - Remove usuário da base de dados
     * 
     * @return void
     */
    public function delete() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do usuário
        $id = $this->getHTTPGetParam('id');
        
        if (!$id) {
            $_SESSION['error'] = 'ID do usuário não fornecido';
            $this->redirectToRoute('users', 'index');
            return;
        }
        
        try {
            // Buscar usuário
            $user = User::find($id);
            
            if (!$user) {
                $_SESSION['error'] = 'Usuário não encontrado';
                $this->redirectToRoute('users', 'index');
                return;
            }
            
            // Verificar se não é o próprio usuário logado
            if ($user->id == $_SESSION['user_id']) {
                $_SESSION['error'] = 'Não é possível eliminar o próprio usuário';
                $this->redirectToRoute('users', 'index');
                return;
            }
            
            // Eliminar o usuário
            if ($user->delete()) {
                $_SESSION['success'] = 'Usuário eliminado com sucesso!';
            } else {
                $_SESSION['error'] = 'Erro ao eliminar usuário';
            }
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao eliminar usuário: ' . $e->getMessage();
        }
        
        // Redirecionar para a listagem
        $this->redirectToRoute('users', 'index');
    }
}
