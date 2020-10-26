<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Exception;

/**
 * Exception thrown when a distribution mises the a parent entity.
 *
 * During normal operation every distribution should have a parent entity, so
 * the only way this exception can occur it because of an unexpected condition
 * occurring at runtime, for example if a data store goes offline.
 *
 * @todo There are currently a number of orphaned asset distributions present in
 *   the database, but this is a historical anomaly and is not considered part
 *   of the normal operation. All orphaned distributions should be removed.
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6218
 *
 * @see \Drupal\asset_distribution\Entity\AssetDistributionInterface::getParent()
 */
class MissingDistributionParentException extends \RuntimeException {

}
