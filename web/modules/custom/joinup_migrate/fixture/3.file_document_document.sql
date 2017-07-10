CREATE OR REPLACE VIEW d8_file_document_document (
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
FROM content_field_additional_doc_file cfadf
INNER JOIN files f ON cfadf.field_additional_doc_file_fid = f.fid
INNER JOIN node n ON cfadf.vid = n.vid
INNER JOIN d8_mapping m ON n.nid = m.nid
WHERE n.type = 'document'
AND TRIM(f.filepath) <> ''
AND f.filepath IS NOT NULL
