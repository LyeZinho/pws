<?php
// models/User.php
class User extends ActiveRecord\Model {
    static $table_name = 'users'; // Nome da tabela no banco de dados
    
    // Validações de presença
    static $validates_presence_of = array(
        array('username', 'message' => 'Username é obrigatório'),
        array('email', 'message' => 'Email é obrigatório'),
        array('password', 'message' => 'Password é obrigatório')
    );
    
    // Validações de unicidade
    static $validates_uniqueness_of = array(
        array('username', 'message' => 'Username já existe'),
        array('email', 'message' => 'Email já existe')
    );
    
    // Relacionamentos
    static $has_many = array(
        array('posts'),
        array('projects')
    );
    
    // Relacionamentos
    public function posts() {
        return $this->has_many('Post');
    }
    
    // Relacionamentos
    public function projects() {
        return $this->has_many('Project');
    }
}