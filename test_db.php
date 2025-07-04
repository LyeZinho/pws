<?php
// test_db.php - Teste de conexão com a base de dados

require_once 'startup/config.php';

try {
    // Teste de conexão com ActiveRecord
    $connection = ActiveRecord\ConnectionManager::get_connection();
    echo "✅ Conexão com a base de dados: OK\n";
    
    // Teste de criação de usuário
    $testUser = new User();
    $testUser->username = 'teste_' . time();
    $testUser->email = 'teste_' . time() . '@example.com';
    $testUser->password = md5('password123');
    
    if ($testUser->save()) {
        echo "✅ Criação de usuário: OK\n";
        echo "📝 Usuário criado com ID: " . $testUser->id . "\n";
        
        // Teste de busca
        $foundUser = User::find($testUser->id);
        if ($foundUser) {
            echo "✅ Busca de usuário: OK\n";
            echo "📝 Username encontrado: " . $foundUser->username . "\n";
        } else {
            echo "❌ Busca de usuário: ERRO\n";
        }
        
        // Limpar teste
        $testUser->delete();
        echo "✅ Remoção de usuário de teste: OK\n";
        
    } else {
        echo "❌ Criação de usuário: ERRO\n";
        if ($testUser->errors) {
            foreach ($testUser->errors as $field => $errors) {
                foreach ($errors as $error) {
                    echo "   - $field: $error\n";
                }
            }
        }
    }
    
    // Teste de contagem de usuários
    $userCount = User::count();
    echo "📊 Total de usuários na base de dados: $userCount\n";
    
    echo "\n🎉 Todos os testes foram executados com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    echo "📍 Arquivo: " . $e->getFile() . "\n";
    echo "📍 Linha: " . $e->getLine() . "\n";
}
?>
