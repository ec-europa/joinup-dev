CREATE OR REPLACE VIEW d8_file_user_photo (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  photo_id,
  photo_path,
  photo_timestamp,
  photo_uid,
  CONCAT('public://user/', DATE_FORMAT(FROM_UNIXTIME(photo_timestamp), '%Y-%m'), '/', SUBSTRING_INDEX(photo_path, '/', -1))
FROM d8_user
WHERE photo_id IS NOT NULL
