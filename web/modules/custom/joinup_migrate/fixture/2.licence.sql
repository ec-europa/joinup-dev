CREATE OR REPLACE VIEW d8_licence (
  nid,
  vid,
  title,
  body,
  uri,
  distribution,
  type
) AS
SELECT
  n.nid,
  n.vid,
  n.title,
  IF(nr.body <> '', nr.body, NULL),
  TRIM(uri.field_id_uri_value),
  d.nid,
  (SELECT td.name FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 75 WHERE n.vid = tn.vid LIMIT 1)
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
INNER JOIN content_field_distribution_licence cfdl ON n.nid = cfdl.field_distribution_licence_nid
INNER JOIN d8_distribution d ON cfdl.nid = d.nid
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
WHERE n.type = 'licence'
ORDER BY n.nid
