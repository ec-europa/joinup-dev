<?php

/**
 * @file
 * Contains \NewsSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\joinup\Traits\RdfEntityTrait;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing news pages.
 */
class NewsSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use RdfEntityTrait;
  use NodeTrait;

  /**
   * Checks if the given news page belongs to the given collection.
   *
   * If there are multiple news pages or collections with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the news page to check.
   * @param string $rdf_entity
   *   The name of the collection to check.
   * @param string $rdf_entity_bundle
   *   The bundle of the parent.
   *
   * @throws \Exception
   *   Thrown when a news content entity with the given title does not exist.
   *
   * @Then the :rdf_entity :rdf_entity_bundle has a news page titled :title
   */
  public function assertOgMembership($title, $rdf_entity, $rdf_entity_bundle) {
    $rdf_entity = $this->getRdfEntityByLabel($rdf_entity, $rdf_entity_bundle);

    $news = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title]);
    /** @var \Drupal\node\NodeInterface $new */
    $new = reset($news);

    if (empty($new)) {
      throw new \Exception("The news page titled '$title' was not found.");
    }

    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $group */
    foreach ($new->get(OgGroupAudienceHelper::DEFAULT_FIELD) as $group) {
      if ($group->getValue()['target_id'] == $rdf_entity->id()) {
        // Test passes.
        return;
      }
    }

    throw new \Exception("The news page '$title' is not associated with {$rdf_entity_bundle} '{$rdf_entity->label()}'.");
  }

  /**
   * Navigates to the canonical page display of a news page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When (I )go to the :title news page
   * @When (I )visit the :title news page
   */
  public function visitNewsPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'news');
    $this->visitPath($node->url());
  }

}
