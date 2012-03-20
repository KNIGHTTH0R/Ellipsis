-- ---------------------------------------------------------------------------
-- View v_integer_value
--
-- This value view represents the latest version of every active integer value 
-- record in the repository. This view should be primarily called by the 
-- generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_integer_value AS
SELECT
    hex(n.uuid) AS uuid,
    hex(n.instance_uuid) AS instance_uuid,
    hex(n.property_uuid) AS property_uuid,
    nd.key AS `key`,
    nd.value AS value,
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
    t_integer n,
    t_integer_data nd
LEFT JOIN t_integer_data nd2 ON
    (
        nd.integer_uuid = nd2.integer_uuid AND
        nd.version_uuid < nd2.version_uuid
    )
WHERE
    nd2.version_uuid IS NULL AND
    n.uuid = nd.integer_uuid AND
    n.instance_uuid = i.uuid AND
    n.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    n.version_uuid = v3.uuid AND
    nd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;

