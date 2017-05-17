CREATE OR REPLACE VIEW d8_owner (
  type,
  nid,
  vid,
  title,
  uri,
  uid,
  collection,
  solution,
  state,
  item_state
) AS
SELECT
  n.type,
  n.nid,
  n.vid,
  n.title,
  TRIM(uri.field_id_uri_value),
  n.uid,
  c.collection,
  NULL,
  ws.state,
  NULL
FROM d8_collection c
INNER JOIN node n ON FIND_IN_SET(n.nid, c.owner)
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN workflow_node w ON n.nid = w.nid
LEFT JOIN workflow_states ws ON w.sid = ws.sid
UNION
SELECT
  n2.type,
  n2.nid,
  n2.vid,
  n2.title,
  TRIM(uri2.field_id_uri_value),
  n2.uid,
  NULL,
  s.nid,
  ws2.state,
  NULL
FROM d8_solution s
INNER JOIN content_field_asset_publisher cfap ON s.vid = cfap.vid
INNER JOIN node n2 ON cfap.field_asset_publisher_nid = n2.nid
LEFT JOIN content_field_id_uri uri2 ON n2.vid = uri2.vid
LEFT JOIN workflow_node w2 ON n2.nid = w2.nid
LEFT JOIN workflow_states ws2 ON w2.sid = ws2.sid
WHERE n2.type = 'publisher'
ORDER BY nid
