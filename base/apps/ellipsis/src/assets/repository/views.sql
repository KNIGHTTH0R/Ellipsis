
SELECT
    m.uuid AS uuid,
    md.name AS name,
    md.description AS description,
    v1.created AS created
FROM
    t_model m,
    t_model_data md,
    t_version v1,
    t_version v2
WHERE
    m.uuid = md.model_uuid AND
    m.version_uuid = v1.uuid AND
    v1.active = TRUE AND
    md.version_uuid = v2.uuid AND
    v2.active = TRUE
ORDER BY
    v1.created ASC
GROUP BY
    m.uuid
