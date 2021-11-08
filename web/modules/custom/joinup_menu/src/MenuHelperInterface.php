<?php

declare(strict_types = 1);

namespace Drupal\joinup_menu;

/**
 * Interface describing helper methods for working with menus.
 */
interface MenuHelperInterface {

  /**
   * Returns the account menu in the format expected by the BCL header template.
   *
   * @return array[]
   *   An array of menu links for the account menu in the BCL header template
   *   format, which is an array of arrays with the following keys:
   *   - link: The URL the link points to.
   *   - label: The link label.
   */
  public function getBclAccountMenu(): array;

  /**
   * Returns the anonymous menu in the format used by the BCL header template.
   *
   * @return array[]
   *   An array of menu links for the anonymous menu in the BCL header template
   *   format, which is an array of arrays with the following keys:
   *   - link: The URL the link points to.
   *   - label: The link label.
   */
  public function getBclAnonymousMenu(): array;

}
