CREATE OR REPLACE VIEW d8_solution (
  collection,
  type,
  nid,
  vid,
  title,
  created_time,
  changed_time,
  uri,
  landing_page,
  docs_url,
  docs_path,
  docs_timestamp,
  docs_uid,
  policy,
  policy2,
  banner,
  body,
  logo,
  logo_timestamp,
  logo_uid,
  metrics_page,
  state,
  item_state
) AS
SELECT
  m.collection,
  m.type,
  m.nid,
  n.vid,
  n.title,
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  TRIM(uri.field_id_uri_value),
  TRIM(ctd.field_documentation_access_url1_url),
  TRIM(ctd1.field_documentation_access_url1_url),
  fd.filepath,
  fd.timestamp,
  fd.uid,
  m.policy,
  m.policy2,
  m.banner,
  nr.body,
  IF(m.logo IS NOT NULL, m.logo, IF(m.type = 'asset_release' AND fl.filepath IS NOT NULL AND fl.filepath <> '', fl.filepath, IF(fpl.filepath IS NOT NULL AND fpl.filepath <> '', fpl.filepath, NULL))),
  IF(m.logo IS NOT NULL, UNIX_TIMESTAMP(), IF(m.type = 'asset_release' AND fl.timestamp IS NOT NULL AND fl.timestamp > 0, fl.timestamp, IF(fpl.timestamp IS NOT NULL AND fpl.timestamp > 0, fpl.timestamp, NULL))),
  IF(m.logo IS NOT NULL, -1, IF(m.type = 'asset_release', fl.uid, fpl.uid)),
  TRIM(cfu.field_id_uri_value),
  ws.state,
  m.content_item_state
FROM d8_mapping m
INNER JOIN d8_prepare p ON m.collection = p.collection
INNER JOIN node n ON m.nid = n.nid
INNER JOIN node_revisions nr ON n.vid = nr.vid
LEFT JOIN og_ancestry o ON m.nid = o.nid
LEFT JOIN node g ON o.group_nid = g.nid AND g.type = 'repository'
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN content_type_asset_release car ON n.vid = car.vid
LEFT JOIN node d ON car.field_asset_homepage_doc_nid = d.nid
LEFT JOIN content_type_documentation ctd ON d.vid = ctd.vid
LEFT JOIN content_field_asset_documentation cfad ON car.vid = cfad.vid
LEFT JOIN node nd ON cfad.field_asset_documentation_nid = nd.nid
LEFT JOIN content_type_documentation ctd1 ON nd.vid = ctd1.vid
LEFT JOIN files fd ON ctd1.field_documentation_access_url_fid = fd.fid
LEFT JOIN workflow_node w ON m.nid = w.nid
LEFT JOIN workflow_states ws ON w.sid = ws.sid
LEFT JOIN content_field_asset_sw_metrics swm ON n.vid = swm.vid
LEFT JOIN node nm ON swm.field_asset_sw_metrics_nid = nm.nid
LEFT JOIN content_field_id_uri cfu ON nm.vid = cfu.vid
LEFT JOIN node nl ON car.field_asset_sw_logo_nid = nl.nid
LEFT JOIN content_type_documentation ctdl ON nl.vid = ctdl.vid
LEFT JOIN files fl ON ctdl.field_documentation_access_url_fid = fl.fid
LEFT JOIN content_field_project_soft_logo cfsl ON n.vid = cfsl.vid
LEFT JOIN files fpl ON cfsl.field_project_soft_logo_fid = fpl.fid
WHERE m.type IN('asset_release', 'project_project')
AND m.migrate = 1
AND (
  (m.type = 'asset_release' AND g.type = 'repository')
  OR
  m.type = 'project_project'
)
ORDER BY m.collection ASC, m.nid ASC