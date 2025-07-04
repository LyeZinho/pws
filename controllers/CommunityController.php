<?php
/**
 * CommunityController - Controller para gerenciar a comunidade
 * 
 * Este cont        if ($post) {
            // Post encontrado - mostra detalhes
            $data = [
                'title' => $post->title,
                'post' => $post
            ];
            $this->renderView('community', 'show-post', $data);
        } else {
            // Post não encontrado - redireciona
            $this->redirectToRoute('community', 'index');
        }
    }
    
    /**
     * Método comment() - Adiciona comentário a um post
     * POST: Processa novo comentário
     */
    public function comment() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Obtém dados do formulário
                $postId = $this->getHTTPPostParam('post_id');
                $content = trim($this->getHTTPPostParam('content'));
                
                // Validações básicas
                if (empty($content)) {
                    $_SESSION['error_message'] = 'O comentário não pode estar vazio.';
                    $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                    return;
                }
                
                if (strlen($content) < 5) {
                    $_SESSION['error_message'] = 'O comentário deve ter pelo menos 5 caracteres.';
                    $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                    return;
                }
                
                // Verifica se o post existe
                $post = Post::find($postId);
                if (!$post) {
                    $_SESSION['error_message'] = 'Post não encontrado.';
                    $this->redirectToRoute('community', 'index');
                    return;
                }
                
                // Cria novo comentário
                $comment = new Comment();
                $comment->content = $content;
                $comment->post_id = $postId;
                $comment->user_id = $_SESSION['user_id'];
                $comment->created_at = date('Y-m-d H:i:s');
                
                if ($comment->save()) {
                    $_SESSION['success_message'] = 'Comentário adicionado com sucesso!';
                } else {
                    $_SESSION['error_message'] = 'Erro ao adicionar comentário. Tente novamente.';
                }
                
                // Redireciona de volta para o post
                $this->redirectToRoute('posts', 'show', ['id' => $postId]);
                
            } catch (Exception $e) {
                error_log("Erro no CommunityController::comment() - " . $e->getMessage());
                $_SESSION['error_message'] = 'Erro interno do sistema. Tente novamente.';
                $this->redirectToRoute('community', 'index');
            }
        } else {
            // Método não permitido
            $this->redirectToRoute('community', 'index');
        }
    }
}ia todas as funcionalidades da comunidade:
 * - Listagem de posts
 * - Criação de novos posts
 * - Visualização de posts específicos
 * - Comentários em posts
 */
class CommunityController extends Controller {
    
    /**
     * Método index() - Lista todos os posts da comunidade
     * Requer autenticação do usuário
     */
    public function index() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        // Busca todos os posts ordenados por data de criação (mais recente primeiro)
        // Inclui informações do usuário que criou cada post
        $posts = Post::find('all', array(
            'order' => 'created_at DESC',
            'include' => array('user') // Carrega dados do usuário junto
        ));
        
        // Prepara dados para a view
        $data = [
            'title' => 'Comunidade - Posts',
            'posts' => $posts
        ];
        
        // Renderiza a view da comunidade
        $this->renderView('community', 'index', $data);
    }
    
    /**
     * Método create() - Criar um novo post
     * GET: Mostra formulário de criação
     * POST: Processa e salva o novo post
     */
    public function create() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Processar criação do post
            $post = new Post();
            $post->title = $this->getHTTPPostParam('title');
            $post->content = $this->getHTTPPostParam('content');
            $post->user_id = $_SESSION['user_id']; // Usuário atual
            
            // Tenta salvar o post
            if ($post->save()) {
                // Sucesso - redireciona para lista de posts
                $this->redirectToRoute('community', 'index');
            } else {
                // Erro - mostra formulário com erros
                $data = [
                    'title' => 'Criar Post',
                    'errors' => $post->errors
                ];
                $this->renderView('community', 'create-post', $data);
            }
        } else {
            // GET request - mostra formulário
            $data = ['title' => 'Criar Novo Post'];
            $this->renderView('community', 'create-post', $data);
        }
    }
    
    /**
     * Método show() - Exibe um post específico
     * Mostra o post com seus comentários
     */
    public function show() {
        // Verifica se o usuário está autenticado
        $this->authenticationFilter();
        
        // Obtém ID do post da URL
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            // ID não fornecido - redireciona para lista
            $this->redirectToRoute('community', 'index');
            return;
        }
        
        // Busca o post com usuário e comentários
        $post = Post::find($id, array(
            'include' => array('user', 'comments')
        ));
        
        if ($post) {
            // Post encontrado - mostra detalhes
            $data = [
                'title' => $post->title,
                'post' => $post
            ];
            $this->renderView('community', 'post', $data);
        } else {
            // Post não encontrado - redireciona para lista
            $this->redirectToRoute('community', 'index');
        }
    }
}