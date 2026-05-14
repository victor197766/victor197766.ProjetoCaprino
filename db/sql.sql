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
-- Table `mydb`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`usuario` (
  `username` VARCHAR(16) NOT NULL,
  `email` VARCHAR(255) NULL,
  `senha` VARCHAR(32) NOT NULL,
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `num_telefone` VARCHAR(10) NOT NULL,
  `user_id` INT(9999) NOT NULL,
  `CPF` VARCHAR(11) NULL,
  `CPNJ` VARCHAR(14) NOT NULL,
  `lote_id` INT NOT NULL,
  `lote_ user_id` INT(9999) NOT NULL,
  PRIMARY KEY (`user_id`, `lote_id`, `lote_ user_id`),
  INDEX `fk_usuario_lote1_idx` (`lote_id` ASC, `lote_ user_id` ASC) VISIBLE,
  CONSTRAINT `fk_usuario_lote1`
    FOREIGN KEY (`lote_id` , `lote_ user_id`)
    REFERENCES `mydb`.`lote` (`id` , ` user_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


-- -----------------------------------------------------
-- Table `mydb`.`agenda`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`agenda` (
  `id` INT NOT NULL,
  `Titulo` VARCHAR(45) NOT NULL,
  `descriçao` VARCHAR(45) NOT NULL,
  `data_hora` VARCHAR(45) NOT NULL,
  `tipo` VARCHAR(45) NOT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`financeiro`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`financeiro` (
  `id` INT NOT NULL,
  `user_id` INT(9999) NOT NULL,
  `lote_id` INT NOT NULL,
  `tipo` ENUM('') NOT NULL,
  `valor` DECIMAL NULL,
  `descrição` VARCHAR(45) NULL,
  `data` DATE NULL,
  `agenda_id` INT NOT NULL,
  PRIMARY KEY (`id`, `user_id`, `lote_id`),
  INDEX `fk_financeiro_agenda1_idx` (`agenda_id` ASC) VISIBLE,
  CONSTRAINT `fk_financeiro_agenda1`
    FOREIGN KEY (`agenda_id`)
    REFERENCES `mydb`.`agenda` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`NASCIMENTO`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`NASCIMENTO` (
  `id` INT NOT NULL,
  `animal_id` INT NOT NULL,
  `lote_id` INT NOT NULL,
  `data_nascimento` DATE NULL,
  PRIMARY KEY (`id`, `animal_id`, `lote_id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`VACINACAO`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`VACINACAO` (
  `ID` INT NOT NULL,
  `data_aplicacao` DATE NULL,
  `proxima_dose` DATE NULL,
  `observacao` VARCHAR(45) NULL,
  PRIMARY KEY (`ID`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`vacina`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`vacina` (
  `id` INT NOT NULL,
  `nome` VARCHAR(45) NULL,
  `fabricante` VARCHAR(45) NULL,
  `dose_recomendada` VARCHAR(45) NULL,
  `VACINACAO_ID` INT NOT NULL,
  PRIMARY KEY (`id`, `VACINACAO_ID`),
  INDEX `fk_vacina_VACINACAO1_idx` (`VACINACAO_ID` ASC) VISIBLE,
  CONSTRAINT `fk_vacina_VACINACAO1`
    FOREIGN KEY (`VACINACAO_ID`)
    REFERENCES `mydb`.`VACINACAO` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`cuidado`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`cuidado` (
  `ID` INT NOT NULL,
  `animal_id` INT NULL,
  `tipo` VARCHAR(45) NULL,
  `descricao` TEXT(999) NULL,
  `doencas` TEXT(999) NULL,
  `tratamento` TEXT(999) NULL,
  `data_evento` DATE NULL,
  `criado_em` TIMESTAMP(999) NULL,
  `vacina_id` INT NOT NULL,
  PRIMARY KEY (`ID`, `vacina_id`),
  INDEX `fk_cuidado_vacina1_idx` (`vacina_id` ASC) VISIBLE,
  CONSTRAINT `fk_cuidado_vacina1`
    FOREIGN KEY (`vacina_id`)
    REFERENCES `mydb`.`vacina` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`MORTE`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`MORTE` (
  `id` INT NOT NULL,
  `causa` VARCHAR(45) NULL,
  `data_morte` DATE NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`animal`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`animal` (
  `id` INT(9999) NOT NULL,
  `matriz_id` INT(9999) NULL,
  `reprodutor_id` VARCHAR(45) NOT NULL,
  `nome` VARCHAR(45) NOT NULL,
  `especie` ENUM('') NOT NULL,
  `sexo` ENUM('') NOT NULL,
  `peso_kg` FLOAT(999) NOT NULL,
  `idade` INT(999) NOT NULL,
  `raca` VARCHAR(45) NOT NULL,
  `identificador` VARCHAR(45) NOT NULL,
  `nascimento_fazenda` TINYINT NOT NULL,
  `vacinado_prev` TINYINT NOT NULL,
  `esta_prenha` TINYINT NOT NULL,
  `tempo_gestacao` VARCHAR(45) NOT NULL,
  `estado_atual` VARCHAR(45) NOT NULL,
  `info_extras` VARCHAR(45) NOT NULL,
  `criado_em` VARCHAR(45) NOT NULL,
  `NASCIMENTO_id` INT NOT NULL,
  `NASCIMENTO_animal_id` INT NOT NULL,
  `NASCIMENTO_lote_id` INT NOT NULL,
  `cuidado_ID` INT NOT NULL,
  `VACINACAO_ID` INT NOT NULL,
  `MORTE_id` INT NOT NULL,
  PRIMARY KEY (`id`, `MORTE_id`),
  INDEX `fk_animal_NASCIMENTO1_idx` (`NASCIMENTO_id` ASC, `NASCIMENTO_animal_id` ASC, `NASCIMENTO_lote_id` ASC) VISIBLE,
  INDEX `fk_animal_cuidado1_idx` (`cuidado_ID` ASC) VISIBLE,
  INDEX `fk_animal_VACINACAO1_idx` (`VACINACAO_ID` ASC) VISIBLE,
  INDEX `fk_animal_MORTE1_idx` (`MORTE_id` ASC) VISIBLE,
  CONSTRAINT `fk_animal_NASCIMENTO1`
    FOREIGN KEY (`NASCIMENTO_id` , `NASCIMENTO_animal_id` , `NASCIMENTO_lote_id`)
    REFERENCES `mydb`.`NASCIMENTO` (`id` , `animal_id` , `lote_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_cuidado1`
    FOREIGN KEY (`cuidado_ID`)
    REFERENCES `mydb`.`cuidado` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_VACINACAO1`
    FOREIGN KEY (`VACINACAO_ID`)
    REFERENCES `mydb`.`VACINACAO` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_animal_MORTE1`
    FOREIGN KEY (`MORTE_id`)
    REFERENCES `mydb`.`MORTE` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`producao`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`producao` (
  `ID` INT NOT NULL,
  `QUANTIDADE_KG` FLOAT NULL,
  `TIPO` VARCHAR(45) NULL,
  `DATA_REGISTRO` DATE NULL,
  `MORTE_id` INT NOT NULL,
  PRIMARY KEY (`ID`, `MORTE_id`),
  INDEX `fk_producao_MORTE1_idx` (`MORTE_id` ASC) VISIBLE,
  CONSTRAINT `fk_producao_MORTE1`
    FOREIGN KEY (`MORTE_id`)
    REFERENCES `mydb`.`MORTE` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`lote`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`lote` (
  `id` INT NOT NULL AUTO_INCREMENT,
  ` user_id` INT(9999) NOT NULL,
  `nome` VARCHAR(45) NOT NULL,
  `tipo` VARCHAR(32) NOT NULL,
  `data_criacao` DATETIME NOT NULL,
  `financeiro_id` INT NOT NULL,
  `financeiro_user_id` INT(9999) NOT NULL,
  `financeiro_lote_id` INT NOT NULL,
  `animal_id` INT(9999) NOT NULL,
  `producao_ID` INT NOT NULL,
  PRIMARY KEY (`id`, ` user_id`),
  INDEX `fk_lote_financeiro1_idx` (`financeiro_id` ASC, `financeiro_user_id` ASC, `financeiro_lote_id` ASC) VISIBLE,
  INDEX `fk_lote_animal1_idx` (`animal_id` ASC) VISIBLE,
  INDEX `fk_lote_producao1_idx` (`producao_ID` ASC) VISIBLE,
  CONSTRAINT `fk_lote_financeiro1`
    FOREIGN KEY (`financeiro_id` , `financeiro_user_id` , `financeiro_lote_id`)
    REFERENCES `mydb`.`financeiro` (`id` , `user_id` , `lote_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lote_animal1`
    FOREIGN KEY (`animal_id`)
    REFERENCES `mydb`.`animal` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_lote_producao1`
    FOREIGN KEY (`producao_ID`)
    REFERENCES `mydb`.`producao` (`ID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `mydb`.`usuario_copy1`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`usuario_copy1` (
  `username` VARCHAR(16) NOT NULL,
  `email` VARCHAR(255) NULL,
  `senha` VARCHAR(32) NOT NULL,
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `num_telefone` VARCHAR(10) NOT NULL,
  `user_id` INT(9999) NOT NULL,
  `CPF` VARCHAR(11) NULL,
  `CPNJ` VARCHAR(14) NOT NULL,
  PRIMARY KEY (`user_id`));


-- -----------------------------------------------------
-- Table `mydb`.`usuario`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `mydb`.`usuario` (
  `username` VARCHAR(16) NOT NULL,
  `email` VARCHAR(255) NULL,
  `senha` VARCHAR(32) NOT NULL,
  `create_time` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `num_telefone` VARCHAR(10) NOT NULL,
  `user_id` INT(9999) NOT NULL,
  `CPF` VARCHAR(11) NULL,
  `CPNJ` VARCHAR(14) NOT NULL,
  `lote_id` INT NOT NULL,
  `lote_ user_id` INT(9999) NOT NULL,
  PRIMARY KEY (`user_id`, `lote_id`, `lote_ user_id`),
  INDEX `fk_usuario_lote1_idx` (`lote_id` ASC, `lote_ user_id` ASC) VISIBLE,
  CONSTRAINT `fk_usuario_lote1`
    FOREIGN KEY (`lote_id` , `lote_ user_id`)
    REFERENCES `mydb`.`lote` (`id` , ` user_id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION);


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
