<?php

declare(strict_types = 1);

namespace Drupal\joinup_front_page\Entity;

/**
 * Reusable methods for entities that can be pinned to the front page.
 *
 * @see \Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface
 */
trait PinnableToFrontpageTrait {

  /**
   * {@inheritdoc}
   */
  public function isPinnedToFrontPage(): bool {
    /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $helper */
    $helper = \Drupal::service('joinup_front_page.front_page_helper');
    return (bool) $helper->getFrontPageMenuItem($this);
  }

  /**
   * {@inheritdoc}
   */
  public function pinToFrontPage(): PinnableToFrontpageInterface {
    /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $helper */
    $helper = \Drupal::service('joinup_front_page.front_page_helper');
    $helper->pinToFrontPage($this);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function unpinFromFrontPage(): PinnableToFrontpageInterface {
    /** @var \Drupal\joinup_front_page\FrontPageMenuHelperInterface $helper */
    $helper = \Drupal::service('joinup_front_page.front_page_helper');
    $helper->unpinFromFrontPage($this);
    return $this;
  }

}
