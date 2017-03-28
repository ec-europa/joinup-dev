# DROP VIEW IF EXISTS d8_owner;
# DROP TABLE IF EXISTS d8_owner;
CREATE TABLE IF NOT EXISTS d8_owner (
    nid INT NOT NULL,
    vid INT NOT NULL,
    title VARCHAR(255),
    uri LONGTEXT,
    uid INT,
    collection VARCHAR(255),
    solution INT
) AS SELECT n.nid AS nid,
            n.vid AS vid,
            n.title AS title,
            TRIM(uri.field_id_uri_value) AS uri,
            n.uid AS uid,
            c.collection AS collection,
            NULL AS solution FROM
         d8_collection c
         INNER JOIN
         node n ON FIND_IN_SET(n.nid, c.owner)
         LEFT JOIN
         content_field_id_uri uri ON n.vid = uri.vid
     UNION SELECT
               n2.nid,
               n2.vid,
               n2.title,
               TRIM(uri2.field_id_uri_value),
               n2.uid,
               NULL,
               s.nid
           FROM
               d8_solution s
               INNER JOIN
               content_field_asset_publisher cfap ON s.vid = cfap.vid
               INNER JOIN
               node n2 ON cfap.field_asset_publisher_nid = n2.nid
               LEFT JOIN
               content_field_id_uri uri2 ON n2.vid = uri2.vid
           WHERE
               n2.type = 'publisher'
     ORDER BY nid
