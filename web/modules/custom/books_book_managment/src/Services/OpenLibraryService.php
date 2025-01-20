<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Implementation of Book Data Services for OpenLibrary API.
 */
class OpenLibraryService implements BookDataServiceInterface {

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
   * {@inheritdoc}
   */
  public function getBookData(string|int $isbn): array|null {
    $uri = 'https://openlibrary.org/api/books?jscmd=data&format=json&bibkeys=ISBN:' . $isbn;

    try {
      $request = $this->httpClient->request('GET', $uri);
      $data = json_decode($request->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $this->loggerChannelFactory->get('OpenLibraryService')
        ->alert($e->getCode() . ' : ' . $e->getMessage());
    }

    if (!isset($data['ISBN:' . $isbn])) {
      $this->loggerChannelFactory->get('OpenLibraryService')
        ->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return NULL;
    }
    $bookData = $data['ISBN:' . $isbn];
    $bookData['isbn'] = $isbn;
    return $bookData;
  }

  /**
   * {@inheritdoc}
   */
  public function formatBookData(array $bookData): array {
    $formattedBookData['title'] = $bookData['title'];
    $formattedBookData['field_pages'] = $bookData['number_of_pages'];
    foreach ($bookData['authors'] as $author) {
      $formattedBookData['field_authors'][] = $author['name'];
    }
    $bookData['publishers'] = reset($bookData['publishers']);
    $formattedBookData['field_publisher'] = $bookData['publishers']['name'];
    $formattedBookData['field_isbn'] = $bookData['isbn'];
    $formattedBookData['field_release'] = date('Y-m-d', strtotime($bookData['publish_date']));
    return $formattedBookData;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormatedBookData(int|string $isbn): array|null {
    $bookData = $this->getBookData($isbn);
    return ($bookData) ? $this->formatBookData($bookData) : $bookData;
  }

}
