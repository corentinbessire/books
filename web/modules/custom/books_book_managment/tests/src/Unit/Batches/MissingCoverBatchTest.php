<?php

namespace Drupal\Tests\books_book_managment\Unit\Batches;

use Drupal\books_book_managment\Batches\MissingCoverBatch;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())
      ->method('addMessage')
      ->with($this->isInstanceOf(TranslatableMarkup::class));

    $stringTranslation = $this->createMock(TranslationInterface::class);
    $stringTranslation->method('translateString')->willReturnArgument(0);

    $container = $this->createMock(ContainerInterface::class);
    $container->method('get')
      ->willReturnMap([
        ['messenger', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $messenger],
        ['string_translation', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $stringTranslation],
      ]);
    \Drupal::setContainer($container);

    $operations = [
      ['processItem', [1]],
    ];

    MissingCoverBatch::missingCoverBatchFinished(FALSE, [], $operations);
  }

}
