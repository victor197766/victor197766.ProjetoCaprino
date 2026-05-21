-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema mydb
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mydb` DEFAULT CHARACTER SET utf8 ;
USE `mydb` ;

-- -----------------------------------------------------
-- Table `mydb`.`agenda`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`agenda` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `Titulo` VARCHAR(45) NOT NULL,
  `descriçao` VARCHAR(45) NOT NULL,
  `data_hora` VARCHAR(45) NOT NULL,
  `tipo` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`morte`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`morte` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `causa` VARCHAR(45) NULL DEFAULT NULL,
  `data_morte` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`nascimento`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`nascimento` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NOT NULL,
  `lote_id` INT(11) NOT NULL,
  `data_nascimento` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`, `animal_id`, `lote_id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`vacinacao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`vacinacao` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `data_aplicacao` DATE NULL DEFAULT NULL,
  `proxima_dose` DATE NULL DEFAULT NULL,
  `observacao` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`ID`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`vacina`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`vacina` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nome` VARCHAR(45) NULL DEFAULT NULL,
  `fabricante` VARCHAR(45) NULL DEFAULT NULL,
  `dose_recomendada` VARCHAR(45) NULL DEFAULT NULL,
  `VACINACAO_ID` INT(11) NOT NULL,
  PRIMARY KEY (`id`, `VACINACAO_ID`),
  INDEX `fk_vacina_VACINACAO1_idx` (`VACINACAO_ID` ASC),
  CONSTRAINT `fk_vacina_VACINACAO1`
    FOREIGN KEY (`VACINACAO_ID`)
    REFERENCES `mydb`.`vacinacao` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`cuidado`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`cuidado` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `animal_id` INT(11) NULL DEFAULT NULL,
  `tipo` VARCHAR(45) NULL DEFAULT NULL,
  `descricao` TEXT NULL DEFAULT NULL,
  `doencas` TEXT NULL DEFAULT NULL,
  `tratamento` TEXT NULL DEFAULT NULL,
  `data_evento` DATE NULL DEFAULT NULL,
  `criado_em` TIMESTAMP NULL DEFAULT NULL,
  `vacina_id` INT(11) NOT NULL,
  PRIMARY KEY (`ID`, `vacina_id`),
  INDEX `fk_cuidado_vacina1_idx` (`vacina_id` ASC),
  CONSTRAINT `fk_cuidado_vacina1`
    FOREIGN KEY (`vacina_id`)
    REFERENCES `mydb`.`vacina` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`animal`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`animal` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `matriz_id` INT(11) NULL DEFAULT NULL,
  `reprodutor_id` VARCHAR(45) NOT NULL,
  `nome` VARCHAR(45) NOT NULL,
  `especie` ENUM('') NOT NULL,
  `sexo` ENUM('') NOT NULL,
  `peso_kg` FLOAT NOT NULL,
  `idade` INT(11) NOT NULL,
  `raca` VARCHAR(45) NOT NULL,
  `identificador` VARCHAR(45) NOT NULL,
  `nascimento_fazenda` TINYINT(4) NOT NULL,
  `vacinado_prev` TINYINT(4) NOT NULL,
  `esta_prenha` TINYINT(4) NOT NULL,
  `tempo_gestacao` VARCHAR(45) NOT NULL,
  `estado_atual` VARCHAR(45) NOT NULL,
  `info_extras` VARCHAR(45) NOT NULL,
  `criado_em` VARCHAR(45) NOT NULL,
  `NASCIMENTO_id` INT(11) NOT NULL,
  `NASCIMENTO_animal_id` INT(11) NOT NULL,
  `NASCIMENTO_lote_id` INT(11) NOT NULL,
  `cuidado_ID` INT(11) NOT NULL,
  `VACINACAO_ID` INT(11) NOT NULL,
  `MORTE_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`, `MORTE_id`),
  INDEX `fk_animal_NASCIMENTO1_idx` (`NASCIMENTO_id` ASC, `NASCIMENTO_animal_id` ASC, `NASCIMENTO_lote_id` ASC),
  INDEX `fk_animal_cuidado1_idx` (`cuidado_ID` ASC),
  INDEX `fk_animal_VACINACAO1_idx` (`VACINACAO_ID` ASC),
  INDEX `fk_animal_MORTE1_idx` (`MORTE_id` ASC),
  CONSTRAINT `fk_animal_MORTE1`
    FOREIGN KEY (`MORTE_id`)
    REFERENCES `mydb`.`morte` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_NASCIMENTO1`
    FOREIGN KEY (`NASCIMENTO_id` , `NASCIMENTO_animal_id` , `NASCIMENTO_lote_id`)
    REFERENCES `mydb`.`nascimento` (`id` , `animal_id` , `lote_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_VACINACAO1`
    FOREIGN KEY (`VACINACAO_ID`)
    REFERENCES `mydb`.`vacinacao` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_cuidado1`
    FOREIGN KEY (`cuidado_ID`)
    REFERENCES `mydb`.`cuidado` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`financeiro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`financeiro` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `lote_id` INT(11) NOT NULL,
  `tipo` ENUM('') NOT NULL,
  `valor` DECIMAL(10,0) NULL DEFAULT NULL,
  `descrição` VARCHAR(45) NULL DEFAULT NULL,
  `data` DATE NULL DEFAULT NULL,
  `agenda_id` INT(11) NOT NULL,
  PRIMARY KEY (`id`, `user_id`, `lote_id`),
  INDEX `fk_financeiro_agenda1_idx` (`agenda_id` ASC),
  CONSTRAINT `fk_financeiro_agenda1`
    FOREIGN KEY (`agenda_id`)
    REFERENCES `mydb`.`agenda` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`producao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`producao` (
  `ID` INT(11) NOT NULL AUTO_INCREMENT,
  `QUANTIDADE_KG` FLOAT NULL DEFAULT NULL,
  `TIPO` VARCHAR(45) NULL DEFAULT NULL,
  `DATA_REGISTRO` DATE NULL DEFAULT NULL,
  `MORTE_id` INT(11) NOT NULL,
  PRIMARY KEY (`ID`, `MORTE_id`),
  INDEX `fk_producao_MORTE1_idx` (`MORTE_id` ASC),
  CONSTRAINT `fk_producao_MORTE1`
    FOREIGN KEY (`MORTE_id`)
    REFERENCES `mydb`.`morte` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`lote` (CORRIGIDA)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lote` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL, -- O lote agora aponta para o criador (Usuário)
  `nome` VARCHAR(45) NOT NULL,
  `tipo` VARCHAR(32) NOT NULL,
  `data_criacao` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  `financeiro_id` INT(11) NOT NULL,
  `financeiro_user_id` INT(11) NOT NULL,
  `financeiro_lote_id` INT(11) NOT NULL,
  `animal_id` INT(11) NOT NULL,
  `producao_ID` INT(11) NOT NULL,
  PRIMARY KEY (`id`, `user_id`),
  INDEX `fk_lote_usuario1_idx` (`user_id` ASC),
  INDEX `fk_lote_financeiro1_idx` (`financeiro_id` ASC, `financeiro_user_id` ASC, `financeiro_lote_id` ASC),
  INDEX `fk_lote_animal1_idx` (`animal_id` ASC),
  INDEX `fk_lote_producao1_idx` (`producao_ID` ASC),
  -- CHAVE ESTRANGEIRA CORRETA: O Lote depende do Usuário existir primeiro
  CONSTRAINT `fk_lote_usuario1`
    FOREIGN KEY (`user_id`)
    REFERENCES `mydb`.`usuario` (`user_id`)
    ON DELETE CASCADE
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lote_animal1`
    FOREIGN KEY (`animal_id`)
    REFERENCES `mydb`.`animal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lote_financeiro1`
    FOREIGN KEY (`financeiro_id` , `financeiro_user_id` , `financeiro_lote_id`)
    REFERENCES `mydb`.`financeiro` (`id` , `user_id` , `lote_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lote_producao1`
    FOREIGN KEY (`producao_ID`)
    REFERENCES `mydb`.`producao` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;


-- -----------------------------------------------------
-- Table `mydb`.`usuario` (CORRIGIDA)
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`usuario` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(16) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `senha` VARCHAR(255) NOT NULL, -- Aumentado para não cortar o hash do password_hash()
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP(),
  `num_telefone` VARCHAR(15) NULL DEFAULT NULL, -- Mudado para NULL (opcional)
  `CPF` VARCHAR(11) NULL DEFAULT NULL,
  `CNPJ` VARCHAR(14) NULL DEFAULT NULL, -- Corrigido o erro de digitação 'CPNJ' e mudado para NULL
  PRIMARY KEY (`user_id`),
  UNIQUE INDEX `email_UNIQUE` (`email` ASC)
) ENGINE = InnoDB DEFAULT CHARACTER SET = utf8;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
