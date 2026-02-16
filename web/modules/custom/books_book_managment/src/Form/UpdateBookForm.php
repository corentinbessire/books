<?php

namespace Drupal\books_book_managment\Form;

use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\books_book_managment\Services\CoverDownloadService;
use Drupal\books_book_managment\Services\GoogleBooksService;
use Drupal\books_book_managment\Services\OpenLibraryService;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to batch-update book data from external APIs.
 */
class UpdateBookForm extends FormBase {

  /**
   * Constructs an UpdateBookForm object.
   *
   * @param \Drupal\books_book_managment\Services\OpenLibraryService $openLibraryService
   *   Open Library Service.
   * @param \Drupal\books_book_managment\Services\GoogleBooksService $googleBooksService
   *   Google Books Service.
   * @param \Drupal\books_book_managment\Services\CoverDownloadService $coverDownloadService
   *   Cover Downloader Service.
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   *   Book Utilities Service.
   */
  public function __construct(
    protected OpenLibraryService $openLibraryService,
    protected GoogleBooksService $googleBooksService,
    protected CoverDownloadService $coverDownloadService,
    protected BooksUtilsService $booksUtilsService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('books.open_library'),
      $container->get('books.google_books'),
      $container->get('books.cover_download'),
      $container->get('books.books_utils'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_book_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#markup' => '<p>' . $this->t('This will re-fetch book data from Google Books and Open Library for all books, and update missing fields.') . '</p>',
    ];

    $form['update_covers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Update missing covers'),
      '#default_value' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update all books'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nids = $this->booksUtilsService->getBooksMissingCover();
    $update_covers = $form_state->getValue('update_covers');

    if (empty($nids)) {
      $this->messenger()->addStatus($this->t('No books need updating.'));
      return;
    }

    $operations = [];
    foreach ($nids as $nid) {
      $operations[] = [
        [static::class, 'updateBookProcess'],
        [$nid, $update_covers],
      ];
    }

    $batch = [
      'title' => $this->t('Updating books'),
      'operations' => $operations,
      'finished' => [static::class, 'updateBookFinished'],
    ];
    batch_set($batch);
  }

  /**
   * Batch process callback to update a single book.
   *
   * @param int $nid
   *   The node ID to process.
   * @param bool $update_covers
   *   Whether to update covers.
   * @param array $context
   *   The batch context.
   */
  public static function updateBookProcess(int $nid, bool $update_covers, array &$context) {
    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    if (!$node) {
      $context['results']['failure'][] = $nid;
      return;
    }

    $isbn = $node->get('field_isbn')->value;
    if (!$isbn) {
      $context['results']['failure'][] = $nid;
      return;
    }

    if ($update_covers) {
      $cover = \Drupal::service('books.cover_download')->downloadBookCover($isbn);
      if ($cover) {
        $node->set('field_cover', $cover);
        $node->save();
        $context['results']['success'][] = $nid;
        return;
      }
    }

    $context['results']['failure'][] = $nid;
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The batch results.
   * @param array $operations
   *   Remaining operations.
   */
  public static function updateBookFinished(bool $success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $successCount = isset($results['success']) ? count($results['success']) : 0;
      $failureCount = isset($results['failure']) ? count($results['failure']) : 0;
      $messenger->addMessage(t('@success books updated, @failure failed.', [
        '@success' => $successCount,
        '@failure' => $failureCount,
      ]));
    }
    else {
      $messenger->addError(t('An error occurred during the update process.'));
    }
  }

}
