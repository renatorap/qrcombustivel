-- Inserir itens de menu para o módulo de licenças

-- 1. Adicionar submenu "Licenças" no módulo Configuração (expandido para ter sub-itens)
INSERT INTO submenu (modulo_id, codigo, ordem, nome, icone, expandido, aplicacao_id, ativo)
VALUES (6, 'licencas', 99, 'Licenças', 'fa-key', 1, 1, 1);

SET @id_submenu_licencas = LAST_INSERT_ID();

-- 2. Adicionar sub-submenu "Gerenciar Licenças" (apenas para administradores)
-- O código deve corresponder ao nome do arquivo PHP (sem .php)
INSERT INTO subsubmenu (submenu_id, codigo, ordem, nome, icone, aplicacao_id, ativo)
VALUES (@id_submenu_licencas, 'licencas', 1, 'Gerenciar Licenças', 'fa-list-check', 1, 1);

SET @id_subsubmenu_gerenciar = LAST_INSERT_ID();

-- 3. Adicionar sub-submenu "Ativar Licença" (para administradores e operadores)
INSERT INTO subsubmenu (submenu_id, codigo, ordem, nome, icone, aplicacao_id, ativo)
VALUES (@id_submenu_licencas, 'ativar_licenca', 2, 'Ativar Licença', 'fa-check-circle', 1, 1);

SET @id_subsubmenu_ativar = LAST_INSERT_ID();

-- 4. Adicionar permissões para "Gerenciar Licenças" (apenas Administrador - perfil 1)
INSERT INTO perfil_menu_permissao (perfil_id, subsubmenu_id, acessar, criar, visualizar, editar, excluir)
VALUES (1, @id_subsubmenu_gerenciar, 1, 1, 1, 1, 1);

-- 5. Adicionar permissões para "Ativar Licença" (Admin + Operadores - perfis 1, 2, 3)
INSERT INTO perfil_menu_permissao (perfil_id, subsubmenu_id, acessar, criar, visualizar, editar, excluir)
VALUES 
(1, @id_subsubmenu_ativar, 1, 1, 1, 1, 1),  -- Administrador
(2, @id_subsubmenu_ativar, 1, 1, 1, 0, 0),  -- Operador Administrativo
(3, @id_subsubmenu_ativar, 1, 1, 1, 0, 0);  -- Operador Prefeitura

SELECT 'Itens de menu de licenças criados com sucesso!' as resultado;
