<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('sincronizacao', 'acessar');

$podeSincronizar = $accessControl->verificarPermissao('sincronizacao', 'criar');

$pageTitle = 'Sincronização';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - QR Combustível</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include '../includes/header.php'; ?>
<?php include '../includes/sidebar.php'; ?>

<main class="main-content">
    <div class="container-fluid">
        <div class="page-title">
            <span><i class="fas fa-sync"></i> <?php echo $pageTitle; ?></span>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Sincronizar Aplicações</h5>
                    </div>
                    <div class="card-body">
                        <p>Esta ferramenta escaneia o diretório <code>pages/</code> e registra automaticamente todas as páginas PHP encontradas.</p>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Como funciona:</strong>
                            <ul class="mb-0">
                                <li>Escaneia todas as páginas PHP no diretório pages/</li>
                                <li>Adiciona novas páginas não cadastradas</li>
                                <li>Atualiza informações de páginas existentes</li>
                                <li>Não remove páginas existentes no banco</li>
                            </ul>
                        </div>

                        <?php if ($podeSincronizar): ?>
                            <button class="btn btn-primary btn-lg" onclick="executarSincronizacao()">
                                <i class="fas fa-sync me-2"></i>Executar Sincronização
                            </button>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Você não possui permissão para executar a sincronização.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mt-4" id="resultadoCard" style="display: none;">
                    <div class="card-header">
                        <h5 class="mb-0">Resultado da Sincronização</h5>
                    </div>
                    <div class="card-body" id="resultadoConteudo"></div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Aplicações Cadastradas</h5>
                    </div>
                    <div class="card-body">
                        <div id="listaAplicacoes">
                            <div class="text-center">
                                <div class="spinner-border"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Módulos</h5>
                    </div>
                    <div class="card-body">
                        <div id="listaModulos">
                            <div class="text-center">
                                <div class="spinner-border spinner-border-sm"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    carregarAplicacoes();
    carregarModulos();
});

function carregarAplicacoes() {
    fetch('../api/sincronizacao.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const lista = document.getElementById('listaAplicacoes');
                lista.innerHTML = data.aplicacoes.length ? 
                    `<div class="list-group">${data.aplicacoes.map(a => `
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas ${a.icone}"></i> <strong>${a.nome}</strong>
                                    <br><small class="text-muted">${a.codigo}</small>
                                </div>
                                <span class="badge ${a.ativo == 1 ? 'bg-success' : 'bg-secondary'}">${a.ativo == 1 ? 'Ativa' : 'Inativa'}</span>
                            </div>
                        </div>
                    `).join('')}</div>` : 
                    '<p class="text-muted">Nenhuma aplicação cadastrada</p>';
            }
        });
}

function carregarModulos() {
    fetch('../api/sincronizacao.php?action=modulos')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const lista = document.getElementById('listaModulos');
                lista.innerHTML = data.modulos.length ?
                    data.modulos.map(m => `<span class="badge bg-info me-1 mb-1">${m}</span>`).join('') :
                    '<p class="text-muted mb-0">Nenhum módulo</p>';
            }
        });
}

function executarSincronizacao() {
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sincronizando...';
    
    document.getElementById('resultadoCard').style.display = 'none';
    
    fetch('../api/sincronizacao.php?action=scan', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-2"></i>Executar Sincronização';
            
            if (data.success) {
                const resultado = document.getElementById('resultadoConteudo');
                resultado.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Sincronização concluída com sucesso!
                    </div>
                    
                    <h6>Resumo:</h6>
                    <ul>
                        <li><strong>${data.summary.novas}</strong> novas aplicações adicionadas</li>
                        <li><strong>${data.summary.atualizadas}</strong> aplicações já existentes</li>
                        <li><strong>${data.summary.erros}</strong> erros encontrados</li>
                    </ul>
                    
                    ${data.data.novas > 0 ? `
                        <h6>Novas Aplicações:</h6>
                        <ul class="list-unstyled">
                            ${data.data.aplicacoes.filter(a => a.status === 'nova').map(a => `
                                <li><i class="fas fa-plus-circle text-success"></i> ${a.codigo} - ${a.nome}</li>
                            `).join('')}
                        </ul>
                    ` : ''}
                    
                    ${data.data.erros.length > 0 ? `
                        <div class="alert alert-warning">
                            <h6>Erros:</h6>
                            <ul class="mb-0">${data.data.erros.map(e => `<li>${e}</li>`).join('')}</ul>
                        </div>
                    ` : ''}
                `;
                
                document.getElementById('resultadoCard').style.display = 'block';
                carregarAplicacoes();
                carregarModulos();
            } else {
                alert('Erro: ' + data.message);
            }
        })
        .catch(error => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync me-2"></i>Executar Sincronização';
            alert('Erro ao executar sincronização: ' + error);
        });
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
