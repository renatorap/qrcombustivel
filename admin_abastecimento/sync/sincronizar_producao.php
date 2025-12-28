<?php
/**
 * Sistema de Sincronização de Dados - Produção para Localhost
 * 
 * Sincroniza dados do servidor de produção para o ambiente local
 * Processa apenas registros novos ou alterados
 */

// Carregar configurações de arquivo externo
$configFile = __DIR__ . '/sync_config.php';
if (!file_exists($configFile)) {
    die("❌ ERRO: Arquivo de configuração não encontrado: $configFile\n" .
        "Crie o arquivo sync_config.php baseado em sync_config.example.php\n");
}

$config = require $configFile;

// Validar configurações
if (empty($config['producao']) || empty($config['local'])) {
    die("❌ ERRO: Configurações de conexão inválidas\n");
}

// Tabelas para sincronizar (na ordem correta para respeitar FKs)
$tabelasParaSincronizar = [
    // ========================================
    // TABELAS REMOVIDAS - Sistema Antigo
    // ========================================
    // Estas tabelas foram removidas do banco local pois são usadas apenas no sistema antigo:
    // - empresa (substituída por clientes)
    // - empresa_users (não mais necessária)
    // - fornecedor_users (não mais necessária)
    // - sc_log (log do ScriptCase)
    // - sec_* (tabelas de segurança do ScriptCase)
    
    // Cadastros básicos (sem dependências)
    'sexo' => [
        'pk' => 'id_sexo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'tp_sanguineo' => [
        'pk' => 'id_tp_sanguineo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'cat_cnh' => [
        'pk' => 'id_cat_cnh',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'situacao' => [
        'pk' => 'id_situacao',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'tp_material' => [
        'pk' => 'id_tp_material',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'tp_produto' => [
        'pk' => 'id_tp_produto',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'un_medida' => [
        'pk' => 'id_un_medida',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'gr_produto' => [
        'pk' => 'id_gr_produto',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Estrutura organizacional
    'un_orcamentaria' => [
        'pk' => 'id_un_orcam',
        'timestamp' => 'updated_at',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'setor' => [
        'pk' => 'id_setor',
        'timestamp' => 'updated_at',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'cargo' => [
        'pk' => 'id_cargo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'forma_trabalho' => [
        'pk' => 'id_forma_trabalho',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Cadastros de veículos
    'marca_veiculo' => [
        'pk' => 'id_marca_veiculo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'cor_veiculo' => [
        'pk' => 'id_cor_veiculo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'tp_veiculo' => [
        'pk' => 'id_tp_veiculo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'combustivel_veiculo' => [
        'pk' => 'id_combustivel_veiculo',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Fornecedores e produtos
    'fornecedor' => [
        'pk' => 'id_fornecedor',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'produto' => [
        'pk' => 'id_produto',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Licitações e contratos
    'licitacao' => [
        'pk' => 'id_licitacao',
        'timestamp' => 'data_hora_ins',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'contrato' => [
        'pk' => 'id_contrato',
        'timestamp' => 'data_hora_ins',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'aditamento_combustivel' => [
        'pk' => 'id_aditamento_combustivel',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Preços
    'preco_combustivel' => [
        'pk' => 'id_preco_combustivel',
        'timestamp' => 'data_hora_ins',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Dados principais
    'veiculo' => [
        'pk' => 'id_veiculo',
        'timestamp' => 'updated_at',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'condutor' => [
        'pk' => 'id_condutor',
        'timestamp' => 'updated_at',
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    
    // Requisições e consumos
    'requisicao' => [
        'pk' => 'id_requisicao',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true
    ],
    'consumo_combustivel' => [
        'pk' => 'id_consumo_combustivel',
        'timestamp' => null,
        'campos_sync' => null,
        'tem_id_cliente' => true,
        'truncate_before' => true,
        'ignore_pk' => true
    ]
    
    // NOTA: Tabelas removidas do sistema antigo (2025-12-26):
    // - empresa (substituída por clientes)
    // - empresa_users (substituída por cliente_usuarios)
    // - fornecedor_users (substituída por fornecedor_usuarios)
    // - sc_log (log do ScriptCase - não mais utilizado)
    // - sec_apps, sec_groups, sec_users, sec_settings (segurança do ScriptCase - não mais utilizado)
];

class SincronizadorDados {
    private $connProducao;
    private $connLocal;
    private $log = [];
    private $estatisticas = [];
    
    public function __construct($configProducao, $configLocal) {
        $this->conectar($configProducao, $configLocal);
    }
    
    private function conectar($configProducao, $configLocal) {
        // Conectar à produção
        $this->connProducao = new mysqli(
            $configProducao['host'],
            $configProducao['user'],
            $configProducao['pass'],
            $configProducao['database'],
            $configProducao['port']
        );
        
        if ($this->connProducao->connect_error) {
            throw new Exception("Erro ao conectar à produção: " . $this->connProducao->connect_error);
        }
        
        $this->connProducao->set_charset('utf8mb4');
        $this->log("✓ Conectado à PRODUÇÃO");
        
        // Conectar ao local
        $this->connLocal = new mysqli(
            $configLocal['host'],
            $configLocal['user'],
            $configLocal['pass'],
            $configLocal['database'],
            $configLocal['port']
        );
        
        if ($this->connLocal->connect_error) {
            throw new Exception("Erro ao conectar ao local: " . $this->connLocal->connect_error);
        }
        
        $this->connLocal->set_charset('utf8mb4');
        $this->log("✓ Conectado ao LOCALHOST");
    }
    
    private function log($mensagem) {
        $timestamp = date('Y-m-d H:i:s');
        $this->log[] = "[$timestamp] $mensagem";
        echo "$mensagem\n";
    }
    
    private function tabelaExiste($conn, $nomeTabela) {
        $result = $conn->query("SHOW TABLES LIKE '$nomeTabela'");
        return $result && $result->num_rows > 0;
    }
    
    private function obterCamposTabela($conn, $nomeTabela) {
        $campos = [];
        $result = $conn->query("SHOW COLUMNS FROM $nomeTabela");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $campos[] = $row['Field'];
            }
        }
        return $campos;
    }
    
    private function obterTiposCampos($conn, $nomeTabela) {
        $tipos = [];
        $result = $conn->query("SHOW COLUMNS FROM $nomeTabela");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $tipos[$row['Field']] = $row['Type'];
            }
        }
        return $tipos;
    }
    
    private function contarRegistros($conn, $nomeTabela) {
        $result = $conn->query("SELECT COUNT(*) as total FROM $nomeTabela");
        if ($result) {
            $row = $result->fetch_assoc();
            return (int)$row['total'];
        }
        return 0;
    }
    
    private function gerarHashTabela($conn, $nomeTabela, $campos, $pk) {
        // Ordena pelos campos comuns para garantir comparação consistente
        $camposOrdenados = $campos;
        sort($camposOrdenados);
        $camposList = implode(', ', $camposOrdenados);
        
        // Gera hash MD5 concatenado de todos os registros ordenados pela PK
        $sql = "SELECT MD5(CONCAT_WS('|', $camposList)) as hash FROM $nomeTabela ORDER BY $pk";
        $result = $conn->query($sql);
        
        if (!$result) {
            return null;
        }
        
        $hashGeral = '';
        while ($row = $result->fetch_assoc()) {
            $hashGeral .= $row['hash'];
        }
        
        return md5($hashGeral);
    }
    
    private function tabelasSaoIdenticas($nomeTabela, $campos, $pk) {
        // Contar registros em ambas as tabelas
        $totalProducao = $this->contarRegistros($this->connProducao, $nomeTabela);
        $totalLocal = $this->contarRegistros($this->connLocal, $nomeTabela);
        
        // Se quantidade diferente, não são idênticas
        if ($totalProducao !== $totalLocal) {
            return false;
        }
        
        // Se ambas estão vazias, são idênticas
        if ($totalProducao === 0) {
            return true;
        }
        
        // Gerar hash de ambas as tabelas para comparação rápida
        $hashProducao = $this->gerarHashTabela($this->connProducao, $nomeTabela, $campos, $pk);
        $hashLocal = $this->gerarHashTabela($this->connLocal, $nomeTabela, $campos, $pk);
        
        // Se algum hash falhou, assumir que não são idênticas (vai sincronizar)
        if ($hashProducao === null || $hashLocal === null) {
            return false;
        }
        
        return $hashProducao === $hashLocal;
    }
    
    public function sincronizarTabela($nomeTabela, $config) {
        $this->log("\n" . str_repeat('=', 70));
        $this->log("Sincronizando tabela: $nomeTabela");
        $this->log(str_repeat('=', 70));
        
        $inicio = microtime(true);
        $pk = $config['pk'];
        $timestamp = $config['timestamp'];
        $temIdCliente = $config['tem_id_cliente'];
        $truncateBefore = isset($config['truncate_before']) ? $config['truncate_before'] : false;
        $ignorePk = isset($config['ignore_pk']) ? $config['ignore_pk'] : false;
        
        // Verificar se a tabela existe em ambos os bancos
        if (!$this->tabelaExiste($this->connProducao, $nomeTabela)) {
            $this->log("⚠ Tabela $nomeTabela não existe na PRODUÇÃO - PULANDO");
            return;
        }
        
        if (!$this->tabelaExiste($this->connLocal, $nomeTabela)) {
            $this->log("⚠ Tabela $nomeTabela não existe no LOCALHOST - PULANDO");
            return;
        }
        
        // Obter estrutura da tabela para comparação
        $campos = $this->obterCamposTabela($this->connProducao, $nomeTabela);
        $camposLocal = $this->obterCamposTabela($this->connLocal, $nomeTabela);
        
        // Campos comuns entre produção e local
        $camposComuns = array_intersect($campos, $camposLocal);
        
        // Validar se tabelas são idênticas (exceto consumo_combustivel)
        if ($nomeTabela !== 'consumo_combustivel' && !$truncateBefore) {
            if ($this->tabelasSaoIdenticas($nomeTabela, $camposComuns, $pk)) {
                $totalRegistros = $this->contarRegistros($this->connLocal, $nomeTabela);
                $this->log("✓ Tabela já sincronizada ($totalRegistros registros idênticos) - PULANDO");
                
                // Registrar estatísticas zeradas
                $this->estatisticas[$nomeTabela] = [
                    'total' => $totalRegistros,
                    'novos' => 0,
                    'atualizados' => 0,
                    'erros' => 0,
                    'duracao' => 0,
                    'pulado' => true
                ];
                
                return;
            }
        }
        
        // Truncar tabela se configurado
        if ($truncateBefore) {
            $this->log("🗑️  Executando TRUNCATE TABLE $nomeTabela...");
            if ($this->connLocal->query("TRUNCATE TABLE $nomeTabela")) {
                $this->log("✓ Tabela truncada com sucesso");
            } else {
                $this->log("✗ Erro ao truncar tabela: " . $this->connLocal->error);
                return;
            }
        }
        
        // Verificar se id_cliente existe no local
        $localTemIdCliente = in_array('id_cliente', $camposLocal);
        
        // Obter tipos dos campos locais para conversão
        $tiposLocais = $this->obterTiposCampos($this->connLocal, $nomeTabela);
        
        // Buscar registros da produção
        $sql = "SELECT * FROM $nomeTabela ORDER BY $pk";
        $result = $this->connProducao->query($sql);
        
        if (!$result) {
            $this->log("✗ Erro ao buscar dados: " . $this->connProducao->error);
            return;
        }
        
        $total = $result->num_rows;
        $novos = 0;
        $atualizados = 0;
        $erros = 0;
        
        $this->log("Total de registros na produção: $total");
        
        while ($registro = $result->fetch_assoc()) {
            try {
                // Mapear PK: se a tabela é empresa e PK é id_empresa no origem, mas no destino existe 'id'
                $pkLocal = $pk;
                if ($nomeTabela === 'empresa' && $pk === 'id_empresa' && in_array('id', $camposLocal)) {
                    $pkLocal = 'id';
                }
                
                // Verificar se registro existe no local usando a PK correta
                $idValue = $registro[$pk];
                $existe = $this->registroExiste($this->connLocal, $nomeTabela, $pkLocal, $idValue);
                
                // Preparar dados para inserção/atualização
                $dados = [];
                foreach ($camposComuns as $campo) {
                    // Pular PK se configurado para ignorar (auto_increment)
                    if ($ignorePk && $campo === $pk) {
                        continue;
                    }
                    
                    $valor = $registro[$campo];
                    // Converter valor de acordo com o tipo do campo local
                    if (isset($tiposLocais[$campo])) {
                        $valor = $this->converterValorParaTipo($valor, $tiposLocais[$campo]);
                    }
                    $dados[$campo] = $valor;
                }
                
                // MAPEAR id_empresa → id_cliente se necessário
                // Se o registro tem id_empresa mas a tabela local tem id_cliente
                if (isset($registro['id_empresa']) && in_array('id_cliente', $camposLocal) && !in_array('id_empresa', $camposLocal)) {
                    $dados['id_cliente'] = $registro['id_empresa'];
                }
                
                // Para tabela empresa: mapear id_empresa → id
                if ($nomeTabela === 'empresa' && isset($registro['id_empresa']) && in_array('id', $camposLocal)) {
                    $dados['id'] = $registro['id_empresa'];
                    unset($dados['id_empresa']); // Remover id_empresa se existir
                }
                
                if ($existe) {
                    // Atualizar se houver timestamp e for mais recente
                    if ($timestamp && isset($registro[$timestamp])) {
                        $timestampLocal = $this->obterTimestamp($this->connLocal, $nomeTabela, $pkLocal, $idValue, $timestamp);
                        if ($timestampLocal && $registro[$timestamp] <= $timestampLocal) {
                            continue; // Registro local está atualizado
                        }
                    }
                    
                    if ($this->atualizarRegistro($this->connLocal, $nomeTabela, $pkLocal, $idValue, $dados)) {
                        $atualizados++;
                    } else {
                        $erros++;
                    }
                } else {
                    // Inserir novo registro
                    if ($this->inserirRegistro($this->connLocal, $nomeTabela, $dados)) {
                        $novos++;
                    } else {
                        $erros++;
                    }
                }
                
            } catch (Exception $e) {
                $erros++;
                $this->log("✗ Erro no registro $pk=$idValue: " . $e->getMessage());
            }
        }
        
        $duracao = round(microtime(true) - $inicio, 2);
                // Armazenar estatísticas
        $this->estatisticas[$nomeTabela] = [
            'total' => $total,
            'novos' => $novos,
            'atualizados' => $atualizados,
            'erros' => $erros,
            'duracao' => $duracao
        ];
                $this->log("\n📊 Resultado da sincronização:");
        $this->log("   • Novos: $novos");
        $this->log("   • Atualizados: $atualizados");
        $this->log("   • Erros: $erros");
        $this->log("   • Tempo: {$duracao}s");
        
        $this->estatisticas[$nomeTabela] = [
            'total' => $total,
            'novos' => $novos,
            'atualizados' => $atualizados,
            'erros' => $erros,
            'duracao' => $duracao
        ];
    }
    
    private function converterValorParaTipo($valor, $tipo) {
        // Converter para bit(1) - campos booleanos
        if (strpos($tipo, 'bit(1)') !== false) {
            // Aceita: 0, 1, '0', '1', true, false, 'S', 'N', etc
            if ($valor === null || $valor === '') return "\0";  // binário 0
            if (is_bool($valor)) return $valor ? "\1" : "\0";
            
            // Se for string, verificar o caractere
            if (is_string($valor)) {
                // Pegar primeiro byte
                $byte = ord($valor[0]);
                // Se for 0 ou 1 binário, retornar como binário
                if ($byte === 0 || $byte === 1) {
                    return chr($byte);
                }
                // Se for '0' ou '1' texto
                if ($valor === '0' || $valor === '1') {
                    return intval($valor) === 1 ? "\1" : "\0";
                }
                // Outros textos
                $v = strtoupper(trim($valor));
                if (in_array($v, ['S', 'SIM', 'YES', 'TRUE'])) return "\1";
                return "\0";
            }
            
            // Se for numérico
            if (is_numeric($valor)) {
                return intval($valor) > 0 ? "\1" : "\0";
            }
            
            return "\0";
        }
        
        return $valor;
    }
    
    private function registroExiste($conn, $tabela, $pk, $valor) {
        $stmt = $conn->prepare("SELECT 1 FROM $tabela WHERE $pk = ? LIMIT 1");
        $stmt->bind_param('s', $valor);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    private function obterTimestamp($conn, $tabela, $pk, $valor, $campoTimestamp) {
        $stmt = $conn->prepare("SELECT $campoTimestamp FROM $tabela WHERE $pk = ? LIMIT 1");
        $stmt->bind_param('s', $valor);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            return $row[$campoTimestamp];
        }
        return null;
    }
    
    private function inserirRegistro($conn, $tabela, $dados) {
        $campos = array_keys($dados);
        $valores = array_values($dados);
        
        $camposStr = implode(', ', $campos);
        $placeholders = implode(', ', array_fill(0, count($valores), '?'));
        
        $sql = "INSERT INTO $tabela ($camposStr) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $this->log("✗ Erro ao preparar INSERT: " . $conn->error);
            return false;
        }
        
        $types = str_repeat('s', count($valores));
        $stmt->bind_param($types, ...$valores);
        
        if (!$stmt->execute()) {
            $this->log("✗ Erro ao executar INSERT: " . $stmt->error);
            return false;
        }
        
        return true;
    }
    
    private function atualizarRegistro($conn, $tabela, $pk, $pkValor, $dados) {
        unset($dados[$pk]); // Não atualizar a PK
        
        if (empty($dados)) {
            return true; // Nada para atualizar
        }
        
        $sets = [];
        $valores = [];
        
        foreach ($dados as $campo => $valor) {
            $sets[] = "$campo = ?";
            $valores[] = $valor;
        }
        
        $valores[] = $pkValor; // Para o WHERE
        
        $setsStr = implode(', ', $sets);
        $sql = "UPDATE $tabela SET $setsStr WHERE $pk = ?";
        
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            $this->log("✗ Erro ao preparar UPDATE: " . $conn->error);
            return false;
        }
        
        $types = str_repeat('s', count($valores));
        $stmt->bind_param($types, ...$valores);
        
        if (!$stmt->execute()) {
            $this->log("✗ Erro ao executar UPDATE: " . $stmt->error);
            return false;
        }
        
        return true;
    }
    
    public function replicarEmpresaParaClientes() {
        $this->log("\n" . str_repeat('=', 70));
        $this->log("🔄 Replicando empresa → clientes");
        $this->log(str_repeat('=', 70));
        
        $inicio = microtime(true);
        
        // Buscar todas as empresas da produção
        $sql = "SELECT * FROM empresa ORDER BY id_empresa";
        $result = $this->connProducao->query($sql);
        
        if (!$result) {
            $this->log("✗ Erro ao buscar empresas: " . $this->connProducao->error);
            return;
        }
        
        $total = $result->num_rows;
        $novos = 0;
        $atualizados = 0;
        $erros = 0;
        
        $this->log("Total de empresas na produção: $total");
        
        while ($empresa = $result->fetch_assoc()) {
            try {
                $idEmpresa = $empresa['id_empresa'];
                
                // Verificar se cliente já existe (por CNPJ ou id_empresa)
                $cnpj = $this->connLocal->real_escape_string($empresa['cnpj']);
                $checkSql = "SELECT id, logo_path FROM clientes WHERE cnpj = '$cnpj' OR id = $idEmpresa LIMIT 1";
                $checkResult = $this->connLocal->query($checkSql);
                $clienteExiste = $checkResult && $checkResult->num_rows > 0;
                $clienteData = $clienteExiste ? $checkResult->fetch_assoc() : null;
                $clienteId = $clienteData ? $clienteData['id'] : null;
                $logoPathAtual = $clienteData ? $clienteData['logo_path'] : null;
                
                // Mapear campos empresa → clientes
                // Normalizar UF (pegar só 2 caracteres se for nome completo)
                $uf = $empresa['uf'];
                if (strlen($uf) > 2) {
                    // Converter nome de estado para sigla
                    $estados = [
                        'São Paulo' => 'SP', 'Rio de Janeiro' => 'RJ', 'Minas Gerais' => 'MG',
                        'Espírito Santo' => 'ES', 'Paraná' => 'PR', 'Santa Catarina' => 'SC',
                        'Rio Grande do Sul' => 'RS', 'Bahia' => 'BA', 'Sergipe' => 'SE',
                        'Alagoas' => 'AL', 'Pernambuco' => 'PE', 'Paraíba' => 'PB',
                        'Rio Grande do Norte' => 'RN', 'Ceará' => 'CE', 'Piauí' => 'PI',
                        'Maranhão' => 'MA', 'Pará' => 'PA', 'Amapá' => 'AP', 'Amazonas' => 'AM',
                        'Roraima' => 'RR', 'Acre' => 'AC', 'Rondônia' => 'RO', 'Tocantins' => 'TO',
                        'Goiás' => 'GO', 'Mato Grosso' => 'MT', 'Mato Grosso do Sul' => 'MS',
                        'Distrito Federal' => 'DF'
                    ];
                    $uf = $estados[$uf] ?? substr($uf, 0, 2);
                }
                
                // Verificar se logo_path atual já está no padrão storage/cliente/logo/logo_*
                $manterLogoAtual = false;
                if ($logoPathAtual && preg_match('/^storage\/cliente\/logo\/logo_/', $logoPathAtual)) {
                    $manterLogoAtual = true;
                }
                
                $dados = [
                    'id' => $idEmpresa,
                    'razao_social' => $empresa['razao_social'],
                    'nome_fantasia' => $empresa['nome_fantasia'],
                    'cnpj' => $empresa['cnpj'],
                    'inscricao_estadual' => $empresa['insc_estadual'],
                    'inscricao_municipal' => $empresa['insc_municipal'],
                    'cep' => $empresa['cep'],
                    'logradouro' => $empresa['logradouro'] . ($empresa['nm_logradouro'] ? ' ' . $empresa['nm_logradouro'] : ''),
                    'numero' => $empresa['numero'],
                    'complemento' => $empresa['compl_endereco'],
                    'bairro' => $empresa['bairro'],
                    'cidade' => $empresa['cidade'] ?: $empresa['nm_cidade'],
                    'uf' => $uf,
                    'telefone' => $empresa['tel_principal'],
                    'celular' => $empresa['tel_contato'],
                    'email' => $empresa['email'],
                    'site' => $empresa['url'],
                    'logo_path' => $empresa['logomarca'],
                    'ativo' => $empresa['id_situacao'] == 1 ? 1 : 0
                ];
                
                if ($clienteExiste) {
                    // Atualizar cliente existente
                    $sets = [];
                    $types = '';
                    $valores = [];
                    
                    foreach ($dados as $campo => $valor) {
                        if ($campo != 'id') {
                            // Não atualizar logo_path se já estiver no padrão storage/cliente/logo/logo_*
                            if ($campo == 'logo_path' && $manterLogoAtual) {
                                continue;
                            }
                            $sets[] = "$campo = ?";
                            $types .= 's';
                            $valores[] = $valor;
                        }
                    }
                    
                    $types .= 'i';
                    $valores[] = $clienteId;
                    
                    $sql = "UPDATE clientes SET " . implode(', ', $sets) . " WHERE id = ?";
                    $stmt = $this->connLocal->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param($types, ...$valores);
                        if ($stmt->execute()) {
                            $atualizados++;
                        } else {
                            $erros++;
                            $this->log("✗ Erro ao atualizar cliente id=$clienteId: " . $stmt->error);
                        }
                        $stmt->close();
                    } else {
                        $erros++;
                        $this->log("✗ Erro ao preparar UPDATE: " . $this->connLocal->error);
                    }
                } else {
                    // Inserir novo cliente
                    $campos = array_keys($dados);
                    $placeholders = array_fill(0, count($dados), '?');
                    $types = str_repeat('s', count($dados));
                    $valores = array_values($dados);
                    
                    $sql = "INSERT INTO clientes (" . implode(', ', $campos) . ") VALUES (" . implode(', ', $placeholders) . ")";
                    $stmt = $this->connLocal->prepare($sql);
                    
                    if ($stmt) {
                        $stmt->bind_param($types, ...$valores);
                        if ($stmt->execute()) {
                            $novos++;
                        } else {
                            // Se erro por ID duplicado, tentar atualizar
                            if (strpos($stmt->error, 'Duplicate entry') !== false) {
                                unset($dados['id']);
                                $sets = [];
                                $types = '';
                                $valores = [];
                                
                                foreach ($dados as $campo => $valor) {
                                    $sets[] = "$campo = ?";
                                    $types .= 's';
                                    $valores[] = $valor;
                                }
                                
                                $types .= 'i';
                                $valores[] = $idEmpresa;
                                
                                $sqlUpdate = "UPDATE clientes SET " . implode(', ', $sets) . " WHERE id = ?";
                                $stmtUpdate = $this->connLocal->prepare($sqlUpdate);
                                
                                if ($stmtUpdate && $stmtUpdate->bind_param($types, ...$valores) && $stmtUpdate->execute()) {
                                    $atualizados++;
                                } else {
                                    $erros++;
                                    $this->log("✗ Erro ao inserir/atualizar empresa id=$idEmpresa: " . $stmt->error);
                                }
                            } else {
                                $erros++;
                                $this->log("✗ Erro ao inserir empresa id=$idEmpresa: " . $stmt->error);
                            }
                        }
                        $stmt->close();
                    } else {
                        $erros++;
                        $this->log("✗ Erro ao preparar INSERT: " . $this->connLocal->error);
                    }
                }
            } catch (Exception $e) {
                $erros++;
                $this->log("✗ Erro no registro id_empresa={$empresa['id_empresa']}: " . $e->getMessage());
            }
        }
        
        $duracao = round(microtime(true) - $inicio, 2);
        
        $this->log("\n📊 Resultado da replicação empresa → clientes:");
        $this->log("   • Novos: $novos");
        $this->log("   • Atualizados: $atualizados");
        $this->log("   • Erros: $erros");
        $this->log("   • Tempo: {$duracao}s");
    }
    
    public function gerarRelatorio() {
        $this->log("\n");
        $this->log("╔════════════════════════════════════════════════════════════════════╗");
        $this->log("║                    RELATÓRIO DE SINCRONIZAÇÃO                      ║");
        $this->log("╚════════════════════════════════════════════════════════════════════╝");
        $this->log("");
        
        $totalNovos = 0;
        $totalAtualizados = 0;
        $totalErros = 0;
        $duracaoTotal = 0;
        
        foreach ($this->estatisticas as $tabela => $stats) {
            $totalNovos += $stats['novos'];
            $totalAtualizados += $stats['atualizados'];
            $totalErros += $stats['erros'];
            $duracaoTotal += $stats['duracao'];
        }
        
        $this->log("📊 Resumo Geral:");
        $this->log("   • Tabelas processadas: " . count($this->estatisticas));
        $this->log("   • Total de novos: $totalNovos");
        $this->log("   • Total de atualizados: $totalAtualizados");
        $this->log("   • Total de erros: $totalErros");
        $this->log("   • Tempo total: " . round($duracaoTotal, 2) . "s");
        $this->log("");
        
        if ($totalErros > 0) {
            $this->log("⚠️  Sincronização concluída com erros!");
        } else {
            $this->log("✅ Sincronização concluída com sucesso!");
        }
        
        return $this->log;
    }
    
    public function __destruct() {
        if ($this->connProducao) {
            $this->connProducao->close();
        }
        if ($this->connLocal) {
            $this->connLocal->close();
        }
    }
}

// Executar sincronização
try {
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║   SINCRONIZADOR DE DADOS - PRODUÇÃO → LOCALHOST                   ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n";
    echo "\n";
    
    $sync = new SincronizadorDados($config['producao'], $config['local']);
    
    foreach ($tabelasParaSincronizar as $tabela => $configTabela) {
        $sync->sincronizarTabela($tabela, $configTabela);
        
        // Após sincronizar empresa, replicar para clientes
        if ($tabela === 'empresa') {
            $sync->replicarEmpresaParaClientes();
        }
    }
    
    $log = $sync->gerarRelatorio();
    
    // Salvar log
    $dataHora = date('Y-m-d_H-i-s');
    $arquivoLog = __DIR__ . "/logs/sync_$dataHora.log";
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($arquivoLog, implode("\n", $log));
    echo "\n📄 Log salvo em: $arquivoLog\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERRO FATAL: " . $e->getMessage() . "\n\n";
    exit(1);
}
