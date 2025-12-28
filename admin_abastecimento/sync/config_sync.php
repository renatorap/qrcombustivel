<?php
/**
 * Configuração de Sincronização
 * Ajuste este arquivo conforme necessário
 */

return [
    // Configurações de Conexão
    'conexoes' => [
        'producao' => [
            'host' => '149.56.235.225',
            'user' => 'conceit1_renatorap',
            'pass' => 'CapSto$%mysql01',
            'port' => 3306,
            'database' => 'conceit1_combustivel'
        ],
        'local' => [
            'host' => 'localhost',
            'user' => 'renatorap',
            'pass' => 'J@melancia01',
            'port' => 3306,
            'database' => 'conceit1_combustivel'
        ]
    ],
    
    // Tabelas a sincronizar (na ordem de dependências)
    'tabelas' => [
        // NOTA: Tabelas do sistema antigo foram removidas (2025-12-26):
        // - empresa (substituída por clientes)
        // - empresa_users (não mais necessária)
        // - fornecedor_users (não mais necessária)
        // - sc_log (log do ScriptCase)
        // - sec_* (tabelas de segurança do ScriptCase)
        
        // Cadastros básicos/auxiliares (sem dependências)
        'sexo',
        'tp_sanguineo',
        'cat_cnh',
        'situacao',
        'tp_material',
        'tp_produto',
        'un_medida',
        'gr_produto',
        
        // Cadastros de estrutura organizacional
        'un_orcamentaria',
        'setor',
        'cargo',
        'forma_trabalho',
        
        // Cadastros de veículos
        'marca_veiculo',
        'cor_veiculo',
        'tp_veiculo',
        'combustivel_veiculo',
        
        // Fornecedores e produtos
        'fornecedor',
        'produto',
        
        // Licitações e contratos
        'licitacao',
        'contrato',
        'aditamento_combustivel',
        
        // Preços e valores
        'preco_combustivel',
        'vl_comb_adit_ativo',
        
        // Dados principais (dependem dos cadastros acima)
        'veiculo',
        'condutor',
        
        // Requisições e consumos
        'requisicao',
        'consumo_combustivel',
        'extrato_combustivel',
        
        // Logs e relatórios
        'relat_abast'
    ],
    
    // Opções de sincronização
    'opcoes' => [
        'sincronizar_apenas_novos' => false, // Se true, não atualiza registros existentes
        'usar_transacoes' => true, // Usar transações para garantir integridade
        'timeout_conexao' => 30, // Timeout em segundos
        'criar_backup_antes' => false, // Criar backup do banco local antes de sincronizar
        'log_detalhado' => true // Log detalhado de cada operação
    ]
];
