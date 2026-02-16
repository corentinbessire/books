<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Implementation of Book Data Services for Google Books API.
 */
class GoogleBooksService implements BookDataServiceInterface {

  /**
   * Constructor of the GoogleBooksService Service.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Guzzle Client Service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Drupal Logger Channel Factory Service.
   * @param \Drupal\Core\Site\Settings $settings
   *   Drupal Setting Service.
   */
  public function __construct(
    protected ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly Settings $settings,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getBookData(string|int $isbn): array|null {
    $googleApiKey = $this->settings->get('google_api_key');
    $uri = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn . '&key=' . $googleApiKey;
    $data = NULL;
    try {
      $request = $this->httpClient->request('GET', $uri);
      $data = json_decode($request->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $this->loggerChannelFactory->get('GoogleBooksService')
        ->alert($e->getCode() . ' : ' . $e->getMessage());
    }

    if ($data['totalItems'] === 0) {
      $this->loggerChannelFactory->get('GoogleBooksService')
        ->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return NULL;
    }
    return array_pop($data['items']);
  }

  /**
   * {@inheritdoc}
   */
  public function formatBookData(array $bookData): array {
    $release = date('Y-m-d', strtotime($bookData['volumeInfo']['publishedDate']));
    $formattedBookData['title'] = $bookData['volumeInfo']['title'];
    $formattedBookData['field_pages'] = $bookData['volumeInfo']['pageCount'];
    $formattedBookData['field_authors'] = $bookData['volumeInfo']['authors'];
    $formattedBookData['field_publisher'] = $bookData['volumeInfo']['publisher'];
    $formattedBookData['field_excerpt'] = $bookData['volumeInfo']['description'];
    foreach ($bookData['volumeInfo']['industryIdentifiers'] as $industryIdentifier) {
      if ($industryIdentifier['type'] === 'ISBN_13') {
        $formattedBookData['field_isbn'] = $industryIdentifier['identifier'];
      }
    }

    $formattedBookData['field_release'] = $release;
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
