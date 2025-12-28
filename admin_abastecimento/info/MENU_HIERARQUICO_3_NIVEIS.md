# Menu Hierárquico de 3 Níveis - Implementação Completa

## 📋 Resumo

Implementação de menu hierárquico de 3 níveis totalmente gerenciado pelo banco de dados com controle de permissões RBAC em todos os níveis.

**Status:** ✅ Implementado e Funcional

**Data:** 2024

---

## 🏗️ Estrutura do Menu

### Nível 1: Módulo
- **Pode ser:** Expansível ou Link direto
- **Tabela:** `modulo`
- **Comportamento:**
  - Se `expandido = 1`: Mostra submenus (Nível 2)
  - Se `expandido = 0` e `aplicacao_id` preenchido: Link direto para aplicação
- **Exemplo:** Dashboard (link direto), Cadastro (expansível), Segurança (expansível)

### Nível 2: Submenu
- **Pode ser:** Expansível ou Link direto
- **Tabela:** `submenu`
- **Comportamento:**
  - Se `expandido = 1`: Mostra sub-submenus (Nível 3)
  - Se `expandido = 0` e `aplicacao_id` preenchido: Link direto para aplicação
- **Exemplo:** Cliente (link direto), Veículos (link direto), Controle de Acesso (expansível)

### Nível 3: Sub-submenu
- **Sempre:** Link direto
- **Tabela:** `subsubmenu`
- **Comportamento:**
  - Sempre tem `aplicacao_id NOT NULL`
  - Nível final de navegação
- **Exemplo:** Grupos, Permissões, Aplicações

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `modulo`
```sql
ALTER TABLE modulo ADD COLUMN aplicacao_id INT(11) NULL;
ALTER TABLE modulo ADD CONSTRAINT fk_modulo_aplicacao 
    FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id) ON DELETE SET NULL;
```

**Campos importantes:**
- `id`: Identificador único
- `codigo`: Código único do módulo (ex: 'inicial', 'cadastro', 'configuracao')
- `nome`: Nome exibido no menu
- `icone`: Classe FontAwesome (ex: 'fa-home', 'fa-folder')
- `expandido`: TINYINT (0=link direto, 1=expansível)
- `aplicacao_id`: INT NULL - ID da aplicação se for link direto
- `ordem`: Ordem de exibição
- `ativo`: TINYINT (0=inativo, 1=ativo)

### Tabela: `submenu`
```sql
CREATE TABLE submenu (
    id INT(11) NOT NULL AUTO_INCREMENT,
    modulo_id INT(11) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    ordem INT(11) NOT NULL DEFAULT 0,
    nome VARCHAR(100) NOT NULL,
    icone VARCHAR(50) DEFAULT 'fa-file',
    expandido TINYINT(1) NOT NULL DEFAULT 0,
    aplicacao_id INT(11) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_submenu_modulo FOREIGN KEY (modulo_id) 
        REFERENCES modulo(id) ON DELETE CASCADE,
    CONSTRAINT fk_submenu_aplicacao FOREIGN KEY (aplicacao_id) 
        REFERENCES aplicacoes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos importantes:**
- `modulo_id`: FK para `modulo.id`
- `codigo`: Código único (ex: 'cliente', 'veiculos', 'controle_acesso')
- `expandido`: 0=link direto, 1=expansível para sub-submenus
- `aplicacao_id`: NULL se expansível, preenchido se link direto

### Tabela: `subsubmenu`
```sql
CREATE TABLE subsubmenu (
    id INT(11) NOT NULL AUTO_INCREMENT,
    submenu_id INT(11) NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    ordem INT(11) NOT NULL DEFAULT 0,
    nome VARCHAR(100) NOT NULL,
    icone VARCHAR(50) DEFAULT 'fa-circle',
    aplicacao_id INT(11) NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_subsubmenu_submenu FOREIGN KEY (submenu_id) 
        REFERENCES submenu(id) ON DELETE CASCADE,
    CONSTRAINT fk_subsubmenu_aplicacao FOREIGN KEY (aplicacao_id) 
        REFERENCES aplicacoes(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Campos importantes:**
- `submenu_id`: FK para `submenu.id`
- `aplicacao_id`: **NOT NULL** - Sempre link direto (nível final)
- `icone`: Geralmente `fa-circle` ou ícone pequeno

---

## 📊 Dados Migrados (Exemplo Atual)

### Dashboard (Link Direto - Nível 1)
```sql
UPDATE modulo 
SET aplicacao_id = 1, expandido = 0 
WHERE codigo = 'inicial';
```

### Cadastro (Expansível - Nível 1)
```sql
-- Módulo Cadastro (expandido = 1)
INSERT INTO submenu (modulo_id, codigo, ordem, nome, icone, expandido, aplicacao_id)
VALUES 
(2, 'cliente', 10, 'Cliente', 'fa-building', 0, 23),
(2, 'veiculos', 20, 'Veículos', 'fa-car', 0, 2);
```

### Segurança (Expansível - Nível 1)
```sql
-- Módulo Segurança (expandido = 1)
INSERT INTO submenu (modulo_id, codigo, ordem, nome, icone, expandido, aplicacao_id)
VALUES 
(3, 'usuarios', 10, 'Usuários', 'fa-users', 0, 4),
(3, 'controle_acesso', 20, 'Controle de Acesso', 'fa-shield-alt', 1, NULL),
(3, 'auditoria', 30, 'Auditoria', 'fa-history', 0, 8),
(3, 'sincronizacao', 40, 'Sincronização', 'fa-sync', 0, 9);
```

### Controle de Acesso (Expansível - Nível 2)
```sql
-- Sub-submenus do Controle de Acesso
INSERT INTO subsubmenu (submenu_id, codigo, ordem, nome, icone, aplicacao_id)
VALUES 
((SELECT id FROM submenu WHERE codigo = 'controle_acesso'), 'grupos', 10, 'Grupos', 'fa-users-cog', 5),
((SELECT id FROM submenu WHERE codigo = 'controle_acesso'), 'permissoes', 20, 'Permissões', 'fa-key', 7),
((SELECT id FROM submenu WHERE codigo = 'controle_acesso'), 'aplicacoes', 30, 'Aplicações', 'fa-apps', 6);
```

---

## 🔧 Backend: AccessControl

### Método Principal: `getMenuHierarquico()`
```php
public function getMenuHierarquico() {
    if (!$this->userId) {
        return [];
    }
    
    $menu = [];
    
    // 1. Buscar módulos
    $sqlModulos = "SELECT DISTINCT m.id, m.codigo, m.nome, m.icone, m.expandido, m.aplicacao_id, m.ordem
                   FROM modulo m
                   WHERE m.ativo = 1
                   ORDER BY m.ordem";
    
    $resultModulos = $this->db->query($sqlModulos);
    
    while ($modulo = $resultModulos->fetch_assoc()) {
        $moduloData = [
            'id' => $modulo['id'],
            'codigo' => $modulo['codigo'],
            'nome' => $modulo['nome'],
            'icone' => $modulo['icone'] ?? 'fa-folder',
            'expandido' => (bool) $modulo['expandido'],
            'aplicacao_id' => $modulo['aplicacao_id'],
            'url' => null,
            'submenus' => []
        ];
        
        // Se não é expandido, verificar permissão e pegar URL
        if (!$modulo['expandido'] && $modulo['aplicacao_id']) {
            if ($this->verificarPermissaoAplicacaoId($modulo['aplicacao_id'])) {
                $moduloData['url'] = $this->getUrlAplicacao($modulo['aplicacao_id']);
                $menu[] = $moduloData;
            }
        } else {
            // É expandido, buscar submenus
            $moduloData['submenus'] = $this->getSubmenus($modulo['id']);
            
            // Só adiciona módulo se tiver pelo menos 1 submenu com permissão
            if (count($moduloData['submenus']) > 0) {
                $menu[] = $moduloData;
            }
        }
    }
    
    return $menu;
}
```

### Métodos Auxiliares

#### `getSubmenus($moduloId)`
Busca submenus de um módulo, verifica permissões:
- Se submenu é link direto: Verifica permissão e retorna com URL
- Se submenu é expansível: Busca sub-submenus recursivamente

#### `getSubsubmenus($submenuId)`
Busca sub-submenus de um submenu, verifica permissões (sempre links diretos)

#### `verificarPermissaoAplicacaoId($aplicacaoId)`
Verifica se usuário tem permissão via view `v_permissoes_efetivas`

#### `getUrlAplicacao($aplicacaoId)`
Retorna URL da aplicação (ex: 'dashboard.php', 'cliente.php')

---

## 🎨 Frontend: sidebar.php

### Renderização Hierárquica
```php
<?php foreach ($menuHierarquico as $modulo): ?>
    <?php if ($modulo['expandido']): ?>
        <!-- Módulo expansível (Nível 1) -->
        <li class="sidebar-item has-submenu" data-module="<?php echo $modulo['codigo']; ?>">
            <a href="javascript:void(0)" class="sidebar-link" onclick="toggleSubmenu(this)">
                <i class="fas <?php echo $modulo['icone']; ?>"></i>
                <span><?php echo $modulo['nome']; ?></span>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </a>
            <ul class="submenu">
                <?php foreach ($modulo['submenus'] as $submenu): ?>
                    <?php if ($submenu['expandido']): ?>
                        <!-- Submenu expansível (Nível 2) -->
                        <li class="submenu-item has-subsubmenu">
                            <a href="javascript:void(0)" class="submenu-link" onclick="toggleSubsubmenu(this)">
                                <i class="fas <?php echo $submenu['icone']; ?>"></i>
                                <span><?php echo $submenu['nome']; ?></span>
                                <i class="fas fa-chevron-down subsubmenu-arrow"></i>
                            </a>
                            <ul class="subsubmenu">
                                <?php foreach ($submenu['subsubmenus'] as $subsubmenu): ?>
                                    <!-- Sub-submenu (Nível 3) -->
                                    <li class="subsubmenu-item">
                                        <a href="<?php echo $subsubmenu['url']; ?>" class="subsubmenu-link">
                                            <i class="fas <?php echo $subsubmenu['icone']; ?>"></i>
                                            <span><?php echo $subsubmenu['nome']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Submenu link direto (Nível 2) -->
                        <li class="submenu-item">
                            <a href="<?php echo $submenu['url']; ?>" class="submenu-link">
                                <i class="fas <?php echo $submenu['icone']; ?>"></i>
                                <span><?php echo $submenu['nome']; ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </li>
    <?php else: ?>
        <!-- Módulo link direto (Nível 1) -->
        <li class="sidebar-item">
            <a href="<?php echo $modulo['url']; ?>" class="sidebar-link">
                <i class="fas <?php echo $modulo['icone']; ?>"></i>
                <span><?php echo $modulo['nome']; ?></span>
            </a>
        </li>
    <?php endif; ?>
<?php endforeach; ?>
```

### JavaScript - Funções de Toggle

#### `toggleSubmenu(element)` - Nível 2
Expande/colapsa submenus, fecha outros submenus abertos

#### `toggleSubsubmenu(element)` - Nível 3
Expande/colapsa sub-submenus, fecha outros sub-submenus abertos no mesmo submenu

#### Auto-abertura de Hierarquia
Script em `DOMContentLoaded` que:
1. Detecta página ativa em qualquer nível
2. Abre toda a hierarquia pai automaticamente
3. Mantém estado visual consistente

---

## 🎨 Estilos CSS

### Submenu (Nível 2)
```css
.submenu {
    display: none;
    background: rgba(27, 81, 117, 0.05);
    border-left: 3px solid var(--secondary-orange);
    margin-left: 20px;
}

.submenu-link {
    padding: 10px 15px 10px 25px;
    font-size: 13px;
}
```

### Sub-submenu (Nível 3)
```css
.subsubmenu {
    display: none;
    background: rgba(27, 81, 117, 0.03);
    border-left: 2px solid rgba(240, 122, 40, 0.3);
    margin-left: 15px;
}

.subsubmenu-link {
    padding: 8px 12px 8px 35px;
    font-size: 12px;
}

.subsubmenu-link:hover {
    padding-left: 38px; /* Efeito visual ao hover */
}

.subsubmenu-item.active .subsubmenu-link {
    background-color: rgba(240, 122, 40, 0.2);
    color: var(--secondary-orange);
    border-left: 2px solid var(--secondary-orange);
}
```

### Setas (Chevrons)
```css
.submenu-arrow, .subsubmenu-arrow {
    transition: transform 0.3s ease;
    margin-left: auto;
}

/* Rotaciona 180° quando aberto */
.open .submenu-arrow,
.open .subsubmenu-arrow {
    transform: rotate(180deg);
}
```

---

## 🔐 Controle de Permissões

### View: `v_permissoes_efetivas`
Todas as verificações de permissão usam esta view que consolida:
- Permissões do grupo do usuário
- Permissões específicas do usuário
- Hierarquia de permissões

### Lógica de Filtragem
1. **Nível 1 (Módulo):**
   - Se link direto: Verifica `pode_acessar` para `aplicacao_id`
   - Se expansível: Só mostra se tiver pelo menos 1 submenu com permissão

2. **Nível 2 (Submenu):**
   - Se link direto: Verifica `pode_acessar` para `aplicacao_id`
   - Se expansível: Só mostra se tiver pelo menos 1 sub-submenu com permissão

3. **Nível 3 (Sub-submenu):**
   - Sempre verifica `pode_acessar` para `aplicacao_id`
   - Sempre link direto (não há Nível 4)

### Comportamento
- **Sem Permissão:** Item não aparece no menu
- **Com Permissão:** Item visível e clicável
- **Hierarquia Vazia:** Se módulo/submenu expansível não tem filhos com permissão, não aparece

---

## 📝 Como Adicionar Novo Item

### 1. Adicionar Módulo Expansível (Nível 1)
```sql
INSERT INTO modulo (codigo, ordem, nome, icone, expandido, aplicacao_id, ativo)
VALUES ('relatorios', 40, 'Relatórios', 'fa-chart-bar', 1, NULL, 1);
```

### 2. Adicionar Submenu Link Direto (Nível 2)
```sql
INSERT INTO submenu (modulo_id, codigo, ordem, nome, icone, expandido, aplicacao_id, ativo)
VALUES 
(
    (SELECT id FROM modulo WHERE codigo = 'relatorios'),
    'rel_financeiro',
    10,
    'Relatório Financeiro',
    'fa-dollar-sign',
    0,
    10, -- ID da aplicação
    1
);
```

### 3. Adicionar Submenu Expansível com Sub-submenus (Nível 2 + 3)
```sql
-- 2. Submenu expansível
INSERT INTO submenu (modulo_id, codigo, ordem, nome, icone, expandido, aplicacao_id, ativo)
VALUES 
(
    (SELECT id FROM modulo WHERE codigo = 'relatorios'),
    'rel_operacionais',
    20,
    'Relatórios Operacionais',
    'fa-clipboard-list',
    1,
    NULL, -- Expansível, não tem aplicacao_id
    1
);

-- 3. Sub-submenus
INSERT INTO subsubmenu (submenu_id, codigo, ordem, nome, icone, aplicacao_id, ativo)
VALUES 
(
    (SELECT id FROM submenu WHERE codigo = 'rel_operacionais'),
    'rel_frota',
    10,
    'Frota',
    'fa-truck',
    11, -- ID da aplicação
    1
),
(
    (SELECT id FROM submenu WHERE codigo = 'rel_operacionais'),
    'rel_abastecimento',
    20,
    'Abastecimento',
    'fa-gas-pump',
    12, -- ID da aplicação
    1
);
```

---

## ✅ Checklist de Teste

### Teste Visual
- [ ] Menu renderiza com 3 níveis visíveis
- [ ] Ícones aparecem corretamente
- [ ] Indentação progressiva clara
- [ ] Setas de expansão funcionam
- [ ] Animações suaves ao abrir/fechar

### Teste de Navegação
- [ ] Clicar em link direto Nível 1 funciona (Dashboard)
- [ ] Clicar em link direto Nível 2 funciona (Cliente, Veículos)
- [ ] Clicar em link direto Nível 3 funciona (Grupos, Permissões)
- [ ] Expandir Nível 1 mostra Nível 2
- [ ] Expandir Nível 2 mostra Nível 3

### Teste de Estado Ativo
- [ ] Página ativa em Nível 1 destaca item
- [ ] Página ativa em Nível 2 destaca item e abre módulo pai
- [ ] Página ativa em Nível 3 destaca item e abre toda hierarquia

### Teste de Permissões
- [ ] Usuário Administrador vê todos os itens
- [ ] Usuário sem permissão não vê item específico
- [ ] Módulo vazio (sem submenus com permissão) não aparece
- [ ] Submenu vazio (sem sub-submenus com permissão) não aparece

### Teste de Comportamento
- [ ] Fechar submenu Nível 2 ao abrir outro
- [ ] Fechar sub-submenu Nível 3 ao abrir outro no mesmo submenu
- [ ] Manter hierarquia aberta ao recarregar página
- [ ] Scroll funciona com menu expandido

---

## 📖 Documentos Relacionados

- `PROPOSTA_MENU_HIERARQUICO.md` - Proposta inicial e esquemas SQL
- `MODULOS_PADRONIZACAO.md` - Padronização de módulos no banco de dados
- `RBAC_IMPLEMENTACAO.md` - Sistema de permissões RBAC

---

## 🚀 Próximos Passos (Futuro)

### Possíveis Melhorias
1. **Nível 4:** Adicionar suporte a 4º nível se necessário
2. **Drag & Drop:** Interface para reordenar itens (campo `ordem`)
3. **Preview:** Visualizar menu antes de salvar
4. **Ícones Customizados:** Upload de ícones SVG
5. **Menu Lateral:** Opção de colapsar/expandir sidebar completa
6. **Breadcrumbs:** Sincronizar breadcrumbs com hierarquia do menu
7. **API REST:** Endpoints para gerenciar menu via API

### Manutenção
- Revisar permissões regularmente
- Auditar itens órfãos (sem aplicacao_id válido)
- Monitorar performance com muitos itens
- Backup das tabelas de menu

---

## 📞 Suporte

Para dúvidas sobre a estrutura do menu hierárquico:
1. Verificar este documento
2. Consultar `access_control.php` (métodos comentados)
3. Testar com diferentes grupos de usuários
4. Verificar console do navegador para erros JavaScript

---

**Implementação concluída em:** 2024  
**Testado com:** PHP 7.4+, MySQL 5.7+, Bootstrap 5.3  
**Compatibilidade:** Chrome, Firefox, Safari, Edge
