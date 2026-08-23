SET @doctor_permissions = JSON_OBJECT(
  'inicio', JSON_ARRAY('view'),
  'agenda', JSON_ARRAY('view', 'edit'),
  'historia_clinica', JSON_ARRAY('view', 'create')
);

INSERT INTO roles (
  company_id,
  name,
  slug,
  scope,
  description,
  permissions,
  is_system,
  created_at,
  updated_at
)
SELECT
  companies.id,
  'Doctor',
  'doctor',
  'company',
  'Atiende citas asignadas y registra historia clinica solo de sus pacientes.',
  @doctor_permissions,
  1,
  NOW(),
  NOW()
FROM companies
WHERE NOT EXISTS (
  SELECT 1
  FROM roles
  WHERE roles.company_id = companies.id
    AND roles.slug = 'doctor'
);

UPDATE roles
SET
  name = 'Doctor',
  scope = 'company',
  description = 'Atiende citas asignadas y registra historia clinica solo de sus pacientes.',
  permissions = @doctor_permissions,
  is_system = 1,
  updated_at = NOW()
WHERE slug = 'doctor';
