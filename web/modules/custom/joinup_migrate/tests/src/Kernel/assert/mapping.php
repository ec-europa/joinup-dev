<?php

/**
 * @file
 * Assertions for 'mapping' migration.
 */

// Migration counts.
$this->assertTotalCount('mapping', 5);
$this->assertSuccessCount('mapping', 5);

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
  'collection' => 'No Repository or Community',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'collection_owner' => NULL,
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '2',
], $imported['60735']);

$this->assertSame([
  'nid' => '145807',
  'type' => 'community',
  'collection' => 'More than one Community or Repository',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'collection_owner' => 'joeroe@example.com',
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '9',
], $imported['145807']);

$this->assertSame([
  'nid' => '105945',
  'type' => 'repository',
  'collection' => 'More than one Community or Repository',
  'policy' => NULL,
  'policy2' => 'Defence',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'collection_owner' => 'joeroe@example.com',
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '10',
], $imported['105945']);

$this->assertSame([
  'nid' => '58694',
  'type' => 'asset_release',
  'collection' => 'New collection',
  'policy' => NULL,
  'policy2' => 'eProcurement',
  'new_collection' => 'Yes',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'collection_owner' => 'doe@example.com',
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '11',
], $imported['58694']);

$this->assertSame([
  'nid' => '26863',
  'type' => 'project_project',
  'collection' => 'New collection',
  'policy' => NULL,
  'policy2' => 'eProcurement',
  'new_collection' => 'No',
  'migrate' => '1',
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'owner' => NULL,
  'collection_owner' => 'doe@example.com',
  'elibrary' => NULL,
  'collection_status' => NULL,
  'content_item_status' => NULL,
  'row_index' => '12',
], $imported['26863']);
