CREATE OR REPLACE ALGORITHM = MERGE VIEW d8_presentation_file (
  vid,
  fid,
  path,
  timestamp,
  uid
) AS
SELECT
  cfpuf.vid,
  f3.fid,
  TRIM(f3.filepath),
  IF(f3.timestamp > 0, f3.timestamp, NULL),
  IF(f3.uid > 0, f3.uid, -1)
FROM content_field_presentation_upload_files cfpuf
INNER JOIN files f3 ON cfpuf.field_presentation_upload_files_fid = f3.fid
WHERE f3.fid IS NOT NULL
AND TRIM(f3.filepath) <> ''
ORDER BY cfpuf.vid ASC, cfpuf.delta ASC
