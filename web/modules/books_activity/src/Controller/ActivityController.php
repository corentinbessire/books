<?php

namespace Drupal\books_activity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Books - Activity routes.
 */
class ActivityController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Builds the response.
   */
  public function finish(NodeInterface $activity) {
    $this->updateActivity($activity, 'Finished');
    return $this->redirect('view.activities.page_1');
  }

  /**
   * Builds the response.
   */
  public function abandon(NodeInterface $activity) {
    $this->updateActivity($activity, 'Abandoned');
    return $this->redirect('view.activities.page_1');
  }

  /**
   * Update the given Activity to the Given Status and set EndDate to Now
   *
   * @param \Drupal\node\NodeInterface $activity
   * @param string $status
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateActivity(NodeInterface $activity, string $status): void {
    if ($activity->bundle() != 'activity') {
      \Drupal::messenger()
        ->addError($this->t('@label is not a valid activity.', ['@label' => $activity->label()]));
    }
    else {
      $activity->field_status = ['target_id' => $this->getStatusbyName($status)];
      $activity->set('field_end_date', date('Y-m-d'));
      $activity->save();
      \Drupal::messenger()
        ->addStatus($this->t('@label has been updated.', ['@label' => $activity->label()]));
    }
  }

  /**
   * Get the 'Status' Term Id by Name
   *
   * @param string $name
   *
   * @return int|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStatusbyName(string $name): ?int {
    $results = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('name', "%$name%", 'LIKE')
      ->condition('vid', 'sta')
      ->execute();
    return reset($results);
  }
}
