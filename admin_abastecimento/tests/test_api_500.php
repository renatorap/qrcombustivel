<?php
/**
 * Teste para verificar se API user_cliente.php retorna resposta adequada
 * Este teste simula uma requisição autenticada
 */

session_start();

// Simular usuário autenticado
$_SESSION['userId'] = 1;
$_SESSION['username'] = 'admin';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste API 500 Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Teste API user_cliente.php - Erro 500</h1>
        
        <div class="alert alert-info">
            <strong>Sessão Ativa:</strong><br>
            User ID: <?php echo $_SESSION['userId']; ?><br>
            Username: <?php echo $_SESSION['username']; ?>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Teste 1: Chamar API List</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" onclick="testList()">Testar API List</button>
                <div id="result-list" class="mt-3"></div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5>Teste 2: Chamar API Current</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" onclick="testCurrent()">Testar API Current</button>
                <div id="result-current" class="mt-3"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Log de Erros PHP</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-secondary" onclick="showErrors()">Mostrar Últimos Erros</button>
                <div id="error-log" class="mt-3"></div>
            </div>
        </div>
    </div>

    <script>
        async function testList() {
            const resultDiv = document.getElementById('result-list');
            resultDiv.innerHTML = '<div class="spinner-border" role="status"></div> Carregando...';
            
            try {
                const response = await fetch('../api/user_cliente.php?action=list');
                const status = response.status;
                const data = await response.json();
                
                let html = `<strong>Status HTTP:</strong> <span class="${status === 200 ? 'success' : 'error'}">${status}</span><br>`;
                html += `<strong>Resposta:</strong><br><pre>${JSON.stringify(data, null, 2)}</pre>`;
                
                if (status === 200 && data.success) {
                    html += `<div class="alert alert-success mt-2">✓ API funcionando corretamente</div>`;
                } else if (status === 200 && !data.success) {
                    html += `<div class="alert alert-warning mt-2">⚠ API retornou erro controlado (melhor que 500!)</div>`;
                } else {
                    html += `<div class="alert alert-danger mt-2">✗ Erro HTTP ${status}</div>`;
                }
                
                resultDiv.innerHTML = html;
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>Erro ao chamar API:</strong><br>
                    ${error.message}<br><br>
                    <em>Isso pode indicar erro 500 ou problema de rede</em>
                </div>`;
            }
        }

        async function testCurrent() {
            const resultDiv = document.getElementById('result-current');
            resultDiv.innerHTML = '<div class="spinner-border" role="status"></div> Carregando...';
            
            try {
                const response = await fetch('../api/user_cliente.php?action=current');
                const status = response.status;
                const data = await response.json();
                
                let html = `<strong>Status HTTP:</strong> <span class="${status === 200 ? 'success' : 'error'}">${status}</span><br>`;
                html += `<strong>Resposta:</strong><br><pre>${JSON.stringify(data, null, 2)}</pre>`;
                
                if (status === 200) {
                    html += `<div class="alert alert-success mt-2">✓ API funcionando</div>`;
                } else {
                    html += `<div class="alert alert-danger mt-2">✗ Erro HTTP ${status}</div>`;
                }
                
                resultDiv.innerHTML = html;
            } catch (error) {
                resultDiv.innerHTML = `<div class="alert alert-danger">
                    <strong>Erro ao chamar API:</strong><br>
                    ${error.message}
                </div>`;
            }
        }

        async function showErrors() {
            const errorDiv = document.getElementById('error-log');
            errorDiv.innerHTML = '<div class="spinner-border" role="status"></div> Carregando...';
            
            try {
                const response = await fetch('get_error_log.php');
                const text = await response.text();
                errorDiv.innerHTML = `<pre style="max-height: 400px; overflow-y: auto;">${text}</pre>`;
            } catch (error) {
                errorDiv.innerHTML = `<div class="alert alert-danger">Erro ao carregar log: ${error.message}</div>`;
            }
        }

        // Executar teste automaticamente ao carregar
        window.addEventListener('DOMContentLoaded', () => {
            console.log('Página carregada, executando teste automático...');
            testList();
        });
    </script>
</body>
</html>
