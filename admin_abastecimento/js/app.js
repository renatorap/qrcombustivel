/**
 * App.js - JavaScript Principal da Aplicação
 * Sistema de Abastecimento QR Combustível
 */

$(document).ready(function() {
    initializeApp();
});

/**
 * Inicializa a aplicação
 */
function initializeApp() {
    highlightActiveMenu();
    setupEventListeners();
    setupAnimations();
}

/**
 * Destaca o menu ativo baseado na página atual
 */
function highlightActiveMenu() {
    const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
    
    $('.sidebar-link').each(function() {
        const href = $(this).attr('href');
        const linkPage = href.split('/').pop();
        
        if (linkPage === currentPage || 
            (currentPage === 'index.php' && linkPage === 'dashboard.php') ||
            (currentPage === '' && linkPage === 'dashboard.php')) {
            $(this).closest('.sidebar-item').addClass('active');
        }
    });
}

/**
 * Configura event listeners globais
 */
function setupEventListeners() {
    // Logout com confirmação
    $(document).on('click', 'a[href*="logout"]', function(e) {
        if (!confirm('Tem certeza que deseja sair?')) {
            e.preventDefault();
            return false;
        }
        // Forçar limpeza de cache no logout
        clearBrowserCache();
    });

    // Toggle de senha
    $(document).on('click', '#togglePassword', function(e) {
        e.preventDefault();
        const input = $('#senha');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Use Bootstrap API to close modals when appropriate (keeps backdrop in sync)
    $(document).on('click', '.modal .close, .modal [data-bs-dismiss="modal"]', function(e) {
        const modalEl = $(this).closest('.modal')[0];
        if (modalEl) {
            const bs = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bs.hide();
        }
    });

    // Clicking on backdrop area should close modal via Bootstrap as well
    $(document).on('click', '.modal', function(e) {
        if (e.target === this) {
            const modalEl = this;
            const bs = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            bs.hide();
        }
    });

    // Animação de fade para alerts
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(function() {
            alert.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    });
}

/**
 * Configura animações
 */
function setupAnimations() {
    // Animar números em cards
    $('.card-value').each(function() {
        const value = $(this).text();
        if (/^\d+/.test(value)) {
            animateNumber($(this));
        }
    });

    // Adicionar animação fade-in aos cards
    $('.card').each(function(index) {
        $(this).css({
            opacity: 0,
            transform: 'translateY(20px)'
        });
        
        setTimeout(() => {
            $(this).animate({
                opacity: 1
            }, 500);
            $(this).css('transform', 'translateY(0)');
        }, index * 100);
    });
}

/**
 * Anima números em crescimento
 * @param {jQuery} element Elemento a animar
 */
function animateNumber(element) {
    const value = element.text();
    const number = parseInt(value.replace(/\D/g, ''));
    
    if (isNaN(number)) return;
    
    let current = 0;
    const increment = Math.ceil(number / 30);
    
    const interval = setInterval(() => {
        current += increment;
        if (current >= number) {
            element.text(value);
            clearInterval(interval);
        } else {
            element.text(current.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.'));
        }
    }, 30);
}

/**
 * Exibe um modal
 * @param {string} modalId ID do modal
 */
function showModal(modalId) {
    $('#' + modalId).addClass('show');
}

/**
 * Fecha um modal
 * @param {string} modalId ID do modal
 */
function closeModal(modalId) {
    $('#' + modalId).removeClass('show');
}

/**
 * Exibe notificação (toast)
 * @param {string} message Mensagem
 * @param {string} type Tipo (success, error, info, warning)
 */
function showNotification(message, type = 'info') {
    const bgColor = type === 'success' ? '#d4edda' :
                    type === 'error' ? '#f8d7da' :
                    type === 'warning' ? '#fff3cd' : '#d1ecf1';
    
    const textColor = type === 'success' ? '#155724' :
                      type === 'error' ? '#721c24' :
                      type === 'warning' ? '#856404' : '#0c5460';
    
    const icon = type === 'success' ? 'fa-check-circle' :
                 type === 'error' ? 'fa-exclamation-circle' :
                 type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
    
    const toast = $(`
        <div class="alert alert-${type}" style="
            position: fixed;
            top: 100px;
            right: 20px;
            min-width: 300px;
            background-color: ${bgColor};
            color: ${textColor};
            z-index: 2000;
            animation: slideDown 0.3s ease;
        ">
            <i class="fas ${icon}"></i> ${message}
        </div>
    `);
    
    $('body').append(toast);
    
    setTimeout(() => {
        toast.fadeOut(500, function() {
            $(this).remove();
        });
    }, 5000);
}

/**
 * Valida e formata CPF
 * @param {string} cpf CPF não formatado
 * @returns {string} CPF formatado
 */
function formatCPF(cpf) {
    cpf = cpf.replace(/\D/g, '');
    if (cpf.length !== 11) return '';
    return cpf.slice(0, 3) + '.' + cpf.slice(3, 6) + '.' + cpf.slice(6, 9) + '-' + cpf.slice(9);
}

/**
 * Valida e formata placa de veículo
 * @param {string} plate Placa não formatada
 * @returns {string} Placa formatada
 */
function formatPlate(plate) {
    plate = plate.toUpperCase().replace(/[^A-Z0-9]/g, '');
    if (plate.length !== 7) return '';
    return plate.slice(0, 3) + '-' + plate.slice(3);
}

/**
 * Formata moeda brasileira
 * @param {number} value Valor
 * @returns {string} Valor formatado
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Formata data para padrão brasileiro
 * @param {Date} date Data
 * @returns {string} Data formatada
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(date);
}

/**
 * Formata hora
 * @param {Date} date Data/Hora
 * @returns {string} Hora formatada
 */
function formatTime(date) {
    return new Intl.DateTimeFormat('pt-BR', {
        hour: '2-digit',
        minute: '2-digit'
    }).format(date);
}

/**
 * Debounce para funções
 * @param {Function} func Função
 * @param {number} wait Tempo de espera
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle para funções
 * @param {Function} func Função
 * @param {number} limit Limite de tempo
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Limpa o cache do navegador (localStorage, sessionStorage)
 * Função chamada no logout para garantir limpeza completa
 */
function clearBrowserCache() {
    try {
        // Limpar localStorage
        if (typeof(Storage) !== "undefined" && localStorage) {
            localStorage.clear();
        }
        
        // Limpar sessionStorage
        if (typeof(Storage) !== "undefined" && sessionStorage) {
            sessionStorage.clear();
        }
        
        // Limpar cache de formulários
        $('form').each(function() {
            this.reset();
        });
        
        // Limpar inputs
        $('input[type="text"], input[type="email"], input[type="password"], textarea').val('');
        $('input[type="checkbox"], input[type="radio"]').prop('checked', false);
        $('select').prop('selectedIndex', 0);
        
        console.log('Cache do navegador limpo com sucesso');
    } catch (e) {
        console.error('Erro ao limpar cache:', e);
    }
}

/**
 * Previne uso do botão voltar após logout
 * Adiciona listener para detectar quando usuário tenta voltar
 */
function preventBackAfterLogout() {
    window.history.forward();
    window.addEventListener('popstate', function() {
        window.history.forward();
    });
}

// Executar prevenção de voltar se não houver sessão
if (window.location.pathname.includes('pages/') && !document.cookie.includes('PHPSESSID')) {
    preventBackAfterLogout();
}