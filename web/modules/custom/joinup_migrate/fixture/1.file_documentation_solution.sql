CREATE OR REPLACE VIEW d8_file_documentation_solution (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  docs_id,
  docs_path,
  docs_timestamp,
  docs_uid,
  CONCAT('public://documentation/', SUBSTRING_INDEX(docs_path, '/', -1))
FROM d8_solution
WHERE docs_id IS NOT NULL
AND docs_path IS NOT NULL
AND docs_path <> ''
