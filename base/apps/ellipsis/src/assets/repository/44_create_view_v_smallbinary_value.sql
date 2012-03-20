-- ---------------------------------------------------------------------------
-- View v_smallbinary_value
--
-- This value view represents the latest version of every active smallbinary
-- value record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_smallbinary_value AS
SELECT
    hex(b.uuid) AS uuid,
    hex(b.instance_uuid) AS instance_uuid,
    hex(b.property_uuid) AS property_uuid,
    bd.key AS `key`,
    bd.value AS value,
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
    t_smallbinary b,
    t_smallbinary_data bd
LEFT JOIN t_smallbinary_data bd2 ON
    (
        bd.smallbinary_uuid = bd2.smallbinary_uuid AND
        bd.version_uuid < bd2.version_uuid
    )
WHERE
    bd2.version_uuid IS NULL AND
    b.uuid = bd.smallbinary_uuid AND
    b.instance_uuid = i.uuid AND
    b.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    bd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;

