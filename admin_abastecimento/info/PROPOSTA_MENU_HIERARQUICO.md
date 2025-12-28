# Proposta: Estrutura de Menu Hierárquico (3 Níveis)

## Visão Geral

Sistema de menu com até 3 níveis de profundidade, totalmente dinâmico e gerenciado pelo banco de dados.

---

## 1. Estrutura do Banco de Dados

### Tabela: `modulo` (Já existe)
```sql
CREATE TABLE modulo (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  codigo VARCHAR(45) UNIQUE NOT NULL,
  ordem SMALLINT(6) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  icone VARCHAR(45),
  expandido TINYINT(1) DEFAULT 0,      -- Se tem submenu
  aplicacao_id INT(11) NULL,           -- NOVO: Link direto para aplicacao (se não for expandido)
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
);
```

### Nova Tabela: `submenu`
```sql
CREATE TABLE submenu (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  modulo_id INT(11) NOT NULL,          -- Módulo pai (Nível 1)
  codigo VARCHAR(45) UNIQUE NOT NULL,
  ordem SMALLINT(6) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  icone VARCHAR(45),
  expandido TINYINT(1) DEFAULT 0,      -- Se tem sub-submenu
  aplicacao_id INT(11) NULL,           -- Link direto para aplicacao (se não for expandido)
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (modulo_id) REFERENCES modulo(id) ON DELETE CASCADE,
  FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
);
```

### Nova Tabela: `subsubmenu`
```sql
CREATE TABLE subsubmenu (
  id INT(11) PRIMARY KEY AUTO_INCREMENT,
  submenu_id INT(11) NOT NULL,         -- Submenu pai (Nível 2)
  codigo VARCHAR(45) UNIQUE NOT NULL,
  ordem SMALLINT(6) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  icone VARCHAR(45),
  aplicacao_id INT(11) NOT NULL,       -- SEMPRE tem link para aplicacao (último nível)
  ativo TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (submenu_id) REFERENCES submenu(id) ON DELETE CASCADE,
  FOREIGN KEY (aplicacao_id) REFERENCES aplicacoes(id)
);
```

---

## 2. Exemplos de Estrutura

### Exemplo 1: Dashboard (Módulo sem expansão)
```
📊 Dashboard (Nível 1)
   └─ Não tem submenu
   └─ aplicacao_id = 1 (dashboard)
   └─ expandido = 0
   └─ Ao clicar: abre dashboard.php
```

**Dados:**
```sql
-- Tabela modulo
id=1, codigo='inicial', nome='Dashboard', icone='fa-home', 
expandido=0, aplicacao_id=1, ordem=1
```

---

### Exemplo 2: Cadastro (Módulo expandido com submenu)
```
📁 Cadastro (Nível 1)
   └─ expandido = 1
   └─ aplicacao_id = NULL
   
   ├─ 🏢 Cliente (Nível 2)
   │    └─ Não tem sub-submenu
   │    └─ aplicacao_id = 23 (clientes)
   │    └─ expandido = 0
   │    └─ Ao clicar: abre cliente.php
   │
   └─ 🚗 Veículos (Nível 2)
        └─ Não tem sub-submenu
        └─ aplicacao_id = 2 (veiculos)
        └─ expandido = 0
        └─ Ao clicar: abre veiculo.php
```

**Dados:**
```sql
-- Tabela modulo
id=2, codigo='cadastro', nome='Cadastro', icone='fa-folder-open', 
expandido=1, aplicacao_id=NULL, ordem=2

-- Tabela submenu
id=1, modulo_id=2, codigo='clientes', nome='Cliente', icone='fa-building',
expandido=0, aplicacao_id=23, ordem=1

id=2, modulo_id=2, codigo='veiculos', nome='Veículos', icone='fa-car',
expandido=0, aplicacao_id=2, ordem=2
```

---

### Exemplo 3: Segurança (Módulo expandido com submenu expandido)
```
🛡️ Segurança (Nível 1)
   └─ expandido = 1
   └─ aplicacao_id = NULL
   
   ├─ 👥 Usuários (Nível 2)
   │    └─ Não tem sub-submenu
   │    └─ aplicacao_id = 4 (usuarios)
   │    └─ expandido = 0
   │    └─ Ao clicar: abre usuarios.php
   │
   ├─ 🔐 Controle de Acesso (Nível 2)
   │    └─ TEM sub-submenu
   │    └─ aplicacao_id = NULL
   │    └─ expandido = 1
   │    
   │    ├─ 👥 Grupos (Nível 3)
   │    │    └─ aplicacao_id = 5 (grupos)
   │    │    └─ Ao clicar: abre grupos.php
   │    │
   │    ├─ 🔑 Permissões (Nível 3)
   │    │    └─ aplicacao_id = 7 (permissoes)
   │    │    └─ Ao clicar: abre permissoes.php
   │    │
   │    └─ 📋 Aplicações (Nível 3)
   │         └─ aplicacao_id = 6 (aplicacoes)
   │         └─ Ao clicar: abre aplicacoes.php
   │
   └─ 📊 Auditoria (Nível 2)
        └─ Não tem sub-submenu
        └─ aplicacao_id = 8 (auditoria)
        └─ expandido = 0
        └─ Ao clicar: abre auditoria.php
```

**Dados:**
```sql
-- Tabela modulo
id=7, codigo='seguranca', nome='Segurança', icone='fa-shield-alt', 
expandido=1, aplicacao_id=NULL, ordem=7

-- Tabela submenu
id=10, modulo_id=7, codigo='usuarios', nome='Usuários', icone='fa-users',
expandido=0, aplicacao_id=4, ordem=1

id=11, modulo_id=7, codigo='controle_acesso', nome='Controle de Acesso', 
icone='fa-lock', expandido=1, aplicacao_id=NULL, ordem=2

id=12, modulo_id=7, codigo='auditoria', nome='Auditoria', icone='fa-chart-line',
expandido=0, aplicacao_id=8, ordem=3

-- Tabela subsubmenu
id=1, submenu_id=11, codigo='grupos', nome='Grupos', 
icone='fa-users-cog', aplicacao_id=5, ordem=1

id=2, submenu_id=11, codigo='permissoes', nome='Permissões', 
icone='fa-key', aplicacao_id=7, ordem=2

id=3, submenu_id=11, codigo='aplicacoes', nome='Aplicações', 
icone='fa-th', aplicacao_id=6, ordem=3
```

---

## 3. Estrutura Visual no Sistema

### HTML Gerado (Exemplo Segurança)

```html
<aside class="sidebar">
    <ul class="sidebar-menu">
        
        <!-- NÍVEL 1: Módulo Segurança -->
        <li class="sidebar-item has-submenu">
            <a href="javascript:void(0)" class="sidebar-link" onclick="toggleSubmenu(this)">
                <i class="fas fa-shield-alt"></i>
                <span>Segurança</span>
                <i class="fas fa-chevron-down submenu-arrow"></i>
            </a>
            
            <!-- NÍVEL 2: Submenus -->
            <ul class="submenu">
                
                <!-- Submenu Simples: Usuários -->
                <li class="submenu-item">
                    <a href="usuarios.php" class="submenu-link">
                        <i class="fas fa-users"></i>
                        <span>Usuários</span>
                    </a>
                </li>
                
                <!-- Submenu Expandido: Controle de Acesso -->
                <li class="submenu-item has-subsubmenu">
                    <a href="javascript:void(0)" class="submenu-link" onclick="toggleSubsubmenu(this)">
                        <i class="fas fa-lock"></i>
                        <span>Controle de Acesso</span>
                        <i class="fas fa-chevron-down subsubmenu-arrow"></i>
                    </a>
                    
                    <!-- NÍVEL 3: Sub-submenus -->
                    <ul class="subsubmenu">
                        <li class="subsubmenu-item">
                            <a href="grupos.php" class="subsubmenu-link">
                                <i class="fas fa-users-cog"></i>
                                <span>Grupos</span>
                            </a>
                        </li>
                        <li class="subsubmenu-item">
                            <a href="permissoes.php" class="subsubmenu-link">
                                <i class="fas fa-key"></i>
                                <span>Permissões</span>
                            </a>
                        </li>
                        <li class="subsubmenu-item">
                            <a href="aplicacoes.php" class="subsubmenu-link">
                                <i class="fas fa-th"></i>
                                <span>Aplicações</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- Submenu Simples: Auditoria -->
                <li class="submenu-item">
                    <a href="auditoria.php" class="submenu-link">
                        <i class="fas fa-chart-line"></i>
                        <span>Auditoria</span>
                    </a>
                </li>
                
            </ul>
        </li>
        
    </ul>
</aside>
```

---

## 4. Regras de Negócio

### Nível 1 (Módulo)
- ✅ **expandido = 0**: Deve ter `aplicacao_id` preenchido (link direto)
- ✅ **expandido = 1**: Não tem `aplicacao_id` (apenas container)
- ✅ Ordenado por campo `ordem`
- ✅ Mostra apenas se usuário tem permissão em pelo menos 1 aplicação filha

### Nível 2 (Submenu)
- ✅ **expandido = 0**: Deve ter `aplicacao_id` preenchido (link direto)
- ✅ **expandido = 1**: Não tem `aplicacao_id` (apenas container)
- ✅ Ordenado por campo `ordem` dentro do módulo
- ✅ Mostra apenas se usuário tem permissão de acesso

### Nível 3 (Sub-submenu)
- ✅ **SEMPRE** tem `aplicacao_id` preenchido (último nível)
- ✅ Não tem campo `expandido` (sempre é folha)
- ✅ Ordenado por campo `ordem` dentro do submenu
- ✅ Mostra apenas se usuário tem permissão de acesso

---

## 5. Método PHP Proposto

### AccessControl::getMenuHierarquico()

```php
public function getMenuHierarquico() {
    if (!$this->userId) {
        return [];
    }
    
    // 1. Buscar todos os módulos ativos
    $modulos = $this->getModulosComPermissao();
    
    foreach ($modulos as &$modulo) {
        if ($modulo['expandido']) {
            // 2. Buscar submenus do módulo
            $modulo['submenus'] = $this->getSubmenusComPermissao($modulo['id']);
            
            foreach ($modulo['submenus'] as &$submenu) {
                if ($submenu['expandido']) {
                    // 3. Buscar sub-submenus
                    $submenu['subsubmenus'] = $this->getSubsubmenusComPermissao($submenu['id']);
                }
            }
        }
    }
    
    return $modulos;
}

private function getModulosComPermissao() {
    // Retorna apenas módulos onde usuário tem acesso a pelo menos 1 aplicação
}

private function getSubmenusComPermissao($moduloId) {
    // Retorna apenas submenus onde usuário tem permissão
}

private function getSubsubmenusComPermissao($submenuId) {
    // Retorna apenas sub-submenus onde usuário tem permissão
}
```

---

## 6. CSS Adicional Necessário

```css
/* Sub-submenu styles */
.subsubmenu {
    display: none;
    list-style: none;
    padding-left: 40px;
    background: rgba(0,0,0,0.1);
}

.submenu-item.has-subsubmenu > .submenu-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.subsubmenu-arrow {
    transition: transform 0.3s;
    font-size: 0.8em;
}

.submenu-item.has-subsubmenu.open > .subsubmenu {
    display: block;
}

.subsubmenu-item {
    margin: 0;
}

.subsubmenu-link {
    padding: 8px 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
    font-size: 0.9em;
}

.subsubmenu-link:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    padding-left: 20px;
}

.subsubmenu-item.active .subsubmenu-link {
    background: rgba(255,255,255,0.15);
    color: white;
    border-left: 3px solid #4CAF50;
}
```

---

## 7. JavaScript Adicional

```javascript
function toggleSubsubmenu(element) {
    const parent = element.closest('.submenu-item');
    const subsubmenu = parent.querySelector('.subsubmenu');
    const arrow = element.querySelector('.subsubmenu-arrow');
    
    // Toggle
    if (parent.classList.contains('open')) {
        parent.classList.remove('open');
        subsubmenu.style.display = 'none';
        arrow.style.transform = 'rotate(0deg)';
    } else {
        parent.classList.add('open');
        subsubmenu.style.display = 'block';
        arrow.style.transform = 'rotate(180deg)';
    }
}

// Manter sub-submenu aberto se página ativa
document.addEventListener('DOMContentLoaded', function() {
    const activeSubsubmenuItem = document.querySelector('.subsubmenu-item.active');
    if (activeSubsubmenuItem) {
        const parent = activeSubsubmenuItem.closest('.submenu-item.has-subsubmenu');
        if (parent) {
            parent.classList.add('open');
            parent.querySelector('.subsubmenu').style.display = 'block';
            parent.querySelector('.subsubmenu-arrow').style.transform = 'rotate(180deg)';
        }
    }
});
```

---

## 8. Exemplos de Uso Real

### Exemplo A: Sistema Completo
```
📊 Dashboard                           [Módulo direto]
📁 Cadastro                            [Módulo expandido]
   ├─ 🏢 Cliente                       [Submenu direto]
   ├─ 🚗 Veículos                      [Submenu direto]
   └─ 👥 Motoristas                    [Submenu direto]
⛽ Abastecimento                       [Módulo expandido]
   ├─ 📝 Requisições                   [Submenu direto]
   └─ ✅ Aprovações                    [Submenu direto]
🛡️ Segurança                          [Módulo expandido]
   ├─ 👥 Usuários                      [Submenu direto]
   ├─ 🔐 Controle de Acesso            [Submenu expandido]
   │    ├─ 👥 Grupos                  [Sub-submenu]
   │    ├─ 🔑 Permissões              [Sub-submenu]
   │    └─ 📋 Aplicações              [Sub-submenu]
   └─ 📊 Auditoria                     [Submenu direto]
⚙️ Configuração                       [Módulo direto]
```

---

## 9. Vantagens da Nova Estrutura

✅ **Flexibilidade**: Suporta até 3 níveis de profundidade
✅ **Organização**: Agrupamento lógico de funcionalidades
✅ **Escalabilidade**: Fácil adicionar novos itens
✅ **Permissões**: Respeita RBAC em todos os níveis
✅ **Performance**: Queries otimizadas com JOINs
✅ **UX**: Interface intuitiva e familiar

---

## 10. Migração Necessária

### Passo 1: Criar Tabelas
```sql
-- Criar tabela submenu
-- Criar tabela subsubmenu
-- Adicionar coluna aplicacao_id em modulo
```

### Passo 2: Migrar Dados Existentes
```sql
-- Mover aplicações da tabela aplicacoes para submenu
-- Criar registros de submenu baseados em aplicacoes.modulo
```

### Passo 3: Atualizar Código
- AccessControl: novo método getMenuHierarquico()
- Sidebar: renderizar 3 níveis
- CSS: estilos para sub-submenu
- JS: toggle para sub-submenu

---

## Conclusão

Esta estrutura permite:
- ✅ Menu de 3 níveis totalmente dinâmico
- ✅ Flexibilidade para links diretos ou expansões em qualquer nível
- ✅ Controle de permissões granular
- ✅ Fácil manutenção via banco de dados
- ✅ Interface limpa e organizada

**Está de acordo com o que você imaginou?**
