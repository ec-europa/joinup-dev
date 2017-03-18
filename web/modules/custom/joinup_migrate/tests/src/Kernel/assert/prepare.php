<?php

/**
 * @file
 * Assertions for 'prepare' migration.
 */

// Migration counts.
$this->assertTotalCount('prepare', 2);
$this->assertSuccessCount('prepare', 2);

// Expected logged messages.
$this->assertMessage('prepare', "Collection 'No Repository or Community' should inherit data from D6 but has no 'community' or 'repository' records defined.");
$this->assertMessage('prepare', "On collection 'More than one Community or Repository' nid 105945 (11) is overriding existing value 145807 (community).");

// Imported content check.
$imported = $this->legacyDb->select('d8_prepare')
  ->fields('d8_prepare')
  ->execute()
  ->fetchAllAssoc('collection', PDO::FETCH_ASSOC);

$this->assertSame([
  'collection' => 'More than one Community or Repository',
  'type' => 'repository',
  'nid' => '105945',
  'policy2' => 'Defence',
  'policy' => NULL,
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'elibrary' => NULL,
  'publisher' => NULL,
  'contact' => NULL,
  'status' => NULL,
  'roles' => NULL,
], $imported['More than one Community or Repository']);

$this->assertSame([
  'collection' => 'New collection',
  'type' => '',
  'nid' => '0',
  'policy2' => 'eProcurement',
  'policy' => NULL,
  'abstract' => NULL,
  'logo' => NULL,
  'banner' => NULL,
  'elibrary' => NULL,
  'publisher' => NULL,
  'contact' => NULL,
  'status' => NULL,
  'roles' => '{"admin":[],"facilitator":[],"member":{"6565":1323255883}}',
], $imported['New collection']);
