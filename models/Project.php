<?php
// models/Project.php
class Project extends ActiveRecord\Model {
    static $table_name = 'projects'; // Nome da tabela no banco de dados
    
    // Validações de presença
    static $validates_presence_of = array(
        array('name', 'message' => 'Nome é obrigatório'),
        array('description', 'message' => 'Descrição é obrigatória'),
        array('user_id', 'message' => 'Utilizador é obrigatório')
    );
    
    // Validações de unicidade ou seja se o nome do projeto já existe 
    static $belongs_to = array(
        array('user')
    );
    
    // Relacionamentos
    public function user() {
        return $this->belongs_to('User');
    }
}