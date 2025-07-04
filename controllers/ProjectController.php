<?php
/**
 * ProjectController - Controller para operações CRUD de projetos
 * 
 * Este controller implementa todas as operações CRUD (Create, Read, Update, Delete) para projetos:
 * - index(): Lista todos os projetos do sistema
 * - show(): Mostra um projeto específico com detalhes
 * - create(): Formulário para criar novo projeto
 * - store(): Salva novo projeto na base de dados
 * - edit(): Formulário para editar projeto existente
 * - update(): Actualiza projeto na base de dados
 * - delete(): Remove projeto da base de dados
 * - join(): Permite que um utilizador se junte a um projeto
 * - leave(): Permite que um utilizador saia de um projeto
 * 
 * Funcionalidades especiais:
 * - Validação de propriedade do projeto (só o criador pode editar/deletar)
 * - Sistema de membros do projeto
 * - Filtragem por status e categoria
 * - Sistema de paginação para projetos
 * 
 * @package GEstufas\Controllers
 * @version 1.0.0
 * @author Sistema GEstufas
 * @since 2025
 */
class ProjectController extends Controller {
    
    /**
     * index() - Lista todos os projetos do sistema
     * 
     * Exibe uma listagem paginada de todos os projetos, incluindo:
     * - Informações do criador
     * - Status do projeto
     * - Número de membros
     * - Data de criação
     * - Descrição resumida
     * 
     * @return void
     */
    public function index() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Obter parâmetros de paginação e filtros
            $page = intval($this->getHTTPGetParam('page')) ?: 1;
            $perPage = 12; // Projetos por página
            $offset = ($page - 1) * $perPage;
            
            // Filtros opcionais
            $statusFilter = $this->getHTTPGetParam('status');
            $searchTerm = $this->getHTTPGetParam('search');
            
            // Construir condições de busca
            $conditions = [];
            $params = [];
            
            if ($statusFilter && in_array($statusFilter, ['active', 'completed', 'on_hold'])) {
                $conditions[] = 'status = ?';
                $params[] = $statusFilter;
            }
            
            if ($searchTerm) {
                $conditions[] = '(title LIKE ? OR description LIKE ?)';
                $params[] = '%' . $searchTerm . '%';
                $params[] = '%' . $searchTerm . '%';
            }
            
            $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '';
            
            // Buscar projetos com informações do criador
            $findOptions = array(
                'include' => array('user'), // Incluir dados do usuário criador
                'order' => 'created_at DESC', // Mais recentes primeiro
                'limit' => $perPage,
                'offset' => $offset
            );
            
            if ($whereClause) {
                $findOptions['conditions'] = array_merge([$whereClause], $params);
            }
            
            $projects = Project::find('all', $findOptions);
            
            // Contar total de projetos para paginação
            $countOptions = [];
            if ($whereClause) {
                $countOptions['conditions'] = array_merge([$whereClause], $params);
            }
            
            $totalProjects = Project::count($countOptions);
            $totalPages = ceil($totalProjects / $perPage);
            
            // Para cada projeto, contar membros (simulado - implementar tabela de membros futuramente)
            foreach ($projects as $project) {
                $project->member_count = 1; // Por enquanto só o criador
                // TODO: Implementar contagem real de membros quando a tabela for criada
            }
            
            // Preparar dados para a view
            $data = [
                'title' => 'Gestão de Projetos',
                'projects' => $projects,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalProjects' => $totalProjects,
                'perPage' => $perPage,
                'statusFilter' => $statusFilter,
                'searchTerm' => $searchTerm
            ];
            
            // Renderizar a view
            $this->renderView('projects', 'index', $data);
            
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro no ProjectController::index() - " . $e->getMessage());
            
            // Preparar dados de erro para a view
            $data = [
                'title' => 'Erro - Gestão de Projetos',
                'error' => 'Erro ao carregar projetos: ' . $e->getMessage(),
                'projects' => []
            ];
            
            $this->renderView('projects', 'index', $data);
        }
    }
    
    /**
     * show() - Mostra detalhes de um projeto específico
     * 
     * Exibe um projeto completo com:
     * - Descrição integral
     * - Informações do criador
     * - Lista de membros
     * - Status e progresso
     * - Tecnologias utilizadas
     * 
     * @return void
     */
    public function show() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do projeto da URL
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        try {
            // Buscar o projeto com informações do usuário
            $project = Project::find($projectId, array(
                'include' => array('user')
            ));
            
            if (!$project) {
                $data = [
                    'title' => 'Projeto Não Encontrado',
                    'error' => 'O projeto solicitado não foi encontrado.'
                ];
                $this->renderView('projects', 'show', $data);
                return;
            }
            
            // Verificar se o usuário atual é o criador do projeto
            $canEdit = ($_SESSION['user_id'] == $project->user_id);
            
            // TODO: Buscar membros do projeto quando a tabela for implementada
            $members = [$project->user]; // Por enquanto só o criador
            
            // Calcular estatísticas do projeto
            $stats = [
                'total_members' => count($members),
                'days_since_creation' => ceil((time() - strtotime($project->created_at)) / (60 * 60 * 24)),
                'last_update_days' => ceil((time() - strtotime($project->updated_at)) / (60 * 60 * 24))
            ];
            
            // Preparar dados para a view
            $data = [
                'title' => 'Projeto: ' . htmlspecialchars($project->title),
                'project' => $project,
                'members' => $members,
                'canEdit' => $canEdit,
                'currentUserId' => $_SESSION['user_id'],
                'stats' => $stats
            ];
            
            $this->renderView('projects', 'show', $data);
            
        } catch (Exception $e) {
            error_log("Erro no ProjectController::show() - " . $e->getMessage());
            
            $data = [
                'title' => 'Erro ao Carregar Projeto',
                'error' => 'Erro ao carregar o projeto: ' . $e->getMessage()
            ];
            
            $this->renderView('projects', 'show', $data);
        }
    }
    
    /**
     * create() - Criar um novo projeto
     * 
     * GET: Exibe formulário de criação
     * POST: Processa dados e salva o novo projeto
     * 
     * @return void
     */
    public function create() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Processar criação do projeto
            $this->store();
        } else {
            // Exibir formulário de criação
            $data = [
                'title' => 'Criar Novo Projeto',
            ];
            
            $this->renderView('projects', 'create', $data);
        }
    }
    
    /**
     * store() - Salva um novo projeto na base de dados
     * 
     * Valida os dados recebidos via POST e cria o projeto
     * 
     * @return void
     */
    public function store() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Obter dados do formulário
            $title = trim($this->getHTTPPostParam('title'));
            $description = trim($this->getHTTPPostParam('description'));
            $technologies = trim($this->getHTTPPostParam('technologies'));
            $repository_url = trim($this->getHTTPPostParam('repository_url'));
            $live_url = trim($this->getHTTPPostParam('live_url'));
            $status = trim($this->getHTTPPostParam('status'));
            
            // Validações básicas
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'O título é obrigatório.';
            } elseif (strlen($title) < 3) {
                $errors[] = 'O título deve ter pelo menos 3 caracteres.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'O título não pode ter mais de 255 caracteres.';
            }
            
            if (empty($description)) {
                $errors[] = 'A descrição é obrigatória.';
            } elseif (strlen($description) < 10) {
                $errors[] = 'A descrição deve ter pelo menos 10 caracteres.';
            }
            
            if (!in_array($status, ['active', 'completed', 'on_hold'])) {
                $errors[] = 'Status inválido.';
            }
            
            // Validar URLs se fornecidas
            if (!empty($repository_url) && !filter_var($repository_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'URL do repositório inválida.';
            }
            
            if (!empty($live_url) && !filter_var($live_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'URL da demonstração inválida.';
            }
            
            // Se há erros, voltar ao formulário
            if (!empty($errors)) {
                $data = [
                    'title' => 'Criar Novo Projeto',
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'description' => $description,
                        'technologies' => $technologies,
                        'repository_url' => $repository_url,
                        'live_url' => $live_url,
                        'status' => $status
                    ]
                ];
                
                $this->renderView('projects', 'create', $data);
                return;
            }
            
            // Criar novo projeto
            $project = new Project();
            $project->title = $title;
            $project->description = $description;
            $project->technologies = $technologies;
            $project->repository_url = $repository_url;
            $project->live_url = $live_url;
            $project->status = $status;
            $project->user_id = $_SESSION['user_id'];
            $project->created_at = date('Y-m-d H:i:s');
            $project->updated_at = date('Y-m-d H:i:s');
            
            if ($project->save()) {
                // Projeto criado com sucesso
                $_SESSION['success_message'] = 'Projeto criado com sucesso!';
                $this->redirectToRoute('projects', 'show', ['id' => $project->id]);
            } else {
                // Erro ao salvar
                $errors = ['Erro ao salvar o projeto. Tente novamente.'];
                
                // Se há erros de validação do ActiveRecord
                if ($project->errors) {
                    $errors = [];
                    foreach ($project->errors->full_messages() as $error) {
                        $errors[] = $error;
                    }
                }
                
                $data = [
                    'title' => 'Criar Novo Projeto',
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'description' => $description,
                        'technologies' => $technologies,
                        'repository_url' => $repository_url,
                        'live_url' => $live_url,
                        'status' => $status
                    ]
                ];
                
                $this->renderView('projects', 'create', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no ProjectController::store() - " . $e->getMessage());
            
            $data = [
                'title' => 'Criar Novo Projeto',
                'errors' => ['Erro interno do sistema. Tente novamente.'],
                'formData' => [
                    'title' => $this->getHTTPPostParam('title'),
                    'description' => $this->getHTTPPostParam('description'),
                    'technologies' => $this->getHTTPPostParam('technologies'),
                    'repository_url' => $this->getHTTPPostParam('repository_url'),
                    'live_url' => $this->getHTTPPostParam('live_url'),
                    'status' => $this->getHTTPPostParam('status')
                ]
            ];
            
            $this->renderView('projects', 'create', $data);
        }
    }
    
    /**
     * edit() - Editar um projeto existente
     * 
     * GET: Exibe formulário de edição
     * POST: Redireciona para update()
     * 
     * @return void
     */
    public function edit() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do projeto
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        try {
            // Buscar o projeto
            $project = Project::find($projectId);
            
            if (!$project) {
                $_SESSION['error_message'] = 'Projeto não encontrado.';
                $this->redirectToRoute('projects', 'index');
                return;
            }
            
            // Verificar se o usuário é o criador do projeto
            if ($project->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para editar este projeto.';
                $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Processar actualização
                $this->update();
            } else {
                // Exibir formulário de edição
                $data = [
                    'title' => 'Editar Projeto: ' . htmlspecialchars($project->title),
                    'project' => $project
                ];
                
                $this->renderView('projects', 'edit', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no ProjectController::edit() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro ao carregar projeto para edição.';
            $this->redirectToRoute('projects', 'index');
        }
    }
    
    /**
     * update() - Actualiza um projeto na base de dados
     * 
     * Valida os dados e actualiza o projeto existente
     * 
     * @return void
     */
    public function update() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do projeto
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        try {
            // Buscar o projeto
            $project = Project::find($projectId);
            
            if (!$project) {
                $_SESSION['error_message'] = 'Projeto não encontrado.';
                $this->redirectToRoute('projects', 'index');
                return;
            }
            
            // Verificar se o usuário é o criador do projeto
            if ($project->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para editar este projeto.';
                $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
                return;
            }
            
            // Obter dados do formulário
            $title = trim($this->getHTTPPostParam('title'));
            $description = trim($this->getHTTPPostParam('description'));
            $technologies = trim($this->getHTTPPostParam('technologies'));
            $repository_url = trim($this->getHTTPPostParam('repository_url'));
            $live_url = trim($this->getHTTPPostParam('live_url'));
            $status = trim($this->getHTTPPostParam('status'));
            
            // Validações básicas
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'O título é obrigatório.';
            } elseif (strlen($title) < 3) {
                $errors[] = 'O título deve ter pelo menos 3 caracteres.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'O título não pode ter mais de 255 caracteres.';
            }
            
            if (empty($description)) {
                $errors[] = 'A descrição é obrigatória.';
            } elseif (strlen($description) < 10) {
                $errors[] = 'A descrição deve ter pelo menos 10 caracteres.';
            }
            
            if (!in_array($status, ['active', 'completed', 'on_hold'])) {
                $errors[] = 'Status inválido.';
            }
            
            // Validar URLs se fornecidas
            if (!empty($repository_url) && !filter_var($repository_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'URL do repositório inválida.';
            }
            
            if (!empty($live_url) && !filter_var($live_url, FILTER_VALIDATE_URL)) {
                $errors[] = 'URL da demonstração inválida.';
            }
            
            // Se há erros, voltar ao formulário
            if (!empty($errors)) {
                $data = [
                    'title' => 'Editar Projeto: ' . htmlspecialchars($project->title),
                    'project' => $project,
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'description' => $description,
                        'technologies' => $technologies,
                        'repository_url' => $repository_url,
                        'live_url' => $live_url,
                        'status' => $status
                    ]
                ];
                
                $this->renderView('projects', 'edit', $data);
                return;
            }
            
            // Actualizar dados do projeto
            $project->title = $title;
            $project->description = $description;
            $project->technologies = $technologies;
            $project->repository_url = $repository_url;
            $project->live_url = $live_url;
            $project->status = $status;
            $project->updated_at = date('Y-m-d H:i:s');
            
            if ($project->save()) {
                // Projeto actualizado com sucesso
                $_SESSION['success_message'] = 'Projeto actualizado com sucesso!';
                $this->redirectToRoute('projects', 'show', ['id' => $project->id]);
            } else {
                // Erro ao salvar
                $errors = ['Erro ao actualizar o projeto. Tente novamente.'];
                
                // Se há erros de validação do ActiveRecord
                if ($project->errors) {
                    $errors = [];
                    foreach ($project->errors->full_messages() as $error) {
                        $errors[] = $error;
                    }
                }
                
                $data = [
                    'title' => 'Editar Projeto: ' . htmlspecialchars($project->title),
                    'project' => $project,
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'description' => $description,
                        'technologies' => $technologies,
                        'repository_url' => $repository_url,
                        'live_url' => $live_url,
                        'status' => $status
                    ]
                ];
                
                $this->renderView('projects', 'edit', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no ProjectController::update() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno do sistema.';
            $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
        }
    }
    
    /**
     * delete() - Remove um projeto da base de dados
     * 
     * Remove o projeto permanentemente
     * 
     * @return void
     */
    public function delete() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do projeto
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        try {
            // Buscar o projeto
            $project = Project::find($projectId);
            
            if (!$project) {
                $_SESSION['error_message'] = 'Projeto não encontrado.';
                $this->redirectToRoute('projects', 'index');
                return;
            }
            
            // Verificar se o usuário é o criador do projeto
            if ($project->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para eliminar este projeto.';
                $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
                return;
            }
            
            // TODO: Eliminar dados relacionados (membros, tarefas, etc.) quando implementados
            
            // Eliminar o projeto
            if ($project->delete()) {
                $_SESSION['success_message'] = 'Projeto eliminado com sucesso!';
                $this->redirectToRoute('projects', 'index');
            } else {
                $_SESSION['error_message'] = 'Erro ao eliminar o projeto.';
                $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
            }
            
        } catch (Exception $e) {
            error_log("Erro no ProjectController::delete() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno do sistema.';
            $this->redirectToRoute('projects', 'index');
        }
    }
    
    /**
     * join() - Permite que um utilizador se junte a um projeto
     * 
     * TODO: Implementar sistema de membros
     * 
     * @return void
     */
    public function join() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        // TODO: Implementar lógica de adesão ao projeto
        $_SESSION['info_message'] = 'Funcionalidade de aderir a projetos será implementada em breve.';
        $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
    }
    
    /**
     * leave() - Permite que um utilizador saia de um projeto
     * 
     * TODO: Implementar sistema de membros
     * 
     * @return void
     */
    public function leave() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        $projectId = $this->getHTTPGetParam('id');
        
        if (!$projectId) {
            $this->redirectToRoute('projects', 'index');
            return;
        }
        
        // TODO: Implementar lógica de saída do projeto
        $_SESSION['info_message'] = 'Funcionalidade de sair de projetos será implementada em breve.';
        $this->redirectToRoute('projects', 'show', ['id' => $projectId]);
    }
}
