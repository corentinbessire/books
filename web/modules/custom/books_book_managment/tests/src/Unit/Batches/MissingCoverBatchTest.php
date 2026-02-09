<?php

namespace Drupal\Tests\books_book_managment\Unit\Batches;

use Drupal\books_book_managment\Batches\MissingCoverBatch;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for MissingCoverBatch.
 *
 * @group books_book_managment
 * @coversDefaultClass \Drupal\books_book_managment\Batches\MissingCoverBatch
 */
class MissingCoverBatchTest extends UnitTestCase {

  /**
   * Tests missingCoverBatchFinished() with failed batch.
   *
   * @covers ::missingCoverBatchFinished
   */
  public function testBatchFinishedFailure(): void {
    $messenger = $this->createMock(\Drupal\Core\Messenger\MessengerInterface::class);
    $messenger->expects($this->once())
      ->method('addMessage')
      ->with($this->stringContains('An error occurred'));

    $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
    $container->method('get')
      ->with('messenger')
      ->willReturn($messenger);
    \Drupal::setContainer($container);

    $operations = [
      ['processItem', [1]],
    ];

    MissingCoverBatch::missingCoverBatchFinished(FALSE, [], $operations);
  }

}
