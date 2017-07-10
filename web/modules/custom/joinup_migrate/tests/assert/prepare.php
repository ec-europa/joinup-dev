<?php

/**
 * @file
 * Assertions for 'prepare' migration.
 */

$expected_values = [
  'Collection with erroneous items' => [
    'policy' => 'Defence',
    'policy2' => 'Defence',
    'collection_owner' => '7143',
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
    'collection_owner' => '7098',
  ],
  'Collection from Project' => [
    'type' => 'project_project',
    'nid' => '42438',
    'contact_email' => 'contact@semic.eu',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'owner_text_name' => 'Dark Side of The Force',
    'owner_text_type' => 'SupraNationalAuthority',
    'collection_owner' => '7160',
  ],
  'Collection from Community' => [
    'type' => 'community',
    'nid' => '149141',
    'policy' => 'eGovernment',
    'policy2' => 'Collaboration',
    'elibrary' => '0',
    'collection_owner' => '7287,6416',
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
    'collection_owner' => '7051',
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
  ],
  'Membership testing' => [
    'type' => 'project_project',
    'nid' => '27026',
    'policy' => 'eGovernment',
    'policy2' => 'Open government',
    'contact_email' => 'health@gnusolidario.org',
    'collection_owner' => '9351',
  ],
];

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
    'collection_owner' => NULL,
    'url' => NULL,
  ];
  ksort($expected_value);
  ksort($import);
}
