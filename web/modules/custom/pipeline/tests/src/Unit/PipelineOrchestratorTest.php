<?php

/**
 * @file
 * Tests the orchestrator.
 */

namespace Drupal\Tests\pipeline\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Messenger\Messenger;
use Drupal\pipeline\PipelineOrchestrator;
use Drupal\pipeline\PipelineState;
use Drupal\pipeline\PipelineStateManager;
use Drupal\pipeline\Plugin\PipelinePipelinePluginBase;
use Drupal\pipeline\Plugin\PipelinePipelinePluginManager;
use Drupal\pipeline\Plugin\PipelineStepPluginBase;
use Drupal\pipeline\Plugin\PipelineStepPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the data pipeline processor.
 *
 * @group pipeline
 * @coversDefaultClass \Drupal\pipeline\PipelineOrchestrator
 */
class PipelineOrchestratorTest extends UnitTestCase {

  /**
   * The data pipeline plugin manager.
   *
   * @var \Drupal\pipeline\Plugin\PipelinePipelinePluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $pipelinePluginManager;

  /**
   * The pipeline step plugin manager.
   *
   * @var \Drupal\pipeline\Plugin\PipelineStepPluginManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stepPluginManager;

  /**
   * The state manager.
   *
   * @var \Drupal\pipeline\PipelineStateManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $stateManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $formBuilder;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->pipelinePluginManager = $this->prophesize(PipelinePipelinePluginManager::class);
    $this->stepPluginManager = $this->prophesize(PipelineStepPluginManager::class);
    $this->stateManager = $this->prophesize(PipelineStateManager::class);
    $this->formBuilder = $this->prophesize(FormBuilder::class);
    $this->messenger = $this->prophesize(Messenger::class);
  }

  /**
   * Tests instantiating the orchestrator.
   *
   * @covers ::__construct
   */
  public function testInstance() {
    $this->assertInstanceOf(PipelineOrchestrator::class, $this->createOrchestrator());
  }

  /**
   * Test resetting the state machine.
   *
   * @covers ::reset
   */
  public function testReset() {
    $definition = ['label' => 'Bar', 'steps' => ['test_step']];
    $this->pipelinePluginManager->createInstance('demo_pipe')
      ->willReturn(new TestPipeline([], '', $definition, $this->stepPluginManager->reveal(), $this->stateManager->reveal()));
    $this->stateManager->reset()->shouldBeCalled();
    $this->createOrchestrator()->reset('demo_pipe');
  }

  /**
   * Test running the state machine: Build the form.
   *
   * @covers ::run
   */
  public function testRun() {
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('plugin.manager.pipeline_step', $this->stepPluginManager->reveal());
    \Drupal::setContainer($container);

    $state = new PipelineState('demo_pipe', 'test_step');
    $state_manager = $this->stateManager;
    $state_manager->isPersisted()->willReturn(TRUE);
    $state_manager->getState()->willReturn($state);
    $state_manager->reset()->shouldBeCalled();

    $this->stepPluginManager->hasDefinition('test_step')->willReturn(TRUE);
    $this->stepPluginManager->createInstance('test_step')->willReturn(new TestStep([], '', ['label' => 'Foo']));
    $definition = ['label' => 'Bar', 'steps' => ['test_step']];
    $this->pipelinePluginManager->createInstance('demo_pipe')
      ->willReturn(new TestPipeline([], '', $definition, $this->stepPluginManager->reveal(), $this->stateManager->reveal()));

    (new TestOrchestrator(
      $this->pipelinePluginManager->reveal(),
      $this->stateManager->reveal(),
      $this->formBuilder->reveal(),
      $this->messenger->reveal()
    ))->run('demo_pipe');
  }

  /**
   * Initializes a new orchestrator object.
   *
   * @return \Drupal\pipeline\PipelineOrchestratorInterface
   *   The new orchestrator object.
   */
  protected function createOrchestrator() {
    return new PipelineOrchestrator(
      $this->pipelinePluginManager->reveal(),
      $this->stateManager->reveal(),
      $this->formBuilder->reveal(),
      $this->messenger->reveal()
    );
  }

}

/**
 * Testing step plugin.
 */
class TestStep extends PipelineStepPluginBase {

  /**
   * {@inheritdoc}
   */
  public function execute(array &$data) {}

}

/**
 * Testing orchestrator.
 *
 * Used to replace the ::getStepInstance() method with a mock.
 */
class TestOrchestrator extends PipelineOrchestrator {

  /**
   * {@inheritdoc}
   */
  protected function getStepInstance(PipelineState $state) {
    return new TestStep([], '', ['label' => 'Foo']);
  }

}

/**
 * Testing pipeline plugin.
 */
class TestPipeline extends PipelinePipelinePluginBase {}
