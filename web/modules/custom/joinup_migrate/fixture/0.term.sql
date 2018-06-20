CREATE OR REPLACE VIEW d8_term (
  node_vid,
  vocabulary,
  name,
  tid
) AS
SELECT
  tn.vid,
  td.vid,
  td.name,
  td.tid
FROM
  term_data td
  INNER JOIN term_node tn ON td.tid = tn.tid
