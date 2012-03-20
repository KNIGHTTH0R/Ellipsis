-- ---------------------------------------------------------------------------
-- View v_model
--
-- This model view represents the latest version of every active model record
-- in the repository. All programmed code should refer to this model view 
-- INSTEAD of querying data from the t_model and t_model_data tables directly.
-- A model can be accessed directly by "uuid" or "name".
-- ---------------------------------------------------------------------------

CREATE VIEW v_model AS
SELECT
    hex(m.uuid) AS uuid,
    md.name AS name,
    md.description AS description,
    v2.created AS created
FROM
    t_version v1,
    t_version v2,
    t_model m,
    t_model_data md
LEFT JOIN t_model_data md2 ON
    (
        md.model_uuid = md2.model_uuid AND
        md.version_uuid < md2.version_uuid
    )
WHERE
    md2.version_uuid IS NULL AND
    m.uuid = md.model_uuid AND
    m.version_uuid = v1.uuid AND
    md.version_uuid = v2.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE;

