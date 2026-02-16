<?php

namespace Drupal\books_activity\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\isbn\IsbnToolsServiceInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Books - Activity routes.
 */
class ActivityController extends ControllerBase {

  /**
   * The controller constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messengerInterface
   *   Drupal Messagenger Service.
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   *   Custom Books Utilitary service.
   * @param \Drupal\isbn\IsbnToolsServiceInterface $isbnToolsService
   *   ISBN Tools service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The Current Request.
   */
  public function __construct(
    protected MessengerInterface $messengerInterface,
    protected BooksUtilsService $booksUtilsService,
    private IsbnToolsServiceInterface $isbnToolsService,
    protected Request $request,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('books.books_utils'),
      $container->get('isbn.isbn_service'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function new(string $isbn) {
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
      if (!$url = $this->request->headers->get('referer')) {
        $url = '<front>';
      }
      return $this->redirect($url);
    }
  }

  /**
   * {@inheritDoc}
   */
  public function finish(NodeInterface $activity) {
    $this->updateActivity($activity, 'Finished');
    return $this->redirect('view.activities.page_1');
  }

  /**
   * {@inheritDoc}
   */
  public function abandon(NodeInterface $activity) {
    $this->updateActivity($activity, 'Abandoned');
    return $this->redirect('view.activities.page_1');
  }

  /**
   * Update the given Activity to the Given Status and set EndDate to Now.
   *
   * @param \Drupal\node\NodeInterface $activity
   *   Activity node to update.
   * @param string $status
   *   Status to apply.
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
   * Get the 'Status' Term Id by Name.
   *
   * @param string $name
   *   Name of the Status.
   *
   * @return int|null
   *   Id of the Status Term.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getStatusByName(string $name): ?int {
    $results = $this->entityTypeManager->getStorage('taxonomy_term')->getQuery()
      ->condition('name', "%$name%", 'LIKE')
      ->condition('vid', 'sta')
      ->accessCheck()
      ->execute();
    return reset($results);
  }

}
