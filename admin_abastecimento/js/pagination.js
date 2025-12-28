/**
 * Biblioteca de Paginação Reutilizável
 * Sistema de Gestão de Combustível - QR Combustível
 */

/**
 * Renderiza a paginação completa com navegação
 * @param {number} currentPage - Página atual
 * @param {number} totalPages - Total de páginas
 * @param {string} containerId - ID do container da paginação (padrão: 'paginacao')
 * @param {function} callback - Função de callback ao clicar em uma página
 */
function renderPagination(currentPage, totalPages, containerId = 'paginacao', callback = null) {
    const paginacao = $(`#${containerId}`);
    paginacao.empty();

    if (totalPages <= 1) return;

    const maxLinks = 5; // Máximo de links de páginas visíveis

    // Botão Primeira Página
    paginacao.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="1" title="Primeira">
                <i class="fas fa-angle-double-left"></i>
            </a>
        </li>
    `);

    // Botão Anterior
    paginacao.append(`
        <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage - 1}" title="Anterior">
                <i class="fas fa-angle-left"></i>
            </a>
        </li>
    `);

    // Calcular range de páginas a exibir
    let startPage = Math.max(1, currentPage - Math.floor(maxLinks / 2));
    let endPage = Math.min(totalPages, startPage + maxLinks - 1);

    // Ajustar startPage se estivermos próximos do fim
    if (endPage - startPage < maxLinks - 1) {
        startPage = Math.max(1, endPage - maxLinks + 1);
    }

    // Mostrar primeira página e reticências se necessário
    if (startPage > 1) {
        paginacao.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" data-page="1">1</a>
            </li>
        `);
        if (startPage > 2) {
            paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
    }

    // Links de páginas no range
    for (let i = startPage; i <= endPage; i++) {
        const active = i === currentPage ? 'active' : '';
        paginacao.append(`
            <li class="page-item ${active}">
                <a class="page-link" href="javascript:void(0)" data-page="${i}">${i}</a>
            </li>
        `);
    }

    // Mostrar última página e reticências se necessário
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            paginacao.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
        }
        paginacao.append(`
            <li class="page-item">
                <a class="page-link" href="javascript:void(0)" data-page="${totalPages}">${totalPages}</a>
            </li>
        `);
    }

    // Botão Próxima
    paginacao.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${currentPage + 1}" title="Próxima">
                <i class="fas fa-angle-right"></i>
            </a>
        </li>
    `);

    // Botão Última Página
    paginacao.append(`
        <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" data-page="${totalPages}" title="Última">
                <i class="fas fa-angle-double-right"></i>
            </a>
        </li>
    `);

    // Adicionar event listeners
    if (callback) {
        paginacao.find('.page-link').on('click', function(e) {
            e.preventDefault();
            const page = parseInt($(this).data('page'));
            if (!isNaN(page) && page >= 1 && page <= totalPages) {
                callback(page);
            }
        });
    }
}

/**
 * Alias para manter compatibilidade com código existente
 */
function renderPaginacao(currentPage, totalPages, containerId = 'paginacao', callback = null) {
    renderPagination(currentPage, totalPages, containerId, callback);
}

function renderizarPaginacao(currentPage, totalPages, containerId = 'paginacao', callback = null) {
    renderPagination(currentPage, totalPages, containerId, callback);
}
