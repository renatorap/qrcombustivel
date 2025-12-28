<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Erros JavaScript - Sistema Multicliente</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4a90c4 0%, #2e6fa1 100%);
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            border-bottom: 4px solid #f07a28;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .section {
            background: #f8f9fa;
            border-left: 4px solid #f07a28;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
            font-weight: bold;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 14px;
        }
        button {
            background: #4a90c4;
            color: white;
            border: none;
            border-bottom: 2px solid #f07a28;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
            transition: all 0.3s;
        }
        button:hover {
            background: #f07a28;
            border-bottom-color: #d66a20;
        }
        button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .test-result {
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 5px solid;
        }
        .test-pass {
            background: #d4edda;
            border-color: #28a745;
        }
        .test-fail {
            background: #f8d7da;
            border-color: #dc3545;
        }
        #console-output {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            margin-top: 15px;
        }
        .console-error { color: #fc8181; }
        .console-warn { color: #f6e05e; }
        .console-info { color: #63b3ed; }
        .console-log { color: #68d391; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Erros JavaScript - Sistema Multicliente</h1>
        
        <div class="section">
            <h2>1. Testes de API</h2>
            <button onclick="testApiUserCliente()">🔄 Testar API user_cliente.php</button>
            <button onclick="testApiVeiculo()">🚗 Testar API veiculo.php</button>
            <button onclick="testApiUsuarios()">👥 Testar API usuarios.php</button>
            <div id="api-results"></div>
        </div>

        <div class="section">
            <h2>2. Testes de Elementos DOM</h2>
            <button onclick="testDomElements()">🔍 Verificar Elementos</button>
            <div id="dom-results"></div>
        </div>

        <div class="section">
            <h2>3. Testes de Funções JavaScript</h2>
            <button onclick="testJsFunctions()">⚙️ Verificar Funções</button>
            <div id="js-results"></div>
        </div>

        <div class="section">
            <h2>4. Console do Navegador</h2>
            <p class="info">Monitorando erros JavaScript em tempo real...</p>
            <button onclick="clearConsoleOutput()">🗑️ Limpar Console</button>
            <div id="console-output"></div>
        </div>

        <div class="section">
            <h2>5. Informações da Sessão</h2>
            <div id="session-info">
                <p><strong>Cliente ID:</strong> <?php echo $_SESSION['cliente_id'] ?? 'null'; ?></p>
                <p><strong>Cliente Nome:</strong> <?php echo htmlspecialchars($_SESSION['cliente_nome'] ?? 'N/A'); ?></p>
                <p><strong>Cliente Logo:</strong> <?php echo htmlspecialchars($_SESSION['cliente_logo'] ?? 'N/A'); ?></p>
                <p><strong>Usuário ID:</strong> <?php echo $_SESSION['userId'] ?? 'N/A'; ?></p>
                <p><strong>Login:</strong> <?php echo htmlspecialchars($_SESSION['login'] ?? 'N/A'); ?></p>
            </div>
        </div>

        <div class="section">
            <h2>6. Ações</h2>
            <button onclick="window.location.href='diagnostico_multicliente.php'">🔧 Diagnóstico de Banco</button>
            <button onclick="window.location.href='../pages/dashboard.php'">🏠 Ir para Dashboard</button>
            <button onclick="location.reload()">🔄 Recarregar Página</button>
        </div>
    </div>

    <script>
        // Capturar erros do console
        const consoleOutput = document.getElementById('console-output');
        const originalConsoleError = console.error;
        const originalConsoleWarn = console.warn;
        const originalConsoleInfo = console.info;
        const originalConsoleLog = console.log;

        function addToConsole(type, ...args) {
            const timestamp = new Date().toLocaleTimeString();
            const message = args.map(arg => 
                typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
            ).join(' ');
            
            const line = document.createElement('div');
            line.className = 'console-' + type;
            line.textContent = `[${timestamp}] [${type.toUpperCase()}] ${message}`;
            consoleOutput.appendChild(line);
            consoleOutput.scrollTop = consoleOutput.scrollHeight;
        }

        console.error = function(...args) {
            addToConsole('error', ...args);
            originalConsoleError.apply(console, args);
        };

        console.warn = function(...args) {
            addToConsole('warn', ...args);
            originalConsoleWarn.apply(console, args);
        };

        console.info = function(...args) {
            addToConsole('info', ...args);
            originalConsoleInfo.apply(console, args);
        };

        console.log = function(...args) {
            addToConsole('log', ...args);
            originalConsoleLog.apply(console, args);
        };

        function clearConsoleOutput() {
            consoleOutput.innerHTML = '';
        }

        // Teste 1: APIs
        async function testApiUserCliente() {
            const results = document.getElementById('api-results');
            results.innerHTML = '<p>Testando...</p>';
            
            const tests = [];

            // Test LIST
            try {
                const r = await fetch('../api/user_cliente.php?action=list');
                const data = await r.json();
                tests.push({
                    name: 'GET ?action=list',
                    pass: data.success === true,
                    message: data.success ? `${data.total} clientes encontrados` : data.message
                });
            } catch (err) {
                tests.push({
                    name: 'GET ?action=list',
                    pass: false,
                    message: err.message
                });
            }

            // Test CURRENT
            try {
                const r = await fetch('../api/user_cliente.php?action=current');
                const data = await r.json();
                tests.push({
                    name: 'GET ?action=current',
                    pass: data.success === true,
                    message: data.cliente ? `Cliente: ${data.cliente.nome}` : 'Nenhum cliente selecionado'
                });
            } catch (err) {
                tests.push({
                    name: 'GET ?action=current',
                    pass: false,
                    message: err.message
                });
            }

            displayTestResults(results, tests);
        }

        async function testApiVeiculo() {
            const results = document.getElementById('api-results');
            results.innerHTML = '<p>Testando...</p>';
            
            try {
                const r = await fetch('../api/veiculo.php?action=list&page=1');
                const data = await r.json();
                
                displayTestResults(results, [{
                    name: 'API veiculo.php - list',
                    pass: data.success === true,
                    message: data.success ? `${data.veiculos?.length || 0} veículos` : data.message
                }]);
            } catch (err) {
                displayTestResults(results, [{
                    name: 'API veiculo.php - list',
                    pass: false,
                    message: err.message
                }]);
            }
        }

        async function testApiUsuarios() {
            const results = document.getElementById('api-results');
            results.innerHTML = '<p>Testando...</p>';
            
            try {
                const r = await fetch('../api/usuarios.php?action=list&page=1');
                const data = await r.json();
                
                displayTestResults(results, [{
                    name: 'API usuarios.php - list',
                    pass: data.success === true,
                    message: data.success ? `${data.usuarios?.length || 0} usuários` : data.message
                }]);
            } catch (err) {
                displayTestResults(results, [{
                    name: 'API usuarios.php - list',
                    pass: false,
                    message: err.message
                }]);
            }
        }

        // Teste 2: DOM Elements
        function testDomElements() {
            const results = document.getElementById('dom-results');
            const elements = [
                'clienteSelector',
                'headerClienteNome',
                'headerClienteLogo'
            ];
            
            const tests = elements.map(id => {
                const el = document.getElementById(id);
                return {
                    name: `Elemento #${id}`,
                    pass: el !== null,
                    message: el ? `Encontrado (${el.tagName})` : 'Não encontrado'
                };
            });

            displayTestResults(results, tests);
        }

        // Teste 3: Funções JS
        function testJsFunctions() {
            const results = document.getElementById('js-results');
            const functions = [
                'loadClientesSelector',
                'switchCliente',
                'logout'
            ];
            
            const tests = functions.map(fn => {
                const exists = typeof window[fn] === 'function';
                return {
                    name: `Função ${fn}()`,
                    pass: exists,
                    message: exists ? 'Definida' : 'Não encontrada'
                };
            });

            displayTestResults(results, tests);
        }

        function displayTestResults(container, tests) {
            container.innerHTML = tests.map(test => `
                <div class="test-result ${test.pass ? 'test-pass' : 'test-fail'}">
                    <strong>${test.pass ? '✓' : '✗'} ${test.name}</strong>: ${test.message}
                </div>
            `).join('');
        }

        // Auto-teste ao carregar
        console.info('Página de verificação carregada');
        
        window.addEventListener('error', function(e) {
            console.error('Erro global capturado:', e.message, 'em', e.filename, 'linha', e.lineno);
        });

        window.addEventListener('unhandledrejection', function(e) {
            console.error('Promise rejeitada:', e.reason);
        });
    </script>
</body>
</html>
