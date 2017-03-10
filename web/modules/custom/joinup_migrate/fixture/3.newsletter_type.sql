CREATE OR REPLACE VIEW d8_newsletter_type (
  newsletter
) AS
SELECT
  n.newsletter
FROM d8_newsletter n
GROUP BY n.newsletter
UNION
SELECT
  td.name
FROM term_data td
INNER JOIN simplenews_snid_tid st ON td.tid = st.tid
INNER JOIN simplenews_subscriptions ss ON st.snid = ss.snid
WHERE td.vid = 64
ORDER BY newsletter
