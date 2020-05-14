<?php

/**
 * @file
 * Contains \JoinupParagraphsSubContext.
 */

declare(strict_types = 1);

use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Element\TraversableElement;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\BrowserCapabilityDetectionTrait;
use Drupal\joinup\Traits\WysiwygTrait;
use PHPUnit\Framework\Assert;

/**
 * Behat step definitions for testing searches.
 */
class JoinupParagraphsSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use BrowserCapabilityDetectionTrait;
  use WysiwygTrait;

  /**
   * A copycat of behat's internal region mapping.
   *
   * This will be used to determine the selector of each paragraph field name.
   * This is because in some cases, the title of the field is not visible as the
   * field is configured to already display a paragraph form instead of the add
   * buttons.
   *
   * @var array
   */
  const FIELD_MAP = [
    'Custom page body' => 'field-paragraphs-body',
  ];

  /**
   * Enters the given text in the given WYSIWYG editor.
   *
   * If this is running on a JavaScript enabled browser it will first click the
   * 'Source' button so the text can be entered as normal HTML.
   *
   * @param string $text
   *   The text to enter in the WYSIWYG editor.
   * @param string $label
   *   The label of the field containing the WYSIWYG editor.
   * @param string $field
   *   The field in which to take action.
   * @param int $position
   *   The delta of the paragraph entry.
   *
   * @When I enter :text in the :label wysiwyg editor in the :field field at position :position
   */
  public function enterTextInWysiwyg(string $text, string $label, string $field, int $position): void {
    $field = $this->getParagraphsElement($field, $position);

    // If we are running in a JavaScript enabled browser, first click the
    // 'Source' button so we can enter the text as HTML and get the same result
    // as in a non-JS browser.
    if ($this->browserSupportsJavaScript()) {
      $this->pressWysiwygButton($label, 'Source', $field);
      $this->setWysiwygText($label, $text, $field);
    }
    else {
      $this->getSession()->getPage()->fillField($label, $text);
    }
  }

  /**
   * Asserts the existence of a button in a row of paragraphs.
   *
   * @param string $button
   *   The button label.
   * @param string $field
   *   The field name.
   * @param int $position
   *   The paragraphs row number. Starts at 1 for readability.
   *
   * @Given I should see the :button button in the :field field at row :position
   */
  public function assertButtonInParagraphsItem(string $button, string $field, int $position): void {
    $paragraph_item = $this->getParagraphsElement($field, --$position);
    Assert::assertNotEmpty($paragraph_item->findButton($button));
  }

  /**
   * Asserts that a button does not exist in a row of paragraphs.
   *
   * @param string $button
   *   The button label.
   * @param string $field
   *   The field name.
   * @param int $position
   *   The paragraphs row number. Starts at 1 for readability.
   *
   * @Given I should not see the :button button in the :field field at row :position
   */
  public function assertButtonNotInParagraphsItem(string $button, string $field, int $position): void {
    $paragraph_item = $this->getParagraphsElement($field, --$position);
    Assert::assertEmpty($paragraph_item->findButton($button));
  }

  /**
   * Clicks the remove button in the given paragraphs item.
   *
   * @param string $button
   *   The button label.
   * @param string $field
   *   The field identifier. One of the keys in the
   *   \JoinupParagraphsSubContext::fieldMapping array.
   * @param int|null $position
   *   (optional) The position of the item to remove. Starts at 1 for
   *   readability. If left empty, the button will be searched in the entire
   *   field.
   *
   * @Given I press :button in the :field field at row :position
   * @Given I press :button in the :field paragraphs field
   */
  public function clickButtonInParagraphsField(string $button, string $field, ?int $position = NULL): void {
    $paragraph_item = $this->getParagraphsElement($field, --$position);
    $button = $paragraph_item->findButton($button);
    $button->click();
  }

  /**
   * Asserts the number of rows in the paragraphs field.
   *
   * @param int $expected_rows
   *   The expected number of rows.
   * @param string $field
   *   The field name.
   *
   * @Given there should be :expected row(s) in the :field field
   */
  public function assertRowsInParagraphsField(int $expected_rows, string $field): void {
    $field = $this->getParagraphsElement($field);
    Assert::assertCount($expected_rows, $field->findAll('xpath', '//tr[contains(@class, "form-table__row")]'));
  }

  /**
   * Asserts the number of paragraphs in the page.
   *
   * @param int $expected_rows
   *   The expected number of paragraphs.
   *
   * @Given there should be :expected_rows paragraph(s) in the page
   */
  public function assertParagraphsCountInPage(int $expected_rows): void {
    Assert::assertCount($expected_rows, $this->getSession()->getPage()->findAll('xpath', '//div[contains(@class, "paragraph")]'));
  }

  /**
   * Asserts the exact text content of the paragraphs in the page.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The table of expected paragraph texts.
   *
   * @Given I should see the following paragraphs in the given order:
   */
  public function assertExactParagraphsContentInPage(TableNode $table): void {
    $expected = $table->getColumn(0);
    $paragraphs = $this->getSession()->getPage()->findAll('xpath', '//div[contains(@class, "paragraph")]');
    $actual = array_map(function (TraversableElement $paragraph): string {
      return $paragraph->getText();
    }, $paragraphs);
    Assert::assertSame($expected, $actual);
  }

  /**
   * Returns the field element according to the xpath selector.
   *
   * @param string $field
   *   The field designator.
   * @param int|null $position
   *   (optional) If empty, the whole field will be returned. If passed, the
   *   corresponding delta will be returned.
   *
   * @return \Behat\Mink\Element\TraversableElement
   *   The field element.
   */
  protected function getParagraphsElement(string $field, ?int $position = NULL): TraversableElement {
    if (!isset(self::FIELD_MAP[$field])) {
      throw new \InvalidArgumentException("The '{$field}' field is not mapped. Check \JoinupParagraphsSubContext::fieldMapping for the mapped fields and add your field and your selector to it.");
    }

    $mapping = self::FIELD_MAP[$field];
    $xpath = "//div[contains(@class, 'field--name-{$mapping}')]";
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    if (empty($element)) {
      throw new \InvalidArgumentException("The {$field} field was not found anywhere in the page.");
    }

    if ($position === NULL) {
      return $element;
    }

    // Normally, the id of the element we are seeking is in the form of
    // '{field-name}-{delta}-item-wrapper'. However, when submitting the remove
    // button for example, it receives a small hash for a suffix and receives
    // the form of '{field-name}-{delta}-item-wrapper--{hash}'.
    $xpath = "//div[starts-with(@id, '{$mapping}-{$position}-item-wrapper')]";
    $element = $element->find('xpath', $xpath);
    if (empty($element)) {
      throw new \InvalidArgumentException("The {$field} field does not have an item at row {$position}.");
    }
    return $element;
  }

  /**
   * Cleans up the paragraphs entities created when nodes where created.
   *
   * This uses the orphan paragraphs purger queue worker.
   *
   * @AfterScenario
   */
  public function paragraphsCleanup(): void {
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $worker */
    $worker = \Drupal::service('plugin.manager.queue_worker')->createInstance('entity_reference_revisions_orphan_purger');
    $queue = \Drupal::queue('entity_reference_revisions_orphan_purger');

    while ($item = $queue->claimItem()) {
      $worker->processItem($item->data);
      $queue->deleteItem($item);
    }
  }

}
