CREATE OR REPLACE VIEW d8_custom_page (
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  group_type,
  group_nid,
  group_title,
  exclude
) AS
SELECT
  p.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  nr.body,
  n.created,
  n.changed,
  n.uid,
  g.type,
  g.nid,
  g.title,
  IF(ctop.field_og_page_exclude_menu_value = 0 AND n.status = 1, 0, 1)
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
INNER JOIN content_type_og_page ctop ON n.vid = ctop.vid
INNER JOIN og_ancestry o ON n.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid
INNER JOIN d8_mapping m ON g.nid = m.nid
INNER JOIN d8_prepare p ON m.collection = p.collection
WHERE n.type = 'og_page'
