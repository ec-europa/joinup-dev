CREATE OR REPLACE VIEW d8_video (
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  status,
  video
) AS
SELECT
  n.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  n.body,
  n.created,
  n.changed,
  n.uid,
  n.status,
  CONCAT('https://www.youtube.com/watch?v=', ctv.field_video_link_to_video_value)
FROM d8_node n
LEFT JOIN content_type_video ctv ON n.vid = ctv.vid
WHERE n.type = 'video'
