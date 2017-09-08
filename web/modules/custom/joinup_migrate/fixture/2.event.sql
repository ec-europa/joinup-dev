CREATE OR REPLACE VIEW d8_event (
  collection,
  type,
  nid,
  vid,
  title,
  body,
  created,
  changed,
  uid,
  start_date,
  end_date,
  city,
  venue,
  address,
  coord,
  organisation,
  website,
  mail,
  agenda,
  file_id,
  file_path,
  file_timestamp,
  file_uid,
  state
) AS
SELECT
  n.collection,
  n.type,
  n.nid,
  n.vid,
  n.title,
  CONCAT_WS(
    '\n',
    n.body,
    IF(cte.field_event_exp_participants_value IS NOT NULL AND TRIM(cte.field_event_exp_participants_value) <> '', CONCAT('<div>Expected Participants: ', TRIM(cte.field_event_exp_participants_value), '</div>'), ''),
    IF(cte.field_event_state_value IS NOT NULL AND TRIM(cte.field_event_state_value) <> '', CONCAT('<div>State: ', TRIM(cte.field_event_state_value), '</div>'), '')
  ),
  n.created,
  n.changed,
  n.uid,
  DATE_FORMAT(cte.field_event_dates_value, '%Y-%m-%d\T%H:%i:%s'),
  DATE_FORMAT(cte.field_event_dates_value2, '%Y-%m-%d\T%H:%i:%s'),
  cte.field_event_city_value,
  cte.field_event_venue_value,
  cte.field_event_address_location_value,
  cte.field_event_gmap_location_value,
  cte.field_event_organiser_value,
  cte.field_event_website_url,
  cte.field_event_contact_email_value,
  cte.field_event_agenda_value,
  f.fid,
  IF(f.filepath IS NOT NULL AND TRIM(f.filepath) <> '', SUBSTRING(TRIM(f.filepath), 21), NULL),
  IF(f.timestamp > 0, f.timestamp, NULL),
  IF(f.uid > 0, f.uid, -1),
  IFNULL(ws.state, 'validated')
FROM d8_node n
LEFT JOIN content_type_event cte ON n.vid = cte.vid
LEFT JOIN files f ON cte.field_event_logo_fid = f.fid
LEFT JOIN workflow_node w ON n.nid = w.nid
LEFT JOIN workflow_states ws ON w.sid = ws.sid
WHERE n.type = 'event'
AND cte.field_event_dates_value IS NOT NULL
AND SUBSTR(cte.field_event_dates_value, 1, 10) <> '1970-01-01'
