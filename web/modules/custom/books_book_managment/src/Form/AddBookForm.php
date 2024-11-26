<?php

namespace Drupal\books_book_managment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\books_book_managment\Services\CoverDownloadService;
use Drupal\books_book_managment\Services\GoogleBooksService;
use Drupal\books_book_managment\Services\OpenLibraryService;
use Drupal\isbn\IsbnToolsServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Custom Form to add book by ISBN.
 */
class AddBookForm extends FormBase {

  /**
   * Constructs a AddBookForm object.
   *
   * @param \Drupal\isbn\IsbnToolsServiceInterface $isbnToolsService
   *   ISBN Tools Service.
   * @param \Drupal\books_book_managment\Services\OpenLibraryService $openLibraryService
   *   Open Library Service.
   * @param \Drupal\books_book_managment\Services\GoogleBooksService $googleBooksService
   *   Google Book Service.
   * @param \Drupal\books_book_managment\Services\CoverDownloadService $coverDownloadService
   *   Cover Downloader Service.
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   *   Book Utilities Serice.
   */
  public function __construct(
    protected IsbnToolsServiceInterface $isbnToolsService,
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
      $container->get('isbn.isbn_service'),
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
    return 'add_book_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['isbn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add book'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /**
     * @var \Drupal\isbn\IsbnToolsService $isbnValidator
     */
    $isbnValidator = $this->isbnToolsService;
    if (!$isbnValidator->isValidIsbn($form_state->getValue('isbn'))) {
      $form_state->setError($form['wrapper']['isbn'], 'This is not a valid ISBN number.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $isbn = $form_state->getValue('isbn');
    $ol_book_data = $this->openLibraryService
      ->getBookData($isbn);

    $gb_book_data = $this->googleBooksService
      ->getBookData($isbn);

    $book_data = $this->mergeBookData($gb_book_data, $ol_book_data);

    if ($book_data) {
      $cover = $this->coverDownloadService
        ->downloadBookCover($isbn);
      if ($cover) {
        $book_data['field_cover'] = $cover;
      }
      $book = $this->booksUtilsService
        ->saveBookData($isbn, $book_data);

      $this->messenger()->addStatus($this->t('Book has been created'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $book->id()]);
    }
    $this->messenger()->addStatus($this->t('Not Found'));
  }

  /**
   * Merge Book Data of multiple Source.
   *
   * @param array $array1
   *   Data From Source 1.
   * @param array $array2
   *   Data From Source 2.
   *
   * @return array
   *   merged Data.
   */
  protected function mergeBookData(array $array1, array $array2): array {
    $keys = array_unique(array_merge(array_keys($array1), array_keys($array2)));
    $books_data = [];
    foreach ($keys as $key) {
      $books_data[$key] = $array1[$key] ?? $array2[$key];
    }
    return $books_data;
  }

}
