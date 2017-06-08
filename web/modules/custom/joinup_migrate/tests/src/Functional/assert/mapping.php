<?php

/**
 * @file
 * Assertions for 'mapping' migration.
 */

$expected_values = [
  58694 => [
    'type' => 'asset_release',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '7',
  ],
  49860 => [
    'type' => 'project_project',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'owner' => 'Y',
    'owner_name' => 'Dark Side of The Force',
    'owner_type' => 'SupraNationalAuthority',
    'content_item_state' => 'proposed',
    'row_index' => '8',
  ],
  139528 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'content_item_state' => 'needs_update',
    'row_index' => '10',
  ],
  42233 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '11',
  ],
  138766 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '12',
  ],
  133560 => [
    'type' => 'case_epractice',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '13',
  ],
  53012 => [
    'type' => 'factsheet',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '14',
  ],
  63578 => [
    'type' => 'legaldocument',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '15',
  ],
  155691 => [
    'type' => 'presentation',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '16',
  ],
  27607 => [
    'type' => 'news',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'content_item_state' => 'draft',
    'row_index' => '17',
  ],
  155894 => [
    'type' => 'news',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '18',
  ],
  152066 => [
    'type' => 'newsletter',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '19',
  ],
  145278 => [
    'type' => 'event',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'row_index' => '20',
  ],
  42438 => [
    'type' => 'project_project',
    'collection' => 'Collection from Project',
    'policy2' => 'Open government',
    'owner' => 'Y',
    'owner_name' => 'Dark Side of The Force',
    'owner_type' => 'SupraNationalAuthority',
    'row_index' => '21',
  ],
  149141 => [
    'type' => 'community',
    'collection' => 'Collection from Community',
    'policy2' => 'Collaboration',
    'owner' => 'Y',
    'row_index' => '22',
  ],
  82307 => [
    'type' => 'repository',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'owner' => 'Y',
    'row_index' => '23',
  ],
  76726 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '24',
  ],
  102713 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'logo' => 'acme.jpg',
    'banner' => 'butterfly-wallpaper.jpg',
    'row_index' => '25',
  ],
  125548 => [
    'type' => 'document',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '26',
  ],
  42444 => [
    'type' => 'project_project',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'owner_name' => 'ACME University',
    'owner_type' => 'Academia-ScientificOrganisation',
    'row_index' => '27',
  ],
  150255 => [
    'type' => 'event',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '28',
  ],
  65803 => [
    'type' => 'news',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '29',
  ],
  63567 => [
    'type' => 'project_project',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'owner_name' => 'Dark Side of The Force',
    'owner_type' => 'SupraNationalAuthority',
    'row_index' => '31',
  ],
  59180 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '32',
  ],
  59183 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '33',
  ],
  60208 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'row_index' => '34',
  ],
  157710 => [
    'type' => 'community',
    'collection' => 'Collection with 2 entities having custom section',
    'policy2' => 'Open government',
    'owner' => 'Y',
    'row_index' => '35',
  ],
  27024 => [
    'type' => 'project_project',
    'collection' => 'Collection with 2 entities having custom section',
    'policy2' => 'Open government',
    'row_index' => '36',
  ],
  66790 => [
    'type' => 'community',
    'collection' => 'Collection with 1 entity having custom section',
    'policy2' => 'Open government',
    'owner' => 'Y',
    'row_index' => '37',
  ],
];

// Migration counts.
$expected_values_count = count($expected_values);
$this->assertTotalCount('mapping', $expected_values_count);
$this->assertSuccessCount('mapping', $expected_values_count);

// Expected logged messages.
$this->assertMessage('mapping', "Row: 2, Nid: 60735: Collection doesn't exist");
$this->assertMessage('mapping', "Row: 3, Nid: 99999999: This node doesn't exist in the source database");
$this->assertMessage('mapping', "Row: 4, Nid: ew56%43: Invalid nid 'ew56%43'");
$this->assertMessage('mapping', "Row: 5, Nid: 157729: 'MOA-ID 3.2.1' is a release and shouldn't be in the Excel file. Releases are computed");
$this->assertMessage('mapping', "Row: 6, Nid: 60736: Collection name empty");
$this->assertMessage('mapping', "Row: 9, Nid: 87737: Software (project) content should not be in the Excel file. Replace with Project (project_project)");
$this->assertMessage('mapping', "Row: 9, Nid: 87737: Type 'Project' declared, but nid 87737 is 'Software (project)' in Drupal 6");
$this->assertMessage('mapping', "Row: 30, Nid: 156973: Type 'Newsletter' declared, but nid 156973 is 'News (news)' in Drupal 6");

// Imported content check.
$imported = $this->legacyDb->select('d8_mapping')
  ->fields('d8_mapping')
  ->execute()
  ->fetchAllAssoc('nid', PDO::FETCH_ASSOC);

foreach ($expected_values as $nid => $expected_value) {
  $nid = (string) $nid;
  // Ensure defaults.
  $expected_value += [
    'nid' => $nid,
    'policy' => NULL,
    'logo' => NULL,
    'banner' => NULL,
    'owner' => NULL,
    'owner_name' => NULL,
    'owner_type' => NULL,
    'content_item_state' => NULL,
  ];
  ksort($expected_value);
  ksort($imported[$nid]);

  $this->assertSame($expected_value, $imported[$nid]);
}
