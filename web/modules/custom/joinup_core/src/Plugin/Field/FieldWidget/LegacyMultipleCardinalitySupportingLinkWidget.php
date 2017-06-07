<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\joinup_core\Traits\LegacyMultipleCardinalitySupportingFieldWidgetTrait;
use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;

/**
 * Widget for link fields that support multiple cardinality for existing data.
 *
 * In the Drupal 6 version of Joinup it was possible to enter multiple values
 * for some link fields, but in the new version we only allow to enter a single
 * value. However, it should still be possible to edit existing multivalue data
 * for content that was migrated from Drupal 6.
 *
 * @FieldWidget(
 *   id = "link_legacy_multicardinality",
 *   label = @Translation("With legacy multivalue support"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LegacyMultipleCardinalitySupportingLinkWidget extends LinkWidget {

  use LegacyMultipleCardinalitySupportingFieldWidgetTrait;

}
