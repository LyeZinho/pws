<!-- 
    View para listar todos os posts da comunidade
    Mostra uma lista de posts com título, autor e data
-->
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Comunidade</h1>
                <a href="?c=community&a=create" class="btn btn-success">Criar Post</a>
            </div>
            
            <?php if (empty($posts)): ?>
                <!-- Nenhum post encontrado -->
                <div class="alert alert-info">
                    <p>Ainda não há posts na comunidade.</p>
                    <a href="?c=community&a=create" class="btn btn-primary">Criar o primeiro post</a>
                </div>
            <?php else: ?>
                <!-- Lista de posts -->
                <div class="row">
                    <?php foreach ($posts as $post): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5><?php echo htmlspecialchars($post->title); ?></h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <?php echo htmlspecialchars(substr($post->content, 0, 150)); ?>
                                        <?php if (strlen($post->content) > 150): ?>...<?php endif; ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Por: <?php echo htmlspecialchars($post->user->username); ?>
                                            <br>
                                            <?php echo date('d/m/Y H:i', strtotime($post->created_at)); ?>
                                        </small>
                                        <a href="?c=community&a=show&id=<?php echo $post->id; ?>" 
                                           class="btn btn-primary btn-sm">Ver Post</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>