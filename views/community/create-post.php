<!-- 
    View para criar um novo post
    Formulário com título e conteúdo
-->
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4>Criar Novo Post</h4>
                </div>
                <div class="card-body">
                    <!-- Exibe erros se existirem -->
                    <?php if (isset($errors) && !empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $field => $fieldErrors): ?>
                                    <?php foreach ($fieldErrors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Formulário de criação -->
                    <form method="POST" action="?c=community&a=create">
                        <div class="mb-3">
                            <label for="title" class="form-label">Título do Post</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="title" 
                                   name="title" 
                                   required
                                   placeholder="Digite o título do seu post"
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Conteúdo</label>
                            <textarea class="form-control" 
                                      id="content" 
                                      name="content" 
                                      rows="6" 
                                      required
                                      placeholder="Digite o conteúdo do seu post"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="?c=community&a=index" class="btn btn-secondary">Cancelar</a>
                            <button type="submit" class="btn btn-success">Publicar Post</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>