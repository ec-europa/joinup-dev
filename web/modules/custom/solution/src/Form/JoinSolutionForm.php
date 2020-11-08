<?php

declare(strict_types = 1);

namespace Drupal\solution\Form;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\joinup_group\Form\JoinGroupFormBase;
use Drupal\og\OgMembershipInterface;

/**
 * A simple form with a button to join or leave a solution.
 */
class JoinSolutionForm extends JoinGroupFormBase {

  /**
   * Returns the label for the submit button.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the label.
   */
  public function getSubmitLabel(): TranslatableMarkup {
    return $this->t('Follow this :type', [
      ':type' => $this->group->bundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSuccessMessage(OgMembershipInterface $membership): TranslatableMarkup {
    return $this->t('You are now following this solution.');
  }

}
