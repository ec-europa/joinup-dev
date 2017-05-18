<?php

/**
 * @file
 * Assertions for 'mapping' migration.
 */

$expected_values = [
  60735 => [
    'type' => 'asset_release',
    'collection' => 'No Repository or Community',
    'policy2' => 'Defence',
    'new_collection' => 'No',
    'row_index' => '2',
  ],
  145807 => [
    'type' => 'community',
    'collection' => 'More than one Community or Repository',
    'policy2' => 'Defence',
    'new_collection' => 'No',
    'collection_owner' => 'joeroe@example.com',
    'row_index' => '9',
  ],
  105945 => [
    'type' => 'repository',
    'collection' => 'More than one Community or Repository',
    'policy2' => 'Defence',
    'new_collection' => 'No',
    'collection_owner' => 'joeroe@example.com',
    'row_index' => '10',
  ],
  58694 => [
    'type' => 'asset_release',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '11',
  ],
  49860 => [
    'type' => 'project_project',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'new_collection' => 'No',
    'owner' => 'Y',
    'owner_name' => 'Dark Side of The Force',
    'owner_type' => 'SupraNationalAuthority',
    'collection_owner' => 'doe@example.com',
    'content_item_state' => 'proposed',
    'row_index' => '12',
  ],
  139528 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'content_item_state' => 'needs_update',
    'row_index' => '14',
  ],
  42233 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '15',
  ],
  138766 => [
    'type' => 'document',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '16',
  ],
  133560 => [
    'type' => 'case_epractice',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '17',
  ],
  53012 => [
    'type' => 'factsheet',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '18',
  ],
  63578 => [
    'type' => 'legaldocument',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '19',
  ],
  155691 => [
    'type' => 'presentation',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '20',
  ],
  27607 => [
    'type' => 'news',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'content_item_state' => 'draft',
    'row_index' => '21',
  ],
  155894 => [
    'type' => 'news',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '22',
  ],
  152066 => [
    'type' => 'newsletter',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '23',
  ],
  145278 => [
    'type' => 'event',
    'collection' => 'New collection',
    'policy2' => 'eProcurement',
    'abstract' => 'Abstract for a new collection',
    'collection_owner' => 'doe@example.com',
    'row_index' => '24',
  ],
  59642 => [
    'type' => 'repository',
    'collection' => 'Collection from Repository',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'baby.doe@example.com',
    'row_index' => '25',
  ],
  149141 => [
    'type' => 'community',
    'collection' => 'Collection from Community',
    'policy2' => 'Collaboration',
    'new_collection' => 'No',
    'collection_owner' => 'jbloggs@example.com',
    'row_index' => '26',
  ],
  144326 => [
    'type' => 'community',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'state' => 'archived',
    'row_index' => '27',
  ],
  76726 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'row_index' => '28',
  ],
  102713 => [
    'type' => 'asset_release',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'owner' => 'Y',
    'collection_owner' => 'doe@example.com',
    'row_index' => '29',
  ],
  125548 => [
    'type' => 'document',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'row_index' => '30',
  ],
  42444 => [
    'type' => 'project_project',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'owner_name' => 'ACME University',
    'owner_type' => 'Academia-ScientificOrganisation',
    'collection_owner' => 'doe@example.com',
    'row_index' => '31',
  ],
  150255 => [
    'type' => 'event',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'row_index' => '32',
  ],
  65803 => [
    'type' => 'news',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'row_index' => '33',
  ],
  63567 => [
    'type' => 'project_project',
    'collection' => 'Archived collection',
    'policy2' => 'Open government',
    'new_collection' => 'No',
    'collection_owner' => 'doe@example.com',
    'row_index' => '35',
  ],
];

// Migration counts.
$expected_values_count = count($expected_values);
$this->assertTotalCount('mapping', $expected_values_count);
$this->assertSuccessCount('mapping', $expected_values_count);

// Expected logged messages.
$this->assertMessage('mapping', "Row: 3, Nid: 99999999: This node doesn't exist in the source database");
$this->assertMessage('mapping', "Row: 4, Nid: ew56%43: Invalid nid 'ew56%43'");
$this->assertMessage('mapping', "Row: 4, Nid: ew56%43: Invalid 'Collection state': 'wrong' (allowed empty or 'validated' or 'archived')");
$this->assertMessage('mapping', "Row: 5, Nid: 157729: 'MOA-ID 3.2.1' is a release and shouldn't be in the Excel file. Releases are computed");
$this->assertMessage('mapping', "Row: 6, Nid: 58729: Collection name empty or invalid");
$this->assertMessage('mapping', "Row: 7, Nid: 60736: Collection name empty or invalid");
$this->assertMessage('mapping', "Row: 8, Nid: 156207: Invalid 'New Collection': '#N/A'");
$this->assertMessage('mapping', "Row: 13, Nid: 87737: Software (project) content should not be in the Excel file. Replace with Project (project_project)");
$this->assertMessage('mapping', "Row: 34, Nid: 156973: Type 'Newsletter' declared, but nid 156973 is 'news' in Drupal 6");

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
    'new_collection' => 'Yes',
    'migrate' => '1',
    'abstract' => NULL,
    'logo' => NULL,
    'banner' => NULL,
    'owner' => NULL,
    'owner_name' => NULL,
    'owner_type' => NULL,
    'collection_owner' => NULL,
    'elibrary' => NULL,
    'state' => NULL,
    'content_item_state' => NULL,
  ];
  ksort($expected_value);
  ksort($imported[$nid]);

  $this->assertSame($expected_value, $imported[$nid]);
}
