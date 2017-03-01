CREATE OR REPLACE ALGORITHM = MERGE VIEW d8_factsheet_file (
  vid,
  fid,
  path,
  timestamp,
  uid
) AS
SELECT
  cffuf.vid,
  f.fid,
  TRIM(f.filepath),
  IF(f.timestamp > 0, f.timestamp, NULL),
  IF(f.uid > 0, f.uid, -1)
FROM content_field_factsheet_upload_files cffuf
INNER JOIN files f ON cffuf.field_factsheet_upload_files_fid = f.fid
WHERE f.fid IS NOT NULL
AND TRIM(f.filepath) <> ''
ORDER BY cffuf.vid ASC, cffuf.delta ASC
LIMIT 1
