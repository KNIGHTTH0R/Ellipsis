-- ---------------------------------------------------------------------------
-- View v_model_instance_value
--
-- This value view represents the latest version of every active model 
-- instance value record in the repository. This view should be primarily 
-- called by the generic value view rather than directly.
-- ---------------------------------------------------------------------------

CREATE VIEW v_model_instance_value AS
SELECT
    hex(mi.uuid) AS uuid,
    hex(mi.instance_uuid) AS instance_uuid,
    hex(mi.property_uuid) AS property_uuid,
    mid.key AS `key`,
    NULL AS value,
    mid.model_uuid AS value_model_uuid,
    mid.instance_uuid AS value_instance_uuid,
    hex(v5.uuid) AS version_uuid,
    v4.created AS created,
    v5.created AS modified
FROM
    t_version v1,
    t_version v2,
    t_version v3,
    t_version v4,
    t_version v5,
    t_instance i,
    t_property p,
    t_model vm,
    t_model_instance mi,
    t_model_instance_data mid
LEFT JOIN t_model_instance_data mid2 ON
    (
        mid.model_instance_uuid = mid2.model_instance_uuid AND
        mid.version_uuid < mid2.version_uuid
    )
WHERE
    mid2.version_uuid IS NULL AND
    mi.uuid = mid.model_instance_uuid AND
    mi.instance_uuid = i.uuid AND
    mi.property_uuid = p.uuid AND
    mid.model_uuid = vm.uuid AND
    i.version_uuid = v1.uuid AND
    p.version_uuid = v2.uuid AND
    vm.version_uuid = v3.uuid AND
    mi.version_uuid = v4.uuid AND
    mid.version_uuid = v5.uuid AND
    v1.active = TRUE AND
    v2.active = TRUE AND
    v3.active = TRUE AND
    v4.active = TRUE AND
    v5.active = TRUE;


