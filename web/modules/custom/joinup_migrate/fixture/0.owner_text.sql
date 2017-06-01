CREATE OR REPLACE VIEW d8_owner_text (
  name,
  type
) AS
SELECT DISTINCT
  TRIM(owner_name),
  TRIM(owner_type)
FROM d8_mapping
WHERE owner_name IS NOT NULL
AND owner_type IS NOT NULL
AND TRIM(owner_name) <> ''
AND TRIM(owner_type) <> ''
