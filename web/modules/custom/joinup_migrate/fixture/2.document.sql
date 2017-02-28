CREATE OR REPLACE VIEW d8_document (
  collection,
  nid,
  vid,
  type,
  document_type,
  title,
  created,
  changed,
  uid,
  body,
  publication_date,
  original_url,
  file_id,
  file_path,
  file_timestamp,
  file_uid
) AS
SELECT
  n.collection,
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
  cfpd.field_publication_date_value,
  IF(ctd.field_original_url_url = 'N/A', NULL, ctd.field_original_url_url),
  cfadf.field_additional_doc_file_fid,
  IF(f.filepath IS NOT NULL AND TRIM(f.filepath) <> '', TRIM(f.filepath), NULL),
  IF(f.timestamp > 0, f.timestamp, NULL),
  IF(f.uid IS NOT NULL, IF(f.uid > 0, f.uid, -1), NULL)
FROM d8_node n
LEFT JOIN content_field_publication_date cfpd ON n.vid = cfpd.vid
LEFT JOIN content_type_document ctd ON n.vid = ctd.vid
LEFT JOIN content_field_additional_doc_file cfadf ON n.vid = cfadf.vid
LEFT JOIN files f ON cfadf.field_additional_doc_file_fid = f.fid
WHERE n.type IN('case_epractice', 'document', 'factsheet', 'legaldocument', 'presentation')
