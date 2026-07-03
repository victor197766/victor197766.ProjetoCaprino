-- ============================================================
--  CORREÇÃO: Tornar colunas auxiliares da tabela `animal`
--  opcionais (NULL), já que um animal recém-cadastrado
--  ainda não possui morte, vacinação, nascimento, etc.
-- ============================================================

USE `mydb`;

ALTER TABLE `animal`
    -- Colunas que devem ser opcionais (animal novo não tem esses dados ainda)
    MODIFY COLUMN `MORTE_id`            INT(11)      NULL DEFAULT NULL,
    MODIFY COLUMN `NASCIMENTO_id`       INT(11)      NULL DEFAULT NULL,
    MODIFY COLUMN `NASCIMENTO_animal_id` INT(11)     NULL DEFAULT NULL,
    MODIFY COLUMN `NASCIMENTO_lote_id`  INT(11)      NULL DEFAULT NULL,
    MODIFY COLUMN `cuidado_ID`          INT(11)      NULL DEFAULT NULL,
    MODIFY COLUMN `VACINACAO_ID`        INT(11)      NULL DEFAULT NULL,

    -- Campos de texto que também estavam NOT NULL mas podem estar vazios
    MODIFY COLUMN `nome`           VARCHAR(45)  NULL DEFAULT NULL,
    MODIFY COLUMN `tempo_gestacao` VARCHAR(45)  NULL DEFAULT NULL,
    MODIFY COLUMN `estado_atual`   VARCHAR(45)  NULL DEFAULT NULL,
    MODIFY COLUMN `info_extras`    VARCHAR(200) NULL DEFAULT NULL,
    MODIFY COLUMN `criado_em`      TIMESTAMP    NULL DEFAULT CURRENT_TIMESTAMP,

    -- reprodutor_id pode ser desconhecido
    MODIFY COLUMN `reprodutor_id`  VARCHAR(45)  NULL DEFAULT NULL;
