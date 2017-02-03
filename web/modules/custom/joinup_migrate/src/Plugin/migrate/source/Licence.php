<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

/**
 * Migrates licences.
 *
 * @MigrateSource(
 *   id = "licence"
 * )
 */
class Licence extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'n',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'title' => $this->t('Name'),
      'body' => $this->t('Description'),
      'type' => $this->t('Type'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Build a list of licences used effectively by distributions.
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', 'nar')
      ->distinct()
      ->fields('nl', ['nid'])
      // @todo Limit distributions to those linked in interoperability solutions
      //   but expand this filter if there's other conclusion in ISAICP-2840,
      //   comment 2003714.
      // @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-2840?focusedCommentId=2003714&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-2003714
      ->condition('nar.type', 'asset_release');

    $query->join('content_field_asset_distribution', 'cad', 'nar.vid = cad.vid');
    $query->join('node', 'nd', 'cad.field_asset_distribution_nid = nd.nid');
    $query->join("{$this->getDestinationDbName()}.joinup_migrate_mapping", 'm', "nar.nid = m.nid AND m.type = 'asset_release' AND m.migrate = 1");
    $query->join('content_field_distribution_licence', 'cl', 'nd.vid = cl.vid');
    $query->join('node', 'nl', "cl.field_distribution_licence_nid = nl.nid AND nl.type = 'licence'");

    $allowed_licences = $query->execute()->fetchCol();

    $this->alias['node'] = 'n';

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->select('node', $this->alias['node'])
      ->fields($this->alias['node'], ['nid', 'title'])
      ->condition("{$this->alias['node']}.type", 'licence');

    if ($allowed_licences) {
      $query->condition("{$this->alias['node']}.nid", $allowed_licences, 'IN');
    }
    else {
      $query->condition(1, 2);
    }

    $this->alias['node_revision'] = $query->join('node_revisions', 'node_revision', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['term_node'] = $query->leftJoin('term_node', 'term_node', "{$this->alias['node']}.vid = %alias.vid");
    // The licence type vocabulary ID is 75.
    $this->alias['term_data'] = $query->leftJoin('term_data', 'term_data', "{$this->alias['term_node']}.tid = %alias.tid AND %alias.vid = 75");

    $query->addField($this->alias['node_revision'], 'body');
    $query->addExpression("{$this->alias['term_data']}.name", 'type');

    return $query
      // Assure the URI field.
      ->addTag('uri');
  }

}
