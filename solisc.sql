/**
 *  This file is part of SIS.
 *
 *  SIS is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  SIS is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with SIS.  If not, see <http://www.gnu.org/licenses/>.
 */

--
-- Table structure for table `participante`
--

DROP TABLE IF EXISTS `participante`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `participante` (
  `id` int(8) NOT NULL auto_increment,
  `email` varchar(50) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `sexo` char(1) NOT NULL default 'M',
  `pais` varchar(50) NOT NULL default 'Brasil',
  `uf` char(2) default NULL,
  `cidade` varchar(50) default NULL,
  `logradouro` varchar(50) default NULL,
  `bairro` varchar(50) default NULL,
  `forma_tratamento` char(10) default NULL,
  `tipo_documento` varchar(20) default NULL,
  `num_documento` varchar(20) default NULL,
  `estudante` tinyint(1) NOT NULL default '0',
  `inst_ensino` varchar(50) default NULL,
  `curso` varchar(50) default NULL,
  `nivel_ensino` varchar(25) default NULL,
  `empresa` varchar(25) default NULL,
  `cargo` varchar(25) default NULL,
  `id_caravana` int(8) default NULL,
  `id_grupo` int(8) default NULL,
  `dt_inscricao` date default NULL,
  `vl_pagamento` decimal(10,2) default NULL,
  `dt_pagamento` date default NULL,
  `senha` char(40) default NULL,
  `dt_vencimento` date default NULL,
  `cep` char(9) default NULL,
  `admin` tinyint(1) default '0',
  `confirmed` boolean not null default False,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `email` USING BTREE (`email`),
  KEY `caravana_participante_fk` USING BTREE (`id_caravana`),
  KEY `grupo_usuarios_participante_fk` USING BTREE (`id_grupo`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `caravana`
--

DROP TABLE IF EXISTS `caravana`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `caravana` (
  `id` int(8) NOT NULL auto_increment,
  `cidade` varchar(25) NOT NULL,
  `id_organizador` int(8) NOT NULL,
  `cod_desconto` char(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `participante_caravana_fk` USING BTREE (`id_organizador`),
  CONSTRAINT `participante_caravana_fk` FOREIGN KEY (`id_organizador`) REFERENCES `participante` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
SET character_set_client = @saved_cs_client;


--
-- Table structure for table `grupo`
--
DROP TABLE IF EXISTS `grupo`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `grupo` (
    `id` integer PRIMARY KEY auto_increment,
    `nome` varchar(30) UNIQUE,
    `dataLimite` date default null,
    `descricao` text,
    `desconto` integer,
    `numeroParticipantes` integer,
    `valor` decimal(10,2) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `membroGrupo`
--
DROP TABLE IF EXISTS `membroGrupo`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `membroGrupo` (
    `participante` integer,
    `grupo` integer,
    PRIMARY KEY (`participante`),
    FOREIGN KEY (`participante`) REFERENCES participante(`id`) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (`grupo`) REFERENCES grupo(`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `grupo_usuarios`
--

DROP TABLE IF EXISTS `grupo_usuarios`;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
CREATE TABLE `grupo_usuarios` (
  `id` int(8) NOT NULL,
  `id_responsavel` int(8) NOT NULL,
  `nome` varchar(25) NOT NULL,
  `cidade` varchar(25) NOT NULL,
  `cod_desconto` char(20) default NULL,
  PRIMARY KEY  (`id`),
  KEY `participante_grupo_usuarios_fk` USING BTREE (`id_responsavel`),
  CONSTRAINT `participante_grupo_usuarios_fk` FOREIGN KEY (`id_responsavel`) REFERENCES `participante` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Table structure for table `precos`
--

DROP TABLE IF EXISTS `precos`;
CREATE TABLE `precos` (
  `dt_limite` date NOT NULL,
  `vl_normal` decimal(10,2) default NULL,
  `vl_estudante` decimal(10,2) default NULL,
  `vl_ufsc` decimal(10,2) default NULL,
  `vl_caravana` decimal(10,2) default NULL,
  `vl_grupo` decimal(10,2) default NULL,
  PRIMARY KEY  (`dt_limite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `precos`
--

LOCK TABLES `precos` WRITE;
/*!40000 ALTER TABLE `precos` DISABLE KEYS */;
INSERT INTO `precos` VALUES ('2010-10-20','70','35','35','35','35');
/*!40000 ALTER TABLE `precos` ENABLE KEYS */;
UNLOCK TABLES;


DROP TABLE IF EXISTS `recovery_hashes`;
CREATE TABLE `recovery_hashes` (
    `hash` varchar(64),
    `request_date` date not null,
    `user` integer not null,
    PRIMARY KEY (`hash`),
    FOREIGN KEY (`user`) REFERENCES participante(`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8