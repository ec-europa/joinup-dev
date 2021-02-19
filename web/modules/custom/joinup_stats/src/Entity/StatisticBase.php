<?php

declare(strict_types = 1);

namespace Drupal\joinup_stats\Entity;

use Drupal\meta_entity\Entity\MetaEntity;

/**
 * Base class for meta entities that store statistical information.
 */
abstract class StatisticBase extends MetaEntity implements StatisticInterface {
}
