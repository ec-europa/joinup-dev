<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Entity label is unique within the entities of the same bundle from the group.
 *
 * @todo Is it worth it moving this constraint upstream, in the OG module?
 *
 * @Constraint(
 *   id = "UniqueFieldValueInGroupAndBundle",
 *   label = @Translation("Entity label is unique within the entities of the same bundle from the group", context = "Validation"),
 * )
 */
class UniqueFieldValueInGroupAndBundle extends Constraint {

  /**
   * Constraint violation message.
   *
   * @var string
   */
  public $message = "The @bundle @field_label value (%value) is already taken by <a href=\":url\">@label</a>.";

  /**
   * The OG audience field.
   *
   * @var string
   */
  public $groupAudienceField;

  /**
   * {@inheritdoc}
   */
  public function getDefaultOption(): string {
    return 'groupAudienceField';
  }

  /**
   * {@inheritdoc}
   */
  public function getRequiredOptions(): array {
    return [
      'groupAudienceField',
    ] + parent::getRequiredOptions();
  }

}
