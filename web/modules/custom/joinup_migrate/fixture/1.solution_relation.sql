CREATE OR REPLACE VIEW d8_solution_relation (
  nid,
  value
) AS
SELECT
  r.nid,
  r.field_asset_node_reference_value
FROM content_field_asset_node_reference r
INNER JOIN d8_solution s ON r.vid = s.vid
INNER JOIN og_ancestry o ON s.nid = o.nid
INNER JOIN node g ON o.group_nid = g.nid
WHERE s.type = 'asset_release'
AND r.field_asset_node_reference_type IS NOT NULL
AND g.type = 'repository'
