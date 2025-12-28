# Changelog - Sistema de Licenciamento

## [1.1.0] - 2025-12-02

### Adicionado
- **Controle de acesso diferenciado por grupo** quando licença está expirada
- Operadores Administrativos (grupo_id = 2) e Operadores Prefeitura (grupo_id = 3) podem acessar página de ativação mesmo com licença expirada
- Operadores Posto (grupo_id = 4) e Abastecimento (grupo_id = 10) são totalmente bloqueados com licença expirada
- Alertas contextuais na página de ativação baseados no motivo do redirecionamento
- Mensagem específica na tela de login para usuários bloqueados

### Modificado
- **LicenseChecker.php**: Lógica de verificação agora diferencia grupos de usuários
  - Adicionado método `podeAtivarLicenca()` - identifica grupos 2 e 3
  - Adicionado método `bloqueioTotal()` - identifica grupos 4 e 10
  - Adicionado método `isPaginaOperadorLicenca()` - verifica páginas acessíveis por operadores
  - Método `bloquearAcesso()` agora redireciona conforme grupo do usuário
  
- **ativar_licenca.php**: Adicionados alertas dinâmicos
  - Exibe alerta vermelho quando `motivo=expirada`
  - Exibe alerta amarelo quando `motivo=pendente`
  
- **index.php**: Adicionado tratamento de erro de licença expirada
  - Exibe alerta quando parâmetro `erro=licenca_expirada` presente na URL

### Documentação
- Atualizado **LICENCIAMENTO.md**:
  - Adicionada seção detalhando comportamento por grupo
  - Adicionada seção de exemplos práticos de cenários
  - Ampliada seção de segurança
  
- Atualizado **TESTE_LICENCIAMENTO.md**:
  - Adicionados testes específicos para cada grupo de usuário
  - Adicionados testes de tentativa de burlar bloqueio
  - Reorganizada seção de testes de licença expirada

### Fluxos Implementados

#### Fluxo 1: Administrador (Sempre Liberado)
```
Login → Sistema detecta licença expirada → ✅ Acesso total permitido
```

#### Fluxo 2: Operador Administrativo/Prefeitura (Acesso Limitado)
```
Login → Tenta acessar página → Sistema detecta licença expirada
    → Redireciona para ativar_licenca.php?motivo=expirada
    → Operador pode ativar nova licença
    → Após ativação: Acesso total liberado
```

#### Fluxo 3: Operador Posto/Abastecimento (Bloqueio Total)
```
Login → Tenta acessar página → Sistema detecta licença expirada
    → Redireciona para index.php?erro=licenca_expirada
    → ❌ Bloqueado completamente
    → Precisa que admin/operador administrativo renove
```

### Arquivos Alterados
- `/config/license_checker.php` - Lógica principal de controle
- `/pages/ativar_licenca.php` - Alertas contextuais
- `/index.php` - Tratamento de erro na tela de login
- `/docs/LICENCIAMENTO.md` - Documentação atualizada
- `/docs/TESTE_LICENCIAMENTO.md` - Guia de testes expandido

### Segurança
- ✅ Impossível burlar verificação por acesso direto a URLs
- ✅ Verificação ocorre em todas as páginas protegidas
- ✅ Grupos sem permissão não conseguem acessar página de ativação
- ✅ Administradores sempre mantêm acesso para resolver problemas

---

## [1.0.0] - 2025-12-02

### Lançamento Inicial
- Sistema completo de licenciamento mensal
- Geração de códigos únicos
- Envio por e-mail
- Ativação controlada por perfil
- Bloqueio automático quando expirada
- Interface de gerenciamento para administradores
- Interface de ativação para operadores
- Documentação completa
