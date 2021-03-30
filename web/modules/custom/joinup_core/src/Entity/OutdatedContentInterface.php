<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Entity;

use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;

/**
 * Entity whose content may be outdated.
 */
interface OutdatedContentInterface extends EntityPublicationTimeInterface {

  /**
   * Returns a timestamp with the date/time when this entity become outdated.
   *
   * @return int|null
   *   A timestamp with the date/time when this entity become outdated or NULL,
   *   if the entity will be never outdated or it has not been published yet.
   */
  public function getOutdatedTime(): ?int;

}
