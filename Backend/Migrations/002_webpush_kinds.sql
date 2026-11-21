-- Aplica somente em instalações que já rodaram a versão anterior de 001_webpush_up.sql.
-- Adiciona a variante do lembrete ('antes' ou 'no_horario') à chave única do histórico,
-- permitindo um aviso antes do horário e outro no horário marcado para a mesma consulta.
-- Registros já existentes passam a valer como 'antes'.

ALTER TABLE webpush_deliveries
    ADD COLUMN kind VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'antes' AFTER starts_at,
    DROP INDEX webpush_delivery_unique,
    ADD UNIQUE KEY webpush_delivery_unique (id_consulta, subscription_id, starts_at, kind);
