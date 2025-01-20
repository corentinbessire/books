<?php

namespace Drupal\Tests\books_book_managment\Unit\Services;

use Drupal\books_book_managment\Services\OpenLibraryService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

/**
 * Unit tests for OpenLibraryService.
 *
 * @group books_book_managment
 * @coversDefaultClass \Drupal\books_book_managment\Services\OpenLibraryService
 */
class OpenLibraryServiceTest extends UnitTestCase {

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The mocked logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\books_book_managment\Services\OpenLibraryService
   */
  protected $openLibraryService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create mocks.
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Set up logger factory mock.
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('OpenLibraryService')
      ->willReturn($this->logger);

    // Create the service with mocked dependencies.
    $this->openLibraryService = new OpenLibraryService(
      $this->httpClient,
      $this->loggerFactory
    );
  }

  /**
   * Tests getBookData() with successful API response.
   *
   * @covers ::getBookData
   */
  public function testGetBookDataSuccess(): void {
    $isbn = '9780142437247';
    $mockData = [
      'ISBN:' . $isbn => [
        'title' => "Moby-Dick, or, The whale",
        'number_of_pages' => 654,
        'authors' => [
          ['name' => "Herman Melville"],
        ],
        'publishers' => [
          ['name' => 	"Penguin Books"],
        ],
        'publish_date' => "2003",
      ],
    ];

    $response = new Response(200, [], json_encode($mockData));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        'https://openlibrary.org/api/books?jscmd=data&format=json&bibkeys=ISBN:' . $isbn
      )
      ->willReturn($response);

    $result = $this->openLibraryService->getBookData($isbn);

    $expected = $mockData['ISBN:' . $isbn];
    $expected['isbn'] = $isbn;

    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getBookData() with API error.
   *
   * @covers ::getBookData
   */
  public function testGetBookDataError(): void {
    $isbn = '9780123456789';

    $this->logger->expects($this->once())
      ->method('alert');

    $result = $this->openLibraryService->getBookData($isbn);
    $this->assertNull($result);
  }

  /**
   * Tests formatBookData().
   *
   * @covers ::formatBookData
   */
  public function testFormatBookData(): void {
    $bookData = [
      'title' => 'Test Book',
      'number_of_pages' => 200,
      'authors' => [
        ['name' => 'John Doe'],
        ['name' => 'Jane Smith'],
      ],
      'publishers' => [
        ['name' => 'Test Publisher'],
      ],
      'publish_date' => '2023-01-01',
      'isbn' => '9780123456789',
    ];

    $expected = [
      'title' => 'Test Book',
      'field_pages' => 200,
      'field_authors' => ['John Doe', 'Jane Smith'],
      'field_publisher' =>'Test Publisher',
      'field_isbn' => '9780123456789',
      'field_release' => '2023-01-01',
    ];

    $result = $this->openLibraryService->formatBookData($bookData);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getFormatedBookData() with successful response.
   *
   * @covers ::getFormatedBookData
   */
  public function testGetFormatedBookDataSuccess(): void {
    $isbn = '9780123456789';
    $mockData = [
      'title' => 'Test Book',
      'number_of_pages' => 200,
      'authors' => [
        ['name' => 'John Doe'],
      ],
      'publishers' => [
        ['name' => 'Test Publisher'],
      ],
      'publish_date' => '2023-01-01',
      'isbn' => $isbn,
    ];

    // Mock getBookData() to return test data
    $this->openLibraryService = $this->getMockBuilder(OpenLibraryService::class)
      ->onlyMethods(['getBookData'])
      ->setConstructorArgs([$this->httpClient, $this->loggerFactory])
      ->getMock();

    $this->openLibraryService->expects($this->once())
      ->method('getBookData')
      ->with($isbn)
      ->willReturn($mockData);

    $result = $this->openLibraryService->getFormatedBookData($isbn);

    $this->assertIsArray($result);
    $this->assertArrayHasKey('title', $result);
    $this->assertArrayHasKey('field_isbn', $result);
  }

  /**
   * Tests getFormatedBookData() with null response.
   *
   * @covers ::getFormatedBookData
   */
  public function testGetFormatedBookDataNull(): void {
    $isbn = '9780123456789';

    // Mock getBookData() to return null
    $this->openLibraryService = $this->getMockBuilder(OpenLibraryService::class)
      ->onlyMethods(['getBookData'])
      ->setConstructorArgs([$this->httpClient, $this->loggerFactory])
      ->getMock();

    $this->openLibraryService->expects($this->once())
      ->method('getBookData')
      ->with($isbn)
      ->willReturn(NULL);

    $result = $this->openLibraryService->getFormatedBookData($isbn);
    $this->assertNull($result);
  }

}
