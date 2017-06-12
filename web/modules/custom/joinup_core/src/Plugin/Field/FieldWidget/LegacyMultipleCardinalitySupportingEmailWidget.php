<?php

namespace Drupal\joinup_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\EmailDefaultWidget;
use Drupal\joinup_core\Traits\LegacyMultipleCardinalitySupportingFieldWidgetTrait;

/**
 * Widget for e-mail fields that support multiple cardinality for existing data.
 *
 * In the Drupal 6 version of Joinup it was possible to enter multiple e-mail
 * addresses for some e-mail fields. In the new version we only allow to enter a
 * single e-mail address for these fields. However, it should still be possible
 * to edit existing multivalue data for content that was migrated from Drupal 6.
 *
 * @FieldWidget(
 *   id = "email_legacy_multicardinality",
 *   label = @Translation("Email with legacy multivalue support"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class LegacyMultipleCardinalitySupportingEmailWidget extends EmailDefaultWidget {

  use LegacyMultipleCardinalitySupportingFieldWidgetTrait;

}
