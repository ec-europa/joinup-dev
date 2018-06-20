CREATE OR REPLACE VIEW d8_owner_text (
  name,
  type
) AS
(
  SELECT
    TRIM(CHAR(9) FROM TRIM(owner_name)),
    TRIM(CHAR(9) FROM TRIM(owner_type))
  FROM d8_mapping
  WHERE owner_name IS NOT NULL
  AND owner_type IS NOT NULL
  AND TRIM(CHAR(9) FROM TRIM(owner_name)) <> ''
  AND TRIM(CHAR(9) FROM TRIM(owner_type)) <> ''
)
UNION (
  SELECT
    TRIM(CHAR(9) FROM TRIM(owner_text_name)),
    TRIM(CHAR(9) FROM TRIM(owner_text_type))
  FROM d8_prepare
  WHERE owner_text_name IS NOT NULL
  AND owner_text_name IS NOT NULL
  AND TRIM(CHAR(9) FROM TRIM(owner_text_name)) <> ''
  AND TRIM(CHAR(9) FROM TRIM(owner_text_type)) <> ''
)
