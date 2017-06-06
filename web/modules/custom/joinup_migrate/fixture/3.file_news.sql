CREATE OR REPLACE VIEW d8_file_news (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  f.fid,
  SUBSTRING(f.filepath, 21),
  f.timestamp,
  f.uid,
  CONCAT('public://news/attachment/', SUBSTRING_INDEX(f.filepath, '/', -1))
FROM content_field_documentation cfd
INNER JOIN files f ON cfd.field_documentation_fid = f.fid
INNER JOIN node n ON cfd.vid = n.vid
INNER JOIN d8_news news ON n.nid = news.nid
