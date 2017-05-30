CREATE OR REPLACE VIEW d8_document_file (
  nid,
  delta,
  vid,
  fid,
  path,
  timestamp,
  uid
) AS
-- Files from 'document'.
SELECT
  cfadf.nid,
  cfadf.delta,
  cfadf.vid,
  f1.fid,
  TRIM(f1.filepath),
  IF(f1.timestamp > 0, f1.timestamp, NULL),
  IF(f1.uid > 0, f1.uid, -1)
FROM content_field_additional_doc_file cfadf
INNER JOIN files f1 ON cfadf.field_additional_doc_file_fid = f1.fid
INNER JOIN node n1 ON cfadf.vid = n1.vid
INNER JOIN d8_document d1 ON n1.nid = d1.nid
WHERE f1.fid IS NOT NULL
AND TRIM(f1.filepath) <> ''
UNION
-- Files from 'case_epractice'.
SELECT
  cfcd.nid,
  cfcd.delta,
  cfcd.vid,
  f2.fid,
  TRIM(f2.filepath),
  IF(f2.timestamp > 0, f2.timestamp, NULL),
  IF(f2.uid > 0, f2.uid, -1)
FROM content_field_case_documentation cfcd
INNER JOIN files f2 ON cfcd.field_case_documentation_fid = f2.fid
INNER JOIN node n2 ON cfcd.vid = n2.vid
INNER JOIN d8_document d2 ON n2.nid = d2.nid
WHERE f2.fid IS NOT NULL
AND TRIM(f2.filepath) <> ''
UNION
-- Files from 'presentation'.
SELECT
  cfpuf.nid,
  cfpuf.delta,
  cfpuf.vid,
  f3.fid,
  TRIM(f3.filepath),
  IF(f3.timestamp > 0, f3.timestamp, NULL),
  IF(f3.uid > 0, f3.uid, -1)
FROM content_field_presentation_upload_files cfpuf
INNER JOIN files f3 ON cfpuf.field_presentation_upload_files_fid = f3.fid
INNER JOIN node n3 ON cfpuf.vid = n3.vid
INNER JOIN d8_document d3 ON n3.nid = d3.nid
WHERE f3.fid IS NOT NULL
AND TRIM(f3.filepath) <> ''
UNION
-- Files from 'factsheet'.
SELECT
  cffuf.nid,
  cffuf.delta,
  cffuf.vid,
  f4.fid,
  TRIM(f4.filepath),
  IF(f4.timestamp > 0, f4.timestamp, NULL),
  IF(f4.uid > 0, f4.uid, -1)
FROM content_field_factsheet_upload_files cffuf
INNER JOIN files f4 ON cffuf.field_factsheet_upload_files_fid = f4.fid
INNER JOIN node n4 ON cffuf.vid = n4.vid
INNER JOIN d8_document d4 ON n4.nid = d4.nid
WHERE f4.fid IS NOT NULL
AND TRIM(f4.filepath) <> ''
ORDER BY vid ASC, delta ASC
