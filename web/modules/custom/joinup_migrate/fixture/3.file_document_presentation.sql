CREATE OR REPLACE VIEW d8_file_document_presentation (
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
FROM content_field_presentation_upload_files cfpuf
INNER JOIN files f ON cfpuf.field_presentation_upload_files_fid = f.fid
INNER JOIN node n ON cfpuf.vid = n.vid
INNER JOIN d8_mapping m ON n.nid = m.nid
WHERE n.type = 'presentation'
WHERE TRIM(f.filepath) <> ''
AND f.filepath IS NOT NULL
