UPDATE roles
SET permissions = JSON_SET(
    COALESCE(permissions, JSON_OBJECT()),
    '$.bitacora',
    JSON_ARRAY('view')
)
WHERE slug = 'administrador';
