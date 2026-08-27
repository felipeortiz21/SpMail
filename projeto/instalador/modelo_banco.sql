-- SpMail — Estrutura do Banco de Dados
-- Fonte única de verdade do schema (usada pelo instalador em criar_config.php).
-- Compatível com MySQL 8 / MariaDB. Todas as tabelas em InnoDB + utf8mb4.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

-- --------------------------------------------------------

--
-- Estrutura da tabela `cliques`
--

CREATE TABLE IF NOT EXISTS `cliques` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contato` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL,
  `link` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cliques_mensagem` (`mensagem`),
  KEY `idx_cliques_contato` (`contato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `url` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pasta` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_empresa` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `smtp` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `porta` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seguranca` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `autenticacao` tinyint(1) NOT NULL,
  `email_resposta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_email_resposta` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emails_por_hora` int(11) NOT NULL,
  `emails_por_hora_nao_comercial` int(11) NOT NULL,
  `horario_comercial_ini` int(11) NOT NULL,
  `horario_comercial_fin` int(11) NOT NULL,
  `envio_variacao_percentual` int(11) NOT NULL DEFAULT 30,
  `dkim_ativo` tinyint(1) NOT NULL DEFAULT 0,
  `dkim_dominio` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dkim_selector` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `dkim_chave_privada` text COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `contatos`
--

CREATE TABLE IF NOT EXISTS `contatos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(60) COLLATE utf8mb4_bin NOT NULL,
  `nome` varchar(60) COLLATE utf8mb4_bin NOT NULL,
  `telefone` varchar(12) COLLATE utf8mb4_bin NOT NULL,
  `grupo` varchar(200) COLLATE utf8mb4_bin NOT NULL,
  `aut` int(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_contatos_email` (`email`),
  KEY `idx_contatos_grupo` (`grupo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- --------------------------------------------------------

--
-- Estrutura da tabela `grupos`
--

CREATE TABLE IF NOT EXISTS `grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE IF NOT EXISTS `mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grupos` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emails_adicionais` varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `assunto` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_envio` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_envio_ini` datetime NULL DEFAULT NULL,
  `data_envio_fin` datetime NULL DEFAULT NULL,
  `data_atualizacao` datetime NULL DEFAULT NULL,
  `obs` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mensagens_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `restantes`
--

CREATE TABLE IF NOT EXISTS `restantes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensagem` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `enviado` varchar(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `erro_mensagem` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `idx_restantes_mensagem_enviado` (`mensagem`, `enviado`),
  KEY `idx_restantes_mensagem_email` (`mensagem`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `setores`
--

CREATE TABLE IF NOT EXISTS `setores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `obs` text COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `usuarios`
--
-- `setores` é reaproveitado como papel de acesso: o valor 'Administrador Geral'
-- concede acesso à área de Configurações do Sistema (ver libs/seguranca.php).
-- `ativo` permite desativar um usuário sem excluí-lo (login passa a ser recusado).

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setores` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `views`
--

CREATE TABLE IF NOT EXISTS `views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contato` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensagem` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_hora` datetime NOT NULL,
  `link` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_views_mensagem` (`mensagem`),
  KEY `idx_views_contato` (`contato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
