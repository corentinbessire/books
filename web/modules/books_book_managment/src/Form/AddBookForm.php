<?php

namespace Drupal\books_book_managment\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $nid = \Drupal::service('books.google_books')
      ->getBookData($form_state->getValue('isbn'));
    if ($nid) {
      $this->messenger()->addStatus($this->t('Book has been created'));
      $form_state->setRedirect('entity.node.canonical', ['node' => $nid]);
    }
    $this->messenger()->addStatus($this->t('Not Found'));

  }

}
