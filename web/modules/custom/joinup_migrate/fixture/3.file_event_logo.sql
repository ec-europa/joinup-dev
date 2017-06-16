CREATE OR REPLACE VIEW d8_file_event_logo (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  file_id,
  file_path,
  file_timestamp,
  file_uid,
  CONCAT('public://event/logo/', SUBSTRING_INDEX(file_path, '/', -1))
FROM d8_event
WHERE file_path IS NOT NULL
AND file_path <> ''
