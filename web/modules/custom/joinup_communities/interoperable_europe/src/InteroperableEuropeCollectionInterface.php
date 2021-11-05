<?php

declare(strict_types = 1);

namespace Drupal\interoperable_europe;

/**
 * Interface for the Interoperable Europe collection.
 */
interface InteroperableEuropeCollectionInterface {

  /**
   * The RDF entity ID of the Interoperable Europe collection.
   */
  public const COLLECTION_ENTITY_ID = 'http://data.europa.eu/w21/8e30f798-ff2b-478b-9c09-5ed5a63b4c8c';

  /**
   * The node ID of the Interoperable Europe landing page.
   */
  public const LANDING_PAGE_ENTITY_ID = 704740;

}
