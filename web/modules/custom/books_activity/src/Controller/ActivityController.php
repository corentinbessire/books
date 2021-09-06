<?php

namespace Drupal\books_activity\Controller;

use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\isbn\IsbnToolsService;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Returns responses for Books - Activity routes.
 */
class ActivityController extends ControllerBase {

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messengerInterface;

  /**
   * @var \Drupal\books_book_managment\Services\BooksUtilsService
   */
  protected $booksUtilsService;

  /**
   * @var \Drupal\isbn\IsbnToolsService
   */
  private $isbnToolsService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Messenger\MessengerInterface $messengerInterface
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    MessengerInterface         $messengerInterface,
    BooksUtilsService          $booksUtilsService,
    IsbnToolsService           $isbnToolsService
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->messengerInterface = $messengerInterface;
    $this->booksUtilsService = $booksUtilsService;
    $this->isbnToolsService = $isbnToolsService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger'),
      $container->get('books.books_utils'),
      $container->get('isbn.isbn_service'),
    );
  }

  public function new(string $isbn) {
    return ['#markup' => $isbn];
    /*
    if ($this->isbnToolsService->isValidIsbn($isbn)) {

      if ($book = $this->booksUtilsService->getBook($isbn)) {
        $values = [
          'type' => 'activity',
          'title' => $book->title->value,
          'field_start_date' => date('Y-m-d'),
          'field_book' => ['target_id' => $book->id()],
          'field_status' => ['target_id' => $this->getStatusByName('Reading')],
        ];
        $activity = $this->entityTypeManager->getStorage('node')
          ->create($values);
        $activity->save();
        $this->messengerInterface
          ->addStatus($this->t('You have started reading @title on the @date', [
            '@title' => $activity->label(),
            '@date' => $activity->field_start_date->value,
          ]));
        return $this->redirect('view.activities.page_1');
      }
    }
    else {
      $this->messengerInterface
        ->addError($this->t('@isbn is not a valid ISBN number.', ['@isbn' => $isbn]));
      if (!$url = \Drupal::request()->headers->get('referer')) {
        $url = '<front>';
      }
      return $this->redirect($url);
    }
    */
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
      $this->messengerInterface
        ->addError($this->t('@label is not a valid activity.', ['@label' => $activity->label()]));
    }
    else {
      $activity->field_status = ['target_id' => $this->getStatusByName($status)];
      $activity->set('field_end_date', date('Y-m-d'));
      $activity->save();
      $this->messengerInterface
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
  protected function getStatusByName(string $name): ?int {
    $results = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('name', "%$name%", 'LIKE')
      ->condition('vid', 'sta')
      ->execute();
    return reset($results);
  }

}
