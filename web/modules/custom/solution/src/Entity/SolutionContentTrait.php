<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CommunityInterface;
use Drupal\collection\Exception\MissingCommunityException;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\solution\Exception\MissingSolutionException;

/**
 * Shared code for bundle classes that are solution content.
 */
trait SolutionContentTrait {

  /**
   * {@inheritdoc}
   */
  public function getCommunity(): CommunityInterface {
    try {
      // Asset releases are 2nd level collection content, through a solution.
      $solution = $this->getSolution();
    }
    catch (MissingSolutionException $exception) {
      throw new MissingCommunityException($exception->getMessage(), 0, $exception);
    }

    return $solution->getCommunity();
  }

  /**
   * {@inheritdoc}
   */
  public function getSolution(): SolutionInterface {
    try {
      /** @var \Drupal\solution\Entity\SolutionInterface $group */
      $group = $this->getGroup();
    }
    catch (MissingGroupException $exception) {
      throw new MissingSolutionException($exception->getMessage(), 0, $exception);
    }

    return $group;
  }

}
