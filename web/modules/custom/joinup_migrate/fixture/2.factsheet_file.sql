CREATE OR REPLACE ALGORITHM = MERGE VIEW d8_factsheet_file (
  vid,
  fid,
  path,
  timestamp,
  uid
) AS
SELECT
  cffuf.vid,
  f4.fid,
  TRIM(f4.filepath),
  IF(f4.timestamp > 0, f4.timestamp, NULL),
  IF(f4.uid > 0, f4.uid, -1)
FROM content_field_factsheet_upload_files cffuf
INNER JOIN files f4 ON cffuf.field_factsheet_upload_files_fid = f4.fid
WHERE f4.fid IS NOT NULL
AND TRIM(f4.filepath) <> ''
ORDER BY cffuf.vid ASC, cffuf.delta ASC
LIMIT 1
