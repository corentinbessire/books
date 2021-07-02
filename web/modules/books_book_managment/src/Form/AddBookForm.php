<?php

namespace Drupal\books_book_managment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\isbn\IsbnToolsService;

/**
 * Provides a Books - Book Managment form.
 */
class AddBookForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'books_book_managment_add_book';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['#attributes']['class'][] = 'w-full';
    $form['#attributes']['class'][] = 'max-w-lg';

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'flex',
          'flex-wrap',
          '-mx-3',
          'mb-6',
        ],
      ],
    ];

    $form['wrapper']['isbn'] = [
      '#type' => 'textfield',
      '#title' => $this->t('ISBN'),
      '#required' => TRUE,
    ];

    $form['wrapper']['actions'] = [
      '#type' => 'actions',
    ];
    $form['wrapper']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#attributes' => [
        'class' => [
          'shadow',
          'bg-purple-500',
          'hover:bg-purple-400',
          'focus:shadow-outline',
          'focus:outline-none',
          'text-white',
          'font-bold',
          'py-2',
          'px-4',
          'rounded',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /**
     * @var IsbnToolsService $isbnValidator
     */
    $isbnValidator = \Drupal::service('isbn.isbn_service');
    if (!$isbnValidator->isValidIsbn($form_state->getValue('isbn'))) {
      $form_state->setError($form['wrapper']['isbn'], 'This is not a valid ISBN number.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $isbn = $form_state->getValue('isbn');
    $ol_book_data = \Drupal::service('books.open_library')
      ->getBookData($isbn);
    $ol_book_data['field_release'] = NULL;
    $gl_book_data = \Drupal::service('books.google_books')
      ->getBookData($isbn);
    $gl_book_data['field_pages'] = NULL;
    $book_data = $this->mergeBookData($ol_book_data, $gl_book_data);
    dump($book_data);    die();

    if ($book_data) {
      $book = \Drupal::service('books.books_utils')
        ->saveBookData($isbn, $book_data);

      $this->messenger()->addStatus($this->t('Book has been created'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $book->id()]);
    }
    $this->messenger()->addStatus($this->t('Not Found'));

  }


  protected function mergeBookData(array $array1, array $array2): array {
    $keys = array_unique(array_merge(array_keys($array1), array_keys($array2)));
    $books_data = [];
    foreach ($keys as $key) {
      $books_data[$key] = $array1[$key] ?? $array2[$key];
    }
    return $books_data;
  }

}
