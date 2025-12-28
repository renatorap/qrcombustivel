/**
 * Font Awesome Icon Picker
 * Componente para seleção visual de ícones FontAwesome
 */

// Lista de ícones FontAwesome mais usados
const fontAwesomeIcons = [
    // Navegação e Ações
    'fa-home', 'fa-dashboard', 'fa-tachometer-alt', 'fa-chart-line', 'fa-chart-bar',
    // Usuários e Pessoas
    'fa-user', 'fa-users', 'fa-user-circle', 'fa-user-cog', 'fa-users-cog', 'fa-id-card',
    // Arquivos e Documentos
    'fa-file', 'fa-file-alt', 'fa-folder', 'fa-folder-open', 'fa-copy', 'fa-clipboard',
    // Comunicação
    'fa-envelope', 'fa-comment', 'fa-comments', 'fa-phone', 'fa-mobile-alt',
    // Configurações
    'fa-cog', 'fa-cogs', 'fa-wrench', 'fa-tools', 'fa-sliders-h',
    // Segurança
    'fa-shield-alt', 'fa-lock', 'fa-unlock', 'fa-key', 'fa-shield-check',
    // Negócios
    'fa-building', 'fa-briefcase', 'fa-store', 'fa-warehouse', 'fa-industry',
    // Transporte
    'fa-car', 'fa-truck', 'fa-bus', 'fa-motorcycle', 'fa-bicycle', 'fa-plane', 'fa-ship',
    // Combustível
    'fa-gas-pump', 'fa-oil-can', 'fa-fire', 'fa-battery-full',
    // Finanças
    'fa-dollar-sign', 'fa-euro-sign', 'fa-coins', 'fa-credit-card', 'fa-receipt', 'fa-wallet',
    // Mídia
    'fa-image', 'fa-camera', 'fa-video', 'fa-music', 'fa-film',
    // Ações
    'fa-plus', 'fa-minus', 'fa-edit', 'fa-trash', 'fa-save', 'fa-download', 'fa-upload',
    'fa-search', 'fa-filter', 'fa-sort', 'fa-sync', 'fa-redo', 'fa-undo',
    // Status
    'fa-check', 'fa-times', 'fa-check-circle', 'fa-times-circle', 'fa-exclamation-triangle',
    'fa-info-circle', 'fa-question-circle', 'fa-ban',
    // Setas
    'fa-arrow-right', 'fa-arrow-left', 'fa-arrow-up', 'fa-arrow-down',
    'fa-chevron-right', 'fa-chevron-left', 'fa-chevron-up', 'fa-chevron-down',
    // Menu e Interface
    'fa-bars', 'fa-th', 'fa-th-large', 'fa-th-list', 'fa-list', 'fa-list-ul', 'fa-list-ol',
    'fa-sitemap', 'fa-layer-group', 'fa-stream',
    // Calendário e Tempo
    'fa-calendar', 'fa-calendar-alt', 'fa-clock', 'fa-history', 'fa-hourglass',
    // Localização
    'fa-map', 'fa-map-marker-alt', 'fa-location-arrow', 'fa-globe', 'fa-compass',
    // Compras
    'fa-shopping-cart', 'fa-shopping-bag', 'fa-tag', 'fa-tags', 'fa-barcode', 'fa-qrcode',
    // Dados
    'fa-database', 'fa-server', 'fa-hdd', 'fa-chart-pie', 'fa-table',
    // Social
    'fa-share', 'fa-share-alt', 'fa-heart', 'fa-star', 'fa-bookmark', 'fa-bell',
    // Diversos
    'fa-circle', 'fa-square', 'fa-battery-half', 'fa-plug', 'fa-lightbulb',
    'fa-paperclip', 'fa-link', 'fa-print', 'fa-fax', 'fa-book', 'fa-graduation-cap'
];

let iconPickerModal = null;
let currentIconInput = null;
let currentIconPreview = null;

/**
 * Inicializa o Icon Picker
 */
function initIconPicker() {
    // Criar modal HTML se não existir
    if (!document.getElementById('iconPickerModal')) {
        const modalHtml = `
            <div class="modal fade" id="iconPickerModal" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header py-2">
                            <h6 class="modal-title mb-0">
                                <i class="fas fa-icons"></i> Selecionar Ícone FontAwesome
                            </h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-2">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="iconSearchInput" 
                                       placeholder="Buscar ícone..." oninput="filterIcons(this.value)">
                            </div>
                            <div id="iconGrid" class="icon-picker-grid">
                                <!-- Icons will be loaded here -->
                            </div>
                        </div>
                        <div class="modal-footer py-2">
                            <small class="text-muted me-auto">Total: <span id="iconCount">${fontAwesomeIcons.length}</span> ícones</small>
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Adicionar estilos
        const style = document.createElement('style');
        style.textContent = `
            .icon-picker-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
                gap: 8px;
                max-height: 400px;
                overflow-y: auto;
            }
            .icon-picker-item {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 12px 8px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                cursor: pointer;
                transition: all 0.2s ease;
                background: white;
            }
            .icon-picker-item:hover {
                background: #f0f7ff;
                border-color: var(--primary-dark);
                transform: translateY(-2px);
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .icon-picker-item.selected {
                background: var(--primary-dark);
                border-color: var(--primary-dark);
                color: white;
            }
            .icon-picker-item i {
                font-size: 24px;
                margin-bottom: 4px;
            }
            .icon-picker-item.selected i {
                color: white;
            }
            .icon-picker-item small {
                font-size: 9px;
                text-align: center;
                word-break: break-all;
            }
            .icon-picker-trigger {
                cursor: pointer;
            }
            .icon-picker-trigger:hover {
                background-color: #e9ecef !important;
            }
        `;
        document.head.appendChild(style);
    }
    
    iconPickerModal = new bootstrap.Modal(document.getElementById('iconPickerModal'));
    loadIconGrid();
}

/**
 * Carrega o grid de ícones
 */
function loadIconGrid(icons = fontAwesomeIcons) {
    const grid = document.getElementById('iconGrid');
    grid.innerHTML = '';
    
    icons.forEach(icon => {
        const item = document.createElement('div');
        item.className = 'icon-picker-item';
        item.innerHTML = `
            <i class="fas ${icon}"></i>
            <small>${icon.replace('fa-', '')}</small>
        `;
        item.onclick = () => selectIcon(icon, item);
        grid.appendChild(item);
    });
    
    document.getElementById('iconCount').textContent = icons.length;
}

/**
 * Filtra ícones por busca
 */
function filterIcons(searchTerm) {
    const filtered = searchTerm 
        ? fontAwesomeIcons.filter(icon => icon.includes(searchTerm.toLowerCase()))
        : fontAwesomeIcons;
    loadIconGrid(filtered);
}

/**
 * Seleciona um ícone
 */
function selectIcon(icon, element) {
    // Remover seleção anterior
    document.querySelectorAll('.icon-picker-item').forEach(item => {
        item.classList.remove('selected');
    });
    
    // Adicionar seleção atual
    element.classList.add('selected');
    
    // Atualizar input e preview
    if (currentIconInput) {
        currentIconInput.value = icon;
        
        // Disparar evento de input para atualizar preview
        const event = new Event('input', { bubbles: true });
        currentIconInput.dispatchEvent(event);
    }
    
    // Fechar modal após breve delay
    setTimeout(() => {
        iconPickerModal.hide();
    }, 300);
}

/**
 * Abre o seletor de ícones
 */
function openIconPicker(inputId, previewId = null) {
    if (!iconPickerModal) {
        initIconPicker();
    }
    
    currentIconInput = document.getElementById(inputId);
    currentIconPreview = previewId ? document.getElementById(previewId) : null;
    
    // Resetar busca
    document.getElementById('iconSearchInput').value = '';
    loadIconGrid();
    
    // Destacar ícone atual se existir
    const currentIcon = currentIconInput ? currentIconInput.value : '';
    if (currentIcon) {
        setTimeout(() => {
            const currentItem = Array.from(document.querySelectorAll('.icon-picker-item'))
                .find(item => item.querySelector('small').textContent === currentIcon.replace('fa-', ''));
            if (currentItem) {
                currentItem.classList.add('selected');
                currentItem.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }, 100);
    }
    
    iconPickerModal.show();
}

/**
 * Adiciona trigger de click ao campo de ícone
 */
function makeIconInputClickable(inputId, previewId = null) {
    const input = document.getElementById(inputId);
    const container = input.closest('.input-group');
    
    if (container) {
        const iconSpan = container.querySelector('.input-group-text');
        if (iconSpan) {
            iconSpan.classList.add('icon-picker-trigger');
            iconSpan.title = 'Clique para escolher um ícone';
            iconSpan.style.cursor = 'pointer';
            iconSpan.onclick = (e) => {
                e.preventDefault();
                openIconPicker(inputId, previewId);
            };
        }
    }
    
    // Permitir click no próprio input também
    input.style.cursor = 'pointer';
    input.onclick = () => openIconPicker(inputId, previewId);
    input.setAttribute('readonly', 'readonly');
}

// Inicializar quando o documento estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initIconPicker);
} else {
    initIconPicker();
}
