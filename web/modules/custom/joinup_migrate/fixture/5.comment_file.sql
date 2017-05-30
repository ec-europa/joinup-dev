CREATE OR REPLACE VIEW d8_comment_file (
  cid,
  fid,
  path,
  timestamp,
  uid
) AS
SELECT
  cu.cid,
  cu.fid,
  TRIM(f.filepath),
  f.timestamp,
  f.uid
FROM comment_upload cu
-- Filter out un-needed comments.
INNER JOIN d8_comment c ON cu.cid = c.cid
INNER JOIN files f ON cu.fid = f.fid
WHERE TRIM(f.filepath) <> ''
