CREATE OR REPLACE VIEW d8_file_collection_banner (
  fid,
  path,
  timestamp,
  uid,
  destination_uri
) AS
SELECT
  banner,
  banner,
  UNIX_TIMESTAMP(),
  -1,
  CONCAT('public://collection/banner/', SUBSTRING_INDEX(banner, '/', -1))
FROM d8_collection
WHERE banner IS NOT NULL
