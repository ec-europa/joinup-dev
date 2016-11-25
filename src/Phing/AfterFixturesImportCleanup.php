<?php
/**
 * @file
 * Contains \DrupalProject\build\Phing\AfterFixturesImportCleanup.
 */

namespace DrupalProject\Phing;

/**
 * Class AfterFixturesImportCleanup.
 */
class AfterFixturesImportCleanup extends VirtuosoTaskBase {

  /**
   * Set the permissions of the '/sparql' endpoint to allow update queries.
   */
  public function main() {
    $this->execute('sparql DELETE FROM <http://languages-skos>
{
  ?entity ?field ?value.
  }
WHERE
{
  ?entity ?field ?value.
  FILTER(isBlank(?entity))
};');
    $this->execute('sparql DELETE FROM <http://languages-skos> {
  ?entity ?field ?value.
}
WHERE
{
  ?entity ?field ?value.
  ?entity rdf:type <http://publications.europa.eu/resource/authority/Multilingual>
};');

  }

}
