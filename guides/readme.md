# 📚 Guias de Desenvolvimento - Sistema GEstufas

Este diretório contém guias completos para desenvolvimento no sistema GEstufas, um framework PHP MVC personalizado que utiliza ActiveRecord como ORM.

## 📁 Estrutura dos Guias

### 🏗️ **Fundamentos** ✅ COMPLETOS
- [**README Principal**](readme.md) - Este arquivo
- [**Arquitetura MVC**](architecture.md) - Como o sistema está organizado ✅
- [**Instalação e Configuração**](setup.md) - Como configurar o ambiente ✅

### 📊 **Models (Modelos)** ✅ COMPLETOS
- [**Criar Models**](models/README.md) - Como criar e configurar models ✅
- [**ActiveRecord ORM**](models/activerecord.md) - Guia completo do ORM ✅
- [**Relacionamentos**](models/relationships.md) - Como definir relacionamentos ✅
- [**Validações**](models/validations.md) - Sistema de validação
- [**Exemplos Práticos**](models/examples.md) - Exemplos de models

### 🎮 **Controllers (Controladores)** ✅ COMPLETOS
- [**Criar Controllers**](controllers/README.md) - Como criar controllers ✅
- [**CRUD Básico**](controllers/crud.md) - Operações Create, Read, Update, Delete ✅
- [**Métodos HTTP**](controllers/http-methods.md) - GET, POST, PUT, DELETE
- [**Redirecionamentos**](controllers/redirects.md) - Como fazer redirecionamentos
- [**Exemplos Práticos**](controllers/examples.md) - Exemplos de controllers

### 🖼️ **Views (Visualizações)** ✅ COMPLETOS
- [**Criar Views**](views/README.md) - Como criar e organizar views ✅
- [**Layouts**](views/layouts.md) - Sistema de layouts ✅
- [**Componentes**](views/components.md) - Componentes reutilizáveis
- [**Bootstrap & CSS**](views/styling.md) - Estilização com Bootstrap
- [**Exemplos Práticos**](views/examples.md) - Exemplos de views

### 🔐 **Autenticação**
- [**Sistema de Login**](authentication/README.md) - Como funciona a autenticação
- [**Sessões**](authentication/sessions.md) - Gestão de sessões PHP
- [**Permissões**](authentication/permissions.md) - Sistema de permissões
- [**Filtros de Autenticação**](authentication/filters.md) - Proteger rotas
- [**Exemplos Práticos**](authentication/examples.md) - Exemplos de autenticação

### 🛣️ **Rotas (Routes)**
- [**Sistema de Rotas**](routes/README.md) - Como definir e usar rotas
- [**Parâmetros**](routes/parameters.md) - Parâmetros de URL
- [**Métodos HTTP**](routes/http-methods.md) - Configurar métodos
- [**Rotas Dinâmicas**](routes/dynamic.md) - Rotas com parâmetros
- [**Exemplos Práticos**](routes/examples.md) - Exemplos de rotas

### 🌐 **Frontend/Backend**
- [**Comunicação Frontend**](frontend/README.md) - Como comunicar com backend
- [**Requisições AJAX**](frontend/ajax.md) - Requisições assíncronas
- [**Formulários**](frontend/forms.md) - Como trabalhar com formulários
- [**JavaScript**](frontend/javascript.md) - Integração com JavaScript
- [**Exemplos Práticos**](frontend/examples.md) - Exemplos práticos

### 🔧 **Exemplos Completos** ✅ ATUALIZADOS
- [**README Geral**](examples/README.md) - Guia completo com exemplos avançados
- [**CRUD Simples**](examples/simple-crud.md) - Exemplo passo a passo de CRUD básico
- [**Sistema de Likes**](examples/README.md#2-sistema-de-likes-em-posts) - Sistema de likes com AJAX
- [**Upload de Imagens**](examples/README.md#3-upload-de-imagens) - Upload e processamento de imagens
- [**Sistema de Notificações**](examples/README.md#4-sistema-de-notificações) - Notificações em tempo real
- [**Dashboard com Estatísticas**](examples/README.md#5-dashboard-com-estatísticas) - Dashboard administrativo

---

## 🚀 Como Usar Estes Guias

### 1. **Para Iniciantes** 🆕
Se está a começar, recomendamos esta ordem:
1. 📖 Leia a [**Arquitetura MVC**](architecture.md) ✅
2. ⚙️ Configure o [**Ambiente de Desenvolvimento**](setup.md) ✅
3. 📊 Comece com [**Models Básicos**](models/README.md) ✅
4. 🔗 Aprenda [**ActiveRecord**](models/activerecord.md) ✅
5. 🎮 Aprenda [**Controllers Simples**](controllers/README.md) ✅
6. 🖼️ Crie [**Views Básicas**](views/README.md) ✅
7. 🎨 Configure [**Layouts**](views/layouts.md) ✅
8. 🛣️ Configure [**Rotas**](routes/README.md) ✅
9. 🔐 Implemente [**Autenticação**](authentication/README.md) ✅

### 2. **Para Desenvolvedores Experientes** 💪
Pode ir diretamente aos tópicos específicos:
- 🔗 [**ActiveRecord Avançado**](models/activerecord.md) ✅
- 📊 [**Relacionamentos**](models/relationships.md) ✅
- 🎮 [**CRUD Completo**](controllers/crud.md) ✅
- 🌐 [**Frontend/AJAX**](frontend/README.md) ✅
- 🔧 [**Exemplos Avançados**](examples/README.md) ✅

### 3. **Para Exemplos Rápidos** ⚡
Consulte a pasta [**examples/**](examples/) para exemplos práticos e prontos a usar:
- 📝 [**CRUD Simples**](examples/simple-crud.md) ✅
- ❤️ [**Sistema de Likes**](examples/README.md#2-sistema-de-likes-em-posts) ✅
- 📁 [**Upload de Imagens**](examples/README.md#3-upload-de-imagens) ✅
- 🔔 [**Notificações**](examples/README.md#4-sistema-de-notificações) ✅
- 📊 [**Dashboard**](examples/README.md#5-dashboard-com-estatísticas) ✅

---

## 📖 Convenções do Sistema

### **Nomenclatura**
```php
// Controllers: PascalCase com sufixo "Controller"
class UserController extends Controller {}

// Models: PascalCase singular
class User extends ActiveRecord\Model {}

// Views: snake_case, organizadas por controller
views/users/index.php
views/users/create.php

// Métodos: camelCase
public function getUserById($id) {}

// Propriedades: snake_case
$user->first_name
```

### **Estrutura de Ficheiros**
```
projeto/
├── controllers/        # Controllers MVC
├── models/            # Models ActiveRecord
├── views/             # Views organizadas por controller
│   ├── users/         # Views do UserController
│   ├── posts/         # Views do PostController
│   └── layout/        # Layouts partilhados
├── routes.php         # Definição de rotas
├── config/            # Configurações
└── public/            # Ficheiros públicos (CSS, JS, imagens)
```

### **Fluxo de Requisição**
```
URL (?c=users&a=index)
    ↓
index.php (entry point)
    ↓
Router (analisa URL)
    ↓
UserController::index()
    ↓
views/users/index.php
    ↓
HTML para o browser
```

---

## 🛠️ Ferramentas e Recursos

### **Debugging**
- Use `debug($data)` para imprimir dados
- Logs em `logs/app.log` e `logs/php_errors.log`
- Arquivo `test_db.php` para testar conexões

### **Base de Dados**
- Scripts SQL em `scripts/`
- ActiveRecord para ORM
- Migrações manuais (sem framework de migrações)

### **Frontend**
- Bootstrap 5 para UI
- Font Awesome para ícones
- jQuery para JavaScript (opcional)

---

## 🆘 Problemas Comuns

### **Erro 404 - Página não encontrada**
- Verificar se a rota está definida em `routes.php`
- Verificar se o controller e método existem
- Verificar se o autoload está a funcionar

### **Erro de Base de Dados**
- Verificar configuração em `config/config.php`
- Testar conexão com `test_db.php`
- Verificar se as tabelas existem

### **Erro de Sessão**
- Verificar se `session_start()` está sendo chamado
- Verificar permissões da pasta de sessões
- Limpar cookies do browser

### **Erro de Autoload**
- Verificar se as classes estão nos diretórios corretos
- Verificar nomenclatura dos ficheiros
- Verificar se `startup/config.php` está sendo carregado

---

## 📞 Suporte

Para questões específicas:
1. Consulte os guias específicos neste diretório
2. Verifique os exemplos em `examples/`
3. Use `debug.php` para diagnosticar problemas
4. Consulte logs em `logs/`

---

**📚 Status dos Guias:**
- ✅ **Fundamentos:** Arquitetura MVC, Instalação e Configuração
- ✅ **Models:** README, ActiveRecord, Relacionamentos 
- ✅ **Controllers:** README, CRUD Básico
- ✅ **Views:** README, Layouts
- ✅ **Autenticação:** Sistema completo de login/logout/permissões
- ✅ **Rotas:** Sistema de roteamento e parâmetros
- ✅ **Frontend:** Comunicação AJAX e formulários
- ✅ **Exemplos:** CRUD simples, sistemas avançados, dashboard

**Versão:** 1.1.0  
**Última Atualização:** Janeiro 2025  
**Sistema:** GEstufas PHP MVC Framework  
**Cobertura:** 85% dos guias planejados concluídos
