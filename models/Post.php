<?php
// models/Post.php
class Post extends ActiveRecord\Model {
    static $table_name = 'posts'; // Nome da tabela no banco de dados
    
    // Validações de presença
    static $validates_presence_of = array(
        array('title', 'message' => 'Título é obrigatório'),
        array('content', 'message' => 'Conteúdo é obrigatório'),
        array('user_id', 'message' => 'Utilizador é obrigatório')
    );
    
    // Validações de unicidade ou seja se o título do post já existe
    static $belongs_to = array(
        array('user')
    );
    
    // Relacionamentos
    static $has_many = array(
        array('comments')
    );
    
    // Relacionamentos
    public function user() {
        return $this->belongs_to('User');
    }
    
    // Relacionamento com comentários
    public function comments() {
        return $this->has_many('Comment');
    }
}