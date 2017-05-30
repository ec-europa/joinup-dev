CREATE OR REPLACE VIEW d8_attachment (
  type,
  nid,
  delta,
  vid,
  fid,
  path,
  timestamp,
  uid
) AS
-- Files from 'discussion'.
SELECT
  n1.type,
  cfpia.nid,
  cfpia.delta,
  cfpia.vid,
  f1.fid,
  TRIM(f1.filepath),
  IF(f1.timestamp > 0, f1.timestamp, NULL),
  IF(f1.uid > 0, f1.uid, -1)
FROM content_field_project_issues_attachement cfpia
INNER JOIN files f1 ON cfpia.field_project_issues_attachement_fid = f1.fid
INNER JOIN node n1 ON cfpia.vid = n1.vid
INNER JOIN d8_discussion d ON n1.nid = d.nid
WHERE cfpia.field_project_issues_attachement_fid IS NOT NULL
AND TRIM(f1.filepath) <> ''
UNION
-- Files from 'event'.
SELECT
  n2.type,
  cfed.nid,
  cfed.delta,
  cfed.vid,
  f2.fid,
  TRIM(f2.filepath),
  IF(f2.timestamp > 0, f2.timestamp, NULL),
  IF(f2.uid > 0, f2.uid, -1)
FROM content_field_event_documentation cfed
INNER JOIN files f2 ON cfed.field_event_documentation_fid = f2.fid
INNER JOIN node n2 ON cfed.vid = n2.vid
INNER JOIN d8_event e ON n2.nid = e.nid
WHERE cfed.field_event_documentation_fid IS NOT NULL
AND TRIM(f2.filepath) <> ''
UNION
-- Files from 'news'.
SELECT
  n3.type,
  cfd.nid,
  cfd.delta,
  cfd.vid,
  f3.fid,
  TRIM(f3.filepath),
  IF(f3.timestamp > 0, f3.timestamp, NULL),
  IF(f3.uid > 0, f3.uid, -1)
FROM content_field_documentation cfd
INNER JOIN files f3 ON cfd.field_documentation_fid = f3.fid
INNER JOIN node n3 ON cfd.vid = n3.vid
INNER JOIN d8_news n ON n3.nid = n.nid
WHERE cfd.field_documentation_fid IS NOT NULL
AND TRIM(f3.filepath) <> ''
-- Applies overall
ORDER BY nid ASC, delta ASC
