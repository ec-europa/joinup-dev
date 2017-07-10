CREATE OR REPLACE VIEW d8_file_solution_banner (
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
  CONCAT('public://solution/banner/', SUBSTRING_INDEX(banner, '/', -1))
FROM d8_solution
WHERE banner IS NOT NULL
