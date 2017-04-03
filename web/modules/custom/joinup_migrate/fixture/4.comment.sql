CREATE OR REPLACE VIEW d8_comment (
  nid,
  type,
  cid,
  pid,
  uid,
  status,
  subject,
  comment,
  thread,
  timestamp,
  name,
  mail,
  homepage,
  hostname
) AS
SELECT
  n.nid,
  n.type,
  c.cid,
  c.pid,
  c.uid,
  IF(c.status = 0, 1, 0),
  c.subject,
  c.comment,
  c.thread,
  c.timestamp,
  c.name,
  c.mail,
  c.homepage,
  c.hostname
FROM comments c
INNER JOIN node n ON c.nid = n.nid
WHERE c.nid IN(
  SELECT nid
  FROM d8_document
  UNION
  SELECT nid
  FROM d8_event
  UNION
  SELECT nid
  FROM d8_discussion
  UNION
  SELECT nid
  FROM d8_news
)
