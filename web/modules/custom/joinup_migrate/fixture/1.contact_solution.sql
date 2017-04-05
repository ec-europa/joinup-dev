CREATE OR REPLACE VIEW d8_contact_solution (
  nid,
  vid,
  title,
  uri,
  created_time,
  changed_time,
  uid,
  mail,
  webpage,
  solution
) AS
SELECT
  n2.nid,
  n2.vid,
  n2.title,
  TRIM(uri2.field_id_uri_value),
  FROM_UNIXTIME(n2.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n2.changed, '%Y-%m-%dT%H:%i:%s'),
  n2.uid,
  cfcpm2.field_contact_point_mail_value,
  cfcpwp2.field_contact_point_web_page_url,
  s.nid
FROM d8_solution s
INNER JOIN content_type_asset_release ctar ON s.vid = ctar.vid
INNER JOIN node n2 ON ctar.field_asset_contact_point_nid = n2.nid
LEFT JOIN content_field_id_uri uri2 ON n2.vid = uri2.vid
LEFT JOIN content_field_contact_point_mail cfcpm2 ON n2.vid = cfcpm2.vid
LEFT JOIN content_field_contact_point_web_page cfcpwp2 ON n2.vid = cfcpwp2.vid
WHERE n2.type = 'contact_point'
ORDER BY n2.nid
