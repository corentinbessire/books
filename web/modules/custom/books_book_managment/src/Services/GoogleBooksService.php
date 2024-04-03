<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * GoogleBooksService service.
 */
class GoogleBooksService {

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
  private Settings $settings;

  /**
   * Constructs an GoogleBooksService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory, Settings $settings) {
    $this->httpClient = $http_client;
    $this->logger = $loggerChannelFactory->get('GoogleBooksService');
    $this->settings = $settings;
  }

  /**
   * Method description.
   */
  public function getBookData($isbn) {
    $googleApiKey = $this->settings->get('google_api_key');
    $uri = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn . '&key=' . $googleApiKey;
    try {
      $request = $this->httpClient->request('GET', $uri);
      $data = json_decode($request->getBody()->read(99999), TRUE);
    }
    catch (RequestException $e) {
      $this->logger->alert($e->getCode() . ' : ' . $e->getMessage());
    }

    if ($data['totalItems'] === 0) {
      $this->logger->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return [];
    }
    $data = array_pop($data['items']);
    $release = date('Y-m-d', strtotime($data['volumeInfo']['publishedDate']));
    $book_data['title'] = $data['volumeInfo']['title'];
    $book_data['field_pages'] = $data['volumeInfo']['pageCount'];
    $book_data['field_authors'] = $data['volumeInfo']['authors'];
    $book_data['field_publisher'] = $data['volumeInfo']['publisher'];
    $book_data['field_excerpt'] = $data['volumeInfo']['description'];
    $book_data['field_isbn'] = $isbn;
    $book_data['field_release'] = $release;

    return $book_data;
  }

}
