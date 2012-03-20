-- ---------------------------------------------------------------------------
-- View v_longtext_value
--
-- This value view represents the latest version of every active longtext
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_longtext_value AS
SELECT
    hex(t.uuid) AS uuid,
    hex(t.instance_uuid) AS instance_uuid,
    hex(t.property_uuid) AS property_uuid,
    td.key AS `key`,
    td.value AS value,
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
    t_longtext t,
    t_longtext_data td
LEFT JOIN t_longtext_data td2 ON
    (
        td.longtext_uuid = td2.longtext_uuid AND
        td.version_uuid < td2.version_uuid
    )
WHERE
    td2.version_uuid IS NULL AND
    t.uuid = td.longtext_uuid AND
    t.instance_uuid = i.uuid AND
    t.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    t.version_uuid = v3.uuid AND
    td.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


