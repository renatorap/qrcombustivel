# Padronização de Módulos - Sistema QR Combustível

## Visão Geral

O sistema foi padronizado para buscar a estrutura de módulos dinamicamente do banco de dados através da tabela `modulo`.

## Estrutura da Tabela `modulo`

```sql
CREATE TABLE modulo (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  codigo VARCHAR(45) UNIQUE NOT NULL,
  ordem SMALLINT(6) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  icone VARCHAR(45),
  expandido TINYINT(1) DEFAULT 0,
  aplicacao VARCHAR(45),
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Campos

- **id**: Identificador único
- **codigo**: Código de referência para manipulação no backend (ex: 'inicial', 'cadastro', 'seguranca')
- **ordem**: Campo de ordenação para exibição no menu (ordem crescente)
- **nome**: Nome exibido no menu de primeiro nível (ex: 'Dashboard', 'Cadastro', 'Segurança')
- **icone**: Classe do FontAwesome para o ícone (ex: 'fa-home', 'fa-shield-alt')
- **expandido**: Define se o módulo tem submenu expansível (0 = não, 1 = sim)
- **aplicacao**: (Opcional) Referência a aplicação específica
- **ativo**: Status do módulo (0 = inativo, 1 = ativo)

## Módulos Cadastrados

| Ordem | Código        | Nome          | Ícone              | Expandido |
|-------|---------------|---------------|--------------------|-----------|
| 1     | inicial       | Dashboard     | fa-home            | Não       |
| 2     | cadastro      | Cadastro      | fa-folder-open     | Sim       |
| 3     | requisicao    | Requisição    | fa-clipboard-list  | Não       |
| 4     | abastecimento | Abastecimento | fa-gas-pump        | Não       |
| 5     | consulta      | Consulta      | fa-search          | Não       |
| 6     | relatorio     | Relatório     | fa-chart-bar       | Não       |
| 7     | seguranca     | Segurança     | fa-shield-alt      | Sim       |
| 8     | configuracao  | Configuração  | fa-cogs            | Não       |

## Relação com Aplicações

A tabela `aplicacoes` possui o campo `modulo` (VARCHAR) que faz referência ao campo `codigo` da tabela `modulo`.

### Mapeamento Atualizado

| Aplicação      | Módulo Anterior | Módulo Atual  |
|----------------|-----------------|---------------|
| dashboard      | principal       | inicial       |
| veiculos       | frota           | cadastro      |
| clientes       | frota           | cadastro      |
| usuarios       | seguranca       | seguranca     |
| grupos         | seguranca       | seguranca     |
| aplicacoes     | seguranca       | seguranca     |
| permissoes     | seguranca       | seguranca     |
| auditoria      | seguranca       | seguranca     |
| sincronizacao  | seguranca       | seguranca     |
| configuracoes  | sistema         | configuracao  |
| abastecimentos | abastecimento   | abastecimento |

## Implementação no Código

### AccessControl Class

**Novo Método: `getModulos()`**

```php
public function getModulos() {
    $sql = "SELECT id, codigo, nome, icone, expandido, ordem
            FROM modulo 
            WHERE ativo = 1 
            ORDER BY ordem";
    
    $result = $this->db->query($sql);
    
    $modulos = [];
    while ($row = $result->fetch_assoc()) {
        $modulos[$row['codigo']] = [
            'id' => $row['id'],
            'codigo' => $row['codigo'],
            'nome' => $row['nome'],
            'icone' => $row['icone'] ?? 'fa-folder',
            'expandido' => (bool) $row['expandido'],
            'ordem' => $row['ordem']
        ];
    }
    
    return $modulos;
}
```

### Sidebar (includes/sidebar.php)

**Antes:**
```php
// Estrutura do menu hardcoded
$menuEstrutura = [
    'principal' => [
        'titulo' => 'Principal',
        'icone' => 'fa-home',
        'expandido' => false
    ],
    // ... mais módulos
];
```

**Depois:**
```php
// Buscar módulos do banco de dados
$menuEstrutura = $accessControl->getModulos();

// Array retorna estrutura:
// [
//   'inicial' => [
//     'id' => 1,
//     'codigo' => 'inicial',
//     'nome' => 'Dashboard',
//     'icone' => 'fa-home',
//     'expandido' => false,
//     'ordem' => 1
//   ],
//   ...
// ]
```

## Vantagens da Padronização

### 1. **Flexibilidade**
- Adicionar novos módulos sem alterar código
- Reordenar módulos através do campo `ordem`
- Ativar/desativar módulos via banco de dados

### 2. **Manutenção**
- Centralização da estrutura de módulos
- Fácil atualização de nomes e ícones
- Controle de versão simplificado

### 3. **Escalabilidade**
- Suporte a múltiplos módulos sem limite
- Estrutura preparada para futuras expansões
- Facilita criação de novos recursos

## Como Adicionar um Novo Módulo

### 1. Inserir na Tabela `modulo`

```sql
INSERT INTO modulo (codigo, ordem, nome, icone, expandido, ativo) 
VALUES ('novo_modulo', 9, 'Novo Módulo', 'fa-star', 0, 1);
```

### 2. Associar Aplicações ao Módulo

```sql
UPDATE aplicacoes 
SET modulo = 'novo_modulo' 
WHERE codigo IN ('app1', 'app2');
```

### 3. Criar Permissões

```sql
-- As permissões são criadas automaticamente na tabela permissoes_grupo
-- para cada aplicação vinculada ao módulo
```

## Validação e Testes

### Verificar Módulos Ativos

```sql
SELECT * FROM modulo WHERE ativo = 1 ORDER BY ordem;
```

### Verificar Aplicações por Módulo

```sql
SELECT m.nome as modulo, a.nome as aplicacao, a.codigo
FROM modulo m
LEFT JOIN aplicacoes a ON a.modulo = m.codigo
WHERE m.ativo = 1
ORDER BY m.ordem, a.ordem;
```

### Testar Permissões

```sql
SELECT u.login, g.nome as grupo, m.nome as modulo, a.nome as aplicacao
FROM usuarios u
JOIN grupos g ON u.grupo_id = g.id
JOIN permissoes_grupo pg ON pg.grupo_id = g.id
JOIN aplicacoes a ON a.id = pg.aplicacao_id
JOIN modulo m ON m.codigo = a.modulo
WHERE u.id = ? AND pg.pode_acessar = 1
ORDER BY m.ordem, a.ordem;
```

## Migrações Realizadas

### Script 1: Atualizar Ícones

```sql
UPDATE modulo SET icone = 'fa-home' WHERE codigo = 'inicial';
UPDATE modulo SET icone = 'fa-folder-open' WHERE codigo = 'cadastro';
UPDATE modulo SET icone = 'fa-clipboard-list' WHERE codigo = 'requisicao';
UPDATE modulo SET icone = 'fa-gas-pump' WHERE codigo = 'abastecimento';
UPDATE modulo SET icone = 'fa-search' WHERE codigo = 'consulta';
UPDATE modulo SET icone = 'fa-chart-bar' WHERE codigo = 'relatorio';
UPDATE modulo SET icone = 'fa-shield-alt' WHERE codigo = 'seguranca';
UPDATE modulo SET icone = 'fa-cogs' WHERE codigo = 'configuracao';
UPDATE modulo SET expandido = 1 WHERE codigo IN ('cadastro', 'seguranca');
```

### Script 2: Atualizar Referências

```sql
UPDATE aplicacoes SET modulo = 'inicial' WHERE modulo = 'principal';
UPDATE aplicacoes SET modulo = 'cadastro' WHERE modulo = 'frota';
UPDATE aplicacoes SET modulo = 'configuracao' WHERE modulo = 'sistema';
```

## Arquivos Modificados

1. **config/access_control.php**
   - Adicionado método `getModulos()`
   
2. **includes/sidebar.php**
   - Substituído array hardcoded por chamada `$accessControl->getModulos()`
   - Atualizado referências de 'frota' para 'cadastro'
   - Atualizado referências de 'principal' para 'inicial'
   - Atualizado referências de 'sistema' para 'configuracao'

## Compatibilidade

- ✅ Mantém compatibilidade com sistema de permissões existente
- ✅ Não requer alteração em permissões já configuradas
- ✅ Menu se adapta automaticamente às permissões do usuário
- ✅ Suporta módulos com e sem submenu

## Data de Implementação

19 de novembro de 2025

## Autor

Sistema QR Combustível - Versão 2.0
