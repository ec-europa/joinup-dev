CREATE OR REPLACE VIEW d8_file_document_case (
  fid,
  path,
  timestamp,
  uid,
  destination_uri,
  nid
) AS
SELECT
  f.fid,
  SUBSTRING(TRIM(f.filepath), 21),
  f.timestamp,
  f.uid,
  CONCAT('public://document/', DATE_FORMAT(FROM_UNIXTIME(f.timestamp), '%Y-%m'), '/', SUBSTRING_INDEX(f.filepath, '/', -1)),
  n.nid
FROM content_field_case_documentation cfcd
INNER JOIN files f ON cfcd.field_case_documentation_fid = f.fid
INNER JOIN node n ON cfcd.vid = n.vid
INNER JOIN d8_mapping m ON n.nid = m.nid
WHERE n.type = 'case_epractice'
AND TRIM(f.filepath) <> ''
AND f.filepath IS NOT NULL