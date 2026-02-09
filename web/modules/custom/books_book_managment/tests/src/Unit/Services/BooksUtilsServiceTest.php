<?php

namespace Drupal\Tests\books_book_managment\Unit\Services;

use Drupal\books_book_managment\Services\BooksUtilsService;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for BooksUtilsService.
 *
 * @group books_book_managment
 * @coversDefaultClass \Drupal\books_book_managment\Services\BooksUtilsService
 */
class BooksUtilsServiceTest extends UnitTestCase {

  /**
   * The logger factory mock.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The service under test.
   *
   * @var \Drupal\books_book_managment\Services\BooksUtilsService
   */
  protected $booksUtilsService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger = $this->createMock(LoggerChannelInterface::class);
    $this->loggerFactory->method('get')->willReturn($logger);

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $this->booksUtilsService = new BooksUtilsService(
      $this->loggerFactory,
      $this->entityTypeManager
    );
  }

  /**
   * Tests getBook() loads existing book by ISBN.
   *
   * @covers ::getBook
   */
  public function testGetBookExisting(): void {
    $isbn = '9780142437247';
    $node = $this->createMock(NodeInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['field_isbn' => $isbn])
      ->willReturn([$node]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getBook($isbn);
    $this->assertSame($node, $result);
  }

  /**
   * Tests getBook() creates new node when not found and create=TRUE.
   *
   * @covers ::getBook
   */
  public function testGetBookCreateNew(): void {
    $isbn = '9780000000000';
    $newNode = $this->createMock(NodeInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['field_isbn' => $isbn])
      ->willReturn([]);
    $storage->expects($this->once())
      ->method('create')
      ->with(['type' => 'book'])
      ->willReturn($newNode);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getBook($isbn, TRUE);
    $this->assertSame($newNode, $result);
  }

  /**
   * Tests getBook() returns NULL when not found and create=FALSE.
   *
   * @covers ::getBook
   */
  public function testGetBookNoCreate(): void {
    $isbn = '9780000000000';

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getBook($isbn, FALSE);
    $this->assertNull($result);
  }

  /**
   * Tests getTermByName() loads existing term.
   *
   * @covers ::getTermByName
   */
  public function testGetTermByNameExisting(): void {
    $term = $this->createMock(EntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn([5]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())->method('getQuery')->willReturn($query);
    $storage->expects($this->once())->method('load')->with(5)->willReturn($term);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getTermByName('Penguin', 'publisher');
    $this->assertSame($term, $result);
  }

  /**
   * Tests getTermByName() creates new term when not found.
   *
   * @covers ::getTermByName
   */
  public function testGetTermByNameCreatesNew(): void {
    $newTerm = $this->createMock(EntityInterface::class);

    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())->method('getQuery')->willReturn($query);
    $storage->expects($this->once())
      ->method('create')
      ->with(['vid' => 'publisher'])
      ->willReturn($newTerm);

    $newTerm->expects($this->once())->method('set')->with('name', 'New Publisher');
    $newTerm->expects($this->once())->method('save');

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getTermByName('New Publisher', 'publisher');
    $this->assertSame($newTerm, $result);
  }

  /**
   * Tests getTermByName() returns NULL for empty name.
   *
   * @covers ::getTermByName
   */
  public function testGetTermByNameEmptyReturnsNull(): void {
    $result = $this->booksUtilsService->getTermByName('', 'publisher');
    $this->assertNull($result);
  }

  /**
   * Tests getBooksMissingCover() returns node IDs.
   *
   * @covers ::getBooksMissingCover
   */
  public function testGetBooksMissingCover(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn([1, 2, 3]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())->method('getQuery')->willReturn($query);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getBooksMissingCover();
    $this->assertEquals([1, 2, 3], $result);
  }

  /**
   * Tests getBooksMissingCover() returns empty array.
   *
   * @covers ::getBooksMissingCover
   */
  public function testGetBooksMissingCoverEmpty(): void {
    $query = $this->createMock(QueryInterface::class);
    $query->method('condition')->willReturnSelf();
    $query->method('notExists')->willReturnSelf();
    $query->method('accessCheck')->willReturnSelf();
    $query->expects($this->once())->method('execute')->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())->method('getQuery')->willReturn($query);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $result = $this->booksUtilsService->getBooksMissingCover();
    $this->assertEmpty($result);
  }

}
