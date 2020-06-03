<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\solution\Exception\MissingSolutionException;

/**
 * Shared code for bundle classes that are solution content.
 */
trait SolutionContentTrait {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    try {
      // Asset releases are 2nd level collection content, through a solution.
      $solution = $this->getSolution();
    }
    catch (MissingSolutionException $exception) {
      throw new MissingCollectionException($exception->getMessage(), 0, $exception);
    }

    return $solution->getCollection();
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
