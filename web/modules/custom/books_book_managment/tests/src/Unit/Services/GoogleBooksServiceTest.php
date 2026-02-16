<?php

namespace Drupal\Tests\books_book_managment\Unit\Services;

use Drupal\books_book_managment\Services\GoogleBooksService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Unit tests for GoogleBooksService.
 *
 * @group books_book_managment
 * @coversDefaultClass \Drupal\books_book_managment\Services\GoogleBooksService
 */
class GoogleBooksServiceTest extends UnitTestCase {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $loggerFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\books_book_managment\Services\GoogleBooksService
   */
  protected $googleBooksService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    new Settings(['google_api_key' => 'test-api-key']);

    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('GoogleBooksService')
      ->willReturn($this->logger);

    $this->googleBooksService = new GoogleBooksService(
      $this->httpClient,
      $this->loggerFactory,
      Settings::getInstance()
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
      'totalItems' => 1,
      'items' => [
        [
          'volumeInfo' => [
            'title' => 'Moby-Dick',
            'authors' => ['Herman Melville'],
            'publisher' => 'Penguin Books',
            'publishedDate' => '2003-02-01',
            'pageCount' => 654,
          ],
        ],
      ],
    ];

    $response = new Response(200, [], json_encode($mockData));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        $this->stringContains('isbn:' . $isbn)
      )
      ->willReturn($response);

    $result = $this->googleBooksService->getBookData($isbn);

    $this->assertEquals($mockData['items'][0], $result);
  }

  /**
   * Tests getBookData() with API error (RequestException).
   *
   * @covers ::getBookData
   */
  public function testGetBookDataError(): void {
    $isbn = '9780123456789';

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(
        new RequestException('Server error', new Request('GET', 'test'))
      );

    $this->logger->expects($this->atLeastOnce())
      ->method('alert');

    $result = $this->googleBooksService->getBookData($isbn);
    $this->assertNull($result);
  }

  /**
   * Tests getBookData() with no results.
   *
   * @covers ::getBookData
   */
  public function testGetBookDataNoResults(): void {
    $isbn = '9780123456789';
    $mockData = [
      'totalItems' => 0,
      'items' => [],
    ];

    $response = new Response(200, [], json_encode($mockData));

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $this->logger->expects($this->once())
      ->method('alert');

    $result = $this->googleBooksService->getBookData($isbn);
    $this->assertNull($result);
  }

  /**
   * Tests formatBookData().
   *
   * @covers ::formatBookData
   */
  public function testFormatBookData(): void {
    $isbn = '9780123456789';
    $bookData = [
      'volumeInfo' => [
        'title' => 'Test Book',
        'pageCount' => 200,
        'authors' => ['John Doe', 'Jane Smith'],
        'publisher' => 'Test Publisher',
        'description' => 'A test book description',
        'publishedDate' => '2023-01-01',
        'industryIdentifiers' => [
          [
            'type' => 'ISBN_10',
            'identifier' => '0123456789',
          ],
          [
            'type' => 'ISBN_13',
            'identifier' => $isbn,
          ],
        ],
      ],
    ];

    $expected = [
      'title' => 'Test Book',
      'field_pages' => 200,
      'field_authors' => ['John Doe', 'Jane Smith'],
      'field_publisher' => 'Test Publisher',
      'field_excerpt' => 'A test book description',
      'field_isbn' => $isbn,
      'field_release' => '2023-01-01',
    ];

    $result = $this->googleBooksService->formatBookData($bookData);
    $this->assertEquals($expected, $result);
  }

  /**
   * Tests getFormatedBookData() with successful response.
   *
   * @covers ::getFormatedBookData
   */
  public function testGetFormatedBookDataSuccess(): void {
    $isbn = '9780142437247';
    $mockData = [
      'totalItems' => 1,
      'items' => [
        [
          'volumeInfo' => [
            'title' => 'Moby-Dick',
            'authors' => ['Herman Melville'],
            'publisher' => 'National Geographic Books',
            'publishedDate' => '2002-12-31',
            'pageCount' => 654,
            'description' => 'A whale tale',
            'industryIdentifiers' => [
              [
                'type' => 'ISBN_13',
                'identifier' => $isbn,
              ],
            ],
          ],
        ],
      ],
    ];

    $response = new Response(200, [], json_encode($mockData));

    // getFormatedBookData calls getBookData twice (known bug in source).
    $this->httpClient->expects($this->exactly(2))
      ->method('request')
      ->willReturn($response);

    $result = $this->googleBooksService->getFormatedBookData($isbn);
    $this->assertIsArray($result);
    $this->assertEquals('Moby-Dick', $result['title']);
    $this->assertEquals($isbn, $result['field_isbn']);
  }

  /**
   * Tests getFormatedBookData() with null response.
   *
   * @covers ::getFormatedBookData
   */
  public function testGetFormatedBookDataNull(): void {
    $isbn = '9780123456789';

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(
        new RequestException('Not found', new Request('GET', 'test'))
      );

    $result = $this->googleBooksService->getFormatedBookData($isbn);
    $this->assertNull($result);
  }

}
