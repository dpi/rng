<?php

namespace Drupal\Tests\rng\Kernel;

use Drupal\courier\Entity\TemplateCollection;
use Drupal\rng\Entity\Registration;
use Drupal\rng\Entity\Rule;
use Drupal\rng\Entity\RuleComponent;
use Drupal\simpletest\UserCreationTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests RNG message rules are executed.
 *
 * @group rng
 */
class RngMessageRules extends RngKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'entity_test', 'user', 'filter'];

  /**
   * @var \Drupal\rng\EventMetaInterface
   */
  protected $eventMeta;

  /**
   * @var \Drupal\rng\RuleInterface
   */
  protected $rule;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('registration');
    $this->installEntitySchema('registrant');
    $this->installEntitySchema('rng_rule');
    $this->installEntitySchema('rng_rule_component');
    $this->installEntitySchema('courier_template_collection');
    $this->installEntitySchema('courier_message_queue_item');
    $this->installEntitySchema('courier_email');
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig('courier');

    // Test trait needs.
    $this->registrationType = $this->createRegistrationType();
    $this->eventType = $this->createEventType('entity_test', 'entity_test');

    $this->eventMeta = $this->createEvent();
    $event = $this->eventMeta->getEvent();

    $this->rule = $this->createMessageRule($event);
  }

  /**
   * Create a rule and associated components
   *
   * @param $event
   *   An event entity.
   *
   * @return \Drupal\rng\RuleInterface
   *   An unsaved rule entity.
   */
  protected function createMessageRule($event) {
    /** @var \Drupal\courier\Service\CourierManagerInterface $courier_manager */
    $courier_manager = $this->container->get('courier.manager');
    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = $this->container->get('plugin.manager.action');

    $template_collection = TemplateCollection::create();
    $template_collection->save();
    $courier_manager->addTemplates($template_collection);
    $template_collection->save();

    $templates = $template_collection->getTemplates();
    /** @var \Drupal\courier\EmailInterface $courier_email */
    $courier_email = $templates[0];
    $courier_email->setSubject($this->randomMachineName());
    $courier_email->setBody('Greetings, [identity:label]');
    $courier_email->save();

    $rule = Rule::create([
      'event' => ['entity' => $event],
    ]);
    $rule->setIsActive(TRUE);

    $actionPlugin = $action_manager->createInstance('rng_courier_message');
    $configuration = $actionPlugin->getConfiguration();
    $configuration['template_collection'] = $template_collection->id();
    $action = RuleComponent::create([])
      ->setPluginId($actionPlugin->getPluginId())
      ->setConfiguration($configuration)
      ->setType('action');
    $rule->addComponent($action);

    return $rule;
  }

  /**
   * Test messages are sent on registration creation.
   */
  public function testRngRegistrationCreateMessages() {
    $this->rule->set('trigger_id', 'entity:registration:new');
    $this->rule->save();

    /** @var \Drupal\rng\RngEntityModelInterface $rngEntityModel */
    $rngEntityModel = $this->container->get('rng.entity.model');

    $this->assertEquals(0, count($rngEntityModel->getOperationRecords()), 'There are zero entity operations recorded.');
    $this->assertEquals(0, $this->countMessagesInQueue(), 'There are zero messages in queue.');

    $event = $this->eventMeta->getEvent();

    $identity = $this->createUser();
    $registration = Registration::create(['type' => $this->registrationType->id()]);
    $registration
      ->setEvent($event)
      ->addIdentity($identity)
      ->save();

    $this->terminateRequest();
    $this->assertEquals(1, count($rngEntityModel->getOperationRecords()), 'There is one entity operation recorded.');
    $this->assertEquals(1, $this->countMessagesInQueue(), 'There is one message in queue.');
  }

  /**
   * Test messages are sent on registration update.
   */
  public function testRngRegistrationUpdateMessages() {
    $this->rule->set('trigger_id', 'entity:registration:update');
    $this->rule->save();

    /** @var \Drupal\rng\RngEntityModelInterface $rngEntityModel */
    $rngEntityModel = $this->container->get('rng.entity.model');

    $event = $this->eventMeta->getEvent();

    $this->assertEquals(0, count($rngEntityModel->getOperationRecords()), 'There are zero entity operations recorded.');

    $identity = $this->createUser();
    $registration = Registration::create(['type' => $this->registrationType->id()]);
    $registration
      ->setEvent($event)
      ->addIdentity($identity)
      ->save();

    $this->terminateRequest();
    $this->assertEquals(1, count($rngEntityModel->getOperationRecords()), 'There is one entity operation recorded.');
    $this->assertEquals(0, $this->countMessagesInQueue(), 'There are zero messages in queue.');

    $registration->save();

    $this->terminateRequest();
    // Two operations, insert and update.
    $this->assertEquals(2, count($rngEntityModel->getOperationRecords()), 'There is two entity operations recorded.');
    $this->assertEquals(1, $this->countMessagesInQueue(), 'There is one message in queue.');
  }

  /**
   * Simulate request termination.
   */
  protected function terminateRequest() {
    $request = $this->container->get('request_stack')->getCurrentRequest();
    $kernel = $this->container->get('kernel');
    $response = new Response();
    $kernel->terminate($request, $response);
  }

  /**
   * Count number of messages in Courier queue.
   *
   * @return integer
   */
  protected function countMessagesInQueue() {
    return \Drupal::entityTypeManager()
      ->getStorage('courier_message_queue_item')
      ->getQuery()
      ->count()
      ->execute();
  }

}
