<?php

/**
 * @file
 * Assertions for 'prepare' migration.
 */

$expected_values = [
  'More than one Community or Repository' => [
    'type' => 'repository',
    'nid' => '105945',
    'policy2' => 'Defence',
    'collection_owner' => 'joeroe@example.com',
  ],
  'New collection' => [
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'contact_email' => 'DIGIT-CIPA-SUPPORT@ec.europa.eu',
    'roles' => '{"admin":[],"facilitator":[],"member":{"6842":1378476956}}',
  ],
  'Collection from Repository' => [
    'type' => 'repository',
    'nid' => '59642',
    'policy2' => 'Open government',
    'collection_owner' => 'baby.doe@example.com',
  ],
  'Collection from Community' => [
    'type' => 'community',
    'nid' => '149141',
    'policy2' => 'Collaboration',
    'elibrary' => '0',
    'collection_owner' => 'jbloggs@example.com',
  ],
  'Archived collection' => [
    'type' => 'community',
    'nid' => '144326',
    'policy2' => 'Open government',
    'state' => 'archived',
    'collection_owner' => 'doe@example.com',
    'contact' => '137963',
    'publisher' => '137962',
    'roles' => '{"admin":[],"facilitator":{"6364":1472543615},"member":{"6364":1472543615,"7355":1367501419}}',
  ],
];

// Migration counts.
$expected_values_count = count($expected_values);
$this->assertTotalCount('prepare', $expected_values_count);
$this->assertSuccessCount('prepare', $expected_values_count);

// Expected logged messages.
$this->assertMessage('prepare', "Collection 'No Repository or Community' should inherit data from D6 but has no 'community' or 'repository' records defined.");
$this->assertMessage('prepare', "Collection 'New collection' column 'New collection' should be either 'Yes' or 'No'. Both found.");
$this->assertMessage('prepare', "Collection 'More than one Community or Repository' (nid 105945, type repository) is overriding existing value created by nid 145807 (community).");

// Imported content check.
$imported = $this->legacyDb->select('d8_prepare')
  ->fields('d8_prepare')
  ->execute()
  ->fetchAllAssoc('collection', PDO::FETCH_ASSOC);

foreach ($expected_values as $collection => $expected_value) {
  // Ensure defaults.
  $expected_value += [
    'collection' => $collection,
    'type' => '',
    'nid' => '0',
    'policy2' => NULL,
    'policy' => NULL,
    'abstract' => NULL,
    'logo' => NULL,
    'banner' => NULL,
    'elibrary' => NULL,
    'owner_text_name' => NULL,
    'owner_text_type' => NULL,
    'publisher' => NULL,
    'contact' => NULL,
    'contact_email' => NULL,
    'collection_owner' => NULL,
    'state' => 'validated',
    'roles' => NULL,
  ];
  ksort($expected_value);
  ksort($imported[$collection]);

  $this->assertSame($expected_value, $imported[$collection]);
}
