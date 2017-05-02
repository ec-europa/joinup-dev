CREATE OR REPLACE VIEW d8_node (
  collection,
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
  m.collection,
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
INNER JOIN d8_mapping m ON n.nid = m.nid AND m.migrate = 1
INNER JOIN d8_prepare p ON m.collection = p.collection
WHERE n.type IN('project_issue', 'og_page', 'case_epractice', 'document', 'factsheet', 'legaldocument', 'presentation', 'newsletter', 'news', 'event', 'project_issue')
ORDER BY n.nid ASC
