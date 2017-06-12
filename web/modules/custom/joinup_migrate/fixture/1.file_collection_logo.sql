CREATE OR REPLACE VIEW d8_file_collection_logo (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  logo_id,
  logo,
  logo_timestamp,
  logo_uid,
  CONCAT('public://collection/logo/', SUBSTRING_INDEX(logo, '/', -1))
FROM d8_collection
WHERE logo_id IS NOT NULL
