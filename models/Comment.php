<?php
/**
 * Comment - Modelo para comentários nos posts
 * 
 * Este modelo representa os comentários que os usuários podem fazer nos posts.
 * Cada comentário pertence a um post específico e é criado por um usuário.
 * 
 * Relacionamentos:
 * - belongs_to: User (usuário que criou o comentário)
 * - belongs_to: Post (post ao qual o comentário pertence)
 * 
 * Validações:
 * - content: obrigatório, mínimo 5 caracteres
 * - post_id: obrigatório, deve existir na tabela posts
 * - user_id: obrigatório, deve existir na tabela users
 */
class Comment extends ActiveRecord\Model {
    
    /**
     * Nome da tabela na base de dados
     */
    static $table_name = 'comments';
    
    /**
     * Validações de presença - campos obrigatórios
     */
    static $validates_presence_of = array(
        array('content', 'message' => 'Conteúdo do comentário é obrigatório'),
        array('post_id', 'message' => 'Post é obrigatório'),
        array('user_id', 'message' => 'Usuário é obrigatório')
    );
    
    /**
     * Validações de tamanho - conteúdo deve ter pelo menos 5 caracteres
     */
    static $validates_length_of = array(
        array('content', 'minimum' => 5, 'message' => 'Comentário deve ter pelo menos 5 caracteres')
    );
    
    /**
     * Relacionamentos com outros modelos
     */
    static $belongs_to = array(
        array('user', 'class_name' => 'User', 'foreign_key' => 'user_id'),
        array('post', 'class_name' => 'Post', 'foreign_key' => 'post_id')
    );
    
    /**
     * Método para obter o usuário que criou o comentário
     * 
     * @return User|null
     */
    public function user() {
        return $this->belongs_to('User');
    }
    
    /**
     * Método para obter o post ao qual o comentário pertence
     * 
     * @return Post|null
     */
    public function post() {
        return $this->belongs_to('Post');
    }
    
    /**
     * Método para obter comentários recentes
     * 
     * @param int $limit Número máximo de comentários a retornar
     * @return array
     */
    public static function recent($limit = 10) {
        return self::find('all', array(
            'order' => 'created_at DESC',
            'limit' => $limit,
            'include' => array('user', 'post')
        ));
    }
    
    /**
     * Método para obter comentários de um post específico
     * 
     * @param int $postId ID do post
     * @return array
     */
    public static function byPost($postId) {
        return self::find('all', array(
            'conditions' => array('post_id = ?', $postId),
            'order' => 'created_at ASC',
            'include' => array('user')
        ));
    }
    
    /**
     * Método para obter comentários de um usuário específico
     * 
     * @param int $userId ID do usuário
     * @return array
     */
    public static function byUser($userId) {
        return self::find('all', array(
            'conditions' => array('user_id = ?', $userId),
            'order' => 'created_at DESC',
            'include' => array('post')
        ));
    }
    
    /**
     * Método para contar comentários de um post
     * 
     * @param int $postId ID do post
     * @return int
     */
    public static function countByPost($postId) {
        return self::count(array(
            'conditions' => array('post_id = ?', $postId)
        ));
    }
    
    /**
     * Método para verificar se o usuário pode editar o comentário
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    public function canEdit($userId) {
        return $this->user_id == $userId;
    }
    
    /**
     * Método para verificar se o usuário pode eliminar o comentário
     * 
     * @param int $userId ID do usuário
     * @return bool
     */
    public function canDelete($userId) {
        return $this->user_id == $userId;
    }
    
    /**
     * Método para obter um resumo do comentário
     * 
     * @param int $length Tamanho máximo do resumo
     * @return string
     */
    public function summary($length = 100) {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }
        return substr($this->content, 0, $length) . '...';
    }
    
    /**
     * Método para formatar a data de criação
     * 
     * @param string $format Formato da data
     * @return string
     */
    public function formatCreatedAt($format = 'd/m/Y H:i') {
        return date($format, strtotime($this->created_at));
    }
    
    /**
     * Método para obter o tempo decorrido desde a criação
     * 
     * @return string
     */
    public function timeAgo() {
        $time = time() - strtotime($this->created_at);
        
        if ($time < 60) {
            return 'há ' . $time . ' segundos';
        } elseif ($time < 3600) {
            return 'há ' . round($time / 60) . ' minutos';
        } elseif ($time < 86400) {
            return 'há ' . round($time / 3600) . ' horas';
        } elseif ($time < 2592000) {
            return 'há ' . round($time / 86400) . ' dias';
        } elseif ($time < 31536000) {
            return 'há ' . round($time / 2592000) . ' meses';
        } else {
            return 'há ' . round($time / 31536000) . ' anos';
        }
    }
}
