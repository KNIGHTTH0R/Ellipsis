-- ---------------------------------------------------------------------------
-- View v_instance
--
-- This instance view represents the latest version of every active instance
-- record in the repository. All programmed code should refer to this instance 
-- view INSTEAD of querying data from the t_instance table directly. An 
-- instance can be accessed indirectly by "model_uuid" and then directly by 
-- "uuid".
-- ---------------------------------------------------------------------------

CREATE VIEW v_instance AS
SELECT
    hex(i.uuid) AS uuid,
    hex(i.model_uuid) AS model_uuid,
    v2.created AS created
FROM
    t_version v1,
    t_version v2,
    t_model m,
    t_instance i
LEFT JOIN t_instance i2 ON
    (
        i.uuid = i2.uuid AND
        i.version_uuid < i2.version_uuid
    )
WHERE
    i2.version_uuid IS NULL AND
    i.model_uuid = m.uuid AND
    m.version_uuid = v1.uuid AND
    i.version_uuid = v2.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE;

