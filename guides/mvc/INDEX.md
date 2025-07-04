# Guias MVC - Índice de Navegação

## 📋 Visão Geral

Esta coleção de guias apresenta uma implementação **progressiva** do padrão MVC em PHP, desde conceitos básicos até implementações avançadas com ActiveRecord. Cada guia constrói sobre o anterior, formando um caminho de aprendizado completo.

## 🎯 Estrutura dos Guias

### 📘 [01 - MVC Básico](01-mvc-basico.md)
**Fundamentos do padrão MVC**
- Implementação limpa do MVC do zero
- Router básico e sistema de rotas
- Controllers e Views simples
- Autoloader PSR-4
- Estrutura de projeto modular

**Ideal para:** Iniciantes que querem entender os conceitos fundamentais do MVC.

### 📗 [02 - MVC com MySQL](02-mvc-mysql.md)
**Integração com banco de dados**
- Conexão PDO com MySQL
- CRUD completo com SQL nativo
- Validação de dados
- Sistema de migrations
- Query builder básico

**Ideal para:** Desenvolvedores que já dominam o MVC básico e querem adicionar persistência de dados.

### 📙 [03 - MVC com Autenticação](03-mvc-autenticacao.md)
**Sistema de autenticação completo**
- Login e registro de usuários
- Proteção de rotas com middleware
- Gerenciamento de sessões
- Remember me e recuperação de senha
- Hash seguro de senhas

**Ideal para:** Projetos que necessitam de controle de acesso e gerenciamento de usuários.

### 📕 [04 - MVC com ActiveRecord](04-mvc-activerecord.md) ⭐
**ORM avançado e produtividade**
- PHP ActiveRecord ORM
- Models com relacionamentos declarativos
- Validações integradas
- Migrations automatizadas
- Queries fluentes e scopes

**Ideal para:** Desenvolvimento profissional com foco em produtividade e boas práticas.

## 🚀 Trilha de Aprendizado Recomendada

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   01 - Básico   │───▶│ 02 - MySQL      │───▶│ 03 - Auth       │───▶│ 04 - ActiveRecord│
│                 │    │                 │    │                 │    │                 │
│ • Router        │    │ • PDO           │    │ • Sessions      │    │ • ORM           │
│ • Controllers   │    │ • CRUD          │    │ • Middleware    │    │ • Relationships │
│ • Views         │    │ • Migrations    │    │ • Security      │    │ • Validations   │
│ • Autoload      │    │ • Validations   │    │ • Password Hash │    │ • Callbacks     │
└─────────────────┘    └─────────────────┘    └─────────────────┘    └─────────────────┘
```

## 📊 Comparação dos Guias

| Aspecto | 01 - Básico | 02 - MySQL | 03 - Auth | 04 - ActiveRecord |
|---------|-------------|------------|-----------|-------------------|
| **Complexidade** | ⭐ | ⭐⭐ | ⭐⭐⭐ | ⭐⭐⭐⭐ |
| **Banco de Dados** | ❌ | ✅ PDO | ✅ PDO | ✅ ORM |
| **Autenticação** | ❌ | ❌ | ✅ | ✅ |
| **Relacionamentos** | ❌ | Manual | Manual | Automático |
| **Validações** | Manual | Manual | Manual | Integrado |
| **Migrations** | ❌ | SQL | SQL | PHP |
| **Produtividade** | Básica | Média | Alta | Muito Alta |

## 🛠️ Pré-requisitos por Guia

### Para Todos os Guias:
- PHP 7.4 ou superior
- Servidor web (Apache/Nginx) ou PHP built-in server
- Editor de código (VS Code recomendado)

### Guia 01 - MVC Básico:
- Conhecimento básico de PHP
- Entendimento de orientação a objetos

### Guia 02 - MVC com MySQL:
- Conclusão do Guia 01
- MySQL 5.7+ ou MariaDB
- Conhecimento básico de SQL

### Guia 03 - MVC com Autenticação:
- Conclusão dos Guias 01 e 02
- Conceitos de segurança web
- Entendimento de sessões PHP

### Guia 04 - MVC com ActiveRecord:
- Conclusão dos guias anteriores
- Composer instalado
- Conceitos de ORM

## 🎯 Casos de Uso Recomendados

### 📘 Use o Guia 01 quando:
- Aprender conceitos fundamentais do MVC
- Criar protótipos simples
- Ensinar padrões de arquitetura
- Projetos sem banco de dados

### 📗 Use o Guia 02 quando:
- Adicionar persistência ao projeto
- Trabalhar com dados relacionais
- Criar CRUDs tradicionais
- Projetos de médio porte

### 📙 Use o Guia 03 quando:
- Implementar controle de acesso
- Criar sistemas multiusuário
- Proteger recursos privados
- Gerenciar perfis de usuário

### 📕 Use o Guia 04 quando:
- Desenvolver aplicações profissionais
- Maximizar produtividade
- Trabalhar com relacionamentos complexos
- Implementar validações robustas

## 🔗 Recursos Adicionais

### Documentação Oficial:
- [PHP ActiveRecord](http://www.phpactiverecord.org/)
- [PHP Manual](https://www.php.net/manual/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Guias Relacionados:
- [Database Guides](../database/INDEX.md) - Guias de banco de dados e otimização
- [Authentication Guide](../authentication/README.md) - Guia detalhado de autenticação
- [Frontend Guides](../frontend/README.md) - Guias de interface de usuário

### Ferramentas Recomendadas:
- **IDE:** VS Code, PhpStorm
- **Database:** MySQL Workbench, phpMyAdmin
- **Debugging:** Xdebug, PHP Debug Console
- **Testing:** PHPUnit
- **Dependency Management:** Composer

## 📝 Notas Importantes

### ⚠️ Atenção:
- Cada guia assume conhecimento dos anteriores
- Exemplos são progressivamente mais complexos
- Código de produção requer ajustes adicionais de segurança

### 💡 Dicas:
- Pratique cada conceito antes de avançar
- Adapte exemplos às suas necessidades
- Implemente testes para código crítico
- Use controle de versão (Git)

### 🔄 Atualizações:
Este índice é atualizado conforme novos guias são adicionados ou existentes são melhorados.

---

## 🚀 Começar Agora

1. **Iniciante?** Comece pelo [MVC Básico](01-mvc-basico.md)
2. **Tem experiência?** Vá direto para o [MVC com ActiveRecord](04-mvc-activerecord.md)
3. **Precisa de auth?** Siga a trilha completa até o [MVC com Autenticação](03-mvc-autenticacao.md)

---

**Última atualização:** Dezembro 2024
