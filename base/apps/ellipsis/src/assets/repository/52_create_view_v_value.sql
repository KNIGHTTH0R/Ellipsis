-- ---------------------------------------------------------------------------
-- View v_value
--
-- This value view represents the latest version of every active value record
-- in the repository. All programmed code should refer to this value view
-- INSTEAD of querying data from the t_{$type} and t_{$type}_data tables
-- directly. A value can be accessed indirectly by "instance_uuid" and 
-- "property_uuid" then directly by "key" (if being used by the program).
-- ---------------------------------------------------------------------------

CREATE VIEW v_value AS
SELECT
    v.uuid AS uuid,
    v.instance_uuid AS instance_uuid,
    v.property_uuid AS property_uuid,
    vd.key AS `key`,
    vd.value AS value,
    vd.value_model_uuid AS value_model_uuid,
    vd.value_instance_uuid AS value_instance_uuid,
    v4.version_uuid AS version_uuid,
    v3.created AS created,
    v4.created AS modified
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    v_property p,
    GET_VALUE_TABLE(v.instance_uuid, v.property_uuid, p.type) AS v,
    GET_VALUE_DATA_TABLE(v.instance_uuid, v.property_uuid, p.type) AS vd,
    GET_VALUE_DATA_TABLE(v.instance_uuid, v.property_uuid, p.type) AS vd2,
    LEFT JOIN vd2 ON
    (
        vd.GET_VALUE_DATA_KEY(v.instance_uuid, v.property_uuid, p.type) = vd2.GET_VALUE_DATA_KEY(v.instance_uuid, v.property_uuid, p.type) AND
        vd.version_uuid < vd2.version_uuid
    )
WHERE
    vd2.version_uuid IS NULL AND
    v.uuid = vd.*_uuid AND
    v.instance_uuid = i.uuid AND
    v.property_uuid = p.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    b.version_uuid = v3.uuid AND
    vd.version_uuid = v4.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE;


