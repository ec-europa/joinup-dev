<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #4.
 *
 * URI: dct:hasVersion, dct:isVersionOf, adms:next, adms:prev, adms:last.
 * Description:
 *  - In ADMS v1, an Asset was a release and all other related Assets were
 * releases connected to each other with the properties adms:next, adms:prev and
 * adms:last, marking the next release, the previous release and the last
 * release.
 * In ADMS v2, the distinction between releases occurs through the version
 * number.
 * The procedure that will be followed is: The Asset that has not an adms:prev
 * flag, will be considered the solution. All other Assets related to it, will
 * be marked under it with the property dct:hasVersion. All the other related
 * Assets, will receive the property dct:isVersionOf, pointing to the parent
 * asset. Properties adms:last, adms:next and adms:prev will be removed.
 * The adms:last property does not since it was used to flag the latest release
 * but in ADMS-AP v2, it is not a valid way to sort.
 *
 * @Adms2ConvertPass(
 *   id = "pass_27_28_29_30",
 * )
 */
class Pass27To30 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    $sink_graph = ConvertToAdms2StepTest::getTestingGraphs()['sink'];
    $query = <<<QUERY
SELECT ?subject ?subject_type ?prev ?next
FROM <$sink_graph>
WHERE { 
  ?subject a ?subject_type .
  VALUES ?subject_type { <http://www.w3.org/ns/adms#Asset> <http://www.w3.org/ns/dcat#Dataset> } .
  OPTIONAL { ?subject <http://www.w3.org/ns/adms#prev> ?prev } . 
  OPTIONAL { ?subject <http://www.w3.org/ns/adms#next> ?next } . 
}
QUERY;

    $results = $this->sparql->query($query);
    $entities = [];
    foreach ($results as $result) {
      $subject = $result->subject->getUri();
      $entities[$subject] = [
        'uri' => $subject,
      ];
      if (isset($result->next)) {
        $entities[$subject]['next'] = $result->next->getUri();
      }
      if (isset($result->prev)) {
        $entities[$subject]['prev'] = $result->prev->getUri();
      }
    }

    // Generate the relations of each asset and build an array of ADMS v2
    // relations.
    foreach ($entities as $id => $entity) {
      if (isset($entity['prev'])) {
        // We need to start with a parent.
        continue;
      }
      $entities = $this->generateAssetRelations($id, $id, $entities);
    }

    foreach ($entities as $entity_id => $entity) {
      $inserts = [];
      if (isset($entity['isVersionOf'])) {
        $inserts[] = "<http://purl.org/dc/terms/isVersionOf> <{$entity['isVersionOf']}>";
      }
      if (!empty($entity['hasVersion'])) {
        foreach ($entity['hasVersion'] as $uri) {
          $inserts[] = "<http://purl.org/dc/terms/hasVersion> <{$uri}>";
        }
      }
      // The semi colon allows to enter multiple values without rewriting the
      // subject.
      $insert_text = implode("; ", $inserts);
      $query = <<<QUERY
        WITH <$sink_graph>
        DELETE { ?subject ?predicate ?value }
        INSERT { ?subject $insert_text }
        WHERE { 
          ?subject ?predicate ?value .
          VALUES ?predicate { <http://www.w3.org/ns/adms#next> <http://www.w3.org/ns/adms#last> <http://www.w3.org/ns/adms#prev> } .
          VALUES ?subject { <$entity_id> }
        }  
QUERY;
      $this->sparql->query($query);
    }
  }

  /**
   * Recursively adds the relations between entities compliant to the ADMS-AP 2.
   *
   * @param string $parent_id
   *   The parent id iri.
   * @param string $current_id
   *   The current id iri.
   * @param array $entities
   *   The entities array.
   *
   * @return array
   *   The updated array of entities.
   */
  protected function generateAssetRelations(string $parent_id, string $current_id, array &$entities): array {
    // First add the entry to the entities table and then continue so that the
    // releases are added sequentially as in their previous version.
    if (isset($entities[$current_id]) && $parent_id != $current_id) {
      $entities[$parent_id]['hasVersion'][] = $current_id;
      $entities[$current_id]['isVersionOf'] = $parent_id;
    }
    // Escape condition. Stop when there are no more children.
    if (isset($entities[$current_id]['next'])) {
      $entities = $this->generateAssetRelations($parent_id, $entities[$current_id]['next'], $entities);
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $sink_graph = ConvertToAdms2StepTest::getTestingGraphs()['sink'];
    // The following query verifies that there are two entities only with the
    // new properties.
    $query = <<<QUERY
      SELECT DISTINCT(?subject)
      FROM <$sink_graph>
      WHERE {
        {
          <http://example.com/asset/27/2> <http://purl.org/dc/terms/isVersionOf> ?subject .
          <http://example.com/asset/27/3> <http://purl.org/dc/terms/isVersionOf> ?subject .
          <http://example.com/asset/27/4> <http://purl.org/dc/terms/isVersionOf> ?subject .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/2> .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/3> .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/4> .
        }
        UNION {
          <http://example.com/asset/27/6> <http://purl.org/dc/terms/isVersionOf> ?subject .
          <http://example.com/asset/27/7> <http://purl.org/dc/terms/isVersionOf> ?subject .
          <http://example.com/asset/27/8> <http://purl.org/dc/terms/isVersionOf> ?subject .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/6> .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/7> .
          ?subject <http://purl.org/dc/terms/hasVersion> <http://example.com/asset/27/8> .
        } . 
        FILTER NOT EXISTS { ?subject ?non_predicates ?non_value } .  
        VALUES ?non_predicates { <http://www.w3.org/ns/adms#next> <http://www.w3.org/ns/adms#prev> <http://www.w3.org/ns/adms#last> } 
      }
      ORDER BY ASC(?subject)
QUERY;

    $results = $this->sparql->query($query)->getArrayCopy();
    $results = array_map(function ($result) {
      return $result->subject->getUri();
    }, $results);
    $test->assertEquals([
      'http://example.com/asset/27/1',
      'http://example.com/asset/27/5',
    ], $results);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/asset/27/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/2"/>
    <adms:last rdf:resource="http://example.com/asset/27/4"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/2">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/3"/>
    <adms:prev rdf:resource="http://example.com/asset/27/1"/>
    <adms:last rdf:resource="http://example.com/asset/27/4"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/3">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/4"/>
    <adms:prev rdf:resource="http://example.com/asset/27/2"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/4">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:prev rdf:resource="http://example.com/asset/27/3"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/5">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/6"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/6">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/7"/>
    <adms:prev rdf:resource="http://example.com/asset/27/5"/>
    <adms:last rdf:resource="http://example.com/asset/27/8"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/7">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:next rdf:resource="http://example.com/asset/27/8"/>
    <adms:prev rdf:resource="http://example.com/asset/27/6"/>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/asset/27/8">
    <rdf:type rdf:resource="http://www.w3.org/ns/adms#Asset"/>
    <dct:title xml:lang="en">Asset 11/1</dct:title>
    <adms:prev rdf:resource="http://example.com/asset/27/7"/>
</rdf:Description>
RDF;
  }

}
