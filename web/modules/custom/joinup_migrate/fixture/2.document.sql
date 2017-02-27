CREATE OR REPLACE VIEW d8_document (
  nid,
  vid,
  type,
  document_type,
  title,
  created,
  changed,
  uid,
  body,
  publication_date
) AS
SELECT
  n.nid,
  n.vid,
  n.type,
  IF(n.type = 'case_epractice', 'case', n.type),
  n.title,
  n.created,
  n.changed,
  n.uid,
  CONCAT(
    n.body,
    IF(ctd.field_isbn_value IS NOT NULL AND TRIM(ctd.field_isbn_value) <> '', CONCAT('<p>ISBN Number: ', TRIM(ctd.field_isbn_value), '</p>'), NULL),
    IF(ctd.field_description_of_license_value IS NOT NULL AND TRIM(ctd.field_description_of_license_value) <> '', CONCAT('<p>Description of license: ', TRIM(ctd.field_description_of_license_value), '</p>'), NULL),
    (SELECT CONCAT('<p>Nature of documentation: ', td.name, '</p>') FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 55 WHERE tn.vid = n.vid)
  ),
  cfpd.field_publication_date_value
FROM d8_node n
LEFT JOIN content_field_publication_date cfpd ON n.vid = cfpd.vid
LEFT JOIN content_type_document ctd ON n.vid = ctd.vid
WHERE n.type IN('case_epractice', 'document', 'factsheet', 'legaldocument', 'presentation')