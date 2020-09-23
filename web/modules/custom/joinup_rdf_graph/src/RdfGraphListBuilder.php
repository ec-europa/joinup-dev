<?php

declare(strict_types = 1);

namespace Drupal\joinup_rdf_graph;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;

/**
 * Provides a list builder for 'rdf_graph' RDF entities.
 */
class RdfGraphListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected $limit = 20;

  /**
   * {@inheritdoc}
   */
  public function getEntityIds(): array {
    return $this->getStorage()->getQuery()
      ->condition('rid', 'rdf_graph')
      ->pager($this->limit)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $rdf_graph): array {
    /** @var \Drupal\joinup_rdf_graph\Entity\RdfGraphInterface $rdf_graph */
    $row['label'] = [
      'data' => [
        '#type' => 'link',
        '#url' => $rdf_graph->toUrl(),
        '#title' => $rdf_graph->label(),
      ],
    ];
    $id_label = mb_strlen($rdf_graph->id()) > 60 ? substr($rdf_graph->id(), 0, 60) . '…' : $rdf_graph->id();
    $url = Url::fromUri(Settings::get('joinup')['sparql_public_endpoint'])
      ->setOption('query', [
        'query' => "SELECT ?s ?p ?o FROM <{$rdf_graph->id()}> WHERE { ?s ?p ?o . }",
        'format' => 'text/html',
        'timeout' => 0,
        'debug' => 'on',
      ]);

    $row['id'] = [
      'data' => [
        [
          '#type' => 'link',
          '#url' => Url::fromUri($rdf_graph->id()),
          '#title' => $id_label,
        ],
        [
          '#type' => 'link',
          '#url' => $url,
          '#title' => $this->t('Query endpoint'),
          '#prefix' => ' (',
          '#suffix' => ')',
        ],
      ],
    ];
    $row['operations']['data'] = $this->buildOperations($rdf_graph);

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'label' => $this->t('Title'),
      'id' => $this->t('Graph URI'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t("There are no RDF graph items yet.");
    return $build;
  }

}
