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
  policy_context,
  desc_target_users_groups,
  desc_implementation,
  tech_solution,
  main_results,
  roi_desc,
  track_record_sharing,
  lessons_learnt,
  publication_date,
  technology_choice,
  return_investment,
  scope,
  case_sector,
  target_users_or_group,
  factsheet_topic,
  presentation_nature_of_doc,
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
  CONCAT(n.title, IF(ctce.field_acronym_value IS NOT NULL AND TRIM(ctce.field_acronym_value) <> '', CONCAT(' (', TRIM(ctce.field_acronym_value), ')'), '')),
  n.created,
  n.changed,
  n.uid,
  CONCAT(
    n.body,
    IF(ctd.field_isbn_value IS NOT NULL AND TRIM(ctd.field_isbn_value) <> '', CONCAT('\n<p>ISBN Number: ', TRIM(ctd.field_isbn_value), '</p>\n'), ''),
    IF(ctd.field_description_of_license_value IS NOT NULL AND TRIM(ctd.field_description_of_license_value) <> '', CONCAT('<p>Description of license: ', TRIM(ctd.field_description_of_license_value), '</p>\n'), ''),
    IFNULL((SELECT CONCAT('<p>Nature of documentation: ', td.name, '</p>\n') FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 55 WHERE tn.vid = n.vid AND n.type = 'document'), '')
  ),
  ctce.field_policy_context_value,
  ctce.field_desc_target_users_groups_value,
  ctce.field_desc_implementation_value,
  ctce.field_tech_solution_value,
  ctce.field_main_results_value,
  ctce.field_roi_desc_value,
  ctce.field_track_record_sharing_value,
  ctce.field_lessons_learnt_value,
  cfpd.field_publication_date_value,
  (SELECT CONCAT('Technology choice: ', GROUP_CONCAT(DISTINCT td.name SEPARATOR ', ')) FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 50 WHERE tn.vid = n.vid AND td.name <> 'Not applicable / Not available'),
  (SELECT CONCAT('Return on investment: ', GROUP_CONCAT(DISTINCT td.name)) FROM content_type_case_epractice c INNER JOIN term_data td ON c.field_return_investment_value = td.tid WHERE c.vid = n.vid),
  (SELECT CONCAT('Scope: ', GROUP_CONCAT(DISTINCT td.name SEPARATOR ', ')) FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 45 WHERE tn.vid = n.vid AND td.name <> 'Other'),
  (SELECT GROUP_CONCAT(DISTINCT td.name) FROM content_field_case_sector s INNER JOIN term_data td ON s.field_case_sector_value = td.tid WHERE s.vid = n.vid AND td.name <> 'Other'),
  (SELECT GROUP_CONCAT(DISTINCT td.name) FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 85 WHERE tn.vid = n.vid AND td.name <> 'Other'),
  (SELECT GROUP_CONCAT(DISTINCT td.name) FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 57 WHERE tn.vid = n.vid),
  (SELECT GROUP_CONCAT(DISTINCT td.name) FROM term_node tn INNER JOIN term_data td ON tn.tid = td.tid AND td.vid = 55 WHERE tn.vid = n.vid AND n.type = 'presentation'),
  CASE n.type
    WHEN 'document' THEN IF(ctd.field_original_url_url = 'N/A', NULL, ctd.field_original_url_url)
    WHEN 'case_epractice' THEN IF(cfcd.field_case_documentation_fid IS NULL, ctce.field_website_url_url, NULL)
  END,
  CASE n.type
    WHEN 'document' THEN cfadf.field_additional_doc_file_fid
    WHEN 'case_epractice' THEN cfcd.field_case_documentation_fid
    WHEN 'factsheet' THEN d8ff.fid
    WHEN 'presentation' THEN d8pf.fid
  END,
  CASE n.type
    WHEN 'document' THEN IF(f.filepath IS NOT NULL AND TRIM(f.filepath) <> '', TRIM(f.filepath), NULL)
    WHEN 'case_epractice' THEN IF(f1.filepath IS NOT NULL AND TRIM(f1.filepath) <> '', TRIM(f1.filepath), NULL)
    WHEN 'factsheet' THEN d8ff.path
    WHEN 'presentation' THEN d8pf.path
  END,
  CASE n.type
    WHEN 'document' THEN IF(f.timestamp > 0, f.timestamp, NULL)
    WHEN 'case_epractice' THEN IF(f1.timestamp > 0, f1.timestamp, NULL)
    WHEN 'factsheet' THEN d8ff.timestamp
    WHEN 'presentation' THEN d8pf.timestamp
  END,
  CASE n.type
    WHEN 'document' THEN IF(f.uid IS NOT NULL, IF(f.uid > 0, f.uid, -1), NULL)
    WHEN 'case_epractice' THEN IF(f1.uid IS NOT NULL, IF(f1.uid > 0, f1.uid, -1), NULL)
    WHEN 'factsheet' THEN d8ff.uid
    WHEN 'presentation' THEN d8pf.uid
  END
FROM d8_node n
LEFT JOIN content_field_publication_date cfpd ON n.vid = cfpd.vid
LEFT JOIN content_type_document ctd ON n.vid = ctd.vid
LEFT JOIN content_field_additional_doc_file cfadf ON n.vid = cfadf.vid
LEFT JOIN files f ON cfadf.field_additional_doc_file_fid = f.fid
LEFT JOIN content_type_case_epractice ctce ON n.vid = ctce.vid
LEFT JOIN content_field_case_documentation cfcd ON n.vid = cfcd.vid
LEFT JOIN files f1 ON cfcd.field_case_documentation_fid = f1.fid
LEFT JOIN d8_factsheet_file d8ff ON n.vid = d8ff.vid
LEFT JOIN d8_presentation_file d8pf ON n.vid = d8pf.vid
WHERE n.type IN('case_epractice', 'document', 'factsheet', 'legaldocument', 'presentation')
