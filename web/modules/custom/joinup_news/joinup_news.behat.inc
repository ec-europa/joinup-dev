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
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title doesn't exist.
   *
   * @Then the :rdf_entity collection has a news page titled :title
   */
  public function assertOgMembership($title, $rdf_entity) {
    $rdf_entity = $this->getRdfEntityByLabel($rdf_entity, 'collection');

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

    throw new \Exception("The news page '$title' is not associated with collection '{$rdf_entity->label()}'.");
  }

  /**
   * Checks if the given news page belongs to the given solution.
   *
   * If there are multiple news pages or solutions with the same name, then
   * only the first one is checked.
   *
   * @param string $title
   *   The title of the news page to check.
   * @param string $rdf_entity
   *   The name of the solution to check.
   *
   * @throws \Exception
   *   Thrown when a custom page with the given title does not exist or is not
   *   related to the solution.
   *
   * @Then the :rdf_entity solution has a news page titled :title
   */
  public function assertSolutionParent($title, $rdf_entity) {
    $rdf_entity = $this->getRdfEntityByLabel($rdf_entity, 'solution');

    $news = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $title, 'field_news_parent' => $rdf_entity->id()]);
    /** @var \Drupal\node\NodeInterface $new */
    $new = reset($news);

    if (empty($new)) {
      throw new \Exception("The news page titled '$title' was not found or the entity is not related to solution {$rdf_entity->id()}.");
    }
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
