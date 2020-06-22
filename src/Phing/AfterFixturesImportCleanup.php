<?php

namespace Joinup\Phing;

use Virtuoso\Task\VirtuosoTaskBase;

/**
 * Class AfterFixturesImportCleanup.
 */
class AfterFixturesImportCleanup extends VirtuosoTaskBase {

  /**
   * Clean up the fixtures after import.
   */
  public function main() {
    // We get our languages from the Metadata Registry. The Metadata Registry
    // maintains two authority tables, one for individual languages and one for
    // groups of languages called multilingual. For legacy reasons the two
    // tables are published as a merger of the two.
    // The multilingual language groups are not useful for us and we need to
    // filter them out to avoid having the language lists polluted with entries
    // labeled 'Multilingual Code'.
    // @see http://publications.europa.eu/mdr/resource//documentation/schema/cat.html#element_languages
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-2764
    $this->query('DELETE FROM <http://languages-skos> { ?entity ?field ?value. } WHERE { ?entity ?field ?value . FILTER(str(?value) = "Multilingual Code") };');

    // @see ISAICP-3084
    $this->query('INSERT INTO <http://adms-sw-v1.00> { <http://purl.org/adms/licencetype/ViralEffect-ShareAlike>  <http://www.w3.org/2004/02/skos/core#inScheme> <http://purl.org/adms/licencetype/1.1> };');

    // The licences are defined in both the adms-sw and the adms-skos files.
    // In adms-sw the version 1.1 is included while the adms-skos has the
    // version 1.0. As a bundle can have only one uri mapped, the 1.0 version
    // has to be removed.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-2503
    $this->query('DELETE FROM <http://adms_skos_v1.00> { ?entity ?field ?value. } WHERE { ?entity ?field ?value . ?entity <http://www.w3.org/2004/02/skos/core#inScheme> <http://purl.org/adms/licencetype/1.0>};');

    // Remove any non english version of the taxonomy terms since we are only
    // supporting english version in the website. If more terms are needed, the
    // supported languages should be extended (the "en" below).
    // This needs to repeat multiple times as the terms might.
    $this->query('DELETE { GRAPH ?g { ?entity ?field ?value } } WHERE { GRAPH ?g { ?entity ?field ?value . FILTER (LANG(?value) != "" && LANG(?value) != "en") } };');

    // @see ISAICP-3216
    // Add the "Concept" type to all collection elements so that they are listed
    // as Parent terms.
    $this->query('WITH <http://eira_skos> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
    // Add the link to all "Concept" type elements so that they are all considered
    // as children of the EIRA vocabulary regardless of the depth.
    $this->query('WITH <http://eira_skos> INSERT INTO <http://eira_skos> { ?subject skos:topConceptOf <http://data.europa.eu/dr8> } WHERE { ?subject a skos:Concept .};');
    // Create a backwards connection from the children to the parent.
    $this->query('WITH <http://eira_skos> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');
    // Remove deprecated countries from the country list.
    // @See ISAICP-3442
    $this->query('WITH <http://countries-skos> DELETE { ?entity ?field ?value. } WHERE { ?entity ?field ?value . ?entity <http://publications.europa.eu/ontology/authority/end.use> ?date . FILTER ( bound(?date) ) };');

    // Languages are required to be of type <http://purl.org/dc/terms/Location>
    // but are listed as <http://www.w3.org/2004/02/skos/core#Concept> which is
    // also correct. Add the entry
    // { ?subject a <http://purl.org/dc/terms/Location> } for each language in
    // the <http://countries-skos> graph.
    // @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-4566
    $this->query('WITH <http://countries-skos> INSERT { ?entity <http://www.w3.org/1999/02/22-rdf-syntax-ns#type> <http://purl.org/dc/terms/Location> } WHERE { ?entity a <http://www.w3.org/2004/02/skos/core#Concept> };');

    // As with the EIRA vocabulary, for elements of type skos:Collection, add
    // the skos:Concept as a type to the list so that the parent elements are
    // properly mapped to a bundle, and add the skos:broader for the backwards
    // connection from the child to the parent.
    $this->query('WITH <http://licence-legal-type> INSERT { ?subject a skos:Concept } WHERE { ?subject a skos:Collection . };');
    $this->query('WITH <http://licence-legal-type> INSERT { ?subject skos:topConceptOf <http://joinup.eu/legal-type#> } WHERE { ?subject a skos:Concept . FILTER NOT EXISTS { ?subject skos:topConceptOf <http://joinup.eu/legal-type#> } };');
    $this->query('WITH <http://licence-legal-type> INSERT { ?member skos:broaderTransitive ?collection } WHERE { ?collection a skos:Collection . ?collection skos:member ?member };');
  }

}
