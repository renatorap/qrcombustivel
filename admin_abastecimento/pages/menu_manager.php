<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/access_control.php';
require_once __DIR__ . '/../config/cache_control.php';

// Verificar autenticação
if (!isset($_SESSION['userId'])) {
    header('Location: ../index.php');
    exit;
}

$userId = $_SESSION['userId'];
$accessControl = new AccessControl($userId);

// Verificar permissão para acessar esta página
if (!$accessControl->verificarPermissao('menu_manager', 'acessar')) {
    header('Location: dashboard.php');
    exit;
}

$pageTitle = "Gerenciamento de Menu";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo COMPANY_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css">
    <style>
        .menu-tree {
            list-style: none;
            padding-left: 0;
        }
        .menu-tree > li {
            margin-bottom: 10px;
        }
        .menu-item {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: move;
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            border-color: var(--primary-dark);
        }
        .menu-item.dragging {
            opacity: 0.5;
        }
        .menu-item-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }
        .menu-item-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        .menu-item-icon {
            width: 40px;
            height: 40px;
            background: var(--gray-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
        }
        .menu-item-details {
            flex: 1;
        }
        .menu-item-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 3px;
        }
        .menu-item-meta {
            font-size: 12px;
            color: var(--text-secondary);
        }
        .menu-item-actions {
            display: flex;
            gap: 8px;
        }
        .submenu-container {
            margin-left: 40px;
            margin-top: 10px;
            padding-left: 20px;
            border-left: 3px solid var(--secondary-orange);
        }
        .subsubmenu-container {
            margin-left: 40px;
            margin-top: 10px;
            padding-left: 20px;
            border-left: 2px solid rgba(245, 155, 76, 0.3);
        }
        .badge-level {
            font-size: 10px;
            padding: 3px 8px;
        }
        .drag-handle {
            cursor: move;
            color: var(--gray-medium);
        }
        .drag-handle:hover {
            color: var(--primary-dark);
        }
        .icon-preview {
            font-size: 20px;
            margin-right: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: var(--gray-medium);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="main-content">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <main class="content">
            <div class="page-title">
                <div>
                    <h1><i class="fas fa-sitemap"></i> <?php echo $pageTitle; ?></h1>
                    <p class="text-secondary">Gerencie a estrutura hierárquica do menu do sistema</p>
                </div>
                <div>
                    <button class="btn btn-primary" onclick="openAddModal('modulo')">
                        <i class="fas fa-plus"></i> Novo Módulo
                    </button>
                </div>
            </div>

            <div class="filters">
                <div class="filter-group">
                    <label><i class="fas fa-filter"></i> Filtros:</label>
                    <select class="form-select" id="filterStatus" onchange="loadMenuTree()">
                        <option value="">Todos</option>
                        <option value="1" selected>Ativos</option>
                        <option value="0">Inativos</option>
                    </select>
                </div>
                <div class="filter-group">
                    <button class="btn btn-outline-secondary" onclick="loadMenuTree()">
                        <i class="fas fa-sync"></i> Atualizar
                    </button>
                </div>
            </div>

            <div id="menuTreeContainer">
                <div class="empty-state">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Carregando estrutura do menu...</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Adicionar/Editar -->
    <div class="modal fade" id="menuItemModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title mb-0" id="modalTitle">Adicionar Item</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="menuItemForm">
                    <div class="modal-body py-3">
                        <input type="hidden" id="itemId" name="id">
                        <input type="hidden" id="itemType" name="tipo">
                        <input type="hidden" id="parentId" name="parent_id">

                        <div class="row g-2">
                            <!-- Código e Nome na mesma linha -->
                            <div class="col-md-6">
                                <label for="itemCodigo" class="form-label mb-1 small">Código *</label>
                                <input type="text" class="form-control form-control-sm" id="itemCodigo" name="codigo" required placeholder="Ex: clientes_sub">
                            </div>
                            <div class="col-md-6">
                                <label for="itemNome" class="form-label mb-1 small">Nome *</label>
                                <input type="text" class="form-control form-control-sm" id="itemNome" name="nome" required placeholder="Ex: Clientes">
                            </div>

                            <!-- Ícone e Ordem na mesma linha -->
                            <div class="col-md-8">
                                <label for="itemIcone" class="form-label mb-1 small">Ícone</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text" title="Clique para escolher um ícone">
                                        <i class="fas fa-folder" id="iconPreview" style="font-size: 14px;"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-sm" id="itemIcone" name="icone" 
                                           placeholder="Clique para escolher" oninput="updateIconPreview()">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="itemOrdem" class="form-label mb-1 small">Ordem</label>
                                <input type="number" class="form-control form-control-sm" id="itemOrdem" name="ordem" value="0" min="0">
                            </div>

                            <!-- Tipo (radio buttons compactos) -->
                            <div class="col-12" id="expandidoGroup">
                                <label class="form-label mb-1 small">Tipo</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="expandido" id="expandidoSim" value="1" onchange="toggleAplicacaoField()">
                                        <label class="form-check-label small" for="expandidoSim">Expansível</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="expandido" id="expandidoNao" value="0" checked onchange="toggleAplicacaoField()">
                                        <label class="form-check-label small" for="expandidoNao">Link direto</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Aplicação -->
                            <div class="col-12" id="aplicacaoGroup">
                                <label for="itemAplicacao" class="form-label mb-1 small">Aplicação *</label>
                                <select class="form-select form-select-sm" id="itemAplicacao" name="aplicacao_id">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>

                            <!-- Status (switch compacto) -->
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="itemAtivo" name="ativo" checked>
                                    <label class="form-check-label small" for="itemAtivo">Ativo</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i> Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="../js/app.js"></script>
    <script src="../js/icon_picker.js"></script>
    <script src="../js/menu_manager.js"></script>
</body>
</html>
