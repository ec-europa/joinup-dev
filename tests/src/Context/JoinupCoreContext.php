<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Chip;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\EntityTrait;
use Drupal\joinup\Traits\TraversingTrait;
use Drupal\joinup\Traits\UtilityTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for functionalities provided by Joinup core module.
 */
class JoinupCoreContext extends RawDrupalContext {

  use BrowserCapabilityDetectionTrait;
  use EntityTrait;
  use TraversingTrait;
  use UtilityTrait;

  /**
   * Asserts that only the expected chips are shown in the page.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table containing the expected chip labels.
   * @param string|null $region
   *   Optional region in which to locate the chips to check. If omitted all
   *   chips found in the entire page will be checked.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   *
   * @Then the page should show( only) the( following) chip(s):
   * @Then the page should show( only) the( following) chip(s) in the :region( region):
   */
  public function assertChipElements(TableNode $table, ?string $region = NULL): void {
    $chips = $this->getChips($region);
    $found = array_map(function (Chip $element) {
      return $element->getText();
    }, $chips);

    $expected = $table->getColumn(0);
    Assert::assertEquals($expected, $found, "The expected chip elements don't match the ones found in the page", 0.0, 10, TRUE);
  }

  /**
   * Asserts that the expected number of chips are shown.
   *
   * @param string|null $number
   *   The expected number of chips. This is a string rather than an integer
   *   because step definitions are represented in text.
   * @param string|null $region
   *   Optional region in which to locate the chips to check. If omitted all
   *   chips found in the entire page will be checked.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   *
   * @Then the page should contain :number chip(s)
   * @Then the page should not contain any chips
   * @Then the :region region should contain :number chip(s)
   * @Then the :region region should not contain any chips
   */
  public function assertChipCount(?string $number = NULL, ?string $region = NULL): void {
    $number = (int) $number;
    Assert::assertCount($number, $this->getChips($region));
  }

  /**
   * Clicks the remove button in a chip.
   *
   * @param string $text
   *   The text or partial text of the chip.
   *
   * @throws \Exception
   *   Thrown when either the chip or its remove button are not found.
   *
   * @When I press the remove button on the chip :text
   */
  public function clickRemoveChipButton(string $text): void {
    // Find the element that contains the given text.
    $chip = $this->getChipByText($text);
    if (!$chip) {
      throw new \Exception("The chip containing the label '$text' was not found on the page.");
    }

    // Find the related button.
    $button = $chip->getRemoveButton();
    if (!$button) {
      throw new \Exception("Couldn't find the button to remove the chip '$text'.");
    }

    $button->press();
  }

  /**
   * Returns the chips that are present in the given region.
   *
   * @param string|null $region
   *   Optional region in which to locate the chips to return. If omitted all
   *   chips found in the entire page will be returned.
   *
   * @return \Drupal\joinup\Chip[]
   *   The elements representing the chips.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   */
  protected function getChips(?string $region = NULL): array {
    // Default to the entire page if the region is omitted.
    $base_element = $region ? $this->getRegion($region) : $this->getSession()->getPage();

    $chips = [];

    // If the chips are being displayed using the `mdl-chip-input` JavaScript
    // library then they will only be visible if the browser supports JS. If the
    // browser doesn't support JavaScript then the chips are still present in
    // the DOM as invisible form input fields. We can still add them with the
    // `is_visible` flag disabled to indicate they only can be interacted with
    // in a limited way.
    $locator_visibility_mapping = [
      '.mdl-chip__text' => TRUE,
    ];
    if (!$this->browserSupportsJavaScript()) {
      $locator_visibility_mapping['.mdl-chipfield__input'] = FALSE;
    }
    foreach ($locator_visibility_mapping as $locator => $is_visible) {
      foreach ($base_element->findAll('css', $locator) as $element) {
        $chips[] = new Chip($element, $is_visible);
      }
    }

    return $chips;
  }

  /**
   * Returns the chip with the given text.
   *
   * If multiple chips are present with the same text, then the first one will
   * be returned.
   *
   * @param string $text
   *   The text that should be present in the chip.
   * @param string|null $region
   *   Optional region in which to locate the chips to return. If omitted all
   *   chips found in the entire page will be returned.
   *
   * @throws \Exception
   *    Thrown when the region is not found.
   *
   * @return \Drupal\joinup\Chip|null
   *   The chip, or NULL if no chip with the given text is present in the page.
   */
  protected function getChipByText(string $text, ?string $region = NULL): ?Chip {
    foreach ($this->getChips($region) as $chip) {
      if ($chip->getText() === $text) {
        return $chip;
      }
    }

    return NULL;
  }

  /**
   * Visits a taxonomy term page.
   *
   * @param string $name
   *   The taxonomy term name.
   *
   * @Given I go to the :name (taxonomy )term( page)
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   *   Thrown when the taxonomy term does not have a URL to go to.
   */
  public function visitTaxonomyTermPage(string $name): void {
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = static::getEntityByLabel('taxonomy_term', $name);
    $this->visitPath($term->toUrl()->toString());
  }

  /**
   * Asserts that a list of fields are disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is not disabled.
   *
   * @Then the following fields should be disabled :fields
   */
  public function givenDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new \Exception('The following fields were not found or were enabled: ' . implode(', ', $found));
    }
  }

  /**
   * Asserts that a list of fields are not disabled.
   *
   * @param string $fields
   *   A list of comma separated field labels.
   *
   * @throws \Exception
   *   Thrown when a field is not found by name or is disabled.
   *
   * @Then the following fields should not be disabled :fields
   */
  public function givenNotDisabledFields(string $fields): void {
    $fields = $this->explodeCommaSeparatedStepArgument($fields);
    $found = [];
    foreach ($fields as $field) {
      $element = $this->findDisabledField($field);
      if (!empty($element)) {
        $found[] = $field;
      }
    }

    if (!empty($found)) {
      throw new \Exception('The following fields were disabled: ' . implode(', ', $found));
    }
  }

  /**
   * Asserts the content type of the response from the server.
   *
   * @param string $content_type
   *   The expected content type.
   *
   * @Then the content type of the response should be :content_type
   */
  public function assertResponseContentType(string $content_type): void {
    $this->assertSession()->responseHeaderEquals('Content-Type', $content_type);
  }

  /**
   * Asserts the order of elements.
   *
   * @Then I should see the following group menu items in the specified order:
   */
  public function assertRepeatedElementContainsText(TableNode $table): void {
    $parent = $this->getSession()->getPage()->findAll('css', '.block-group-menu-blocknavigation li.sidebar-menu__item');
    $i = 0;
    foreach ($table->getHash() as $repeatedElement) {
      $child = $parent[$i];
      $actual_text = $child->find('css', 'a.sidebar-menu__link')->getText();
      Assert::assertEquals($repeatedElement['text'], $actual_text);
      $i++;
    }
  }

  /**
   * Searches for links matching the criteria and clicks on the last of them.
   *
   * Since the results are sequential, the last link in the results is also the
   * last instance of the link in the page matching the given criteria.
   *
   * @param string $link
   *   The link locator.
   *
   * @Given I click the last :link link
   */
  public function assertClickLastLink(string $link): void {
    $locator = ['link', $link];
    $links = $this->getSession()->getPage()->findAll('named', $locator);
    $link = end($links);
    $link->click();
  }

  /**
   * Sets the last execution time of a pipeline.
   *
   * @param string $pipeline_label
   *   The pipeline label.
   * @param string $days
   *   The amount of days since the last execution of the pipeline.
   *
   * @Given the :pipeline pipeline was last executed :days days ago
   */
  public function givenPipelineRanTimeAgo(string $pipeline_label, string $days): void {
    $pipeline_manager = \Drupal::getContainer()->get('plugin.manager.pipeline_pipeline');
    $pipeline = NULL;
    /**  @var \Drupal\pipeline\Plugin\PipelinePipelineInterface $pipeline_id */
    foreach ($pipeline_manager->getDefinitions() as $definition) {
      if ($definition['label'] == $pipeline_label) {
        $pipeline = $definition;
        break;
      }
    }

    Assert::assertNotEmpty($pipeline, "Pipeline {$pipeline_label} was not found.");
    $time = \Drupal::getContainer()->get('datetime.time');
    $collection = \Drupal::getContainer()->get('keyvalue')->get('joinup_pipeline_log');
    $collection->set($pipeline['id'], $time->getRequestTime() - ($days * 86400));
  }

  /**
   * Deletes all execution dates of pipelines.
   *
   * In order to be able to not have random failures in tests, e.g. when another
   * test runs a pipeline before this, this step will clean up the history so
   * that we can test in a clean state.
   *
   * @Given no pipelines have run
   */
  public function givenNoPipelineRanYet(): void {
    $collection = \Drupal::getContainer()->get('keyvalue')->get('joinup_pipeline_log');
    $collection->deleteAll();
  }

}
