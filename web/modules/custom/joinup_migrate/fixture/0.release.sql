CREATE OR REPLACE VIEW d8_release (
  nid,
  vid,
  title,
  created_time,
  changed_time,
  uri,
  solution,
  docs_url,
  docs_path,
  docs_timestamp,
  docs_uid,
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
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  TRIM(uri.field_id_uri_value),
  g.nid,
  TRIM(ctd.field_documentation_access_url1_url),
  fd.filepath,
  fd.timestamp,
  fd.uid,
  ctar.field_language_multiple_value,
  ctar.field_asset_version_note_value,
  cfav.field_asset_version_value,
  ws.state,
  m.content_item_state
FROM node n
INNER JOIN og_ancestry o ON n.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid AND g.type = 'project_project'
INNER JOIN d8_mapping m ON g.nid = m.nid
INNER JOIN content_type_asset_release ctar ON n.vid = ctar.vid
INNER JOIN content_field_asset_version cfav ON n.vid = cfav.vid
LEFT JOIN content_field_asset_documentation cfad ON ctar.vid = cfad.vid
LEFT JOIN node nd ON cfad.field_asset_documentation_nid = nd.nid
LEFT JOIN content_type_documentation ctd ON nd.vid = ctd.vid
LEFT JOIN files fd ON ctd.field_documentation_access_url_fid = fd.fid
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN workflow_node w ON m.nid = w.nid
LEFT JOIN workflow_states ws ON w.sid = ws.sid
WHERE n.type = 'asset_release'
