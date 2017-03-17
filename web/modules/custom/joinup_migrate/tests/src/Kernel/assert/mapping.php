<?php

/**
 * @file
 * Assertions for 'mapping' migration.
 */

// Migration counts.
$this->assertTotalCount('mapping', 3);
$this->assertSuccessCount('mapping', 3);

// Expected logged messages.
$this->assertMessage('mapping', "Row: 4, Nid: 99999999: This node doesn't exist in the source database");
$this->assertMessage('mapping', "Row: 5, Nid: ew56%43: Invalid Nid 'ew56%43'");
$this->assertMessage('mapping', "Row: 6, Nid: 157729: 'MOA-ID 3.2.1' is a release and shouldn't be in the Excel file. Releases are computed");
$this->assertMessage('mapping', "Row: 7, Nid: 58729: Collection name empty or invalid");
$this->assertMessage('mapping', "Row: 8, Nid: 60736: Collection name empty or invalid");
$this->assertMessage('mapping', "Row: 9, Nid: 156207: Invalid 'New Collection': '#N/A'");

// Imported content check.
$imported = $this->legacyDb->select('d8_mapping')
  ->fields('d8_mapping')
  ->execute()
  ->fetchAllAssoc('nid', PDO::FETCH_ASSOC);

$this->assertSame([
  'nid' => '60735',
  'type' => 'asset_release',
  'collection' => '"New collection == No" but lacks a Repository or a Community',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '3',
], $imported['60735']);

$this->assertSame([
  'nid' => '105945',
  'type' => 'repository',
  'collection' => 'A collection with more than one Community or Repository',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '11',
], $imported['105945']);

$this->assertSame([
  'nid' => '145807',
  'type' => 'community',
  'collection' => 'A collection with more than one Community or Repository',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '10',
], $imported['145807']);
