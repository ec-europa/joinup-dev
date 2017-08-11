CREATE OR REPLACE VIEW d8_owner_solution (
  type,
  nid,
  vid,
  title,
  uri,
  uid,
  solution,
  state,
  item_state
) AS
SELECT
  n2.type,
  n2.nid,
  n2.vid,
  n2.title,
  TRIM(uri2.field_id_uri_value),
  n2.uid,
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
ORDER BY n2.nid
