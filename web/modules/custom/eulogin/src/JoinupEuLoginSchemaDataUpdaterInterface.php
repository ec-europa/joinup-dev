<?php

declare(strict_types = 1);

namespace Drupal\joinup_eulogin;

/**
 * Provides an interface for the 'joinup_eulogin.schema_data_updater' service.
 *
 * The 'joinup_eulogin.computed_attributes' service retrieves the organisation
 * name given the organisation domain. The later is a CAS response attribute.
 * In order to compute the organisation name we need to look-up the CAS response
 * schema, exposed at https://ecas.ec.europa.eu/cas/schemas, where a list of
 * allowed organisations is defined. But instead of making a GET request to
 * https://ecas.ec.europa.eu/cas/schemas/ on each user login, we're storing only
 * a part of the schema, containing the organisation list, in the key-value
 * store. The schema might change from time to time. E.g. a new schema version
 * is released and that adds a new organisation or change an organisation name,
 * etc. We want to update our version if something new comes in. This service
 * updates the local stored schema data only if there's new version of schema is
 * available.
 */
interface JoinupEuLoginSchemaDataUpdaterInterface {

  /**
   * Parses the schema and updates the stored data.
   *
   * @return bool
   *   TRUE if update occurred, FALSE if it's already up-to-date.
   */
  public function update(): bool;

}
