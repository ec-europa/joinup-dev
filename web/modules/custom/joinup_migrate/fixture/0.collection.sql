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
  uid,
  owner,
  owner_text_name,
  owner_text_type,
  contact,
  banner,
  logo_id,
  logo,
  logo_timestamp,
  logo_uid,
  state,
  contact_email
) AS
SELECT
  p.collection,
  p.nid,
  n.vid,
  n.type,
  IFNULL(p.url, TRIM(uri.field_id_uri_value)),
  n.created,
  n.changed,
  p.policy,
  p.policy2,
  IF (p.abstract IS NOT NULL AND TRIM(p.abstract) <> '', TRIM(p.abstract), IF(o.og_description IS NOT NULL AND TRIM(o.og_description) <> '', TRIM(o.og_description), NULL)),
  IF (p.description IS NOT NULL AND TRIM(p.description) <> '', TRIM(p.description), IF(nr.body IS NOT NULL AND TRIM(nr.body) <> '', TRIM(nr.body), NULL)),
  IF(n.type = 'community', ctc.field_community_url_url, cfru.field_repository_url_url),
  p.elibrary,
  -- Pick-up the first, if there are more.
  SUBSTRING_INDEX(p.collection_owner, ',', 1),
  p.publisher,
  p.owner_text_name,
  p.owner_text_type,
  p.contact,
  IF(p.banner IS NOT NULL, CONCAT('../resources/migrate/collection/banner/', p.banner), NULL),
  IF(p.nid IS NULL, CONCAT('../resources/migrate/collection/logo/', p.logo), IF(n.type = 'community' AND fc.filepath IS NOT NULL AND TRIM(fc.filepath) <> '', fc.fid, IF(fr.filepath IS NOT NULL AND TRIM(fr.filepath) <> '', fr.fid, NULL))),
  IF(p.nid IS NULL, CONCAT('../resources/migrate/collection/logo/', p.logo), IF(n.type = 'community' AND fc.filepath IS NOT NULL AND TRIM(fc.filepath) <> '', SUBSTRING(TRIM(fc.filepath), 21), IF(fr.filepath IS NOT NULL AND TRIM(fr.filepath) <> '', SUBSTRING(TRIM(fr.filepath), 21), NULL))),
  IF(p.nid IS NULL, NULL, IF(n.type = 'community' AND fc.timestamp IS NOT NULL AND fc.timestamp > 0, fc.timestamp, IF(fr.timestamp IS NOT NULL AND fr.timestamp > 0, fr.timestamp, NULL))),
  IF(p.nid IS NOT NULL, -1, IF(n.type = 'community', fc.uid, fr.uid)),
  p.state,
  p.contact_email
FROM d8_prepare p
LEFT JOIN node n ON p.nid = n.nid
LEFT JOIN node_revisions nr ON n.vid = nr.vid
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN content_type_community ctc ON n.vid = ctc.vid
LEFT JOIN files fc ON ctc.field_community_logo_fid = fc.fid
LEFT JOIN content_type_repository ctr ON n.vid = ctr.vid
LEFT JOIN content_field_repository_url cfru ON ctr.vid = cfru.vid AND cfru.delta = 0
LEFT JOIN files fr ON ctr.field_repository_logo_fid = fr.fid
LEFT JOIN og o ON n.nid = o.nid
