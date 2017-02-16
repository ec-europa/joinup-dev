CREATE OR REPLACE VIEW d8_release (
  nid,
  vid,
  title,
  created_time,
  changed_time,
  uri,
  solution,
  language,
  version_notes,
  version_number
) AS
SELECT
  n.nid,
  n.vid,
  n.title,
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  TRIM(uri.field_id_uri_value),
  g.nid,
  ctar.field_language_multiple_value,
  ctar.field_asset_version_note_value,
  cfav.field_asset_version_value
FROM node n
INNER JOIN og_ancestry o ON n.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid AND g.type = 'project_project'
INNER JOIN d8_mapping m ON g.nid = m.nid AND m.migrate = 1
INNER JOIN content_type_asset_release ctar ON n.vid = ctar.vid
INNER JOIN content_field_asset_version cfav ON n.vid = cfav.vid
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
WHERE n.type = 'asset_release'
ORDER BY g.nid ASC, n.nid ASC
