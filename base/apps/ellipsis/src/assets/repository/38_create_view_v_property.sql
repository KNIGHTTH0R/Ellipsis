-- ---------------------------------------------------------------------------
-- View v_property
--
-- This property view represents the latest version of every active property
-- record in the repository. All programmed code should refer to this property 
-- view INSTEAD of querying data from the t_property and t_property_data 
-- tables directly. A property can be accessed indirectly by "model_uuid" and
-- then directly by "uuid" or "name".
-- ---------------------------------------------------------------------------

CREATE VIEW v_property AS
SELECT
    hex(p.uuid) AS uuid,
    hex(p.model_uuid) AS model_uuid,
    pd.name AS name,
    pd.description AS description,
    pd.type AS `type`,
    pd.mincount AS mincount,
    pd.maxcount AS maxcount,
    pd.minlength AS minlength,
    pd.maxlength AS maxlength,
    pd.validation AS validation,
    pd.input_search AS input_search,
    pd.input_replace AS input_replace,
    pd.output_search AS output_search,
    pd.output_replace AS output_replace,
    v3.created AS created
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_model m,
    t_property p,
    t_property_data pd
LEFT JOIN t_property_data pd2 ON
    (
        pd.property_uuid = pd2.property_uuid AND
        pd.version_uuid < pd2.version_uuid
    )
WHERE
    pd2.version_uuid IS NULL AND
    p.uuid = pd.property_uuid AND
    p.model_uuid = m.uuid AND
    m.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    pd.version_uuid = v3.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE;

