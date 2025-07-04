-- =============================================================================
-- EXEMPLO COMPLETO DE CRIAÇÃO DE BASE DE DADOS MYSQL
-- Compatível com PHP e ActiveRecord
-- =============================================================================

-- 1. SETUP INICIAL DA BASE DE DADOS
-- =============================================================================

-- Criar a base de dados
CREATE DATABASE IF NOT EXISTS exemplo_app 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Usar a base de dados
USE exemplo_app;

-- Configurações básicas para compatibilidade
SET FOREIGN_KEY_CHECKS = 1;
SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- =============================================================================
-- 2. TABELA SIMPLES - UTILIZADORES
-- =============================================================================

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- =============================================================================
-- 3. RELAÇÃO UM PARA UM - PERFIL DE UTILIZADOR
-- =============================================================================

CREATE TABLE user_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    bio TEXT,
    avatar_url VARCHAR(255),
    website VARCHAR(255),
    location VARCHAR(100),
    profession VARCHAR(100),
    social_media JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chave estrangeira para relação 1:1
    FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

-- =============================================================================
-- 4. RELAÇÃO UM PARA MUITOS - CATEGORIAS E POSTS
-- =============================================================================

-- Tabela de categorias (pai)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug),
    INDEX idx_active (is_active)
);

-- Tabela de posts (filha) - Um para muitos
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT,
    excerpt VARCHAR(500),
    featured_image VARCHAR(255),
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at TIMESTAMP NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE,
    
    -- Índices para performance
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
);

-- =============================================================================
-- 5. RELAÇÃO MUITOS PARA MUITOS - TAGS E POSTS
-- =============================================================================

-- Tabela de tags
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    color VARCHAR(7) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_slug (slug)
);

-- Tabela intermediária para relação muitos para muitos
CREATE TABLE post_tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    tag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (post_id) REFERENCES posts(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índice composto para evitar duplicatas
    UNIQUE KEY unique_post_tag (post_id, tag_id),
    INDEX idx_post_id (post_id),
    INDEX idx_tag_id (tag_id)
);

-- =============================================================================
-- 6. RELAÇÃO MUITOS PARA MUITOS COM ATRIBUTOS ADICIONAIS - PROJETOS E UTILIZADORES
-- =============================================================================

-- Tabela de projetos
CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    status ENUM('planning', 'active', 'completed', 'cancelled') DEFAULT 'planning',
    start_date DATE,
    end_date DATE,
    budget DECIMAL(10, 2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_status (status)
);

-- Tabela intermediária com atributos adicionais
CREATE TABLE project_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('owner', 'manager', 'developer', 'designer', 'tester') NOT NULL,
    hourly_rate DECIMAL(8, 2),
    join_date DATE NOT NULL,
    leave_date DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (project_id) REFERENCES projects(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índices
    UNIQUE KEY unique_active_member (project_id, user_id, is_active),
    INDEX idx_project_id (project_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- =============================================================================
-- 7. COMENTÁRIOS - RELAÇÃO HIERÁRQUICA (AUTO-REFERÊNCIA)
-- =============================================================================

CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_id INT NULL, -- Auto-referência para comentários aninhados
    content TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Chaves estrangeiras
    FOREIGN KEY (post_id) REFERENCES posts(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    -- Índices
    INDEX idx_post_id (post_id),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_id (parent_id),
    INDEX idx_approved (is_approved)
);

-- =============================================================================
-- 8. DADOS DE EXEMPLO
-- =============================================================================

-- Inserir utilizadores de exemplo
INSERT INTO users (name, email, password, phone, date_of_birth) VALUES
('João Silva', 'joao@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '912345678', '1990-05-15'),
('Maria Santos', 'maria@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '923456789', '1985-08-22'),
('Pedro Costa', 'pedro@exemplo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '934567890', '1992-12-03');

-- Inserir perfis de utilizador (1:1)
INSERT INTO user_profiles (user_id, bio, profession, location) VALUES
(1, 'Desenvolvedor web apaixonado por tecnologia', 'Desenvolvedor Full Stack', 'Lisboa'),
(2, 'Designer criativa com experiência em UX/UI', 'UX Designer', 'Porto'),
(3, 'Gestor de projetos com foco em metodologias ágeis', 'Project Manager', 'Coimbra');

-- Inserir categorias
INSERT INTO categories (name, slug, description) VALUES
('Tecnologia', 'tecnologia', 'Artigos sobre programação e tecnologia'),
('Design', 'design', 'Artigos sobre design e criatividade'),
('Negócios', 'negocios', 'Artigos sobre gestão e empreendedorismo');

-- Inserir posts (1:muitos)
INSERT INTO posts (user_id, category_id, title, slug, content, status, published_at) VALUES
(1, 1, 'Introdução ao PHP Moderno', 'introducao-php-moderno', 'Conteúdo do artigo sobre PHP...', 'published', NOW()),
(2, 2, 'Princípios de Design UX', 'principios-design-ux', 'Conteúdo sobre design UX...', 'published', NOW()),
(3, 3, 'Gestão Ágil de Projetos', 'gestao-agil-projetos', 'Conteúdo sobre metodologias ágeis...', 'draft', NULL);

-- Inserir tags
INSERT INTO tags (name, slug, color) VALUES
('PHP', 'php', '#777BB4'),
('MySQL', 'mysql', '#4479A1'),
('JavaScript', 'javascript', '#F7DF1E'),
('UX', 'ux', '#FF6B6B'),
('Agile', 'agile', '#4ECDC4');

-- Relacionar posts com tags (muitos:muitos)
INSERT INTO post_tags (post_id, tag_id) VALUES
(1, 1), -- PHP post com tag PHP
(1, 2), -- PHP post com tag MySQL
(2, 4), -- Design post com tag UX
(3, 5); -- Gestão post com tag Agile

-- Inserir projetos
INSERT INTO projects (name, description, status, start_date, budget) VALUES
('Website Corporativo', 'Desenvolvimento de website para empresa', 'active', '2025-01-01', 5000.00),
('App Mobile', 'Aplicação móvel para gestão de tarefas', 'planning', '2025-03-01', 8000.00);

-- Relacionar utilizadores com projetos (muitos:muitos com atributos)
INSERT INTO project_members (project_id, user_id, role, hourly_rate, join_date) VALUES
(1, 1, 'developer', 25.00, '2025-01-01'),
(1, 2, 'designer', 30.00, '2025-01-01'),
(1, 3, 'manager', 35.00, '2025-01-01'),
(2, 1, 'owner', 40.00, '2025-02-15');

-- Inserir comentários (incluindo aninhados)
INSERT INTO comments (post_id, user_id, parent_id, content, is_approved) VALUES
(1, 2, NULL, 'Excelente artigo sobre PHP!', TRUE),
(1, 3, 1, 'Concordo completamente!', TRUE),
(1, 1, NULL, 'Obrigado pelos comentários!', TRUE);

-- =============================================================================
-- 9. VIEWS ÚTEIS PARA CONSULTAS COMPLEXAS
-- =============================================================================

-- View para posts com informações do autor e categoria
CREATE VIEW post_details AS
SELECT 
    p.id,
    p.title,
    p.slug,
    p.excerpt,
    p.status,
    p.published_at,
    p.view_count,
    u.name as author_name,
    u.email as author_email,
    c.name as category_name,
    c.slug as category_slug,
    p.created_at,
    p.updated_at
FROM posts p
JOIN users u ON p.user_id = u.id
JOIN categories c ON p.category_id = c.id;

-- View para membros de projetos ativos
CREATE VIEW active_project_members AS
SELECT 
    pm.id,
    p.name as project_name,
    u.name as member_name,
    u.email as member_email,
    pm.role,
    pm.hourly_rate,
    pm.join_date,
    DATEDIFF(COALESCE(pm.leave_date, CURDATE()), pm.join_date) as days_in_project
FROM project_members pm
JOIN projects p ON pm.project_id = p.id
JOIN users u ON pm.user_id = u.id
WHERE pm.is_active = TRUE;

-- =============================================================================
-- 10. PROCEDURES E FUNCTIONS ÚTEIS
-- =============================================================================

DELIMITER //

-- Procedure para obter estatísticas de um utilizador
CREATE PROCEDURE GetUserStats(IN user_id INT)
BEGIN
    SELECT 
        u.name,
        u.email,
        COUNT(DISTINCT p.id) as total_posts,
        COUNT(DISTINCT c.id) as total_comments,
        COUNT(DISTINCT pm.project_id) as total_projects
    FROM users u
    LEFT JOIN posts p ON u.id = p.user_id
    LEFT JOIN comments c ON u.id = c.user_id
    LEFT JOIN project_members pm ON u.id = pm.user_id AND pm.is_active = TRUE
    WHERE u.id = user_id
    GROUP BY u.id;
END //

-- Function para calcular idade
CREATE FUNCTION CalculateAge(birth_date DATE) 
RETURNS INT
READS SQL DATA
DETERMINISTIC
BEGIN
    RETURN YEAR(CURDATE()) - YEAR(birth_date) - 
           (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(birth_date, '%m%d'));
END //

DELIMITER ;

-- =============================================================================
-- 11. TRIGGERS PARA AUDITORIA E AUTOMAÇÃO
-- =============================================================================

DELIMITER //

-- Trigger para atualizar contagem de visualizações
CREATE TRIGGER update_post_view_count 
AFTER INSERT ON comments
FOR EACH ROW
BEGIN
    UPDATE posts 
    SET view_count = view_count + 1 
    WHERE id = NEW.post_id;
END //

-- Trigger para log de alterações de posts
CREATE TRIGGER post_audit_log
AFTER UPDATE ON posts
FOR EACH ROW
BEGIN
    INSERT INTO logs (table_name, record_id, action, old_values, new_values, user_id, created_at)
    VALUES (
        'posts', 
        NEW.id, 
        'UPDATE',
        JSON_OBJECT('title', OLD.title, 'status', OLD.status),
        JSON_OBJECT('title', NEW.title, 'status', NEW.status),
        NEW.user_id,
        NOW()
    );
END //

DELIMITER ;

-- Tabela de logs para auditoria (opcional)
CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON,
    new_values JSON,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at)
);

-- =============================================================================
-- 12. QUERIES DE EXEMPLO PARA TESTAR AS RELAÇÕES
-- =============================================================================

/*
-- Exemplo 1: Buscar posts com autor e categoria
SELECT * FROM post_details WHERE status = 'published';

-- Exemplo 2: Buscar utilizador com perfil (1:1)
SELECT u.*, up.bio, up.profession 
FROM users u 
LEFT JOIN user_profiles up ON u.id = up.user_id 
WHERE u.id = 1;

-- Exemplo 3: Buscar posts de uma categoria (1:muitos)
SELECT p.title, u.name as author 
FROM posts p 
JOIN users u ON p.user_id = u.id 
WHERE p.category_id = 1;

-- Exemplo 4: Buscar tags de um post (muitos:muitos)
SELECT p.title, t.name as tag_name 
FROM posts p 
JOIN post_tags pt ON p.id = pt.post_id 
JOIN tags t ON pt.tag_id = t.id 
WHERE p.id = 1;

-- Exemplo 5: Buscar membros de um projeto com funções
SELECT p.name as project, u.name as member, pm.role, pm.hourly_rate 
FROM projects p 
JOIN project_members pm ON p.id = pm.project_id 
JOIN users u ON pm.user_id = u.id 
WHERE p.id = 1 AND pm.is_active = TRUE;

-- Exemplo 6: Comentários aninhados
SELECT 
    c1.content as comment,
    c2.content as reply,
    u1.name as commenter,
    u2.name as replier
FROM comments c1
LEFT JOIN comments c2 ON c1.id = c2.parent_id
JOIN users u1 ON c1.user_id = u1.id
LEFT JOIN users u2 ON c2.user_id = u2.id
WHERE c1.post_id = 1 AND c1.parent_id IS NULL;

-- Exemplo 7: Estatísticas por utilizador
CALL GetUserStats(1);

-- Exemplo 8: Utilizadores com idade calculada
SELECT name, date_of_birth, CalculateAge(date_of_birth) as age 
FROM users 
WHERE date_of_birth IS NOT NULL;
*/

-- =============================================================================
-- FIM DO SCRIPT
-- =============================================================================