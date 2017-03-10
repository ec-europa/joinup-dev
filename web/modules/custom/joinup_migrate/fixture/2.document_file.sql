CREATE OR REPLACE VIEW d8_document_file (
  vid,
  fid,
  path,
  timestamp,
  uid,
  delta
) AS
SELECT
  cfadf.vid,
  f1.fid,
  TRIM(f1.filepath),
  IF(f1.timestamp > 0, f1.timestamp, NULL),
  IF(f1.uid > 0, f1.uid, -1),
  cfadf.delta
FROM content_field_additional_doc_file cfadf
INNER JOIN files f1 ON cfadf.field_additional_doc_file_fid = f1.fid
WHERE f1.fid IS NOT NULL
AND TRIM(f1.filepath) <> ''
UNION
SELECT
  cfcd.vid,
  f2.fid,
  TRIM(f2.filepath),
  IF(f2.timestamp > 0, f2.timestamp, NULL),
  IF(f2.uid > 0, f2.uid, -1),
  cfcd.delta
FROM content_field_case_documentation cfcd
INNER JOIN files f2 ON cfcd.field_case_documentation_fid = f2.fid
WHERE f2.fid IS NOT NULL
AND TRIM(f2.filepath) <> ''
UNION
SELECT
  cfpuf.vid,
  f3.fid,
  TRIM(f3.filepath),
  IF(f3.timestamp > 0, f3.timestamp, NULL),
  IF(f3.uid > 0, f3.uid, -1),
  cfpuf.delta
FROM content_field_presentation_upload_files cfpuf
INNER JOIN files f3 ON cfpuf.field_presentation_upload_files_fid = f3.fid
WHERE f3.fid IS NOT NULL
AND TRIM(f3.filepath) <> ''
UNION
SELECT
  cffuf.vid,
  f4.fid,
  TRIM(f4.filepath),
  IF(f4.timestamp > 0, f4.timestamp, NULL),
  IF(f4.uid > 0, f4.uid, -1),
  cffuf.delta
FROM content_field_factsheet_upload_files cffuf
INNER JOIN files f4 ON cffuf.field_factsheet_upload_files_fid = f4.fid
WHERE f4.fid IS NOT NULL
AND TRIM(f4.filepath) <> ''
ORDER BY vid ASC, delta ASC
