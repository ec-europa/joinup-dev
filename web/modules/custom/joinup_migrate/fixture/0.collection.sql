CREATE OR REPLACE VIEW d8_collection (
  collection,
  nid,
  vid,
  type,
  uri,
  created_time,
  changed_time,
  policy,
  policy2,
  abstract,
  body,
  access_url,
  elibrary,
  owner,
  owner_text_name,
  owner_text_type,
  contact,
  banner,
  logo,
  logo_timestamp,
  state,
  contact_email
) AS
SELECT
  p.collection,
  p.nid,
  n.vid,
  n.type,
  TRIM(uri.field_id_uri_value),
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  p.policy,
  p.policy2,
  IF (p.abstract IS NOT NULL AND p.abstract <> '', p.abstract, IF(o.og_description IS NOT NULL AND o.og_description <> '', o.og_description, NULL)),
  nr.body,
  IF(n.type = 'community', ctc.field_community_url_url, cfru.field_repository_url_url),
  p.elibrary,
  p.publisher,
  p.owner_text_name,
  p.owner_text_type,
  p.contact,
  p.banner,
  IF(p.nid = 0, CONCAT('../resources/migrate/collection/logo/', p.logo), IF(n.type = 'community' AND fc.filepath IS NOT NULL AND fc.filepath <> '', SUBSTRING(fc.filepath, 21), IF(fr.filepath IS NOT NULL AND fr.filepath <> '', SUBSTRING(fr.filepath, 21), NULL))),
  IF(p.nid = 0, NULL, IF(n.type = 'community' AND fc.timestamp IS NOT NULL AND fc.timestamp > 0, fc.timestamp, IF(fr.timestamp IS NOT NULL AND fr.timestamp > 0, fr.timestamp, NULL))),
  p.state,
  p.contact_email
FROM d8_prepare p
LEFT JOIN node n ON p.nid = n.nid
LEFT JOIN node_revisions nr ON n.vid = nr.vid
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN content_type_community ctc ON n.vid = ctc.vid
LEFT JOIN files fc ON ctc.field_community_logo_fid = fc.fid
LEFT JOIN content_type_repository ctr ON n.vid = ctr.vid
LEFT JOIN content_field_repository_url cfru ON ctr.vid = cfru.vid
LEFT JOIN files fr ON ctr.field_repository_logo_fid = fr.fid
LEFT JOIN og o ON n.nid = o.nid
