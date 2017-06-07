CREATE OR REPLACE VIEW d8_file_discussion_attachment (
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
FROM content_field_project_issues_attachement cfpia
INNER JOIN files f ON cfpia.field_project_issues_attachement_fid = f.fid
INNER JOIN node n ON cfpia.vid = n.vid
INNER JOIN d8_discussion d ON n.nid = d.nid
WHERE f.fid IS NOT NULL
AND TRIM(f.filepath) <> ''
AND f.filepath IS NOT NULL

