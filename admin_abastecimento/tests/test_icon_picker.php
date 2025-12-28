<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Icon Picker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .test-section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .test-result {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
        }
        .test-pass {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        .test-fail {
            background-color: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4"><i class="fas fa-vial"></i> Teste do Icon Picker</h1>
        
        <!-- Teste 1: Modal do Icon Picker -->
        <div class="test-section">
            <h3>Teste 1: Modal do Icon Picker</h3>
            <p>Verifica se o modal é criado corretamente.</p>
            <button class="btn btn-primary" onclick="test1()">Executar Teste 1</button>
            <div id="test1Result" class="test-result" style="display: none;"></div>
        </div>

        <!-- Teste 2: Campo com Icon Picker -->
        <div class="test-section">
            <h3>Teste 2: Campo com Icon Picker</h3>
            <p>Teste interativo - clique no ícone ou no campo para abrir o seletor.</p>
            
            <div class="mb-3">
                <label class="form-label">Ícone de Teste</label>
                <div class="input-group">
                    <span class="input-group-text" id="testIconPreview">
                        <i class="fas fa-home"></i>
                    </span>
                    <input type="text" class="form-control" id="testIconInput" 
                           value="fa-home" placeholder="Clique para escolher">
                </div>
            </div>
            
            <button class="btn btn-success" onclick="test2()">Abrir Seletor</button>
            <div id="test2Result" class="test-result" style="display: none;"></div>
        </div>

        <!-- Teste 3: Busca de Ícones -->
        <div class="test-section">
            <h3>Teste 3: Busca de Ícones</h3>
            <p>Verifica se a busca filtra os ícones corretamente.</p>
            <button class="btn btn-primary" onclick="test3()">Executar Teste 3</button>
            <div id="test3Result" class="test-result" style="display: none;"></div>
        </div>

        <!-- Teste 4: Seleção de Ícone -->
        <div class="test-section">
            <h3>Teste 4: Seleção de Ícone</h3>
            <p>Verifica se a seleção atualiza o campo corretamente.</p>
            <div class="mb-3">
                <label class="form-label">Ícone Teste 4</label>
                <div class="input-group">
                    <span class="input-group-text" id="test4IconPreview">
                        <i class="fas fa-question"></i>
                    </span>
                    <input type="text" class="form-control" id="test4IconInput" 
                           value="fa-question" readonly>
                </div>
            </div>
            <button class="btn btn-primary" onclick="test4()">Executar Teste 4</button>
            <div id="test4Result" class="test-result" style="display: none;"></div>
        </div>

        <!-- Teste 5: Múltiplos Campos -->
        <div class="test-section">
            <h3>Teste 5: Múltiplos Campos</h3>
            <p>Verifica se funciona com múltiplos campos na mesma página.</p>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Campo 1</label>
                    <div class="input-group">
                        <span class="input-group-text" id="multi1Preview">
                            <i class="fas fa-user"></i>
                        </span>
                        <input type="text" class="form-control" id="multi1Input" value="fa-user">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Campo 2</label>
                    <div class="input-group">
                        <span class="input-group-text" id="multi2Preview">
                            <i class="fas fa-car"></i>
                        </span>
                        <input type="text" class="form-control" id="multi2Input" value="fa-car">
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Campo 3</label>
                    <div class="input-group">
                        <span class="input-group-text" id="multi3Preview">
                            <i class="fas fa-cog"></i>
                        </span>
                        <input type="text" class="form-control" id="multi3Input" value="fa-cog">
                    </div>
                </div>
            </div>
            
            <button class="btn btn-primary" onclick="test5()">Executar Teste 5</button>
            <div id="test5Result" class="test-result" style="display: none;"></div>
        </div>

        <!-- Resumo dos Testes -->
        <div class="test-section bg-light">
            <h3>Resumo</h3>
            <div id="testSummary"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/icon_picker.js"></script>
    
    <script>
        let testResults = {
            passed: 0,
            failed: 0,
            total: 5
        };

        function updateSummary() {
            const summaryEl = document.getElementById('testSummary');
            const percentage = ((testResults.passed / testResults.total) * 100).toFixed(0);
            summaryEl.innerHTML = `
                <p><strong>Testes Executados:</strong> ${testResults.passed + testResults.failed} de ${testResults.total}</p>
                <p><strong>Aprovados:</strong> <span class="text-success">${testResults.passed}</span></p>
                <p><strong>Reprovados:</strong> <span class="text-danger">${testResults.failed}</span></p>
                <div class="progress">
                    <div class="progress-bar bg-success" style="width: ${percentage}%">${percentage}%</div>
                </div>
            `;
        }

        function showResult(testId, passed, message) {
            const resultEl = document.getElementById(testId + 'Result');
            resultEl.style.display = 'block';
            resultEl.className = 'test-result ' + (passed ? 'test-pass' : 'test-fail');
            resultEl.innerHTML = `<i class="fas fa-${passed ? 'check' : 'times'}-circle"></i> ${message}`;
            
            if (passed) {
                testResults.passed++;
            } else {
                testResults.failed++;
            }
            updateSummary();
        }

        // Teste 1: Verifica se o modal existe
        function test1() {
            const modal = document.getElementById('iconPickerModal');
            const passed = modal !== null && modal.classList.contains('modal');
            showResult('test1', passed, 
                passed ? 'Modal do Icon Picker criado com sucesso!' : 'Falha: Modal não encontrado!');
        }

        // Teste 2: Abre o seletor
        function test2() {
            makeIconInputClickable('testIconInput', 'testIconPreview');
            setTimeout(() => {
                openIconPicker('testIconInput', 'testIconPreview');
                const modal = document.getElementById('iconPickerModal');
                const isVisible = modal.classList.contains('show');
                showResult('test2', isVisible, 
                    isVisible ? 'Seletor aberto com sucesso!' : 'Falha ao abrir o seletor!');
            }, 100);
        }

        // Teste 3: Verifica busca
        function test3() {
            const searchInput = document.getElementById('iconSearchInput');
            const iconGrid = document.getElementById('iconGrid');
            
            if (!searchInput || !iconGrid) {
                showResult('test3', false, 'Falha: Elementos de busca não encontrados!');
                return;
            }
            
            // Simula busca por 'car'
            filterIcons('car');
            
            setTimeout(() => {
                const icons = iconGrid.querySelectorAll('.icon-picker-item');
                const hasCarIcons = icons.length > 0;
                const allContainsCar = Array.from(icons).every(icon => 
                    icon.textContent.toLowerCase().includes('car')
                );
                
                const passed = hasCarIcons && allContainsCar;
                showResult('test3', passed, 
                    passed ? `Busca funcionando! ${icons.length} ícones encontrados.` : 'Falha na busca de ícones!');
                
                // Limpar busca
                filterIcons('');
            }, 100);
        }

        // Teste 4: Simula seleção
        function test4() {
            makeIconInputClickable('test4IconInput', 'test4IconPreview');
            
            // Simula seleção do ícone fa-star
            const input = document.getElementById('test4IconInput');
            input.value = 'fa-star';
            
            // Atualiza preview
            const preview = document.querySelector('#test4IconPreview i');
            preview.className = 'fas fa-star';
            
            setTimeout(() => {
                const inputValue = input.value;
                const previewClass = preview.className;
                const passed = inputValue === 'fa-star' && previewClass.includes('fa-star');
                
                showResult('test4', passed, 
                    passed ? 'Seleção e preview atualizados com sucesso!' : 'Falha ao atualizar campo/preview!');
            }, 100);
        }

        // Teste 5: Múltiplos campos
        function test5() {
            makeIconInputClickable('multi1Input', 'multi1Preview');
            makeIconInputClickable('multi2Input', 'multi2Preview');
            makeIconInputClickable('multi3Input', 'multi3Preview');
            
            const field1 = document.getElementById('multi1Input');
            const field2 = document.getElementById('multi2Input');
            const field3 = document.getElementById('multi3Input');
            
            const passed = field1.hasAttribute('readonly') && 
                          field2.hasAttribute('readonly') && 
                          field3.hasAttribute('readonly');
            
            showResult('test5', passed, 
                passed ? 'Múltiplos campos configurados com sucesso!' : 'Falha ao configurar múltiplos campos!');
        }

        // Inicializar icon picker ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-executar teste 1
            setTimeout(() => {
                test1();
            }, 500);
        });
    </script>
</body>
</html>
