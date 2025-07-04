<?php
/**
 * HomeController - Controller da Página Inicial
 * 
 * Este controller é responsável por gerenciar a página inicial da aplicação.
 * É o primeiro ponto de contacto dos usuários com o sistema.
 * 
 * Funcionalidades:
 * - Exibir dashboard com estatísticas
 * - Mostrar posts recentes
 * - Exibir informações do sistema
 * - Fornecer links de navegação
 * 
 * @package GEstufas\Controllers
 * @version 1.0.0
 * @author Sistema GEstufas
 * @since 2025
 */
class HomeController extends Controller {
    
    /**
     * Método index() - Página inicial do sistema
     * 
     * Este método renderiza a página principal do sistema, incluindo:
     * - Dashboard com estatísticas
     * - Posts recentes da comunidade
     * - Informações do usuário logado
     * - Links para principais funcionalidades
     * 
     * @return void
     */
    public function index() {
        try {
            // Dados básicos para a página
            $data = [
                'title' => 'GEstufas - Sistema de Gestão de Estufas',
                'message' => 'Bem-vindo ao sistema de gestão de estufas!',
                'currentUser' => null,
                'isLoggedIn' => false,
                'stats' => [
                    'totalUsers' => 0,
                    'totalPosts' => 0,
                    'totalProjects' => 0,
                    'totalComments' => 0
                ],
                'recentPosts' => [],
                'recentComments' => []
            ];
            
            // Verificar se o usuário está logado
            if (isset($_SESSION['user_id'])) {
                $data['isLoggedIn'] = true;
                
                // Obter dados do usuário atual
                $currentUser = User::find($_SESSION['user_id']);
                if ($currentUser) {
                    $data['currentUser'] = $currentUser;
                }
            }
            
            // Obter estatísticas do sistema
            try {
                $data['stats']['totalUsers'] = User::count();
                $data['stats']['totalPosts'] = Post::count();
                $data['stats']['totalProjects'] = Project::count();
                $data['stats']['totalComments'] = Comment::count();
            } catch (Exception $e) {
                // Se houver erro nas estatísticas, continuar sem elas
                logError('Erro ao obter estatísticas: ' . $e->getMessage(), 'WARNING');
            }
            
            // Obter posts recentes (apenas se houver posts)
            try {
                $data['recentPosts'] = Post::find('all', array(
                    'limit' => 5,
                    'order' => 'created_at DESC',
                    'include' => array('user')
                ));
            } catch (Exception $e) {
                // Se houver erro, continuar sem posts
                logError('Erro ao obter posts recentes: ' . $e->getMessage(), 'WARNING');
                $data['recentPosts'] = [];
            }
            
            // Obter comentários recentes (apenas se houver comentários)
            try {
                $data['recentComments'] = Comment::find('all', array(
                    'limit' => 5,
                    'order' => 'created_at DESC',
                    'include' => array('user', 'post')
                ));
            } catch (Exception $e) {
                // Se houver erro, continuar sem comentários
                logError('Erro ao obter comentários recentes: ' . $e->getMessage(), 'WARNING');
                $data['recentComments'] = [];
            }
            
            // Renderizar a view da página inicial
            $this->renderView('home', 'index', $data);
            
        } catch (Exception $e) {
            // Em caso de erro crítico, mostrar página de erro
            logError('Erro crítico no HomeController: ' . $e->getMessage(), 'ERROR');
            
            // Dados mínimos para página de erro
            $errorData = [
                'title' => 'Erro - GEstufas',
                'error' => 'Ocorreu um erro ao carregar a página inicial.',
                'message' => 'Por favor, tente novamente mais tarde.'
            ];
            
            $this->renderView('error', 'index', $errorData);
        }
    }
    
    /**
     * Método dashboard() - Dashboard administrativo
     * 
     * Exibe dashboard com informações detalhadas para administradores
     * 
     * @return void
     */
    public function dashboard() {
        // Verificar se o usuário está autenticado
        $this->authenticationFilter();
        
        try {
            // Dados para o dashboard
            $data = [
                'title' => 'Dashboard - GEstufas',
                'stats' => [
                    'totalUsers' => User::count(),
                    'totalPosts' => Post::count(),
                    'totalProjects' => Project::count(),
                    'totalComments' => Comment::count(),
                    'usersThisMonth' => $this->getUsersThisMonth(),
                    'postsThisMonth' => $this->getPostsThisMonth()
                ],
                'recentActivity' => $this->getRecentActivity(),
                'topUsers' => $this->getTopUsers()
            ];
            
            $this->renderView('home', 'dashboard', $data);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Erro ao carregar dashboard: ' . $e->getMessage();
            $this->redirectToRoute('home', 'index');
        }
    }
    
    /**
     * Método privado para obter usuários criados este mês
     * 
     * @return int
     */
    private function getUsersThisMonth() {
        try {
            $startOfMonth = date('Y-m-01 00:00:00');
            return User::count(array(
                'conditions' => array('created_at >= ?', $startOfMonth)
            ));
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Método privado para obter posts criados este mês
     * 
     * @return int
     */
    private function getPostsThisMonth() {
        try {
            $startOfMonth = date('Y-m-01 00:00:00');
            return Post::count(array(
                'conditions' => array('created_at >= ?', $startOfMonth)
            ));
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Método privado para obter atividade recente
     * 
     * @return array
     */
    private function getRecentActivity() {
        try {
            // Combinar diferentes tipos de atividade
            $activities = [];
            
            // Posts recentes
            $recentPosts = Post::find('all', array(
                'limit' => 3,
                'order' => 'created_at DESC',
                'include' => array('user')
            ));
            
            foreach ($recentPosts as $post) {
                $activities[] = [
                    'type' => 'post',
                    'title' => $post->title,
                    'user' => $post->user->username,
                    'created_at' => $post->created_at
                ];
            }
            
            // Comentários recentes
            $recentComments = Comment::find('all', array(
                'limit' => 3,
                'order' => 'created_at DESC',
                'include' => array('user', 'post')
            ));
            
            foreach ($recentComments as $comment) {
                $activities[] = [
                    'type' => 'comment',
                    'title' => 'Comentário em: ' . $comment->post->title,
                    'user' => $comment->user->username,
                    'created_at' => $comment->created_at
                ];
            }
            
            // Ordenar por data
            usort($activities, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
            
            return array_slice($activities, 0, 5);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Método privado para obter usuários mais ativos
     * 
     * @return array
     */
    private function getTopUsers() {
        try {
            // Buscar usuários com mais posts
            $users = User::find('all', array(
                'include' => array('posts'),
                'limit' => 5
            ));
            
            // Ordenar por número de posts
            usort($users, function($a, $b) {
                return count($b->posts) - count($a->posts);
            });
            
            return $users;
            
        } catch (Exception $e) {
            return [];
        }
    }
}