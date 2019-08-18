<?php

namespace Drupal\joinup_federation_test\Plugin\pipeline\Step;

use Drupal\joinup_federation\Plugin\pipeline\Step\UserSelectionFilter;

/**
 * Wraps the 'user_selection_filter' plugin for testing purposes.
 *
 * We need this just in order to:
 * - Give public access to ::collectReferences() method so that it can be
 *   accessed by the testing code.
 * - Get the internal protected $whitelist variable to be inspected in tests.
 *
 * @PipelineStep(
 *   id = "test_user_selection_filter",
 *   label = @Translation("Testing user selection"),
 * )
 */
class TestUserSelectionFilter extends UserSelectionFilter {

  /**
   * Wrapper method for the ::buildWhitelist method.
   *
   * @see \Drupal\joinup_federation\Plugin\pipeline\Step\UserSelectionFilter::buildWhitelist
   */
  public function buildWhitelistWrapper(array $whitelist_ids): void {
    parent::buildWhitelist($whitelist_ids);
  }

  /**
   * Gets the protected $whitelist protected array to be inspected in tests.
   *
   * @return array
   *   References.
   */
  public function getWhitelist(): array {
    return $this->whitelist;
  }

}
