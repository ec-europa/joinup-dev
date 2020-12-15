<?php

declare(strict_types = 1);

namespace Drupal\joinup_newsletter\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for newsletter entities in Joinup.
 */
interface NewsletterInterface extends NodeInterface, CollectionContentInterface {
}
