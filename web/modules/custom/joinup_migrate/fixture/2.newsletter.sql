CREATE OR REPLACE VIEW d8_newsletter (
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  newsletter
) AS
SELECT
  n.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  n.body,
  n.created,
  n.changed,
  n.uid,
  td.name
FROM d8_node n
INNER JOIN term_node tn ON n.vid = tn.vid
INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 64
WHERE n.type = 'newsletter'
