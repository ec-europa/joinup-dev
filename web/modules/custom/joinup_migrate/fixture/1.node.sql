CREATE OR REPLACE VIEW d8_node (
  nid,
  vid,
  type,
  title,
  created,
  changed,
  uid,
  body
) AS
SELECT
  n.nid,
  n.vid,
  n.type,
  n.title,
  n.created,
  n.changed,
  IF(u.uid IS NOT NULL, u.uid, -1),
  nr.body
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
LEFT JOIN d8_user u ON n.uid = u.uid
WHERE n.type IN('project_issue', 'og_page', 'case_epractice', 'document', 'factsheet', 'legaldocument', 'presentation')
