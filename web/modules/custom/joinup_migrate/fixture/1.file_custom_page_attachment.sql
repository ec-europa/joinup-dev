CREATE OR REPLACE VIEW d8_file_custom_page_attachment (
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
  CONCAT('public://custom-page/attachment/', SUBSTRING_INDEX(f.filepath, '/', -1))
FROM upload u
INNER JOIN node n ON u.vid = n.vid
INNER JOIN files f ON u.fid = f.fid
INNER JOIN d8_custom_page cp ON n.nid = cp.nid
WHERE TRIM(f.filepath) <> ''
AND f.filepath IS NOT NULL
