<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope;
use Drupal\joinup\Traits\NodeTrait;

/**
 * Behat step definitions for testing news pages.
 */
class JoinupNewsContext extends RawDrupalContext {

  use NodeTrait;

  /**
   * Navigates to the canonical page display of a news page.
   *
   * @param string $title
   *   The name of the news page.
   *
   * @When I go to the :title news
   * @When I visit the :title news
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function visitNewsPage($title) {
    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->getNodeByTitle($title, 'news');
    $this->visitPath($node->toUrl()->toString());
  }

  /**
   * Provides default values for required fields.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\BeforeNodeCreateScope $scope
   *   An object containing the entity properties and fields that are to be used
   *   for creating the node as properties on the object.
   *
   * @BeforeNodeCreate
   */
  public static function massageNewsFieldsBeforeNodeCreate(BeforeNodeCreateScope $scope): void {
    $node = $scope->getEntity();

    if ($node->type !== 'news') {
      return;
    }

    // The Headline field is required, and in normal usage a news article cannot
    // be created without one. Provide a default value if the scenario omits it.
    if (empty($node->field_news_headline)) {
      $node->field_news_headline = sprintf('Top %d interoperability tips. You will never believe what is on number %d!', rand(0, 100000), rand(0, 10));
    }
  }

}
