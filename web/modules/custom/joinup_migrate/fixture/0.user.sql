CREATE OR REPLACE VIEW d8_user (
  uid,
  status,
  name,
  pass,
  mail,
  created,
  access,
  login,
  timezone,
  timezone_name,
  init,
  nid,
  vid,
  last_name,
  first_name,
  company_name,
  photo_path,
  photo_timestamp,
  photo_uid,
  roles
) AS
SELECT
  u.uid,
  u.status,
  u.name,
  u.pass,
  u.mail,
  u.created,
  u.access,
  u.login,
  u.timezone,
  u.timezone_name,
  u.init,
  n.nid,
  n.vid,
  ctp.field_lastname_value,
  ctp.field_firstname_value,
  ctp.field_company_name_value,
  f.filepath,
  f.timestamp,
  IF(f.uid IS NOT NULL AND f.uid > 0, f.uid, -1),
  (SELECT GROUP_CONCAT(DISTINCT ur.rid ORDER BY ur.rid) FROM users_roles ur WHERE ur.uid = u.uid AND ur.rid IN(3, 6))
FROM users u
INNER JOIN userpoints up ON u.uid = up.uid
LEFT JOIN node n ON u.uid = n.uid AND n.type = 'profile'
LEFT JOIN content_type_profile ctp ON n.vid = ctp.vid
LEFT JOIN files f ON ctp.field_photo_fid = f.fid
WHERE u.uid > 0
AND u.status = 1
AND up.points >= 10
ORDER BY u.uid ASC
