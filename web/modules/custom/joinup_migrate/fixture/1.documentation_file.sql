CREATE OR REPLACE VIEW d8_documentation_file (
  nid,
  path,
  timestamp,
  uid
) AS
SELECT
  s.nid,
  s.docs_path,
  s.docs_timestamp,
  s.docs_uid
FROM d8_solution s
WHERE s.docs_path IS NOT NULL
AND s.docs_path <> ''
UNION
SELECT
  r.nid,
  r.docs_path,
  r.docs_timestamp,
  r.docs_uid
FROM d8_release r
WHERE r.docs_path IS NOT NULL
AND r.docs_path <> ''
