CREATE OR REPLACE VIEW d8_contact_email (
  name
) AS
SELECT DISTINCT
  TRIM(c.field_project_common_contact_value)
FROM content_field_project_common_contact c
INNER JOIN node p ON c.vid = p.vid
INNER JOIN d8_solution s ON p.nid = s.nid
WHERE TRIM(c.field_project_common_contact_value) <> ''
AND c.field_project_common_contact_value IS NOT NULL
ORDER BY c.field_project_common_contact_value ASC
