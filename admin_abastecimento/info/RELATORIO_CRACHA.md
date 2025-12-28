# Relatório de Crachás - Implementação Completa

## Arquivos Criados

### 1. `/pages/relatorio_cracha.php`
Página principal do relatório de crachás com:
- Interface de filtros (Nome, CPF, CNH, Situação, Tipo)
- Lista de condutores com cards selecionáveis
- Resumo de seleção em painel lateral
- Botões para impressão individual ou em lote

### 2. `/js/relatorio_cracha.js`
JavaScript para gerenciar:
- Carregamento de condutores via AJAX
- Aplicação de filtros em tempo real
- Seleção/desseleção de condutores
- Chamadas para impressão (HTML e PDF)

### 3. `/pages/condutor_cracha_multiplo_pdf.php`
Gerador de PDF para impressão múltipla:
- Formato A4 (210mm x 297mm)
- 4 crachás por folha
- Espaçamento vertical de 5mm entre crachás
- Suporta filtros ou IDs específicos
- Usa o mesmo layout de `condutor_cracha_pdf.php`

### 4. `/api/condutor.php` (modificado)
Nova action `list_for_report`:
- Lista condutores sem paginação
- Filtros: nome, CPF, CNH, situação, tipo (motorista/outro)
- Por padrão retorna apenas condutores ativos
- Filtrado por cliente da sessão

## Características

### Filtros Disponíveis
- **Nome**: Busca parcial (case-insensitive)
- **CPF**: Busca por números (aceita com ou sem máscara)
- **CNH**: Busca parcial
- **Situação**: Dropdown com todas as situações (padrão: apenas ativos)
- **Tipo**: Motoristas / Outros / Todos

### Seleção de Condutores
- Click no card ou checkbox para selecionar/desselecionar
- Botão "Selecionar Todos" (seleciona apenas os filtrados)
- Botão "Limpar Seleção"
- Contador de selecionados vs disponíveis

### Opções de Impressão
1. **Imprimir Selecionados**: Abre PDF no navegador com condutores marcados
2. **Baixar PDF Selecionados**: Download direto do PDF
3. **Imprimir Todos**: Imprime todos os condutores que atendem aos filtros atuais

### Layout do PDF
- **Formato**: ISO A4 (210mm x 297mm)
- **Orientação**: Retrato (Portrait)
- **Crachás por folha**: 4
- **Dimensões do crachá**: 200mm x 63.3mm
- **Espaçamento**: 5mm entre crachás
- **Margens**: 5mm em todos os lados

### Elementos do Crachá (no PDF)
- Imagem de fundo (`assets/cracha.png`)
- Logo do cliente
- Foto do condutor
- Nome do cliente (canto superior direito)
- Nome do condutor (negrito, maiúsculo)
- Matrícula
- Cargo ou "MOTORISTA"
- CNH
- CPF (com máscara 000.000.000-00)
- QR Code com dados do crachá

## Como Adicionar ao Menu

### Opção 1: Via Interface (Recomendado)
1. Acesse o sistema como administrador
2. Vá em **Configurações** > **Gerenciar Menu**
3. Localize o módulo "Condutores"
4. Clique em "Adicionar Submenu"
5. Preencha:
   - **Código**: `relatorio_cracha`
   - **Nome**: `Relatório de Crachás`
   - **Ícone**: `fa-print`
   - **URL**: `relatorio_cracha.php`
   - **Ordem**: (escolha a posição desejada)
6. Salvar

### Opção 2: Via SQL Direto
```sql
-- Inserir submenu em "Condutores"
INSERT INTO submenu (
    codigo, 
    id_modulo, 
    ordem, 
    nome, 
    icone, 
    url, 
    expandido, 
    ativo
)
VALUES (
    'relatorio_cracha',
    (SELECT id FROM modulo WHERE codigo = 'condutores'),
    50, -- Ajuste conforme necessário
    'Relatório de Crachás',
    'fa-print',
    'relatorio_cracha.php',
    0,
    1
);

-- Conceder permissão ao perfil de administrador
INSERT INTO permissoes (id_perfil, modulo, acao)
SELECT id, 'condutores', 'relatorio_cracha'
FROM perfil
WHERE codigo = 'admin';
```

## Regras de Negócio

### Filtro Padrão
- Se nenhum filtro for aplicado, o sistema lista **apenas condutores ativos** (situação = 1)
- Sempre filtra pelo cliente selecionado na sessão

### Crachá Ativo
- Apenas condutores com **via ativa** do crachá são incluídos no PDF
- Via ativa: `id_situacao = 1` e `fim_vigencia IS NULL`

### Segurança
- Requer permissão de acesso ao módulo "condutores"
- Valida token de sessão
- Filtra por cliente da sessão (multi-tenant)
- Sanitização de todos os inputs

## Fluxo de Uso

1. **Acessar**: Menu > Condutores > Relatório de Crachás
2. **Filtrar** (opcional): Preencher campos de filtro e clicar em "Buscar"
3. **Selecionar**: 
   - Clicar em condutores individuais, ou
   - Usar "Selecionar Todos"
4. **Imprimir**:
   - "Imprimir Selecionados" → Abre PDF no navegador
   - "Baixar PDF Selecionados" → Download do arquivo
   - "Imprimir Todos" → Ignora seleção, usa apenas filtros

## Testes Recomendados

1. ✅ Acesso sem cliente selecionado (deve mostrar mensagem)
2. ✅ Filtro por nome (busca parcial)
3. ✅ Filtro por CPF (com e sem máscara)
4. ✅ Filtro por CNH
5. ✅ Seleção individual de condutores
6. ✅ Seleção de todos
7. ✅ Impressão de 1 condutor (1 crachá)
8. ✅ Impressão de 4 condutores (1 página completa)
9. ✅ Impressão de 5+ condutores (múltiplas páginas)
10. ✅ Condutores sem foto (deve mostrar espaço vazio)
11. ✅ Cliente sem logo (deve mostrar espaço vazio)
12. ✅ CPF com e sem máscara (deve formatar corretamente)

## Melhorias Futuras Sugeridas

- [ ] Preview do crachá antes da impressão
- [ ] Exportar lista para Excel
- [ ] Filtro por data de validade da CNH
- [ ] Filtro por cargo específico
- [ ] Opção de ordenação (nome, matrícula, data)
- [ ] Histórico de impressões realizadas
- [ ] Impressão em modo economia (menos tinta)
- [ ] Customização do layout por cliente

## Dependências

- **TCPDF**: Biblioteca PHP para geração de PDF
- **jQuery**: Manipulação DOM e AJAX
- **Bootstrap 5**: Interface responsiva
- **FontAwesome 6**: Ícones
- **API QR Code**: `api.qrserver.com` (para geração de QR codes)

## Arquivos Relacionados

- `/pages/condutor.php` - Gestão de condutores
- `/pages/condutor_cracha.php` - Preview HTML do crachá
- `/pages/condutor_cracha_pdf.php` - PDF individual
- `/api/condutor.php` - API REST para condutores
- `/assets/cracha.png` - Imagem de fundo do crachá
