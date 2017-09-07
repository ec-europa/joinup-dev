CREATE OR REPLACE VIEW d8_release (
  nid,
  vid,
  title,
  body,
  created_time,
  changed_time,
  uri,
  solution,
  language,
  version_notes,
  version_number,
  state,
  item_state
) AS
SELECT
  n.nid,
  n.vid,
  n.title,
  TRIM(nr.body),
  n.created,
  n.changed,
  TRIM(uri.field_id_uri_value),
  g.nid,
  ctar.field_language_multiple_value,
  ctar.field_asset_version_note_value,
  cfav.field_asset_version_value,
  ws.state,
  m.content_item_state
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
INNER JOIN og_ancestry o ON n.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid AND g.type = 'project_project'
INNER JOIN d8_mapping m ON g.nid = m.nid
INNER JOIN content_type_asset_release ctar ON n.vid = ctar.vid
INNER JOIN content_field_asset_version cfav ON n.vid = cfav.vid AND cfav.delta = 0
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN workflow_node w ON m.nid = w.nid
LEFT JOIN workflow_states ws ON w.sid = ws.sid
WHERE n.type = 'asset_release'
