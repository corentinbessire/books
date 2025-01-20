<?php

namespace Drupal\Tests\books_book_managment\Unit\Services;

use Drupal\books_book_managment\Services\GoogleBooksService;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

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
   * The settings object.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

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

    // Create mocks.
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->logger = $this->createMock(LoggerChannelInterface::class);

    // Set up the Settings with test data.
    $settings = [
      'google_api_key' => 'AIzaSyD9AKjGv-hjic3m3LQgeUvOT5V-bKxKGyM',
    ];
    new Settings($settings);
    $this->settings = Settings::getInstance();

    // Set up logger factory mock.
    $this->loggerFactory->expects($this->any())
      ->method('get')
      ->with('GoogleBooksService')
      ->willReturn($this->logger);

    // Create the service with dependencies.
    $this->googleBooksService = new GoogleBooksService(
      $this->httpClient,
      $this->loggerFactory,
      $this->settings
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
            "title" => "Moby-Dick",
            "subtitle" => "or, The Whale",
            "authors" => [
              ["Herman Melville"],
            ],
            "publisher" => "National Geographic Books",
            "publishedDate" => "2002-12-31",
          ],
        ],
      ],
    ];

    $result = $this->googleBooksService->getBookData($isbn);

    $expected = $mockData['items'][0];
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
    $expected = [
      'totalItems' => 0,
      'items' => [],
    ];

    $this->logger->expects($this->once())
      ->method('alert');

    $result = $this->googleBooksService->getBookData($isbn);
    $this->assertEquals($expected, $result);
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

    $expected = [
      'title' => 'Moby-Dick',
      'field_pages' => 0,
      'field_authors' => ['Herman Melville'],
      'field_publisher' => 'National Geographic Books',
      'field_isbn' => $isbn,
      'field_release' => '2002-12-31',
    ];

    $result = $this->googleBooksService->getFormatedBookData($isbn);
    $this->assertIsArray($result);
    $this->assertEquals($expected, $result);

  }

  /**
   * Tests getFormatedBookData() with null response.
   *
   * @covers ::getFormatedBookData
   */
  public function testGetFormatedBookDataNull(): void {
    $isbn = '9780123456789';

    $result = $this->googleBooksService->getFormatedBookData($isbn);
    $this->assertNull($result);
  }

}
