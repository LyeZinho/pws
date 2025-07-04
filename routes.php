<?php
/**
 * Arquivo de Configuração de Rotas do Sistema GEstufas
 * 
 * Este arquivo define todas as rotas disponíveis no sistema.
 * Cada rota especifica:
 * - Método HTTP permitido (GET, POST, PUT, DELETE)
 * - Controller responsável
 * - Action (método) a ser executado
 * 
 * Formato: [método, controller, action]
 * 
 * Exemplos de URL:
 * - ?c=home&a=index -> HomeController::index()
 * - ?c=users&a=create -> UserController::create()
 * - ?c=auth&a=login -> AuthController::login()
 * 
 * @package GEstufas
 * @version 1.0.0
 * @since 2025
 */
return [
    /**
     * Rota Padrão
     * 
     * Quando não há parâmetros na URL, esta rota é utilizada
     */
    'defaultRoute' => ['GET', 'HomeController', 'index'],
    
    /**
     * Rotas da Página Inicial (Home)
     * 
     * Responsável pela página principal e dashboard
     */
    'home' => [
        'index' => ['GET', 'HomeController', 'index'],           // Página inicial
        'dashboard' => ['GET', 'HomeController', 'dashboard'],   // Dashboard administrativo
    ],
    
    /**
     * Rotas de Autenticação (Auth)
     * 
     * Sistema de login, logout e registo de usuários
     */
    'auth' => [
        'index' => ['GET', 'AuthController', 'index'],           // Página de login
        'login' => ['GET|POST', 'AuthController', 'login'],      // Processar login
        'logout' => ['GET', 'AuthController', 'logout'],         // Logout do sistema
        'register' => ['GET|POST', 'AuthController', 'register'], // Registo de novos usuários
    ],
    
    /**
     * Rotas de Gestão de Usuários (Users)
     * 
     * Sistema CRUD completo para usuários
     */
    'users' => [
        'index' => ['GET', 'UserController', 'index'],           // Listar todos os usuários
        'show' => ['GET', 'UserController', 'show'],             // Mostrar usuário específico
        'create' => ['GET', 'UserController', 'create'],         // Formulário criar usuário
        'store' => ['POST', 'UserController', 'store'],          // Salvar novo usuário
        'edit' => ['GET', 'UserController', 'edit'],             // Formulário editar usuário
        'update' => ['POST', 'UserController', 'update'],        // Actualizar usuário
        'delete' => ['GET', 'UserController', 'delete'],         // Eliminar usuário
    ],
    
    /**
     * Rotas da Comunidade (Community)
     * 
     * Sistema de posts e comentários
     */
    'community' => [
        'index' => ['GET', 'CommunityController', 'index'],      // Listar posts
        'create' => ['GET|POST', 'CommunityController', 'create'], // Criar post
        'show' => ['GET', 'CommunityController', 'show'],        // Mostrar post específico
        'edit' => ['GET', 'CommunityController', 'edit'],        // Editar post
        'update' => ['POST', 'CommunityController', 'update'],   // Actualizar post
        'delete' => ['GET', 'CommunityController', 'delete'],    // Eliminar post
        'comment' => ['POST', 'CommunityController', 'comment'], // Adicionar comentário
    ],
    
    /**
     * Rotas de Perfil do Usuário (Profile)
     * 
     * Gestão do perfil pessoal do usuário logado
     */
    'profile' => [
        'index' => ['GET', 'ProfileController', 'index'],        // Ver perfil
        'edit' => ['GET|POST', 'ProfileController', 'edit'],     // Editar perfil
        'posts' => ['GET', 'ProfileController', 'posts'],        // Posts do usuário
        'projects' => ['GET', 'ProfileController', 'projects'],  // Projetos do usuário
    ],
    
    /**
     * Rotas de Posts (Posts)
     * 
     * Sistema CRUD completo para posts
     */
    'posts' => [
        'index' => ['GET', 'PostController', 'index'],           // Listar todos os posts
        'show' => ['GET', 'PostController', 'show'],             // Mostrar post específico
        'create' => ['GET', 'PostController', 'create'],         // Formulário criar post
        'store' => ['POST', 'PostController', 'store'],          // Salvar novo post
        'edit' => ['GET', 'PostController', 'edit'],             // Formulário editar post
        'update' => ['POST', 'PostController', 'update'],        // Actualizar post
        'delete' => ['GET', 'PostController', 'delete'],         // Eliminar post
        'comment' => ['POST', 'PostController', 'comment'],      // Adicionar comentário
    ],
    
    /**
     * Rotas de Projetos (Projects)
     * 
     * Sistema CRUD completo para projetos
     */
    'projects' => [
        'index' => ['GET', 'ProjectController', 'index'],        // Listar todos os projetos
        'show' => ['GET', 'ProjectController', 'show'],          // Mostrar projeto específico
        'create' => ['GET', 'ProjectController', 'create'],      // Formulário criar projeto
        'store' => ['POST', 'ProjectController', 'store'],       // Salvar novo projeto
        'edit' => ['GET', 'ProjectController', 'edit'],          // Formulário editar projeto
        'update' => ['POST', 'ProjectController', 'update'],     // Actualizar projeto
        'delete' => ['GET', 'ProjectController', 'delete'],      // Eliminar projeto
        'join' => ['GET', 'ProjectController', 'join'],          // Aderir ao projeto
        'leave' => ['GET', 'ProjectController', 'leave'],        // Sair do projeto
    ],
    
    /**
     * Rotas de Comentários (Comments)
     * 
     * Sistema para gerir comentários
     */
    'comments' => [
        'edit' => ['GET', 'CommentController', 'edit'],          // Editar comentário
        'update' => ['POST', 'CommentController', 'update'],     // Actualizar comentário
        'delete' => ['GET', 'CommentController', 'delete'],      // Eliminar comentário
    ],
    
    /**
     * Rotas de API (para futuras integrações)
     * 
     * Endpoints para acesso via API REST
     */
    'api' => [
        'users' => ['GET', 'ApiController', 'users'],            // API: listar usuários
        'posts' => ['GET', 'ApiController', 'posts'],            // API: listar posts
        'stats' => ['GET', 'ApiController', 'stats'],            // API: estatísticas
    ],
];