<?php

/**
 * @file
 * Assertions for 'reference' migration.
 */

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/* @var \Drupal\Core\Path\AliasManagerInterface $alias_manager */
$alias_manager = \Drupal::service('path.alias_manager');
$sql = "SELECT body FROM {node_revisions} WHERE nid = :nid ORDER BY vid DESC";
$node = Node::load(76890);
// This file is referenced as file system URL in the body of node 76890.
$file = File::load(30186);
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Community', 'collection');
$collection_alias = $alias_manager->getAliasByPath('/' . $collection->toUrl()->getInternalPath());
/* @var \Drupal\node\NodeInterface $event */
$event = Node::load(150255);
$event_alias = $alias_manager->getAliasByPath('/' . $event->toUrl()->getInternalPath());
/* @var \Drupal\user\UserInterface $account */
$account = User::load(6481);
$account_alias = $alias_manager->getAliasByPath('/' . $account->toUrl()->getInternalPath());
/* @var \Drupal\node\NodeInterface $discussion */
$discussion = Node::load(46378);
$discussion_alias = $alias_manager->getAliasByPath('/' . $discussion->toUrl()->getInternalPath());
$fids = \Drupal::entityTypeManager()->getStorage('file')->getQuery()
  ->condition('filename', 'MDR_logo.png')->execute();
$fid = reset($fids);
/* @var \Drupal\file\FileInterface $unmigrated_file */
$unmigrated_file = File::load($fid);

$text_before_migration = $this->legacyDb->queryRange($sql, 0, 1, [':nid' => 76890])->fetchField();
$text_after_migration = $node->body->value;

// A link to an attachment of a custom page is transformed.
// Before:
$this->assertContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_before_migration);
$this->assertNotContains($file->uuid(), $text_before_migration);
// After:
$this->assertNotContains('https://joinup.ec.europa.eu/sites/default/files/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains('/sites/default/files/custom-page/attachment/list_of_standards_v1.1.xls', $text_after_migration);
$this->assertContains($file->uuid(), $text_after_migration);

// A canonical link to a Community, base for a Collection, is transformed into
// the D8 aliased path to the collection. An aliased link to the same Community
// is transformed in the same collection aliased path.
// Before:
$this->assertContains('/node/149141', $text_before_migration);
$this->assertContains('/community/edp/description', $text_before_migration);
// After:
$this->assertNotContains('/node/149141', $text_after_migration);
$this->assertContains($collection_alias, $text_after_migration);
$this->assertNotContains('/community/edp/description', $text_after_migration);

// A community content canonical link is preserved.
// Before:
$this->assertContains('/node/150255', $text_before_migration);
// After:
$this->assertContains('/node/150255', $text_after_migration);

// The aliased path of community content is transformed into the alias of the
// D8 aliased path.
// Before:
$this->assertContains('/asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0', $text_before_migration);
// After:
$this->assertNotContains('/asset/cpsv-ap/event/cpsv-ap-revision-wg-virtual-meeting-0', $text_after_migration);
$this->assertContains($event_alias, $text_after_migration);

// A user canonical path is preserved.
// Before:
$this->assertContains('/user/6481', $text_before_migration);
// After:
$this->assertContains('/user/6481', $text_after_migration);

// A user aliased path is transformed into the user D8 aliased path.
// Before:
$this->assertContains('/people/6481', $text_before_migration);
// After:
$this->assertContains($account_alias, $text_after_migration);
$this->assertNotContains('/people/6481', $text_after_migration);
$this->assertContains($account->uuid(), $text_after_migration);

// A user canonical path to the content 'profile' node is transformed into the
// user D8 aliased path.
// Before:
$this->assertContains('/node/20226', $text_before_migration);
// After:
$this->assertNotContains('/node/20226', $text_after_migration);

// A user aliased path to the content 'profile' node is transformed into the
// user D8 aliased path.
// Before:
$this->assertContains('/profile/afalciano-profile', $text_before_migration);
// After:
$this->assertNotContains('/profile/afalciano-profile', $text_after_migration);

// An absolute aliased URL to a migrated Discussion with fragment is rewritten
// to the new Discussion alias and the fragment is preserved.
// Before:
$this->assertContains('https://joinup.ec.europa.eu/asset/core_location/issue/related-work-open-geospatial-consortium#some-fragment', $text_before_migration);
// After:
$this->assertContains("$discussion_alias#some-fragment", $text_after_migration);
$this->assertContains($discussion->uuid(), $text_after_migration);

// An absolute link to a Drupal managed file which was not migrated is
// transformed into the link to managed migrated file.
// Before:
$this->assertContains('https://joinup.ec.europa.eu/sites/default/files/e3/0c/57/MDR_logo.png', $text_before_migration);
// After:
$this->assertNotContains('https://joinup.ec.europa.eu/sites/default/files/e3/0c/57/MDR_logo.png', $text_after_migration);
$this->assertContains('/sites/default/files/inline-images/MDR_logo.png', $text_after_migration);
$this->assertContains($unmigrated_file->uuid(), $text_after_migration);

// An absolute link to a Drupal unmanaged file which was not migrated is
// transformed into the link to managed migrated file.
// Before:
$this->assertContains('https://joinup.ec.europa.eu/sites/default/files/ISA_Programme_ADMS_Brochure.pdf', $text_before_migration);
// After:
$this->assertNotContains('https://joinup.ec.europa.eu/sites/default/files/ISA_Programme_ADMS_Brochure.pdf', $text_after_migration);
$this->assertContains('/sites/default/files/inline-files/ISA_Programme_ADMS_Brochure.pdf', $text_after_migration);

// An absolute link to an unmigrated non-Drupal 'svn/' file resource is
// transformed into the link to managed migrated file.
// Before:
$this->assertContains('https://joinup.ec.europa.eu/svn/adms/grefine_rdf/ADMS_v1.00_Spreadsheet_Template_v0.35.xls', $text_before_migration);
// After:
$this->assertNotContains('https://joinup.ec.europa.eu/svn/adms/grefine_rdf/ADMS_v1.00_Spreadsheet_Template_v0.35.xls', $text_after_migration);
$this->assertContains('/sites/default/files/inline-files/ADMS_v1.00_Spreadsheet_Template_v0.35.xls', $text_after_migration);

// An absolute link to an unmigrated non-Drupal 'site/' file resource is
// transformed into the link to managed migrated file.
// Before:
$this->assertContains('http://joinup.ec.europa.eu/site/dcat_application_profile/GeoDCAT-AP/GeoDCAT-AP_2015-04-15_2nd_WG_Virtual_Meeting/GeoDCAT-AP_2015-04-15_2nd_WG_Virtual_Meeting-minutes_v0.05.pdf', $text_before_migration);
// After:
$this->assertNotContains('http://joinup.ec.europa.eu/site/dcat_application_profile/GeoDCAT-AP/GeoDCAT-AP_2015-04-15_2nd_WG_Virtual_Meeting/GeoDCAT-AP_2015-04-15_2nd_WG_Virtual_Meeting-minutes_v0.05.pdf', $text_after_migration);
$this->assertContains('/sites/default/files/inline-files/GeoDCAT-AP_2015-04-15_2nd_WG_Virtual_Meeting-minutes_v0.05.pdf', $text_after_migration);
