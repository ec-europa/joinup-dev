<?php

namespace Drupal\joinup_html_stripper\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\smart_trim\Truncate\TruncateHTML;

/**
 * Field formatter which strips all HTML.
 *
 * This is tailored to the use case of Joinup. It relies on a custom text filter
 * format `stripped_html` which includes all of the filters that are commonly
 * used in Joinup, such as Joinup Video, Glossary items, etc.
 *
 * The goal of this is to be able to not only strip vanilla HTML elements that
 * are present in the content but also placeholder tokens which are included by
 * CKEditor to mark the location of embedded media and other content.
 *
 * This prevents for example JSON metadata to be shown to the end user in places
 * where only plain text is desired (for example in tiles or teasers).
 *
 * @FieldFormatter(
 *   id = "joinup_html_stripper_stripped",
 *   label = @Translation("Stripped"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *     "text",
 *     "text_long",
 *     "text_with_summary"
 *   },
 *   settings = {
 *     "trim_length" = "300"
 *   }
 * )
 */
class StrippedHTMLFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'trim_length' => 600,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['trim_length'] = [
      '#title' => $this->t('Trim length'),
      '#type' => 'number',
      '#default_value' => $this->getSetting('trim_length'),
      '#description' => $this->t('Set the desired trim length, or set to 0 to use the full text.'),
      '#min' => 0,
      '#required' => FALSE,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('@length characters', [
        '@length' => $this->getSetting('trim_length')
      ])
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];

    foreach ($items as $delta => $item) {
      // Default to the summary if present.
      $output = $item->summary ?? $item->value;

      // Strip out any funny whitespace.
      $output = preg_replace('/\n|\r|\t/m', ' ', $output);
      $output = str_replace('&nbsp;', ' ', $output);
      $output = str_replace("\xc2\xa0", ' ', $output);
      $output = trim(preg_replace('/\s\s+/', ' ', $output));

      $output = check_markup($output, 'stripped_html');

      // Trim the text if a maximum length has been set.
      if ($this->getSetting('trim_length') !== 0) {
        $truncate = new TruncateHTML();
        $output = $truncate->truncateChars($output, $this->getSetting('trim_length'), '…');
      }
      $element[$delta] = [
        '#markup' => $output,
      ];
    }

    return $element;
  }

}
