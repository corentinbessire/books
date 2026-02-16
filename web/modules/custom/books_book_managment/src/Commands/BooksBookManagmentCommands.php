<?php

namespace Drupal\books_book_managment\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
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

  use StringTranslationTrait;

  /**
   * BooksBookManagmentCommands constructor.
   *
   * @param \Drupal\books_book_managment\Services\BooksUtilsService $booksUtilsService
   *   Utils for Book management service.
   */
  public function __construct(
    private BooksUtilsService $booksUtilsService,
  ) {
    parent::__construct();
  }

  /**
   * Download missing book covers from external sources.
   *
   * @usage update-cover
   *   Download covers for all books missing one.
   *
   * @command update-cover
   * @aliases buc
   */
  public function updateCover(): void {
    $books = $this->booksUtilsService->getBooksMissingCover();

    if (empty($books)) {
      $this->logger()->warning('No books without cover.');
      return;
    }

    $operations = [];
    $books_count = count($books);
    foreach ($books as $nid) {
      $operations[] = [
        '\Drupal\books_book_managment\Batches\MissingCoverBatch::missingCoverBatchProcess',
        [
          $nid,
          $books_count,
          $this->t('Getting cover for @nid', ['@nid' => $nid]),
        ],
      ];
    }

    $batch = [
      'title' => $this->t('Getting Covers for @num book(s)', ['@num' => count($operations)]),
      'operations' => $operations,
      'finished' => '\Drupal\books_book_managment\Batches\MissingCoverBatch::missingCoverBatchFinished',
    ];
    batch_set($batch);
    drush_backend_batch_process();
    $this->logger()->info('Cover batch operations completed.');
  }

}
