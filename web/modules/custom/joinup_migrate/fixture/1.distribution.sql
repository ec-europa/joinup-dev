CREATE OR REPLACE VIEW d8_distribution (
  nid,
  vid,
  title,
  created_time,
  changed_time,
  uri,
  body,
  file_id,
  file_path,
  file_timestamp,
  file_uid,
  access_url,
  licence,
  parent_nid,
  parent_type
) AS
SELECT
  n.nid,
  n.vid,
  n.title,
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  TRIM(uri.field_id_uri_value),
  nr.body,
  f.fid,
  f.filepath,
  f.timestamp,
  f.uid,
  cfdau1.field_distribution_access_url1_url,
  nl.title,
  ar.nid,
  IF(g.type = 'project_project', 'release', 'solution')
FROM node n
INNER JOIN node_revisions nr ON n.vid = nr.vid
INNER JOIN content_field_asset_distribution cfad ON n.nid = cfad.field_asset_distribution_nid
INNER JOIN node ar ON cfad.vid = ar.vid AND ar.type = 'asset_release'
INNER JOIN og_ancestry o ON ar.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid
INNER JOIN content_type_distribution ctd ON n.vid = ctd.vid
LEFT JOIN files f ON ctd.field_distribution_access_url_fid = f.fid
LEFT JOIN content_field_distribution_access_url1 cfdau1 ON n.vid = cfdau1.vid
LEFT JOIN content_field_distribution_licence cfdl ON n.vid = cfdl.vid
LEFT JOIN node nl ON cfdl.field_distribution_licence_nid = nl.nid AND nl.type = 'licence'
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
WHERE n.type = 'distribution'
AND ar.nid IN(SELECT nid FROM d8_solution UNION SELECT nid FROM d8_release)
ORDER BY ar.nid ASC, n.nid ASC
