<?php

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\system\Kernel\Token\TokenReplaceKernelTestBase;

/**
 * Tests the tokens provided by the joinup_community_content module.
 */
class CustomTokensTest extends TokenReplaceKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'diff',
    'joinup_community_content',
    'og',
    'comment',
    'state_machine',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['node', 'diff']);
    $this->installSchema('node', 'node_access');

    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
      'new_revision' => TRUE,
    ]);
    $node_type->save();
    node_add_body_field($node_type);
  }

  /**
   * Creates a node, then tests the token replacement.
   */
  public function testTokenReplacement() {
    /* @var $node \Drupal\node\NodeInterface */
    $node = Node::create([
      'type' => 'article',
      'tnid' => 0,
      'title' => 'A very original title',
      'body' => [['value' => 'A more than original body.', 'format' => 'plain_text']],
    ]);
    $node->save();

    $input = '[node:diff-url-latest]';
    $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()]);
    // The token works only when two revisions are available.
    $this->assertEquals('[node:diff-url-latest]', $output);

    // Save the current revision id for later.
    $original_revision_id = $node->getRevisionId();
    // Create a new revision.
    $node->setNewRevision();
    $node->save();

    // Generate the expected url.
    $expected = Url::fromRoute('diff.revisions_diff', [
      'node' => $node->id(),
      'left_revision' => $original_revision_id,
      'right_revision' => $node->getRevisionId(),
      'filter' => \Drupal::service('plugin.manager.diff.layout')->getDefaultLayout(),
    ])->setAbsolute()->toString();

    $output = $this->tokenService->replace($input, ['node' => $node], ['langcode' => $this->interfaceLanguage->getId()]);
    $this->assertEquals($expected, $output);
  }

}
