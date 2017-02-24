CREATE OR REPLACE VIEW d8_node_og (
  nid,
  vid,
  type,
  title,
  created,
  changed,
  uid,
  body,
  gid
) AS
SELECT
  n.nid,
  n.vid,
  n.type,
  n.title,
  n.created,
  n.changed,
  n.uid,
  n.body,
  o.group_nid
FROM d8_node n
LEFT JOIN og_ancestry o ON n.nid = o.nid
WHERE n.type IN('og_page', 'case_epractice', 'document', 'legaldocument')
