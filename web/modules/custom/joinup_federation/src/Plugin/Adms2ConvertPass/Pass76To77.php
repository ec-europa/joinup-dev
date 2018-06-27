<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Adms2ConvertPass;

use Drupal\KernelTests\KernelTestBase;
use Drupal\joinup_federation\JoinupFederationAdms2ConvertPassPluginBase;
use Drupal\Tests\joinup_federation\Kernel\ConvertToAdms2StepTest;

/**
 * Conversion Pass #76 and #77.
 *
 * Namespaces for the information below:
 * - dcat = "http://www.w3.org/ns/dcat#"
 * - v = "http://www.w3.org/2006/vcard/ns#"
 *
 * Update the class that the dcat:contactPoint property points to.
 *
 * URI: http://www.w3.org/2006/vcard/ns#VCard
 * Type: Mandatory class
 * Action: Updated
 * Description:
 * - Updated: The vCard class was replaced by the v:Kind class.
 *
 * URI: http://www.w3.org/2006/vcard/ns#formattedName
 * Type: Property
 * Action: Updated
 * Description:
 * - Updated: The property is replaced by the v:fn property to match the
 * specifications of the v:Kind class.
 *
 * URI: http://www.w3.org/2006/vcard/ns#email and
 *   http://www.w3.org/2006/vcard/ns#Email
 * Type: Property
 * Action: Updated
 * Description:
 * - Updated: Both of the above URIs are converted to the property v:hasEmail.
 *
 * URI: http://www.w3.org/2006/vcard/ns#url
 * Type: Property
 * Action: Updated
 * Description:
 * - Updated: The property url will be converted into the v:hasUrl property.
 *
 * @Adms2ConvertPass(
 *   id = "pass_76_to_77",
 * )
 */
class Pass76To77 extends JoinupFederationAdms2ConvertPassPluginBase {

  /**
   * {@inheritdoc}
   */
  public function convert(array $data): void {
    // Update the class of any contact information entry in the sink graph.
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#VCard> }
INSERT { ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#Kind> }
WHERE { 
  ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#VCard>
}
QUERY;
    $this->sparql->query($query);

    // Update the label property predicate for all contact information entries
    // using the new class type from above.
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject <http://www.w3.org/2006/vcard/ns#formattedName> ?label }
INSERT { ?subject <http://www.w3.org/2006/vcard/ns#fn> ?label }
WHERE {
  ?subject <http://www.w3.org/2006/vcard/ns#formattedName> ?label .
  ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#Kind>
}
QUERY;
    $this->sparql->query($query);

    // Update the email properties.
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject ?email_predicate ?email }
INSERT { ?subject <http://www.w3.org/2006/vcard/ns#hasEmail> ?email }
WHERE {
  ?subject ?email_predicate ?email .
  ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#Kind> .
  VALUES ?email_predicate { <http://www.w3.org/2006/vcard/ns#email> <http://www.w3.org/2006/vcard/ns#Email> } . 
}
QUERY;
    $this->sparql->query($query);

    // Update the url properties.
    $query = <<<QUERY
WITH <{$data['sink_graph']}>
DELETE { ?subject <http://www.w3.org/2006/vcard/ns#url> ?url }
INSERT { ?subject <http://www.w3.org/2006/vcard/ns#hasURL> ?url }
WHERE {
  ?subject <http://www.w3.org/2006/vcard/ns#url> ?url .
  ?subject <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://www.w3.org/2006/vcard/ns#Kind> .
}
QUERY;
    $this->sparql->query($query);
  }

  /**
   * {@inheritdoc}
   */
  public function performAssertions(KernelTestBase $test): void {
    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://www.w3.org/2006/vcard/ns#VCard>'
    );
    $test->assertEmpty($results);

    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
      '<http://www.w3.org/2006/vcard/ns#Kind>'
    );
    $test->assertCount(1, $results);

    $empty_predicates = [
      'http://www.w3.org/2006/vcard/ns#email',
      'http://www.w3.org/2006/vcard/ns#Email',
      'http://www.w3.org/2006/vcard/ns#url',
    ];
    foreach ($empty_predicates as $predicate) {
      $results = $this->getTriplesFromGraph(
        ConvertToAdms2StepTest::getTestingGraphs()['sink'],
        NULL,
        $predicate,
        NULL
      );
      $test->assertEmpty($results);
    }

    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/2006/vcard/ns#hasEmail',
      NULL
    );
    $test->assertCount(3, $results);

    $results = $this->getTriplesFromGraph(
      ConvertToAdms2StepTest::getTestingGraphs()['sink'],
      NULL,
      'http://www.w3.org/2006/vcard/ns#hasURL',
      NULL
    );
    $test->assertCount(2, $results);
  }

  /**
   * {@inheritdoc}
   */
  public function getTestingRdfData(): ?string {
    return <<<RDF
<rdf:Description rdf:about="http://example.com/dataset/76/1">
    <rdf:type rdf:resource="http://www.w3.org/ns/dcat#Dataset"/>
    <dcat:contactPoint rdf:resource="http://example.com/contact/76/1"/>
    <dcat:contactPoint rdf:resource="http://example.com/contact/76/2"/>
    <dct:title xml:lang="en">Dataset 76/1</dct:title>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/76/1">
    <rdf:type rdf:resource="http://www.w3.org/2006/vcard/ns#VCard"/>
    <vcard:formattedName xml:lang="en">Owner 11/3</vcard:formattedName>
    <vcard:email>test@example.com</vcard:email>
    <vcard:Email>anothertest@example.com</vcard:Email>
    <vcard:hasURL>http://example.com</vcard:hasURL>
</rdf:Description>
<rdf:Description rdf:about="http://example.com/dataset/76/1">
    <rdf:type rdf:resource="http://www.w3.org/2006/vcard/ns#VCard"/>
    <vcard:formattedName xml:lang="en">Owner 11/3</vcard:formattedName>
    <vcard:hasEmail>sometest@example.com</vcard:hasEmail>
    <vcard:url>http://example.com/somewhere/else</vcard:url>
</rdf:Description>
RDF;
  }

}
