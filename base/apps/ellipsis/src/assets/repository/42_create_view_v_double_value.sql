-- ---------------------------------------------------------------------------
-- View v_double_value
--
-- This value view represents the latest version of every active double value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_double_value AS
SELECT
    hex(d.uuid) AS uuid,
    hex(d.instance_uuid) AS instance_uuid,
    hex(d.property_uuid) AS property_uuid,
    dd.key AS `key`,
    dd.value AS value,
    NULL AS value_model_uuid,
    NULL AS value_instance_uuid,
    hex(v4.uuid) AS version_uuid,
    v3.created AS created,
    v4.created AS modified
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_instance i,
    t_property p,
    t_double d,
    t_double_data dd
LEFT JOIN t_double_data dd2 ON
    (
        dd.double_uuid = dd2.double_uuid AND
        dd.version_uuid < dd2.version_uuid
    )
WHERE
    dd2.version_uuid IS NULL AND
    d.uuid = dd.double_uuid AND
    d.instance_uuid = i.uuid AND
    d.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    d.version_uuid = v3.uuid AND
    dd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;

