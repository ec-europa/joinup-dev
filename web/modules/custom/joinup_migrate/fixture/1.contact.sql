CREATE OR REPLACE VIEW d8_contact (
  nid,
  vid,
  title,
  uri,
  created_time,
  changed_time,
  uid,
  mail,
  webpage,
  collection,
  solution
) AS
SELECT
  n.nid,
  n.vid,
  n.title,
  TRIM(uri.field_id_uri_value),
  FROM_UNIXTIME(n.created, '%Y-%m-%dT%H:%i:%s'),
  FROM_UNIXTIME(n.changed, '%Y-%m-%dT%H:%i:%s'),
  n.uid,
  cfcpm.field_contact_point_mail_value,
  cfcpwp.field_contact_point_web_page_url,
  c.collection,
  NULL
FROM d8_collection c
INNER JOIN node n ON FIND_IN_SET(n.nid, c.contact)
LEFT JOIN content_field_id_uri uri ON n.vid = uri.vid
LEFT JOIN content_field_contact_point_mail cfcpm ON n.vid = cfcpm.vid
LEFT JOIN content_field_contact_point_web_page cfcpwp ON n.vid = cfcpwp.vid
UNION
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
  NULL,
  s.nid
FROM d8_solution s
INNER JOIN content_type_asset_release ctar ON s.vid = ctar.vid
INNER JOIN node n2 ON ctar.field_asset_contact_point_nid = n2.nid
LEFT JOIN content_field_id_uri uri2 ON n2.vid = uri2.vid
LEFT JOIN content_field_contact_point_mail cfcpm2 ON n2.vid = cfcpm2.vid
LEFT JOIN content_field_contact_point_web_page cfcpwp2 ON n2.vid = cfcpwp2.vid
WHERE n2.type = 'contact_point'
