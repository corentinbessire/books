<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;


/**
 * OpenLibraryService service.
 */
class OpenLibraryService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Constructs an GoogleBooksService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->httpClient = $http_client;
    $this->logger = $loggerChannelFactory->get('OpenLibraryService');
  }

  /**
   * Method description.
   */
  public function getBookData($isbn) {
    $uri = 'https://openlibrary.org/api/books?jscmd=data&format=json&bibkeys=ISBN:' . $isbn;
    $request = $this->httpClient->request('GET', $uri);

    try {
      $request = $this->httpClient->request('GET', $uri);
      $request->getBody();
      $data = json_decode($request->getBody()->read(4096), TRUE);
    } catch (RequestException $e) {
      $this->logger->alert($e->getCode() . ' : ' . $e->getMessage());
    }
    if (!isset($data['ISBN:'. $isbn])) {
      $this->logger->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return [];
    }

    $data = $data['ISBN:'. $isbn];
    $book_data['title'] =  $data['title'];
    $book_data['field_pages'] =  $data['number_of_pages'];
    foreach ($data['authors'] as $author) {
      $book_data['field_authors'][] = $author['name'];
    }
    $data['publishers'] = reset($data['publishers']);
    $book_data['field_publisher'] = $data['publishers']['name'];
    $book_data['field_isbn'] =  $isbn;
    $book_data['field_release'] =  date('Y-m-d', strtotime($data['publish_date']));
    return $book_data;
  }

}
