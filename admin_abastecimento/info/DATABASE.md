# Estrutura do Banco de Dados - QR Combustível

## Visão Geral
Este documento descreve a estrutura completa do banco de dados MySQL do sistema QR Combustível, incluindo tabelas, relacionamentos, índices e constraints.

## Diagrama ER (Entidade-Relacionamento)

```
┌──────────────┐         ┌──────────────────┐         ┌──────────────┐
│   usuarios   │         │  abastecimentos  │         │   veiculos   │
├──────────────┤         ├──────────────────┤         ├──────────────┤
│ id (PK)      │────┐    │ id (PK)          │    ┌────│ id (PK)      │
│ usuario      │    │    │ veiculo_id (FK)  │────┘    │ placa (UQ)   │
│ senha        │    └────│ user_id (FK)     │         │ modelo       │
│ email (UQ)   │         │ data_abastecim.  │         │ marca        │
│ perfil       │         │ litros           │         │ created_at   │
│ ativo        │         │ valor_total      │         │ updated_at   │
│ created_at   │         │ tipo_combustivel │         └──────────────┘
│ updated_at   │         │ km_atual         │
└──────────────┘         │ observacoes      │
                         │ created_at       │
       │                 └──────────────────┘
       │
       │                 ┌──────────────────┐
       └─────────────────│ password_resets  │
                         ├──────────────────┤
                         │ id (PK)          │
                         │ user_id (FK)     │
                         │ token (UQ)       │
                         │ expires_at       │
                         │ created_at       │
                         └──────────────────┘
```

## Tabelas

### 1. usuarios
Armazena informações dos usuários do sistema.

```sql
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario VARCHAR(100) NOT NULL UNIQUE COMMENT 'Nome de usuário para login',
    senha VARCHAR(255) NOT NULL COMMENT 'Hash BCrypt da senha',
    email VARCHAR(255) UNIQUE COMMENT 'E-mail do usuário',
    perfil ENUM('admin', 'user') DEFAULT 'user' COMMENT 'Nível de acesso',
    ativo TINYINT(1) DEFAULT 1 COMMENT '1=ativo, 0=inativo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_usuario (usuario),
    INDEX idx_email (email),
    INDEX idx_ativo (ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Usuários do sistema';
```

**Campos**:
- `id`: Identificador único (auto-incremento)
- `usuario`: Nome de usuário (único, obrigatório)
- `senha`: Hash BCrypt da senha (cost factor 12)
- `email`: E-mail único do usuário
- `perfil`: 'admin' (acesso total) ou 'user' (acesso limitado)
- `ativo`: Status do usuário (1=ativo, 0=inativo)
- `created_at`: Data/hora de criação
- `updated_at`: Data/hora da última atualização

**Índices**:
- PRIMARY KEY: `id`
- UNIQUE: `usuario`, `email`
- INDEX: `ativo` (para queries de usuários ativos)

**Regras de Negócio**:
- Usuário deve ser único
- E-mail deve ser único (se fornecido)
- Senha deve ter hash BCrypt
- Usuários inativos não podem fazer login
- Admin tem acesso total ao sistema

### 2. veiculos
Armazena informações dos veículos da frota.

```sql
CREATE TABLE veiculos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    placa VARCHAR(10) NOT NULL UNIQUE COMMENT 'Placa do veículo (formato ABC-1234)',
    modelo VARCHAR(100) NOT NULL COMMENT 'Modelo do veículo',
    marca VARCHAR(100) NOT NULL COMMENT 'Marca do veículo',
    ano INT COMMENT 'Ano de fabricação',
    tipo_combustivel ENUM('gasolina', 'etanol', 'diesel', 'gnv', 'flex') COMMENT 'Tipo de combustível',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_placa (placa),
    INDEX idx_modelo (modelo),
    INDEX idx_marca (marca)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Veículos da frota';
```

**Campos**:
- `id`: Identificador único
- `placa`: Placa do veículo (formato ABC-1234, único)
- `modelo`: Modelo do veículo (ex: Gol, Corolla)
- `marca`: Marca/fabricante (ex: Volkswagen, Toyota)
- `ano`: Ano de fabricação
- `tipo_combustivel`: Tipo de combustível aceito
- `created_at`: Data/hora de cadastro
- `updated_at`: Data/hora da última atualização

**Índices**:
- PRIMARY KEY: `id`
- UNIQUE: `placa`
- INDEX: `modelo`, `marca` (para buscas)

**Regras de Negócio**:
- Placa deve ser única
- Formato sugerido: ABC-1234 ou ABC1D234 (Mercosul)
- Modelo e marca são obrigatórios

### 3. abastecimentos
Registra todos os abastecimentos realizados.

```sql
CREATE TABLE abastecimentos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    veiculo_id INT NOT NULL COMMENT 'FK para veiculos',
    user_id INT NOT NULL COMMENT 'Usuário que registrou o abastecimento',
    data_abastecimento DATETIME NOT NULL COMMENT 'Data/hora do abastecimento',
    litros DECIMAL(10,2) NOT NULL COMMENT 'Quantidade de litros abastecida',
    valor_total DECIMAL(10,2) NOT NULL COMMENT 'Valor total pago (R$)',
    valor_litro DECIMAL(10,2) COMMENT 'Preço por litro (R$)',
    tipo_combustivel ENUM('gasolina', 'gasolina_aditivada', 'etanol', 'diesel', 'diesel_s10', 'gnv') NOT NULL,
    km_atual INT COMMENT 'Quilometragem atual do veículo',
    km_rodados INT COMMENT 'KM rodados desde último abastecimento',
    consumo_medio DECIMAL(10,2) COMMENT 'Consumo médio (km/l)',
    tanque_cheio TINYINT(1) DEFAULT 0 COMMENT '1=tanque cheio, 0=parcial',
    posto VARCHAR(200) COMMENT 'Nome do posto de combustível',
    observacoes TEXT COMMENT 'Observações adicionais',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    
    INDEX idx_veiculo (veiculo_id),
    INDEX idx_data (data_abastecimento),
    INDEX idx_user (user_id),
    INDEX idx_tipo_combustivel (tipo_combustivel)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registros de abastecimento';
```

**Campos**:
- `id`: Identificador único
- `veiculo_id`: Referência ao veículo
- `user_id`: Usuário que registrou
- `data_abastecimento`: Data/hora do abastecimento
- `litros`: Quantidade abastecida
- `valor_total`: Valor total gasto
- `valor_litro`: Preço unitário do combustível
- `tipo_combustivel`: Tipo de combustível usado
- `km_atual`: Odômetro atual
- `km_rodados`: Distância desde último abastecimento
- `consumo_medio`: Eficiência calculada (km/l)
- `tanque_cheio`: Indica se foi abastecimento completo
- `posto`: Local do abastecimento
- `observacoes`: Notas adicionais

**Relacionamentos**:
- `veiculo_id` → `veiculos.id` (CASCADE on delete)
- `user_id` → `usuarios.id` (RESTRICT on delete)

**Triggers Sugeridos**:
```sql
-- Calcular consumo médio automaticamente
DELIMITER $$
CREATE TRIGGER before_insert_abastecimento
BEFORE INSERT ON abastecimentos
FOR EACH ROW
BEGIN
    IF NEW.km_rodados IS NOT NULL AND NEW.litros > 0 THEN
        SET NEW.consumo_medio = NEW.km_rodados / NEW.litros;
    END IF;
    
    IF NEW.valor_total > 0 AND NEW.litros > 0 THEN
        SET NEW.valor_litro = NEW.valor_total / NEW.litros;
    END IF;
END$$
DELIMITER ;
```

### 4. password_resets
Tokens de recuperação de senha.

```sql
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'FK para usuarios',
    token VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token de recuperação (64 chars hex)',
    expires_at DATETIME NOT NULL COMMENT 'Data/hora de expiração',
    used TINYINT(1) DEFAULT 0 COMMENT '1=já usado, 0=ainda válido',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tokens de recuperação de senha';
```

**Campos**:
- `id`: Identificador único
- `user_id`: Usuário que solicitou recuperação
- `token`: Token gerado (64 caracteres hexadecimais)
- `expires_at`: Data/hora de expiração (geralmente 1 hora)
- `used`: Flag indicando se token já foi usado
- `created_at`: Data/hora de criação

**Relacionamentos**:
- `user_id` → `usuarios.id` (CASCADE on delete)

**Regras de Negócio**:
- Token deve ser único
- Token expira em 1 hora (configurável)
- Token só pode ser usado uma vez
- Tokens expirados devem ser limpos periodicamente

**Procedimento de Limpeza**:
```sql
-- Limpar tokens expirados (executar diariamente)
DELETE FROM password_resets 
WHERE expires_at < NOW() OR used = 1;
```

## Views Úteis

### v_veiculos_estatisticas
Estatísticas por veículo.

```sql
CREATE VIEW v_veiculos_estatisticas AS
SELECT 
    v.id,
    v.placa,
    v.modelo,
    v.marca,
    COUNT(a.id) AS total_abastecimentos,
    SUM(a.litros) AS total_litros,
    SUM(a.valor_total) AS total_gasto,
    AVG(a.consumo_medio) AS consumo_medio,
    MAX(a.data_abastecimento) AS ultimo_abastecimento,
    MAX(a.km_atual) AS km_atual
FROM veiculos v
LEFT JOIN abastecimentos a ON v.id = a.veiculo_id
GROUP BY v.id;
```

### v_abastecimentos_recentes
Últimos 100 abastecimentos.

```sql
CREATE VIEW v_abastecimentos_recentes AS
SELECT 
    a.id,
    a.data_abastecimento,
    v.placa,
    v.modelo,
    v.marca,
    a.tipo_combustivel,
    a.litros,
    a.valor_total,
    a.consumo_medio,
    u.usuario AS registrado_por
FROM abastecimentos a
INNER JOIN veiculos v ON a.veiculo_id = v.id
INNER JOIN usuarios u ON a.user_id = u.id
ORDER BY a.data_abastecimento DESC
LIMIT 100;
```

### v_consumo_mensal
Consumo agregado por mês.

```sql
CREATE VIEW v_consumo_mensal AS
SELECT 
    YEAR(a.data_abastecimento) AS ano,
    MONTH(a.data_abastecimento) AS mes,
    COUNT(a.id) AS total_abastecimentos,
    SUM(a.litros) AS total_litros,
    SUM(a.valor_total) AS total_gasto,
    AVG(a.valor_litro) AS preco_medio_litro,
    AVG(a.consumo_medio) AS consumo_medio
FROM abastecimentos a
GROUP BY YEAR(a.data_abastecimento), MONTH(a.data_abastecimento)
ORDER BY ano DESC, mes DESC;
```

## Stored Procedures

### sp_registrar_abastecimento
Registra um novo abastecimento com validações.

```sql
DELIMITER $$
CREATE PROCEDURE sp_registrar_abastecimento(
    IN p_veiculo_id INT,
    IN p_user_id INT,
    IN p_data_abastecimento DATETIME,
    IN p_litros DECIMAL(10,2),
    IN p_valor_total DECIMAL(10,2),
    IN p_tipo_combustivel VARCHAR(50),
    IN p_km_atual INT,
    IN p_tanque_cheio TINYINT,
    IN p_posto VARCHAR(200),
    IN p_observacoes TEXT,
    OUT p_abastecimento_id INT,
    OUT p_erro VARCHAR(255)
)
BEGIN
    DECLARE v_ultimo_km INT;
    DECLARE v_km_rodados INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_erro = 'Erro ao registrar abastecimento';
        ROLLBACK;
    END;
    
    START TRANSACTION;
    
    -- Validações
    IF p_litros <= 0 THEN
        SET p_erro = 'Quantidade de litros deve ser maior que zero';
        ROLLBACK;
    ELSEIF p_valor_total <= 0 THEN
        SET p_erro = 'Valor total deve ser maior que zero';
        ROLLBACK;
    ELSE
        -- Buscar último KM
        SELECT km_atual INTO v_ultimo_km
        FROM abastecimentos
        WHERE veiculo_id = p_veiculo_id
        ORDER BY data_abastecimento DESC
        LIMIT 1;
        
        -- Calcular KM rodados
        IF v_ultimo_km IS NOT NULL AND p_km_atual > v_ultimo_km THEN
            SET v_km_rodados = p_km_atual - v_ultimo_km;
        END IF;
        
        -- Inserir abastecimento
        INSERT INTO abastecimentos (
            veiculo_id, user_id, data_abastecimento, litros,
            valor_total, tipo_combustivel, km_atual, km_rodados,
            tanque_cheio, posto, observacoes
        ) VALUES (
            p_veiculo_id, p_user_id, p_data_abastecimento, p_litros,
            p_valor_total, p_tipo_combustivel, p_km_atual, v_km_rodados,
            p_tanque_cheio, p_posto, p_observacoes
        );
        
        SET p_abastecimento_id = LAST_INSERT_ID();
        SET p_erro = NULL;
        
        COMMIT;
    END IF;
END$$
DELIMITER ;
```

## Índices de Performance

### Índices Recomendados

```sql
-- Melhorar queries de listagem
CREATE INDEX idx_veiculos_busca ON veiculos(placa, modelo, marca);

-- Otimizar buscas de abastecimento por período
CREATE INDEX idx_abast_periodo ON abastecimentos(data_abastecimento, veiculo_id);

-- Acelerar relatórios por tipo de combustível
CREATE INDEX idx_abast_combustivel_data ON abastecimentos(tipo_combustivel, data_abastecimento);

-- Melhorar queries de usuários ativos
CREATE INDEX idx_usuarios_ativo_perfil ON usuarios(ativo, perfil);
```

## Backup e Manutenção

### Script de Backup Diário

```bash
#!/bin/bash
# backup_db.sh

DB_NAME="conceit1_combustivel"
DB_USER="backup_user"
DB_PASS="backup_password"
BACKUP_DIR="/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório se não existir
mkdir -p $BACKUP_DIR

# Backup completo
mysqldump -u $DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    $DB_NAME > $BACKUP_DIR/backup_$DATE.sql

# Comprimir
gzip $BACKUP_DIR/backup_$DATE.sql

# Remover backups antigos (manter últimos 30 dias)
find $BACKUP_DIR -name "backup_*.sql.gz" -mtime +30 -delete

echo "Backup concluído: $BACKUP_DIR/backup_$DATE.sql.gz"
```

### Otimização de Tabelas

```sql
-- Executar mensalmente
OPTIMIZE TABLE usuarios;
OPTIMIZE TABLE veiculos;
OPTIMIZE TABLE abastecimentos;
OPTIMIZE TABLE password_resets;

-- Analisar queries lentas
ANALYZE TABLE abastecimentos;
ANALYZE TABLE veiculos;
```

## Migrations (Estrutura Futura)

### Criar Sistema de Migrations

```sql
CREATE TABLE migrations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255),
    executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Exemplo de migration
INSERT INTO migrations (version, description) VALUES
('001', 'Criação inicial do banco de dados'),
('002', 'Adição de campo ano em veiculos'),
('003', 'Criação de índices de performance');
```

## Segurança do Banco de Dados

### Usuários e Permissões

```sql
-- Usuário da aplicação (acesso limitado)
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT SELECT, INSERT, UPDATE, DELETE ON conceit1_combustivel.* TO 'app_user'@'localhost';

-- Usuário de backup (somente leitura)
CREATE USER 'backup_user'@'localhost' IDENTIFIED BY 'senha_backup';
GRANT SELECT, LOCK TABLES ON conceit1_combustivel.* TO 'backup_user'@'localhost';

-- Aplicar permissões
FLUSH PRIVILEGES;
```

### Auditoria

```sql
-- Tabela de auditoria (opcional)
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_name VARCHAR(50),
    operation ENUM('INSERT', 'UPDATE', 'DELETE'),
    user_id INT,
    old_values JSON,
    new_values JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_table (table_name),
    INDEX idx_operation (operation),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;
```

## Referências

- [MySQL 5.7 Documentation](https://dev.mysql.com/doc/refman/5.7/en/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/5.7/en/optimization.html)
- [Database Design Best Practices](https://dev.mysql.com/doc/workbench/en/wb-data-modeling.html)
