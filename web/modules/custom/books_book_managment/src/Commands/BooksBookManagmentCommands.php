<?php

namespace Drupal\books_book_managment\Commands;

use Drupal\books_book_managment\Services\BooksUtilsService;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class BooksBookManagmentCommands extends DrushCommands {

  /**
   * @var \Drupal\books_book_managment\Services\BooksUtilsService
   */
  private $booksUtilsService;

  /**
   * BooksBookManagmentCommands constructor.
   *
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   */
  public function __construct(BooksUtilsService $booksUtilsService) {
    parent::__construct();
    $this->booksUtilsService = $booksUtilsService;
  }

  /**
   * Command description here.
   *
   * @usage books_book_managment-commandName foo
   *   Usage description
   *
   * @command update-cover
   * @aliases buc
   */
  public function updateCover() {
    $books = $this->booksUtilsService->getBooksMissingCover();

    $operations = [];
    $numOperations = 0;
    $batchId = 1;
    if (!empty($books)) {
      $books_count = count($books);
      foreach ($books as $nid) {
        $operations[] = [
          '\Drupal\books_book_managment\Batches\MissingCoverBatch::missingCoverBatchProcess',
          [
            $nid,
            $books_count,
            t('Getting cover for @nid', ['@nid' => $nid]),
          ],
        ];
        $batchId++;
        $numOperations++;
      }
    }
    else {
      $this->logger->warning('No Books without Cover');
    }
    $batch = [
      'title' => t('Getting Covers for @num book(s)', ['@num' => $numOperations]),
      'operations' => $operations,
      'finished' => '\Drupal\books_book_managment\Batches\MissingCoverBatch::missingCoverBatchFinished',
    ];
    batch_set($batch);
    drush_backend_batch_process();
    $this->logger->info('Archive batch operations end.');

  }

}
