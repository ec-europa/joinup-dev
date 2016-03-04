<?php

/**
 * @file
 * Contains \Drupal\joinup\Context\DrupalContext.
 */

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\DrupalContext as DrupalExtensionDrupalContext;
use Drupal\node\Entity\NodeType;

/**
 * Provides step definitions for interacting with Drupal.
 */
class DrupalContext extends DrupalExtensionDrupalContext {

  /**
   * {@inheritdoc}
   */
  public function loggedIn() {
    $session = $this->getSession();
    $session->visit($this->locatePath('/'));

    // Check if the 'logged-in' class is present on the page.
    $element = $session->getPage();
    return $element->find('css', 'body.user-logged-in');
  }

  /**
   * Checks the existence of a content page.
   *
   * @param int $title
   *   The title of the collection page.
   *
   * @throws \Exception
   *   Thrown when the entity is not found.
   *
   * @Then I should have a :type (content )page titled :title
   */
  public function assertContentPageByTitle($type, $title) {
    $type = $this->getBundleFromLabel($type);

    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(
        ['title' => $title, 'type' => $type]
      );
    $actual = reset($nodes);
    if (empty($actual)) {
      throw new \Exception("Entity titled '$title' was not found.");
    }
  }

  /**
   * Returns the machine name of a node bundle given their Bundle Label.
   *
   * @param string $bundle_label
   *   The label of the bundle.
   *
   * @return string
   *   The machine name of the bundle.
   *
   * @throws \Exception
   *   Thrown when the bundle type is not found.
   */
  public function getBundleFromLabel($bundle_label) {
    foreach (NodeType::loadMultiple() as $type) {
      if ($bundle_label == $type->label()) {
        return $type->id();
      }
    }

    throw new \Exception("Content bundle '$bundle_label' was not found.");
  }

}
