# Implementação do Módulo de Condutores

## Resumo
Módulo completo para cadastro e manutenção de condutores (motoristas) de veículos.

## Arquivos Criados

### Backend
- **`/api/condutor.php`**: API REST completa com 5 operações (list, get, create, update, delete)
- **`/api/condutor_selects.php`**: API para popular dropdowns do formulário (5 endpoints)

### Frontend
- **`/pages/condutor.php`**: Página principal com tabela e modal de cadastro/edição
- **`/js/condutor.js`**: JavaScript para interações CRUD

## Estrutura do Banco de Dados

### Tabela: `condutor`
21 campos no total:

**Chave Primária:**
- `id_condutor` (INT, PK, AUTO_INCREMENT)

**Chaves Estrangeiras:**
- `id_cliente` (INT, FK) - Multicliente
- `id_cat_cnh` (INT, FK) - Categoria da CNH
- `id_sexo` (INT, FK) - Sexo
- `id_tp_sanguineo` (INT, FK) - Tipo Sanguíneo
- `id_cargo` (INT, FK) - Cargo
- `id_situacao` (INT, FK) - Situação

**Campos Obrigatórios:**
- `nome` (VARCHAR 45) *
- `cnh` (VARCHAR 15) *
- `validade_cnh` (DATE) *
- `e_condutor` (BIT) - Default: 1

**Campos Opcionais:**
- `matricula` (VARCHAR 45)
- `data_nascimento` (DATE)
- `foto` (VARCHAR 255) - Path da foto
- `rg` (VARCHAR 15)
- `cpf` (VARCHAR 15)
- `telefone` (VARCHAR 15)
- `email` (VARCHAR 25)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

**Campo Excluído:**
- `id_empresa` - Não utilizado conforme solicitação

## Tabelas Relacionadas

### cat_cnh (Categoria CNH)
- `id_cat_cnh` (PK)
- `codigo` (VARCHAR) - A, B, C, D, E, AB, AC, AD, AE

### sexo
- `id_sexo` (PK)
- `descricao` (VARCHAR) - Masculino, Feminino

### tp_sanguineo (Tipo Sanguíneo)
- `id_tp_sanguineo` (PK)
- `codigo` (VARCHAR) - A+, A-, B+, B-, AB+, AB-, O+, O-

### cargo
- `id_cargo` (PK)
- `descricao` (VARCHAR)
- `id_cliente` (FK) - Filtrado por cliente

### situacao
- `id_situacao` (PK)
- `descricao` (VARCHAR) - Ativo, Inativo, Férias, Afastado

## Funcionalidades Implementadas

### API condutor.php

#### 1. `action=list` (GET)
- Lista paginada de condutores (10 por página)
- Filtrado por `id_cliente` da sessão
- Busca case-insensitive em: nome, cpf, cnh, matricula
- LEFT JOINs com todas as tabelas relacionadas
- Retorna nomes legíveis das categorias

**Resposta:**
```json
{
  "success": true,
  "data": [...],
  "totalPages": 5,
  "currentPage": 1,
  "totalItems": 47
}
```

#### 2. `action=get` (GET)
- Retorna condutor específico por ID
- Valida `id_cliente` para segurança multicliente
- Inclui todos os nomes das tabelas relacionadas

**Parâmetros:**
- `id`: ID do condutor

#### 3. `action=create` (POST)
- Cria novo condutor
- Auto-preenche `id_cliente` da sessão
- Valida campos obrigatórios
- Suporta NULL para campos opcionais

**Campos Obrigatórios:**
- nome
- cnh
- validade_cnh

#### 4. `action=update` (POST)
- Atualiza condutor existente
- Valida ownership por `id_cliente`
- Atualiza apenas campos fornecidos

**Parâmetros:**
- `id`: ID do condutor
- Demais campos opcionais

#### 5. `action=delete` (POST)
- Deleta condutor
- Valida `id_cliente` antes de deletar

**Parâmetros:**
- `id`: ID do condutor

### API condutor_selects.php

5 endpoints para popular dropdowns:

1. **`action=categorias`**: Lista categorias CNH (codigo)
2. **`action=sexos`**: Lista sexos (descricao)
3. **`action=tipos_sanguineos`**: Lista tipos sanguíneos (codigo)
4. **`action=cargos`**: Lista cargos filtrados por cliente (descricao)
5. **`action=situacoes`**: Lista situações (descricao)

**Formato de Resposta Padrão:**
```json
{
  "success": true,
  "data": [
    {"id": 1, "nome": "Categoria A"},
    {"id": 2, "nome": "Categoria B"}
  ]
}
```

## Interface do Usuário

### Página Principal (condutor.php)

**Componentes:**
- Campo de busca (nome, CPF, CNH, matrícula)
- Botão "Novo Condutor"
- Tabela com 7 colunas:
  1. Nome
  2. CPF
  3. CNH
  4. Validade CNH (com alertas de vencimento)
  5. Cargo
  6. Situação
  7. Ações (Visualizar, Editar, Excluir)

**Alertas de Vencimento:**
- 🔴 Vermelho: CNH vencida
- 🟡 Amarelo: CNH vence em 30 dias ou menos

### Modal de Cadastro/Edição

**3 Abas:**

#### 1. Dados Pessoais
- Nome Completo *
- Data de Nascimento
- Sexo
- Tipo Sanguíneo
- Telefone
- E-mail

#### 2. Documentação
- CPF
- RG
- CNH *
- Categoria CNH
- Validade CNH *

#### 3. Dados Profissionais
- Matrícula
- Cargo
- Situação
- É Condutor de Veículo (checkbox)

### Modal de Visualização
- Exibe todos os dados do condutor em modo somente leitura
- Formata datas para DD/MM/YYYY

## JavaScript (condutor.js)

### Funções Principais

**loadSelects()**
- Carrega todos os 5 dropdowns ao abrir modal
- Chamadas AJAX paralelas para performance

**loadCondutores()**
- Lista condutores com paginação
- Suporta busca
- Renderiza tabela com alertas de vencimento

**saveCondutor()**
- Cria ou atualiza condutor
- Validações client-side
- Tratamento de NULL para campos opcionais

**visualizar(id)**
- Carrega e exibe dados no modal de visualização
- Formata datas

**editar(id)**
- Carrega dados do condutor no formulário
- Pré-seleciona dropdowns
- Ajusta título do modal

**excluir(id)**
- Confirmação antes de deletar
- Recarrega lista após exclusão

**renderTable(condutores)**
- Renderiza tabela com formatação
- Adiciona ícones de alerta para CNH vencida/vencendo
- Respeita permissões do usuário

**formatarData(data)**
- Converte YYYY-MM-DD para DD/MM/YYYY

## Controle de Acesso

### Permissões Verificadas:
- `acessar`: Acesso à página
- `criar`: Botão "Novo Condutor"
- `visualizar`: Botão "Visualizar"
- `editar`: Botão "Editar"
- `excluir`: Botão "Excluir"

### Multicliente:
- Todos os condutores são filtrados por `id_cliente` da sessão
- Impossível visualizar/editar condutores de outros clientes
- Campo `id_cliente` auto-preenchido no create

## Configuração do Banco de Dados

### Inserções Realizadas:

```sql
-- Aplicação
INSERT INTO aplicacoes (codigo, nome, url, modulo, icone, ordem, ativo) 
VALUES ('condutores', 'Condutores', 'pages/condutor.php', 'cadastro', 'fa-user-tie', 3, 1);

-- Permissões (Grupo Admin)
INSERT INTO permissoes_grupo (grupo_id, aplicacao_id, pode_acessar, pode_criar, pode_visualizar, pode_editar, pode_excluir, pode_exportar, pode_importar) 
VALUES (1, 30, 1, 1, 1, 1, 1, 1, 1);

-- Submenu
INSERT INTO submenu (codigo, nome, icone, modulo_id, aplicacao_id, expandido, ordem, ativo) 
VALUES ('condutores_sub', 'Condutores', 'fa-user-tie', 2, 30, 0, 3, 1);
```

## Próximas Melhorias (Opcionais)

1. **Upload de Foto**: Implementar upload e exibição da foto do condutor
2. **Dashboard de CNH**: Adicionar widget no dashboard com CNHs a vencer
3. **Notificações**: Alertas automáticos de vencimento de CNH
4. **Relatórios**: Exportação de lista de condutores (PDF/Excel)
5. **Histórico**: Vincular condutores com abastecimentos realizados
6. **Máscaras**: Adicionar máscaras de input para CPF, CNH, telefone
7. **Validação CPF**: Validação de dígitos verificadores do CPF
8. **Integração Veículos**: Associar condutores autorizados por veículo

## Padrão Seguido

Este módulo segue o mesmo padrão arquitetural de `veiculo.php` e `cliente.php`:
- ✅ Multi-tab modal com Bootstrap 5.3
- ✅ API REST com switch cases
- ✅ Controle de acesso integrado
- ✅ Multicliente por sessão
- ✅ Paginação e busca
- ✅ Validações client e server-side
- ✅ Tratamento de NULL consistente
- ✅ Logs de auditoria (via AccessControl)

## Testes Recomendados

1. ✅ Criar condutor apenas com campos obrigatórios
2. ✅ Criar condutor com todos os campos preenchidos
3. ✅ Editar condutor alterando diferentes campos
4. ✅ Buscar por nome, CPF, CNH, matrícula
5. ✅ Verificar paginação com 10+ registros
6. ✅ Verificar alertas de vencimento de CNH
7. ✅ Testar exclusão com confirmação
8. ✅ Verificar filtro multicliente (trocar cliente na sessão)
9. ✅ Testar permissões (remover permissões do grupo)
10. ✅ Validar campos obrigatórios no formulário

## Status

✅ **IMPLEMENTAÇÃO COMPLETA**

Todos os arquivos criados, banco configurado, e sistema pronto para uso.
