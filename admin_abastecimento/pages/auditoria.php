<?php
require_once '../config/cache_control.php'; // Controle de cache e sessão
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/access_control.php';

$accessControl = new AccessControl($_SESSION['userId']);
$accessControl->requerPermissao('auditoria', 'acessar');

$podeExportar = $accessControl->verificarPermissao('auditoria', 'exportar');

$pageTitle = 'Auditoria';
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
            <span><i class="fas fa-history"></i> <?php echo $pageTitle; ?></span>
            <?php if ($podeExportar): ?>
                <button class="btn btn-success" onclick="exportarLogs()">
                    <i class="fas fa-file-excel me-2"></i>Exportar
                </button>
            <?php endif; ?>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filtros</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Usuário</label>
                        <select class="form-select" id="filtroUsuario">
                            <option value="">Todos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Aplicação</label>
                        <select class="form-select" id="filtroAplicacao">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ação</label>
                        <select class="form-select" id="filtroAcao">
                            <option value="">Todas</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Início</label>
                        <input type="date" class="form-control" id="filtroDataInicio">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Data Fim</label>
                        <input type="date" class="form-control" id="filtroDataFim">
                    </div>
                    <div class="col-md-12">
                        <input type="text" class="form-control" id="filtroPesquisa" placeholder="Pesquisar em detalhes, IP...">
                    </div>
                    <div class="col-md-12">
                        <button class="btn btn-primary" onclick="carregarLogs()">
                            <i class="fas fa-filter me-2"></i>Aplicar Filtros
                        </button>
                        <button class="btn btn-outline-secondary" onclick="limparFiltros()">
                            <i class="fas fa-times me-2"></i>Limpar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="mb-0" id="statTotal">-</h3>
                        <small class="text-muted">Total de Ações</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="mb-0" id="statUsuarios">-</h3>
                        <small class="text-muted">Usuários Ativos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="mb-0" id="statAplicacoes">-</h3>
                        <small class="text-muted">Aplicações Acessadas</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 class="mb-0" id="statHoje">-</h3>
                        <small class="text-muted">Ações Hoje</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-enhanced">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>Usuário</th>
                                        <th>Aplicação</th>
                                        <th>Ação</th>
                                        <th>Detalhes</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody id="logsTableBody">
                                    <tr><td colspan="6" class="text-center"><div class="spinner-border"></div></td></tr>
                                </tbody>
                            </table>
                        </div>
                        <nav><ul class="pagination justify-content-center" id="pagination"></ul></nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    carregarFiltros();
    carregarLogs();
    carregarEstatisticas();
    
    // Configurar data inicial (últimos 30 dias)
    const hoje = new Date();
    const inicio = new Date(hoje.getTime() - (30 * 24 * 60 * 60 * 1000));
    document.getElementById('filtroDataInicio').value = inicio.toISOString().split('T')[0];
    document.getElementById('filtroDataFim').value = hoje.toISOString().split('T')[0];
});

function carregarFiltros() {
    fetch('../api/auditoria.php?action=usuarios')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroUsuario');
                select.innerHTML += data.usuarios.map(u => `<option value="${u.id}">${u.nome}</option>`).join('');
            }
        });
    
    fetch('../api/auditoria.php?action=aplicacoes')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroAplicacao');
                select.innerHTML += data.aplicacoes.map(a => `<option value="${a.id}">${a.nome}</option>`).join('');
            }
        });
    
    fetch('../api/auditoria.php?action=acoes')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('filtroAcao');
                select.innerHTML += data.acoes.map(a => `<option value="${a}">${a}</option>`).join('');
            }
        });
}

function carregarLogs() {
    const params = new URLSearchParams({
        action: 'list',
        page: currentPage,
        usuario_id: document.getElementById('filtroUsuario').value,
        aplicacao_id: document.getElementById('filtroAplicacao').value,
        acao: document.getElementById('filtroAcao').value,
        data_inicio: document.getElementById('filtroDataInicio').value,
        data_fim: document.getElementById('filtroDataFim').value,
        search: document.getElementById('filtroPesquisa').value
    });
    
    fetch(`../api/auditoria.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('logsTableBody');
                tbody.innerHTML = data.logs.length ? data.logs.map(log => `
                    <tr>
                        <td><small>${new Date(log.created_at).toLocaleString('pt-BR')}</small></td>
                        <td>${log.usuario_nome || '-'}</td>
                        <td>${log.aplicacao_nome || '-'}</td>
                        <td><span class="badge bg-${getAcaoBadge(log.acao)}">${log.acao}</span></td>
                        <td><small>${log.descricao || '-'}</small></td>
                        <td><code>${log.ip_address || '-'}</code></td>
                    </tr>
                `).join('') : '<tr><td colspan="6" class="text-center">Nenhum log encontrado</td></tr>';
            }
        });
}

function carregarEstatisticas() {
    const params = new URLSearchParams({
        action: 'stats',
        data_inicio: document.getElementById('filtroDataInicio').value,
        data_fim: document.getElementById('filtroDataFim').value
    });
    
    fetch(`../api/auditoria.php?${params}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('statTotal').textContent = data.stats.total_acoes;
                document.getElementById('statUsuarios').textContent = data.stats.usuarios_ativos.length;
                document.getElementById('statAplicacoes').textContent = data.stats.aplicacoes_acessadas.length;
                
                const hoje = data.stats.por_dia.find(d => d.dia === new Date().toISOString().split('T')[0]);
                document.getElementById('statHoje').textContent = hoje ? hoje.total : 0;
            }
        });
}

function getAcaoBadge(acao) {
    const badges = {
        'criar': 'success',
        'editar': 'warning',
        'excluir': 'danger',
        'visualizar': 'info',
        'login': 'primary',
        'logout': 'secondary',
        'exportar': 'dark',
        'importar': 'dark'
    };
    return badges[acao] || 'secondary';
}

function limparFiltros() {
    document.getElementById('filtroUsuario').value = '';
    document.getElementById('filtroAplicacao').value = '';
    document.getElementById('filtroAcao').value = '';
    document.getElementById('filtroPesquisa').value = '';
    const hoje = new Date();
    const inicio = new Date(hoje.getTime() - (30 * 24 * 60 * 60 * 1000));
    document.getElementById('filtroDataInicio').value = inicio.toISOString().split('T')[0];
    document.getElementById('filtroDataFim').value = hoje.toISOString().split('T')[0];
    currentPage = 1;
    carregarLogs();
    carregarEstatisticas();
}

function exportarLogs() {
    const params = new URLSearchParams({
        action: 'export',
        usuario_id: document.getElementById('filtroUsuario').value,
        aplicacao_id: document.getElementById('filtroAplicacao').value,
        acao: document.getElementById('filtroAcao').value,
        data_inicio: document.getElementById('filtroDataInicio').value,
        data_fim: document.getElementById('filtroDataFim').value,
        formato: 'csv'
    });
    
    window.open(`../api/auditoria.php?${params}`, '_blank');
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
