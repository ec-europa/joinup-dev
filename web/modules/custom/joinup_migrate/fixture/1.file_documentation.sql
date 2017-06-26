CREATE OR REPLACE VIEW d8_file_documentation (
  fid,
  path,
  timestamp,
  uid,
  destination_uri,
  url,
  vid
) AS
SELECT
  f.fid,
  SUBSTRING(TRIM(f.filepath), 21) AS path,
  f.timestamp,
  f.uid,
  CONCAT('public://documentation/', SUBSTRING_INDEX(f.filepath, '/', -1)),
  TRIM(ctd.field_documentation_access_url1_url),
  n.vid
FROM node n
INNER JOIN og_ancestry o ON n.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid
INNER JOIN content_field_asset_documentation cfad ON n.vid = cfad.vid
INNER JOIN node nd ON cfad.field_asset_documentation_nid = nd.nid
INNER JOIN content_type_documentation ctd ON nd.vid = ctd.vid
LEFT JOIN files f ON ctd.field_documentation_access_url_fid = f.fid
-- Filter on migrated items.
INNER JOIN d8_mapping m ON (g.nid = m.nid AND m.type = 'project_project') OR (n.nid = m.nid AND m.type = 'asset_release')
WHERE
  (
    f.filepath IS NOT NULL
    AND
    TRIM(f.filepath) <> ''
  )
  OR
  TRIM(ctd.field_documentation_access_url1_url) <> ''
