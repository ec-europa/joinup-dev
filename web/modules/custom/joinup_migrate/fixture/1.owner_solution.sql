CREATE OR REPLACE VIEW d8_owner_solution (
  nid,
  vid,
  title,
  uri,
  uid,
  solution
) AS
SELECT
  n2.nid,
  n2.vid,
  n2.title,
  TRIM(uri2.field_id_uri_value),
  n2.uid,
  s.nid
FROM d8_solution s
INNER JOIN content_field_asset_publisher cfap ON s.vid = cfap.vid
INNER JOIN node n2 ON cfap.field_asset_publisher_nid = n2.nid
LEFT JOIN content_field_id_uri uri2 ON n2.vid = uri2.vid
WHERE n2.type = 'publisher'
ORDER BY n2.nid
