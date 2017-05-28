<?php

/**
 * @file
 * Assertions for 'prepare' migration.
 */

use Drupal\Component\Serialization\Json;

$expected_values = [
  'Collection with erroneous items' => [
    'policy' => 'Defence',
    'policy2' => 'Defence',
    'roles' => '{"admin":{"7143":1495635879}}',
  ],
  'New collection' => [
    'policy' => 'eGovernment',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'roles' => '{"admin":{"7098":1495635879},"member":{"6842":1378476956}}',
  ],
  'Collection from Project' => [
    'type' => 'project_project',
    'nid' => '42438',
    'contact_email' => 'contact@semic.eu',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'roles' => '{"admin":{"7160":1495635879},"facilitator":{"6364":1462873423},"member":{"6364":1462873423,"6422":1323365434,"6737":1334762598,"7077":1325795086}}',
  ],
  'Collection from Community' => [
    'type' => 'community',
    'nid' => '149141',
    'policy' => 'eGovernment',
    'policy2' => 'Collaboration',
    'elibrary' => '0',
    'roles' => '{"admin":{"6416":1495635879,"7287":1495635879}}',
  ],
  'Archived collection' => [
    'type' => 'repository',
    'nid' => '82307',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'owner_text_name' => 'ACME University',
    'owner_text_type' => 'Academia-ScientificOrganisation',
    'state' => 'archived',
    'publisher' => '89762',
    'roles' => '{"admin":{"7051":1495635879}}',
  ],
];

// Migration counts.
$expected_values_count = count($expected_values);
$this->assertTotalCount('prepare', $expected_values_count);
$this->assertSuccessCount('prepare', $expected_values_count);

// Imported content check.
$imported = $this->legacyDb->select('d8_prepare')
  ->fields('d8_prepare')
  ->execute()
  ->fetchAllAssoc('collection', PDO::FETCH_ASSOC);

foreach ($expected_values as $collection => $expected_value) {
  $import = $imported[$collection];

  // Ensure defaults.
  $expected_value += [
    'collection' => $collection,
    'type' => NULL,
    'nid' => NULL,
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
    'state' => 'validated',
    'roles' => NULL,
  ];
  ksort($expected_value);
  ksort($import);

  // Roles need a special comparision.
  $expected_roles = $expected_value['roles'];
  $roles = $import['roles'];
  unset($import['roles']);
  unset($expected_value['roles']);

  $this->assertSame($expected_value, $import);

  if (!empty($roles) && !empty($expected_roles)) {
    // Check roles.
    $roles = Json::decode($roles);
    $expected_roles = Json::decode($expected_roles);

    // Admin should be compared only as user IDs because the creation time is
    // the migration time and that we cannot predict.
    $this->assertSame(array_keys($expected_roles['admin']), array_keys($roles['admin']));
    unset($roles['admin']);
    unset($expected_roles['admin']);
    ksort($roles);
    ksort($expected_roles);
    $this->assertSame($expected_roles, $roles);
  }
}
