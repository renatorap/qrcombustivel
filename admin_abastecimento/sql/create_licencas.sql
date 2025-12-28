-- Tabela para gerenciamento de licenças mensais dos clientes
CREATE TABLE IF NOT EXISTS `licenca` (
  `id_licenca` int(11) NOT NULL AUTO_INCREMENT,
  `id_cliente` int(11) NOT NULL,
  `codigo_licenca` varchar(100) NOT NULL,
  `data_geracao` datetime NOT NULL,
  `data_ativacao` datetime DEFAULT NULL,
  `data_expiracao` date NOT NULL,
  `status` enum('pendente','ativa','expirada','cancelada') DEFAULT 'pendente',
  `gerado_por` int(11) NOT NULL COMMENT 'ID do usuário que gerou',
  `ativado_por` int(11) DEFAULT NULL COMMENT 'ID do usuário que ativou',
  `observacao` text,
  PRIMARY KEY (`id_licenca`),
  UNIQUE KEY `codigo_licenca` (`codigo_licenca`),
  KEY `idx_cliente` (`id_cliente`),
  KEY `idx_status` (`status`),
  KEY `idx_expiracao` (`data_expiracao`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Adicionar índice composto para buscar licença ativa de um cliente
CREATE INDEX idx_cliente_status ON licenca(id_cliente, status, data_expiracao);
