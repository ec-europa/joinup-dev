<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates newsletters.
 *
 * @MigrateSource(
 *   id = "newsletter"
 * )
 */
class Newsletter extends NodeBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'newsletter' => $this->t('Newsletter'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_newsletter', 'n')->fields('n', ['newsletter']);
  }

}
