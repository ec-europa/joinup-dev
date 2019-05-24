<?php

declare(strict_types = 1);

namespace Drupal\joinup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\SparqlArg;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A reporting page that presents solutions indexed by related licences.
 */
class SolutionsByLicenceForm extends FormBase {

  /**
   * The items per page.
   */
  protected const ITEMS_PER_PAGE = 50;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'solutions_by_licence_form';
  }

  /**
   * SolutionsByLicenceController constructor.
   *
   * @param \Drupal\sparql_entity_storage\Database\Driver\sparql\ConnectionInterface $connection
   *   The SPARQL connection.
   */
  public function __construct(ConnectionInterface $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('sparql_endpoint')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (empty($form_state->get('licence_options'))) {
      $form_state->set('licence_options', $this->getLicenceOptions());
    }

    $form['licence_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Licence'),
      '#options' => $form_state->get('licence_options'),
      '#default_value' => $form_state->getValue('licence_option'),
      '#empty_option' => ' - All - ',
    ];
    $form['filter'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    $licence_id = $form_state->getValue('licence_option');
    $results = $this->connection->query($this->getCountQuery($licence_id));
    $total = reset($results)->total->getValue();
    $page = pager_default_initialize($total, self::ITEMS_PER_PAGE);
    $offset = $page * self::ITEMS_PER_PAGE;
    $items = $this->connection->query($this->getQuery($offset, $licence_id));

    $rows = [];
    foreach ($items as $item) {
      $licence_url = Url::fromRoute('entity.rdf_entity.canonical', [
        'rdf_entity' => $item->licence,
      ]);
      $solution_url = Url::fromRoute('entity.rdf_entity.canonical', [
        'rdf_entity' => $item->solution,
      ]);

      $rows[] = [
        $this->getLinkGenerator()->generate($item->licence_label, $licence_url),
        $this->getLinkGenerator()->generate($item->solution_label, $solution_url),
      ];
    }

    $form['table'] = [
      '#theme' => 'table',
      '#header' => ['Licence', 'Solution'],
      '#rows' => $rows,
      '#empty' => $this->t('No solutions available.'),
    ];
    $form['pager'] = [
      '#type' => 'pager',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The only case where the form is submitted is by clicking the filter.
    // Rebuild to filter by licence.
    $form_state->setRebuild();
  }

  /**
   * Returns the full list of licences in the website.
   *
   * @return array
   *   An array of licence labels indexed by licence id.
   */
  protected function getLicenceOptions(): array {
    $licences_query = <<<QUERY
SELECT ?entity_id ?label
FROM <http://joinup.eu/licence/published>
WHERE {
  ?entity_id a <http://purl.org/dc/terms/LicenseDocument> .
  ?entity_id <http://www.w3.org/2000/01/rdf-schema#label> ?label .
}
ORDER BY ASC(?label)
QUERY;

    $results = $this->connection->query($licences_query);
    $return = [];
    foreach ($results as $result) {
      $return[$result->entity_id->getUri()] = $result->label->getValue();
    }
    return $return;
  }

  /**
   * Returns the query that counts total of the results.
   *
   * @param string $licence_id
   *   (Optional) A licence id to narrow down the results with.
   *
   * @return string
   *   The count query.
   */
  protected function getCountQuery(string $licence_id = NULL): string {
    $query = <<<QUERY
SELECT COUNT(*) AS ?total
WHERE {
  SELECT DISTINCT ?solution ?licence_label ?licence ?solution_label (COUNT(*) as ?total)
  WHERE {
    {
      {
        ?solution <http://www.w3.org/ns/dcat#distribution> ?distribution .
        ?distribution <http://purl.org/dc/terms/license> ?licence .
        FILTER NOT EXISTS { ?solution <http://purl.org/dc/terms/isVersionOf> ?version } .
      }
      UNION
      {
        ?solution <http://purl.org/dc/terms/hasVersion> ?release .
        ?release <http://www.w3.org/ns/dcat#distribution> ?distribution .
        ?distribution <http://purl.org/dc/terms/license> ?licence .
      }
    } .
    {
      ?solution a <http://www.w3.org/ns/dcat#Dataset> .
      ?solution <http://purl.org/dc/terms/title> ?solution_label .
      ?licence a <http://purl.org/dc/terms/LicenseDocument> .
      ?licence <http://www.w3.org/2000/01/rdf-schema#label> ?licence_label .
    } .
    @extra_condition
  }
}
QUERY;

    $extra_condition = $licence_id ? 'VALUES ?licence { ' . SparqlArg::uri($licence_id) . ' } ' : NULL;
    $query = str_replace('@extra_condition', $extra_condition, $query);
    return $query;
  }

  /**
   * Returns the query that fetches licence and solution labels.
   *
   * @param int $offset
   *   The offset number.
   * @param string $licence_id
   *   (Optional) A licence id to narrow down the results with.
   *
   * @return string
   *   The query string.
   */
  protected function getQuery(int $offset, string $licence_id = NULL): string {
    $query = <<<QUERY
SELECT DISTINCT ?solution ?licence_label ?licence ?solution_label
WHERE {
  {
    {
      ?solution <http://www.w3.org/ns/dcat#distribution> ?distribution .
      ?distribution <http://purl.org/dc/terms/license> ?licence .
      FILTER NOT EXISTS { ?solution <http://purl.org/dc/terms/isVersionOf> ?version } .
    }
    UNION
    {
      ?solution <http://purl.org/dc/terms/hasVersion> ?release .
      ?release <http://www.w3.org/ns/dcat#distribution> ?distribution .
      ?distribution <http://purl.org/dc/terms/license> ?licence .
    }
  } .
  {
    ?solution a <http://www.w3.org/ns/dcat#Dataset> .
    ?solution <http://purl.org/dc/terms/title> ?solution_label .
    ?licence a <http://purl.org/dc/terms/LicenseDocument> .
    ?licence <http://www.w3.org/2000/01/rdf-schema#label> ?licence_label .
  } .
  @extra_condition
}
ORDER BY ASC(?licence_label) ASC(?solution_label)
LIMIT @limit
OFFSET {$offset}
QUERY;

    $extra_condition = $licence_id ? 'VALUES ?licence { ' . SparqlArg::uri($licence_id) . ' } ' : NULL;
    $search = ['@extra_condition', '@limit'];
    $replace = [$extra_condition, self::ITEMS_PER_PAGE];
    $query = str_replace($search, $replace, $query);
    return $query;
  }

}
