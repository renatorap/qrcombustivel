# Sistema de Captura de Abastecimento - PostoApp

## Visão Geral

Este documento descreve o sistema de captura de abastecimento implementado para o aplicativo PostoApp, que permite a leitura de QR Codes de veículos e crachás de condutores para registrar abastecimentos de forma rápida e eficiente.

## Data de Implementação
18 de Dezembro de 2025

## Arquivos Criados/Modificados

### 1. Página Principal de Captura
- **Arquivo**: `/postoapp/captura_abastecimento.php`
- **Descrição**: Interface principal para captura de dados de abastecimento com leitura integrada de QR Code
- **Recursos**:
  - Leitura de QR Code usando câmera do dispositivo
  - Formulário completo de captura de dados
  - Validações em tempo real
  - Auto-preenchimento de campos
  - Cálculo automático de valores

### 2. Processamento de QR Code - Condutor
- **Arquivo**: `/postoapp/processar_qr_condutor.php`
- **Descrição**: API endpoint para processar QR Code de crachás de condutores
- **Funcionalidades**:
  - Decodifica QR Code do crachá
  - Valida se condutor está ativo
  - Verifica se QR Code foi emitido
  - Retorna ID e nome do condutor

### 3. Processamento de QR Code - Veículo
- **Arquivo**: `/postoapp/processar_qr_veiculo.php`
- **Descrição**: API endpoint para processar QR Code de veículos
- **Funcionalidades**:
  - Decodifica QR Code do veículo
  - Valida se veículo está ativo
  - Verifica se QR Code foi emitido
  - Retorna ID e placa do veículo

### 4. Busca de Combustíveis do Veículo
- **Arquivo**: `/postoapp/buscar_combustiveis_veiculo.php`
- **Descrição**: API endpoint para buscar tipos de combustível compatíveis com o veículo
- **Funcionalidades**:
  - Lista combustíveis cadastrados para o veículo
  - Suporta veículos mono-combustível e flex
  - Retorna ID e descrição dos combustíveis

### 5. Busca de Preço de Combustível
- **Arquivo**: `/postoapp/buscar_preco_combustivel.php`
- **Descrição**: API endpoint para buscar preço vigente do combustível
- **Funcionalidades**:
  - Consulta tabela de preços vigentes
  - Valida contrato ativo com fornecedor
  - Retorna valor unitário do combustível

### 6. Salvamento de Abastecimento
- **Arquivo**: `/postoapp/salvar_abastecimento.php`
- **Descrição**: Endpoint para registrar abastecimento no banco de dados
- **Validações Implementadas**:
  - Verifica capacidade do tanque
  - Valida abastecimentos recentes (menos de 5 horas)
  - Verifica KM maior que último abastecimento
  - Valida preço vigente no contrato
  - Usa transações para garantir integridade dos dados

## Fluxo de Uso

### 1. Leitura de QR Code do Crachá
1. Usuário clica em "Ler QR Code do Crachá"
2. Câmera é ativada automaticamente
3. Usuário aponta para o QR Code do crachá
4. Sistema decodifica e identifica o condutor
5. Campo "Condutor" é preenchido automaticamente

### 2. Leitura de QR Code do Veículo
1. Usuário clica em "Ler QR Code do Veículo"
2. Câmera é ativada automaticamente
3. Usuário aponta para o QR Code do veículo
4. Sistema decodifica e identifica o veículo
5. Campo "Placa do Veículo" é preenchido automaticamente
6. Sistema carrega combustíveis compatíveis

### 3. Seleção de Combustível

#### Veículo Mono-combustível (Gasolina ou Diesel)
- Campo "Combustível" é preenchido automaticamente
- Campo fica desabilitado
- Preço unitário é carregado automaticamente

#### Veículo Flex (Múltiplos Combustíveis)
- Campo "Combustível" exibe select com opções
- Usuário seleciona o combustível desejado
- Preço unitário é carregado após seleção

### 4. Preenchimento de Dados
- **Data e Hora**: Preenchidos automaticamente (editáveis)
- **KM Atual**: Preenchido manualmente pelo operador
- **Litragem**: Preenchido manualmente pelo operador
- **Valor Unitário**: Preenchido automaticamente (não editável)
- **Valor Total**: Calculado automaticamente (Valor Unitário × Litragem)

### 5. Validações Automáticas
- Litragem não pode exceder capacidade do tanque
- Não permite abastecimento se houver registro há menos de 5 horas
- KM atual deve ser maior que último registrado
- Preço deve estar vigente no contrato

## Regras de Negócio

### Condutor
- Deve estar ativo (`id_situacao = 1`)
- Deve ter QR Code emitido (`qrcode_emitido = 1`)
- QR Code é calculado: `id_condutor × base_condutor`

### Veículo
- Deve estar ativo (`id_situacao = 1`)
- Deve ter QR Code emitido (`qrcode_emitido = 1`)
- QR Code é calculado: `id_veiculo × base_veiculo`

### Combustível
- Deve estar cadastrado na tabela `combustivel_veiculo`
- Preço deve estar vigente no contrato
- Vigência: `inicio_vigencia <= hoje AND (fim_vigencia IS NULL OR fim_vigencia >= hoje)`

### Validações de Abastecimento
1. **Capacidade do Tanque**: Litragem ≤ Capacidade do tanque
2. **Intervalo Mínimo**: 5 horas entre abastecimentos do mesmo veículo
3. **Quilometragem**: KM atual > último KM registrado
4. **Contrato**: Preço deve estar em contrato vigente entre cliente e fornecedor

## Estrutura do Banco de Dados

### Tabelas Envolvidas
- `condutor`: Dados dos condutores
- `veiculo`: Dados dos veículos
- `produto`: Tipos de combustível
- `combustivel_veiculo`: Relação veículo × combustível
- `preco_combustivel`: Preços vigentes
- `aditamento_combustivel`: Aditamentos de contrato
- `contrato`: Contratos entre clientes e fornecedores
- `consumo_combustivel`: Registros de abastecimento
- `fornecedor`: Dados dos fornecedores
- `empresa`: Dados dos clientes (municípios)
- `sec_settings`: Configurações do sistema (base_condutor, base_veiculo)

## Tecnologias Utilizadas

### Frontend
- **HTML5**: Estrutura da página
- **CSS3/Bootstrap 5**: Estilização e responsividade
- **JavaScript/jQuery**: Interatividade e AJAX
- **Html5-qrcode**: Biblioteca para leitura de QR Code via câmera

### Backend
- **PHP 7+**: Processamento server-side
- **PDO**: Acesso ao banco de dados
- **MySQL/MariaDB**: Banco de dados

### Segurança
- **JWT**: Validação de token de sessão
- **PDO Prepared Statements**: Proteção contra SQL Injection
- **Validação de entrada**: Sanitização de dados
- **Controle de sessão**: Timeout e validação

## Permissões de Acesso

- **Grupo 10**: Usuários do fornecedor (postoapp)
- **Grupos 1, 2, 3, 4**: Usuários administrativos (admin_abastecimento)

## Características Especiais

### 1. Leitura Local de QR Code
- Utiliza câmera do dispositivo (smartphone/tablet)
- Prioriza câmera traseira em dispositivos móveis
- Funciona offline após carregamento inicial
- Não requer internet para leitura do QR Code

### 2. Auto-preenchimento Inteligente
- Detecta automaticamente tipo de veículo (mono/flex)
- Preenche campos automaticamente quando possível
- Reduz erros de digitação

### 3. Cálculos Automáticos
- Valor total calculado em tempo real
- Atualiza conforme usuário digita litragem
- Mantém 2 casas decimais para valores monetários

### 4. Validações Preventivas
- Impede registros duplicados
- Valida limites físicos (capacidade tanque)
- Verifica consistência de dados (KM crescente)

### 5. Interface Responsiva
- Adaptável a smartphones, tablets e desktops
- Touch-friendly para dispositivos móveis
- Ícones intuitivos (Bootstrap Icons)

## Melhorias Implementadas

### Em relação ao sistema anterior (pump_capt.php):
1. ✅ Leitura de QR Code integrada na mesma página
2. ✅ Suporte a Html5-qrcode (mais moderno que Instascan)
3. ✅ Interface mais intuitiva com Bootstrap 5
4. ✅ Validações mais robustas
5. ✅ Mensagens de erro/sucesso mais claras
6. ✅ Código mais organizado e documentado
7. ✅ API endpoints separados por responsabilidade
8. ✅ Uso de JSON para respostas AJAX
9. ✅ Transações de banco de dados
10. ✅ Melhor tratamento de erros

## Manutenção e Suporte

### Logs e Debugging
- Mensagens de erro exibidas para o usuário
- Erros de banco de dados capturados via try/catch
- Console do navegador registra eventos de QR Code

### Configurações
As bases para cálculo de QR Code são armazenadas em `sec_settings`:
- `base_condutor`: Multiplicador para ID do condutor
- `base_veiculo`: Multiplicador para ID do veículo

### Extensibilidade
O sistema foi projetado para fácil manutenção:
- Código modular e organizado
- APIs RESTful para comunicação
- Separação de responsabilidades
- Comentários no código

## Considerações de Segurança

1. **Autenticação**: Validação de token JWT em todas as páginas
2. **Autorização**: Verificação de grupo de usuário (grupo 10)
3. **Sanitização**: Todos os inputs são filtrados e sanitizados
4. **Prepared Statements**: Proteção contra SQL Injection
5. **Timeout de Sessão**: Sessão expira após 600 segundos (10 minutos)
6. **HTTPS**: Recomendado para produção

## Próximos Passos (Sugestões)

1. Implementar histórico de abastecimentos por veículo
2. Adicionar relatórios de consumo
3. Notificações push para administradores
4. Backup automático de dados
5. Modo offline completo (PWA)
6. Assinatura digital do condutor
7. Foto do hodômetro
8. Integração com GPS para localização

## Contato e Suporte

Para dúvidas ou problemas, consulte a documentação técnica completa ou entre em contato com a equipe de desenvolvimento.

---

**Última Atualização**: 18 de Dezembro de 2025
**Versão**: 1.0.0
