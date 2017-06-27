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
    'roles' => '{"admin":{"7143":[1,1497117946]}}',
  ],
  'New collection' => [
    'policy' => 'eGovernment',
    'policy2' => 'eProcurement',
    'description' => 'Description for a new collection',
    'abstract' => 'Abstract for a new collection',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'banner' => 'Banner_1.png',
    'logo' => 'Logo_1.png',
    'roles' => '{"admin":{"7098":[1,1497117946]},"member":{"6842":[1,1378476956]}}',
  ],
  'Collection from Project' => [
    'type' => 'project_project',
    'nid' => '42438',
    'contact_email' => 'contact@semic.eu',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'roles' => '{"admin":{"7160":[1,1497117946]},"facilitator":{"6364":[1,1462873423]},"member":{"6422":[1,1323365434],"6737":[1,1334762598],"7077":[1,1325795086]}}',
  ],
  'Collection from Community' => [
    'type' => 'community',
    'nid' => '149141',
    'policy' => 'eGovernment',
    'policy2' => 'Collaboration',
    'elibrary' => '0',
    'roles' => '{"admin":{"7287":[1,1497119643],"6416":[1,1497119643]}}',
    'url' => 'http://example.com/this_collection',
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
    'roles' => '{"admin":{"7051":[1,1497119643]}}',
  ],
  'Collection with 2 entities having custom section' => [
    'type' => 'community',
    'nid' => '157710',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
  ],
  'Collection with 1 entity having custom section' => [
    'type' => 'community',
    'nid' => '66790',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'roles' => '{"member":{"1":[1,1457690413],"6549":[1,1452879353],"6822":[1,1422532565],"7077":[1,1434103096],"7355":[1,1418224163]},"facilitator":{"6363":[1,1415370002]}}',
  ],
  'Membership testing' => [
    'type' => 'project_project',
    'nid' => '27026',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'contact_email' => 'health@gnusolidario.org',
    'roles' => '{"admin":{"9351":[1,1497119643]},"member":{"15741":[1,1344037319],"16077":[0,1347301489]}}',
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
    'description' => NULL,
    'abstract' => NULL,
    'logo' => NULL,
    'banner' => NULL,
    'elibrary' => NULL,
    'owner_text_name' => '',
    'owner_text_type' => '',
    'publisher' => NULL,
    'contact' => NULL,
    'contact_email' => NULL,
    'state' => 'validated',
    'roles' => NULL,
    'url' => NULL,
  ];
  ksort($expected_value);
  ksort($import);

  // Roles need a special comparision.
  $expected_roles = $expected_value['roles'];
  $roles = $import['roles'];
  unset($import['roles']);
  unset($expected_value['roles']);

  // Check main values.
  $this->assertSame($expected_value, $import);

  $this->assertTrue(($roles && $expected_roles) || (!$roles && !$expected_roles));

  if ($roles && $expected_roles) {
    // Check roles.
    $roles = Json::decode($roles);
    $expected_roles = Json::decode($expected_roles);

    // Order arrays by key in deep.
    ksort($roles);
    ksort($expected_roles);
    foreach (['roles', 'expected_roles'] as $variable) {
      foreach ($$variable as &$items) {
        ksort($items);
      }
    }

    // Admin should be compared only as user IDs because the creation time is
    // the migration time and that we cannot predict.
    $this->assertTrue((isset($expected_roles['admin']) && isset($roles['admin'])) || (!isset($expected_roles['admin']) && !isset($roles['admin'])));
    if (isset($expected_roles['admin']) && isset($roles['admin'])) {
      $this->assertSame(array_keys($expected_roles['admin']), array_keys($roles['admin']));
      unset($roles['admin']);
      unset($expected_roles['admin']);
    }
    $this->assertSame($expected_roles, $roles);
  }
}
