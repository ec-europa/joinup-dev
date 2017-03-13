CREATE OR REPLACE VIEW d8_newsletter_subscriber (
  id,
  status,
  mail,
  uid,
  langcode,
  newsletter,
  timestamp
) AS
SELECT
  s.snid,
  s.activated,
  s.mail,
  IF(u.uid IS NOT NULL, u.uid, 0),
  IF(s.language IS NOT NULL AND s.language <> '', s.language, 'en'),
  (SELECT GROUP_CONCAT(DISTINCT td.name ORDER BY td.name ASC) FROM simplenews_snid_tid st INNER JOIN term_data td ON st.tid = td.tid AND td.vid = 64 WHERE st.snid = s.snid),
  UNIX_TIMESTAMP()
FROM simplenews_subscriptions s
LEFT JOIN d8_user u ON s.uid = u.uid
