CREATE OR REPLACE VIEW d8_file_comment_attachment (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  f.fid,
  SUBSTRING(TRIM(f.filepath), 21),
  f.timestamp,
  f.uid,
  CONCAT('public://discussion/attachment/', SUBSTRING_INDEX(f.filepath, '/', -1))
FROM comment_upload cu
INNER JOIN files f ON cu.fid = f.fid
-- Filter out un-needed comments.
INNER JOIN d8_comment c ON cu.cid = c.cid
WHERE TRIM(f.filepath) <> ''
