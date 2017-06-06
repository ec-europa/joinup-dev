CREATE OR REPLACE VIEW d8_file_distribution (
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
  CONCAT('public://distribution/', DATE_FORMAT(FROM_UNIXTIME(file_timestamp), '%Y-%m'), '/', SUBSTRING_INDEX(file_path, '/', -1))
FROM d8_distribution
WHERE file_id IS NOT NULL
