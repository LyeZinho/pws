<?php
/**
 * PostController - Controller para operações CRUD de posts
 * 
 * Este controller implementa todas as operações CRUD (Create, Read, Update, Delete) para posts:
 * - index(): Lista todos os posts do sistema
 * - show(): Mostra um post específico com comentários
 * - create(): Formulário para criar novo post
 * - store(): Salva novo post na base de dados
 * - edit(): Formulário para editar post existente
 * - update(): Actualiza post na base de dados
 * - delete(): Remove post da base de dados
 * - comment(): Adiciona comentário a um post
 * 
 * Funcionalidades especiais:
 * - Validação de propriedade do post (só o autor pode editar/deletar)
 * - Listagem de comentários por post
 * - Sistema de paginação para posts
 * - Filtragem por autor
 * 
 * @package GEstufas\Controllers
 * @version 1.0.0
 * @author Sistema GEstufas
 * @since 2025
 */
class PostController extends Controller {
    
    /**
     * index() - Lista todos os posts do sistema
     * 
     * Exibe uma listagem paginada de todos os posts, incluindo:
     * - Informações do autor
     * - Número de comentários
     * - Data de criação
     * - Excerpt do conteúdo
     * 
     * @return void
     */
    public function index() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Obter parâmetros de paginação
            $page = intval($this->getHTTPGetParam('page')) ?: 1;
            $perPage = 10; // Posts por página
            $offset = ($page - 1) * $perPage;
            
            // Buscar posts com informações do autor
            $posts = Post::find('all', array(
                'include' => array('user'), // Incluir dados do usuário
                'order' => 'created_at DESC', // Mais recentes primeiro
                'limit' => $perPage,
                'offset' => $offset
            ));
            
            // Contar total de posts para paginação
            $totalPosts = Post::count();
            $totalPages = ceil($totalPosts / $perPage);
            
            // Para cada post, contar comentários
            foreach ($posts as $post) {
                $post->comment_count = Comment::count(array(
                    'conditions' => array('post_id = ?', $post->id)
                ));
            }
            
            // Preparar dados para a view
            $data = [
                'title' => 'Gestão de Posts',
                'posts' => $posts,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalPosts' => $totalPosts,
                'perPage' => $perPage
            ];
            
            // Renderizar a view
            $this->renderView('posts', 'index', $data);
            
        } catch (Exception $e) {
            // Log do erro
            error_log("Erro no PostController::index() - " . $e->getMessage());
            
            // Preparar dados de erro para a view
            $data = [
                'title' => 'Erro - Gestão de Posts',
                'error' => 'Erro ao carregar posts: ' . $e->getMessage(),
                'posts' => []
            ];
            
            $this->renderView('posts', 'index', $data);
        }
    }
    
    /**
     * show() - Mostra detalhes de um post específico
     * 
     * Exibe um post completo com:
     * - Conteúdo integral
     * - Informações do autor
     * - Todos os comentários
     * - Formulário para adicionar comentário
     * 
     * @return void
     */
    public function show() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do post da URL
        $postId = $this->getHTTPGetParam('id');
        
        if (!$postId) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        try {
            // Buscar o post com informações do usuário
            $post = Post::find($postId, array(
                'include' => array('user')
            ));
            
            if (!$post) {
                $data = [
                    'title' => 'Post Não Encontrado',
                    'error' => 'O post solicitado não foi encontrado.'
                ];
                $this->renderView('posts', 'show', $data);
                return;
            }
            
            // Buscar comentários do post com informações dos usuários
            $comments = Comment::find('all', array(
                'conditions' => array('post_id = ?', $postId),
                'include' => array('user'),
                'order' => 'created_at ASC' // Comentários mais antigos primeiro
            ));
            
            // Verificar se o usuário atual é o autor do post
            $canEdit = ($_SESSION['user_id'] == $post->user_id);
            
            // Preparar dados para a view
            $data = [
                'title' => 'Post: ' . htmlspecialchars($post->title),
                'post' => $post,
                'comments' => $comments,
                'canEdit' => $canEdit,
                'currentUserId' => $_SESSION['user_id']
            ];
            
            $this->renderView('posts', 'show', $data);
            
        } catch (Exception $e) {
            error_log("Erro no PostController::show() - " . $e->getMessage());
            
            $data = [
                'title' => 'Erro ao Carregar Post',
                'error' => 'Erro ao carregar o post: ' . $e->getMessage()
            ];
            
            $this->renderView('posts', 'show', $data);
        }
    }
    
    /**
     * create() - Criar um novo post
     * 
     * GET: Exibe formulário de criação
     * POST: Processa dados e salva o novo post
     * 
     * @return void
     */
    public function create() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Processar criação do post
            $this->store();
        } else {
            // Exibir formulário de criação
            $data = [
                'title' => 'Criar Novo Post',
            ];
            
            $this->renderView('posts', 'create', $data);
        }
    }
    
    /**
     * store() - Salva um novo post na base de dados
     * 
     * Valida os dados recebidos via POST e cria o post
     * 
     * @return void
     */
    public function store() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Obter dados do formulário
            $title = trim($this->getHTTPPostParam('title'));
            $content = trim($this->getHTTPPostParam('content'));
            $tags = trim($this->getHTTPPostParam('tags'));
            
            // Validações básicas
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'O título é obrigatório.';
            } elseif (strlen($title) < 3) {
                $errors[] = 'O título deve ter pelo menos 3 caracteres.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'O título não pode ter mais de 255 caracteres.';
            }
            
            if (empty($content)) {
                $errors[] = 'O conteúdo é obrigatório.';
            } elseif (strlen($content) < 10) {
                $errors[] = 'O conteúdo deve ter pelo menos 10 caracteres.';
            }
            
            // Se há erros, voltar ao formulário
            if (!empty($errors)) {
                $data = [
                    'title' => 'Criar Novo Post',
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags
                    ]
                ];
                
                $this->renderView('posts', 'create', $data);
                return;
            }
            
            // Criar novo post
            $post = new Post();
            $post->title = $title;
            $post->content = $content;
            $post->tags = $tags;
            $post->user_id = $_SESSION['user_id'];
            $post->created_at = date('Y-m-d H:i:s');
            $post->updated_at = date('Y-m-d H:i:s');
            
            if ($post->save()) {
                // Post criado com sucesso
                $_SESSION['success_message'] = 'Post criado com sucesso!';
                $this->redirectToRoute('posts', 'show', ['id' => $post->id]);
            } else {
                // Erro ao salvar
                $errors = ['Erro ao salvar o post. Tente novamente.'];
                
                // Se há erros de validação do ActiveRecord
                if ($post->errors) {
                    $errors = [];
                    foreach ($post->errors->full_messages() as $error) {
                        $errors[] = $error;
                    }
                }
                
                $data = [
                    'title' => 'Criar Novo Post',
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags
                    ]
                ];
                
                $this->renderView('posts', 'create', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no PostController::store() - " . $e->getMessage());
            
            $data = [
                'title' => 'Criar Novo Post',
                'errors' => ['Erro interno do sistema. Tente novamente.'],
                'formData' => [
                    'title' => $this->getHTTPPostParam('title'),
                    'content' => $this->getHTTPPostParam('content'),
                    'tags' => $this->getHTTPPostParam('tags')
                ]
            ];
            
            $this->renderView('posts', 'create', $data);
        }
    }
    
    /**
     * edit() - Editar um post existente
     * 
     * GET: Exibe formulário de edição
     * POST: Redireciona para update()
     * 
     * @return void
     */
    public function edit() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do post
        $postId = $this->getHTTPGetParam('id');
        
        if (!$postId) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        try {
            // Buscar o post
            $post = Post::find($postId);
            
            if (!$post) {
                $_SESSION['error_message'] = 'Post não encontrado.';
                $this->redirectToRoute('posts', 'index');
                return;
            }
            
            // Verificar se o usuário é o autor do post
            if ($post->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para editar este post.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                return;
            }
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Processar actualização
                $this->update();
            } else {
                // Exibir formulário de edição
                $data = [
                    'title' => 'Editar Post: ' . htmlspecialchars($post->title),
                    'post' => $post
                ];
                
                $this->renderView('posts', 'edit', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no PostController::edit() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro ao carregar post para edição.';
            $this->redirectToRoute('posts', 'index');
        }
    }
    
    /**
     * update() - Actualiza um post na base de dados
     * 
     * Valida os dados e actualiza o post existente
     * 
     * @return void
     */
    public function update() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do post
        $postId = $this->getHTTPGetParam('id');
        
        if (!$postId) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        try {
            // Buscar o post
            $post = Post::find($postId);
            
            if (!$post) {
                $_SESSION['error_message'] = 'Post não encontrado.';
                $this->redirectToRoute('posts', 'index');
                return;
            }
            
            // Verificar se o usuário é o autor do post
            if ($post->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para editar este post.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                return;
            }
            
            // Obter dados do formulário
            $title = trim($this->getHTTPPostParam('title'));
            $content = trim($this->getHTTPPostParam('content'));
            $tags = trim($this->getHTTPPostParam('tags'));
            
            // Validações básicas
            $errors = [];
            
            if (empty($title)) {
                $errors[] = 'O título é obrigatório.';
            } elseif (strlen($title) < 3) {
                $errors[] = 'O título deve ter pelo menos 3 caracteres.';
            } elseif (strlen($title) > 255) {
                $errors[] = 'O título não pode ter mais de 255 caracteres.';
            }
            
            if (empty($content)) {
                $errors[] = 'O conteúdo é obrigatório.';
            } elseif (strlen($content) < 10) {
                $errors[] = 'O conteúdo deve ter pelo menos 10 caracteres.';
            }
            
            // Se há erros, voltar ao formulário
            if (!empty($errors)) {
                $data = [
                    'title' => 'Editar Post: ' . htmlspecialchars($post->title),
                    'post' => $post,
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags
                    ]
                ];
                
                $this->renderView('posts', 'edit', $data);
                return;
            }
            
            // Actualizar dados do post
            $post->title = $title;
            $post->content = $content;
            $post->tags = $tags;
            $post->updated_at = date('Y-m-d H:i:s');
            
            if ($post->save()) {
                // Post actualizado com sucesso
                $_SESSION['success_message'] = 'Post actualizado com sucesso!';
                $this->redirectToRoute('posts', 'show', ['id' => $post->id]);
            } else {
                // Erro ao salvar
                $errors = ['Erro ao actualizar o post. Tente novamente.'];
                
                // Se há erros de validação do ActiveRecord
                if ($post->errors) {
                    $errors = [];
                    foreach ($post->errors->full_messages() as $error) {
                        $errors[] = $error;
                    }
                }
                
                $data = [
                    'title' => 'Editar Post: ' . htmlspecialchars($post->title),
                    'post' => $post,
                    'errors' => $errors,
                    'formData' => [
                        'title' => $title,
                        'content' => $content,
                        'tags' => $tags
                    ]
                ];
                
                $this->renderView('posts', 'edit', $data);
            }
            
        } catch (Exception $e) {
            error_log("Erro no PostController::update() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno do sistema.';
            $this->redirectToRoute('posts', 'show', ['id' => $postId]);
        }
    }
    
    /**
     * delete() - Remove um post da base de dados
     * 
     * Remove o post e todos os comentários associados
     * 
     * @return void
     */
    public function delete() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obter ID do post
        $postId = $this->getHTTPGetParam('id');
        
        if (!$postId) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        try {
            // Buscar o post
            $post = Post::find($postId);
            
            if (!$post) {
                $_SESSION['error_message'] = 'Post não encontrado.';
                $this->redirectToRoute('posts', 'index');
                return;
            }
            
            // Verificar se o usuário é o autor do post
            if ($post->user_id != $_SESSION['user_id']) {
                $_SESSION['error_message'] = 'Você não tem permissão para eliminar este post.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                return;
            }
            
            // Eliminar comentários primeiro (devido à foreign key)
            $comments = Comment::find('all', array(
                'conditions' => array('post_id = ?', $postId)
            ));
            
            foreach ($comments as $comment) {
                $comment->delete();
            }
            
            // Eliminar o post
            if ($post->delete()) {
                $_SESSION['success_message'] = 'Post eliminado com sucesso!';
                $this->redirectToRoute('posts', 'index');
            } else {
                $_SESSION['error_message'] = 'Erro ao eliminar o post.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
            }
            
        } catch (Exception $e) {
            error_log("Erro no PostController::delete() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno do sistema.';
            $this->redirectToRoute('posts', 'index');
        }
    }
    
    /**
     * comment() - Adiciona um comentário a um post
     * 
     * Processa comentários enviados via POST
     * 
     * @return void
     */
    public function comment() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        // Obter dados do formulário
        $postId = $this->getHTTPPostParam('post_id');
        $content = trim($this->getHTTPPostParam('content'));
        
        if (!$postId) {
            $this->redirectToRoute('posts', 'index');
            return;
        }
        
        try {
            // Verificar se o post existe
            $post = Post::find($postId);
            
            if (!$post) {
                $_SESSION['error_message'] = 'Post não encontrado.';
                $this->redirectToRoute('posts', 'index');
                return;
            }
            
            // Validar comentário
            if (empty($content)) {
                $_SESSION['error_message'] = 'O comentário não pode estar vazio.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                return;
            }
            
            if (strlen($content) < 3) {
                $_SESSION['error_message'] = 'O comentário deve ter pelo menos 3 caracteres.';
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                return;
            }
            
            // Criar comentário
            $comment = new Comment();
            $comment->content = $content;
            $comment->post_id = $postId;
            $comment->user_id = $_SESSION['user_id'];
            $comment->created_at = date('Y-m-d H:i:s');
            
            if ($comment->save()) {
                $_SESSION['success_message'] = 'Comentário adicionado com sucesso!';
            } else {
                $_SESSION['error_message'] = 'Erro ao adicionar comentário.';
            }
            
            $this->redirectToRoute('posts', 'show', ['id' => $postId]);
            
        } catch (Exception $e) {
            error_log("Erro no PostController::comment() - " . $e->getMessage());
            $_SESSION['error_message'] = 'Erro interno do sistema.';
            $this->redirectToRoute('posts', 'show', ['id' => $postId]);
        }
    }
}
