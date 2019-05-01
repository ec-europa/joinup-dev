<?php

/**
 * @file
 * Contains \EuplSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\Entity\Rdf;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions and related methods provided by the eupl module.
 */
class EuplSubContext extends DrupalSubContextBase {

  use ConfigReadOnlyTrait;
  use EntityTrait;
  use RdfEntityTrait;

  /**
   * Creates the standard 'EUPL' collection and several dependencies.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the entities could not be created, for example because
   *   it already exists.
   *
   * @beforeScenario @eupl
   */
  public function setupEuplData(): void {
    // Create an owner.
    Rdf::create([
      'rid' => 'owner',
      'id' => 'http://example.com/owner',
      'field_owner_name' => 'Owner',
    ])->save();

    // Create the EUPL entity.
    Rdf::create([
      'rid' => 'collection',
      'id' => EUPL_COMMUNITY_ID,
      'label' => 'EUPL',
      'field_ar_state' => 'validated',
      'field_ar_owner' => 'http://example.com/owner',
    ])->save();

    // The 'Implementation monitoring' standard custom page.
    Node::create([
      'nid' => 701805,
      'type' => 'custom_page',
      'uuid' => '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41',
      'title' => 'JLA',
      'og_audience' => EUPL_COMMUNITY_ID,
    ])->save();
  }

  /**
   * Asserts the legal type categories order in the JLA licence listing item.
   *
   * @param string $spdx
   *   The SPDX title ID.
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table of categories.
   *
   * @Then the licence item with the :spdx SPDX tag should include the following legal type categories:
   */
  public function assertLegalTypeTagsCategoriesAndOrder(string $spdx, TableNode $table): void {
    $expected = $table->getColumn(0);
    $xpath = "//div[@data-spdx='{$spdx}']//div[contains(concat(' ', normalize-space(@class), ' '), ' listing__inner-tile--wider ')]//span[contains(concat(' ', normalize-space(@class), ' '), ' licence-tile__label ')]";
    $this->assertCategoriesAndOrder($xpath, $expected);
  }

  /**
   * Asserts that filter categories in the JLA page are present and ordered.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table of categories.
   *
   * @Then I should see the (following )filter categories in the correct order:
   */
  public function assertFilterCategoriesAndOrder(TableNode $table): void {
    $expected = $table->getColumn(0);
    $xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' licence-filter ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' licence-filter__header ')]";
    $this->assertCategoriesAndOrder($xpath, $expected);
  }

  /**
   * Asserts that the given categories are present and in the expected order.
   *
   * @param string $xpath
   *   The XPath query identifying the categories in the page.
   * @param array $expected
   *   The categories that are expected to be present in the page.
   */
  protected function assertCategoriesAndOrder(string $xpath, array $expected): void {
    $category_nodes = $this->getSession()->getPage()->findAll('xpath', $xpath);
    $actual = array_map(function (NodeElement $element) {
      return $element->getText();
    }, $category_nodes);
    Assert::assertEquals($expected, $actual);
  }

  /**
   * Clears the content created for the purpose of this test.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when one of the created entities could not be deleted.
   *
   * @afterScenario @eupl
   */
  public function cleanEuplData(): void {
    /** @var \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository */
    $entity_repository = \Drupal::service('entity.repository');
    $entity_repository->loadEntityByUuid('node', '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41')->delete();

    $collection = Rdf::load(EUPL_COMMUNITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    Rdf::load('http://example.com/owner')->delete();
  }

}
