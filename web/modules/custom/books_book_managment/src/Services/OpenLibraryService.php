<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom Service to handle OpenLibrary API.
 */
class OpenLibraryService {

  /**
   * Constructs an GoogleBooksService object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Guzzle Client Service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Drupal Logger Channel Factory Service.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    protected LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {}

  /**
   * Get Data of a book on Open Library API.
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
    $uri = 'https://openlibrary.org/api/books?jscmd=data&format=json&bibkeys=ISBN:' . $isbn;
    $request = $this->httpClient->request('GET', $uri);

    try {
      $request = $this->httpClient->request('GET', $uri);
      $request->getBody();
      $data = json_decode($request->getBody()->read(4096), TRUE);
    }
    catch (RequestException $e) {
      $this->loggerChannelFactory->get('OpenLibraryService')
        ->alert($e->getCode() . ' : ' . $e->getMessage());
    }
    if (!isset($data['ISBN:' . $isbn])) {
      $this->loggerChannelFactory->get('OpenLibraryService')
        ->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return [];
    }
    $data = $data['ISBN:' . $isbn];

    $book_data['title'] = $data['title'];
    $book_data['field_pages'] = $data['number_of_pages'];
    foreach ($data['authors'] as $author) {
      $book_data['field_authors'][] = $author['name'];
    }
    $data['publishers'] = reset($data['publishers']);
    $book_data['field_publisher'] = $data['publishers']['name'];
    $book_data['field_isbn'] = $isbn;
    $book_data['field_release'] = date('Y-m-d', strtotime($data['publish_date']));
    return $book_data;
  }

}
