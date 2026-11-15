CREATE TABLE webpush_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_psicologo INT NOT NULL,
    endpoint_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(88) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    auth VARCHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    expires_at DATETIME DEFAULT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY webpush_endpoint_unique (endpoint_hash),
    KEY webpush_owner_active (id_psicologo, active),
    CONSTRAINT webpush_owner_fk FOREIGN KEY (id_psicologo) REFERENCES psicologo (id_psicologo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE webpush_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_consulta INT NOT NULL,
    subscription_id BIGINT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    status VARCHAR(16) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'pending',
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    http_status SMALLINT UNSIGNED DEFAULT NULL,
    next_attempt_at DATETIME DEFAULT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY webpush_delivery_unique (id_consulta, subscription_id, starts_at),
    CONSTRAINT webpush_delivery_subscription_fk FOREIGN KEY (subscription_id) REFERENCES webpush_subscriptions (id)
        ON DELETE CASCADE,
    CONSTRAINT webpush_delivery_consulta_fk FOREIGN KEY (id_consulta) REFERENCES consulta (id_consulta)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
