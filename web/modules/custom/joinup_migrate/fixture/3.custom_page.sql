CREATE OR REPLACE VIEW d8_custom_page (
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
  n.gid
FROM d8_node_og n
INNER JOIN d8_collection c ON n.gid = c.nid
WHERE n.type = 'og_page'
