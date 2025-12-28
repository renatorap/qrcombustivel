<?php
/**
 * Script de Diagnóstico do Sistema Multicliente
 * Verifica se as tabelas e estrutura estão corretas
 */

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Diagnóstico Multicliente</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>🔍 Diagnóstico do Sistema Multicliente</h1>";

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo "<p class='error'>❌ Erro ao conectar ao banco de dados</p>";
    exit;
}

echo "<p class='success'>✅ Conexão com banco estabelecida</p>";

// 1. Verificar tabelas
echo "<div class='section'>";
echo "<h2>1. Verificação de Tabelas</h2>";

$tables = ['cliente', 'usuarios', 'veiculo', 'usuario_cliente'];
foreach ($tables as $table) {
    $sql = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ Tabela <strong>$table</strong> existe</p>";
        
        // Mostrar estrutura
        $sql = "DESCRIBE $table";
        $result = $conn->query($sql);
        if ($result) {
            echo "<pre>";
            while ($row = $result->fetch_assoc()) {
                echo sprintf("  %-20s %-20s %s\n", 
                    $row['Field'], 
                    $row['Type'], 
                    $row['Key'] ? "[{$row['Key']}]" : ''
                );
            }
            echo "</pre>";
        }
    } else {
        echo "<p class='error'>❌ Tabela <strong>$table</strong> NÃO existe</p>";
    }
}

echo "</div>";

// 2. Verificar dados
echo "<div class='section'>";
echo "<h2>2. Verificação de Dados</h2>";

// Clientes
$sql = "SELECT COUNT(*) as total FROM cliente WHERE ativo = 1";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>📊 Clientes ativos: <strong>{$row['total']}</strong></p>";
    
    if ($row['total'] > 0) {
        $sql = "SELECT id, razao_social, nome_fantasia FROM cliente WHERE ativo = 1 LIMIT 5";
        $result = $conn->query($sql);
        echo "<pre>";
        while ($r = $result->fetch_assoc()) {
            echo "  [ID: {$r['id']}] {$r['razao_social']} / {$r['nome_fantasia']}\n";
        }
        echo "</pre>";
    }
}

// Usuários
$sql = "SELECT COUNT(*) as total FROM usuarios";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>👥 Usuários cadastrados: <strong>{$row['total']}</strong></p>";
}

// Veículos
$sql = "SELECT COUNT(*) as total FROM veiculo";
$result = $conn->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    echo "<p>🚗 Veículos cadastrados: <strong>{$row['total']}</strong></p>";
}

// Vinculações usuario_cliente
$sql = "SHOW TABLES LIKE 'usuario_cliente'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $sql = "SELECT COUNT(*) as total FROM usuario_cliente WHERE ativo = 1";
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<p>🔗 Vinculações ativas: <strong>{$row['total']}</strong></p>";
        
        if ($row['total'] > 0) {
            $sql = "SELECT uc.*, u.login, c.razao_social 
                    FROM usuario_cliente uc
                    INNER JOIN usuarios u ON uc.usuario_id = u.id
                    INNER JOIN cliente c ON uc.cliente_id = c.id
                    WHERE uc.ativo = 1
                    LIMIT 10";
            $result = $conn->query($sql);
            echo "<pre>";
            while ($r = $result->fetch_assoc()) {
                echo "  {$r['login']} → {$r['razao_social']}\n";
            }
            echo "</pre>";
        }
    }
} else {
    echo "<p class='warning'>⚠️ Tabela usuario_cliente não existe - Execute o script SQL</p>";
}

echo "</div>";

// 3. Verificar campos cliente_id
echo "<div class='section'>";
echo "<h2>3. Verificação de Campos cliente_id</h2>";

$tables_with_cliente = [
    'usuarios' => 'Tabela usuarios',
    'veiculo' => 'Tabela veiculo'
];

foreach ($tables_with_cliente as $table => $label) {
    $sql = "SHOW COLUMNS FROM $table LIKE 'cliente_id'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        echo "<p class='success'>✅ $label tem campo cliente_id</p>";
        
        // Verificar quantos registros têm cliente_id
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN cliente_id IS NOT NULL THEN 1 ELSE 0 END) as com_cliente,
                    SUM(CASE WHEN cliente_id IS NULL THEN 1 ELSE 0 END) as sem_cliente
                FROM $table";
        $result = $conn->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<pre>";
            echo "  Total: {$row['total']}\n";
            echo "  Com cliente_id: {$row['com_cliente']}\n";
            echo "  Sem cliente_id: {$row['sem_cliente']}\n";
            echo "</pre>";
        }
    } else {
        echo "<p class='error'>❌ $label NÃO tem campo cliente_id</p>";
    }
}

echo "</div>";

// 4. Testar API
echo "<div class='section'>";
echo "<h2>4. Status das APIs</h2>";

$apis = [
    'user_cliente.php' => 'API de gerenciamento de clientes'
];

foreach ($apis as $file => $desc) {
    $path = __DIR__ . "/../api/$file";
    if (file_exists($path)) {
        echo "<p class='success'>✅ $desc existe</p>";
    } else {
        echo "<p class='error'>❌ $desc NÃO existe em $path</p>";
    }
}

echo "</div>";

// 5. Erros do PHP
echo "<div class='section'>";
echo "<h2>5. Erros do PHP</h2>";

$error_log = ini_get('error_log');
echo "<p>Log de erros: <code>$error_log</code></p>";

if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent = array_slice($lines, -20);
    echo "<pre>";
    foreach ($recent as $line) {
        if (stripos($line, 'cliente') !== false || stripos($line, 'usuario') !== false) {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "<p class='warning'>⚠️ Arquivo de log não encontrado</p>";
}

echo "</div>";

// 6. Recomendações
echo "<div class='section'>";
echo "<h2>6. Recomendações</h2>";

$sql = "SHOW TABLES LIKE 'usuario_cliente'";
$result = $conn->query($sql);

if (!$result || $result->num_rows === 0) {
    echo "<p class='warning'>⚠️ <strong>AÇÃO NECESSÁRIA:</strong> Execute o script SQL de migração:</p>";
    echo "<pre>mysql -u root -p admin_abastecimento < database/multicliente.sql</pre>";
} else {
    // Verificar se há vinculações
    $sql = "SELECT COUNT(*) as total FROM usuario_cliente";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    
    if ($row['total'] == 0) {
        echo "<p class='warning'>⚠️ <strong>AÇÃO NECESSÁRIA:</strong> Não há vinculações. Execute:</p>";
        echo "<pre>INSERT INTO usuario_cliente (usuario_id, cliente_id, ativo)
SELECT u.id, c.id, 1
FROM usuarios u
CROSS JOIN cliente c
WHERE c.ativo = 1
LIMIT 1;</pre>";
    } else {
        echo "<p class='success'>✅ Sistema configurado corretamente!</p>";
    }
}

echo "</div>";

echo "<hr>";
echo "<p><a href='../pages/dashboard.php'>← Voltar para o sistema</a></p>";
echo "</body></html>";

$conn->close();
?>
