<?php

/**
 * @file
 * Contains \EuplSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\eupl\Eupl;
use Drupal\joinup\HtmlManipulator;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\MaterialDesignTrait;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\node\Entity\Node;
use Drupal\rdf_entity\Entity\Rdf;
use LoversOfBehat\TableExtension\Hook\Scope\AfterTableFetchScope;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions and related methods provided by the eupl module.
 */
class EuplSubContext extends DrupalSubContextBase {

  use BrowserCapabilityDetectionTrait;
  use ConfigReadOnlyTrait;
  use EntityTrait;
  use MaterialDesignTrait;
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
      'id' => Eupl::EUPL_COMMUNITY_ID,
      'label' => 'EUPL',
      'field_ar_state' => 'validated',
      'field_ar_owner' => 'http://example.com/owner',
    ])->save();

    Rdf::create([
      'rid' => 'solution',
      'collection' => Eupl::EUPL_COMMUNITY_ID,
      'id' => Eupl::JLA_SOLUTION,
      'label' => 'Joinup Licensing Assistant',
      'field_is_state' => 'validated',
    ])->save();

    // The 'Implementation monitoring' standard custom page.
    Node::create([
      'nid' => 701805,
      'type' => 'custom_page',
      'uuid' => '3bee8b04-75fd-46a8-94b3-af0d8f5a4c41',
      'title' => 'JLA',
      'og_audience' => Eupl::JLA_SOLUTION,
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
   * Adds a licence to the compare list given its SPDX ID.
   *
   * @param string $spdx_id
   *   The licence SPDX ID.
   *
   * @throws \Exception
   *   When the licence is not found on the page.
   *
   * @When I add the :spdx_id licence to the compare list
   */
  public function selectLicenceForComparision(string $spdx_id): void {
    $this->toggleLicenceForComparision(TRUE, $spdx_id);
  }

  /**
   * Removes a licence for the compare list given its SPDX ID.
   *
   * @param string $spdx_id
   *   The licence SPDX ID.
   *
   * @throws \Exception
   *   When the licence is not found on the page.
   *
   * @When I remove the :spdx_id licence from the compare list
   */
  public function unselectLicenceForComparision(string $spdx_id): void {
    $this->toggleLicenceForComparision(FALSE, $spdx_id);
  }

  /**
   * Checks or unchecks a licence for comparison.
   *
   * @param bool $compare
   *   TRUE if the licence should be added to the compare list.
   * @param string $spdx_id
   *   The licence SPDX ID.
   *
   * @throws \Exception
   *   When the licence is not found on the page.
   */
  protected function toggleLicenceForComparision(bool $compare, string $spdx_id): void {
    $licence = $this->findLicenceTile($spdx_id);
    if ($compare) {
      $this->checkMaterialDesignField('Add to compare list', $licence);
    }
    else {
      $this->uncheckMaterialDesignField('Add to compare list', $licence);
    }
  }

  /**
   * Asserts that Compare buttons are enabled or disabled.
   *
   * @param string $status
   *   Either 'enabled' or 'disabled'.
   *
   * @throws \Exception
   *   When:
   *   - No 'Compare' buttons were found in page.
   *   - The expectancy is not satisfied.
   *
   * @Then the Compare buttons are :status
   */
  public function assertCompareButtonDisableStatus(string $status): void {
    \assert(in_array($status, ['enabled', 'disabled']), 'The $status parameter should take one of the values "enabled" or "disabled" but "' . $status . '" was given.');

    $page = $this->getSession()->getPage();
    $xpath = '//a[text()="Compare" and contains(concat(" ", normalize-space(@class), " "), " licence-tile__button--compare ")]';
    if (!$buttons = $page->find('xpath', $xpath)) {
      throw new \Exception("No 'Compare' buttons found in page.");
    }

    /** @var \Behat\Mink\Element\NodeElement[] $buttons */
    foreach ($buttons as $button) {
      if ($status === 'disabled' && !$button->hasClass('licence-tile__button--disabled')) {
        throw new \Exception("'Compare' buttons should be disabled but are enabled.");
      }
      elseif ($status === 'enabled' && $button->hasClass('licence-tile__button--disabled')) {
        throw new \Exception("'Compare' buttons should be enabled but are disabled.");
      }
    }
  }

  /**
   * Asserts that the given licence can/cannot be selected for the compare list.
   *
   * @param string $spdx_id
   *   The licence SPDX ID.
   * @param string $can
   *   Either 'can' or 'cannot'.
   *
   * @throws \Exception
   *   When the licence tile is not found on the page.
   *
   * @Then the :spdx_id :can be added to the compare list
   */
  public function assertLicenceCanBeCompared(string $spdx_id, string $can) {
    \assert(in_array($can, ['can', 'cannot']), 'The $can parameter should take one of the values "can" or "cannot" but "' . $can . '" was given.');

    $licence = $this->findLicenceTile($spdx_id);
    $checkbox = $licence->find('xpath', '//label[text()="Add to compare list"]/../..');

    if ($can === 'can' && $checkbox->hasClass('is-disabled')) {
      throw new \Exception("The licence '$spdx_id' should be allowed to be compared but is not.");
    }
    elseif ($can === 'cannot' && !$checkbox->hasClass('is-disabled')) {
      throw new \Exception("The licence '$spdx_id' shouldn't be allowed to be compared but it is.");
    }
  }

  /**
   * Asserts that the given licence is/is not selected.
   *
   * @param string $spdx_id
   *   The licence SPDX ID.
   * @param string $checked
   *   Either 'checked' or 'unchecked'.
   *
   * @throws \Exception
   *   When the licence tile is not found on the page.
   *
   * @Then the :spdx_id licence should be :checked
   */
  public function assertLicenceChecked(string $spdx_id, string $checked) {
    \assert(in_array($checked, ['checked', 'unchecked']), 'The $checked parameter should take one of the values "checked" or "unchecked" but "' . $checked . '" was given.');

    $licence = $this->findLicenceTile($spdx_id);
    $checkbox = $licence->find('xpath', '//label[text()="Add to compare list"]/../..');

    if ($checked === 'checked' && !$checkbox->hasClass('is-checked')) {
      throw new \Exception("The licence '$spdx_id' should be checked but is not.");
    }
    elseif ($checked === 'unchecked' && $checkbox->hasClass('is-checked')) {
      throw new \Exception("The licence '$spdx_id' should not be checked but it is.");
    }
  }

  /**
   * Finds a licence tile on the page given a licence SPDX ID.
   *
   * @param string $spdx_id
   *   The licence SPDX ID.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The licence tile.
   *
   * @throws \Exception
   *   When the licence tile is not found on the page.
   */
  protected function findLicenceTile(string $spdx_id): NodeElement {
    if ($licence = $this->getSession()->getPage()->find('css', "div[data-spdx='{$spdx_id}']")) {
      return $licence;
    }
    throw new \Exception("Can't find the '$spdx_id' licence on the page.");
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

    $collection = Rdf::load(Eupl::EUPL_COMMUNITY_ID);
    $collection->skip_notification = TRUE;
    $collection->delete();

    $solution = Rdf::load(Eupl::JLA_SOLUTION);
    $solution->skip_notification = TRUE;
    $solution->delete();

    Rdf::load('http://example.com/owner')->delete();
  }

  /**
   * Inserts an 'x' character in the licence comparer checked cells.
   *
   * There's no way to identify the checked cells in tests as the licence
   * comparer table cells are all empty. Visually, a human is able to identify
   * them as they are CSS styled. For the purpose of the test we insert an 'x'
   * character so that the test is able to identify the checked cells.
   *
   * @param \LoversOfBehat\TableExtension\Hook\Scope\AfterTableFetchScope $scope
   *   The "after table fetch" scope object.
   *
   * @AfterTableFetch
   */
  public static function markLicenceComparerCheckedCells(AfterTableFetchScope $scope): void {
    $html_manipulator = new HtmlManipulator($scope->getHtml());

    if (!$html_manipulator->filter('table[data-drupal-selector="licence-comparer"]')->getNode(0)) {
      // We're only interested in the licence comparer table.
      return;
    }

    /** @var \DOMNode $node */
    foreach ($html_manipulator->filter('.icon--check-2') as $node) {
      // Insert an 'x' character the licence comparer table checked cells so
      // that the tests are able to identify them.
      // @see tests/features/communities/eupl/jla.feature
      $node->textContent = 'x';
    }
    $scope->setHtml($html_manipulator->html());
  }

}
