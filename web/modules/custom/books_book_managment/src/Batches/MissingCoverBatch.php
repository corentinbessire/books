<?php

namespace Drupal\books_book_managment\Batches;

/**
 *
 */
class MissingCoverBatch {

  /**
   *
   */
  public static function missingCoverBatchProcess($id, $total, $operation_details, &$context) {
    $number = count($context['results']);
    $context['results'][] = $id;
    // Optional message displayed under the progressbar.
    $context['message'] = t('Running Batch @number of @total (@details)',
      [
        '@number' => $number,
        '@total' => $total,
        '@details' => $operation_details,
      ]
    );

    $node = \Drupal::service('entity_type.manager')
      ->getStorage('node')
      ->load($id);
    $isbn = $node->field_isbn->value;

    $cover = \Drupal::service('books.cover_download')->downloadBookCover($isbn);
    if ($cover) {
      $node->set('field_cover', $cover);
      $node->save();
      $context['results']['success'][] = t('Cover for @nid added', ['@nid' => $id]);
    }
    else {
      $context['results']['failure'][] = t('Cover for @nid not found', ['@nid' => $id]);

    }
  }

  /**
   *
   */
  public static function missingCoverBatchFinished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();
    if ($success) {
      $failure = count($results['failure']);
      $success = count($results['success']);
      $total = $failure + $success;
      $messenger->addMessage(t('@count results processed.', ['@count' => count($total)]));
      $messenger->addMessage(t('@count covers found.', ['@count' => count($success)]));
      $messenger->addMessage(t('@count covers not found.', ['@count' => count($failure)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $messenger->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
