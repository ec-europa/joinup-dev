<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\joinup_core\Traits\LegacyMultipleCardinalitySupportingFieldWidgetTrait;

/**
 * Widget for text fields that support multiple cardinality for existing data.
 *
 * In the Drupal 6 version of Joinup it was possible to enter multiple values
 * for some text fields, but in the new version we only allow to enter a single
 * value. However, it should still be possible to edit existing multivalue data
 * for content that was migrated from Drupal 6.
 *
 * @FieldWidget(
 *   id = "string_textfield_legacy_multicardinality",
 *   label = @Translation("Textfield with legacy multivalue support"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class LegacyMultipleCardinalitySupportingTextFieldWidget extends StringTextfieldWidget {

  use LegacyMultipleCardinalitySupportingFieldWidgetTrait;

}
