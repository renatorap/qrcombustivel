<?php
/**
 * Arquivo de Configuração para Sincronização
 * 
 * INSTRUÇÕES:
 * 1. Copie este arquivo para sync_config.php
 * 2. Preencha as credenciais corretas
 * 3. Adicione sync_config.php ao .gitignore para não versionar senhas
 */

return [
    'producao' => [
        'host' => 'SEU_HOST_PRODUCAO',
        'user' => 'SEU_USUARIO_PRODUCAO',
        'pass' => 'SUA_SENHA_PRODUCAO',
        'port' => 3306,
        'database' => 'SEU_BANCO_PRODUCAO'
    ],
    'local' => [
        'host' => 'localhost',
        'user' => 'SEU_USUARIO_LOCAL',
        'pass' => 'SUA_SENHA_LOCAL',
        'port' => 3306,
        'database' => 'SEU_BANCO_LOCAL'
    ]
];
