<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom Service to handle Google Books API.
 */
class GoogleBooksService {

  /**
   * Constructor of the GoogleBooksService Service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Guzzle Cleint Service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Drupal Lofgger Channel Factory Service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupsl Setting Service.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly Settings $settings,
  ) {}

  /**
   * Get Data of a book on Google Book API.
   *
   * @param string|int $isbn
   *   ISBN of the book to get data of.
   *
   * @return array
   *   Data of the Book.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getBookData(string|int $isbn) {
    $googleApiKey = $this->settings->get('google_api_key');
    $uri = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn . '&key=' . $googleApiKey;
    $book_data = [];
    try {
      $request = $this->httpClient->request('GET', $uri);
      $data = json_decode($request->getBody()->read(99999), TRUE);
      if ($data['totalItems'] === 0) {
        $this->loggerChannelFactory->get('GoogleBooksService')
          ->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
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
    }
    catch (RequestException $e) {
      $this->loggerChannelFactory->get('GoogleBooksService')
        ->alert($e->getCode() . ' : ' . $e->getMessage());
    }
    return $book_data;
  }

}
