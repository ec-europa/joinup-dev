CREATE OR REPLACE VIEW d8_discussion (
  solution,
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  status
) AS
SELECT
  i.pid,
  s.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  CONCAT_WS(
    '\n',
    nr.body,
    IF(i.component IS NOT NULL AND TRIM(i.component) <> '', CONCAT('<h2>Component</h2>\n', TRIM(i.component), '\n'), ''),
    IF(i.category IS NOT NULL AND TRIM(i.category) <> '', CONCAT('<h2>Category</h2>\n', TRIM(i.category), '\n'), '')
  ),
  n.created,
  n.changed,
  IF(n.uid > 0, n.uid, -1),
  IF(i.sid IN(1, 4, 8, 13, 14), 1, 0)
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
INNER JOIN project_issues i ON n.nid = i.nid
INNER JOIN d8_solution s ON i.pid = s.nid
INNER JOIN node pr ON i.pid = pr.nid AND pr.type = 'project_project'
WHERE n.type = 'project_issue'

