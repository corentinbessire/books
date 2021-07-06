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
    return 'add_book_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {



    $form['wrapper'] = [
      '#type' => 'container',
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
    $gb_book_data = \Drupal::service('books.google_books')
      ->getBookData($isbn);


    $book_data = $this->mergeBookData($ol_book_data, $gb_book_data);

    if ($book_data) {
      $cover = \Drupal::service('books.cover_download')->downloadBookCover($isbn);
      if ($cover) {
        $book_data['field_cover'] = $cover;
      }
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
