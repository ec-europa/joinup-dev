CREATE OR REPLACE VIEW d8_news (
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  source_url
) AS
SELECT
  n.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  CONCAT(n.body, IF(cfc.field_city_value IS NOT NULL AND TRIM(cfc.field_city_value) <> '', CONCAT('\n<p>City/Location: ', TRIM(cfc.field_city_value), '</p>\n'), '')),
  n.created,
  n.changed,
  n.uid,
  TRIM(ctn.field_source_url_url)
FROM d8_node n
INNER JOIN content_type_news ctn ON n.vid = ctn.vid
LEFT JOIN content_field_city cfc ON n.vid = cfc.vid
WHERE n.type = 'news'
