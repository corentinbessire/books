<?php

namespace Drupal\books_book_managment\Service;

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
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $termStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $mediaStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $fileStorage;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Constructs an OpenLibraryService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entityTypeManager, FileSystemInterface $file_system) {
    $this->httpClient = $http_client;
    $this->logger = $loggerChannelFactory->get('OpenLibraryService');
    $this->termStorage = $entityTypeManager->getStorage('taxonomy_term');
    $this->nodeStorage = $entityTypeManager->getStorage('node');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
    $this->fileSystem = $file_system;

  }

  /**
   * Method description.
   */
  public function getBookData($isbn) {
    $uri = 'https://www.googleapis.com/books/v1/volumes?q=isbn:' . $isbn . '&key=AIzaSyD9AKjGv-hjic3m3LQgeUvOT5V-bKxKGyM';

    try {
      $request = $this->httpClient->request('GET', $uri);
      $data = json_decode($request->getBody()->read(4096), TRUE);
    } catch (RequestException $e) {
      $this->logger->alert($e->getCode() . ' : ' . $e->getMessage());
    }

    if ($data['totalItems'] === 0) {
      $this->logger->alert('No data fo ISBN : ' . $isbn . '(' . $uri . ')');
      return FALSE;
    }
    $data = array_pop($data['items']);
    $release = date('Y-m-d', strtotime($data['volumeInfo']['publishedDate']));

    /**
     * @var \Drupal\node\Entity\Node $book
     */
    $book = $this->getBook($isbn);
    $book->set('title', $data['volumeInfo']['title']);
    $book->set('field_pages', $data['volumeInfo']['pageCount']);
    $book->set('field_authors', $this->getTerm($data['volumeInfo']['authors'], 'author'));
    $book->set('field_publisher', $this->getTerm($data['volumeInfo']['publisher'], 'publisher'));
    $book->set('field_isbn', $isbn);
    $book->set('field_release', $release);
    $book->save();
    return $book->id();
  }

  function getBook($isbn) {
    $books = $this->nodeStorage->loadByProperties(['field_isbn' => $isbn]);
    if (!empty($books)) {
      return end($books);
    }
    return $this->nodeStorage->create(['type' => 'book']);
  }

  private function getTerm($termNames, $vid): array {
    if (!is_array($termNames)) {
      $termNames = [$termNames];
    }
    foreach ($termNames as $termName) {

      $result = $this->termStorage->getQuery()
        ->condition('vid', $vid)
        ->condition('name', $termName)
        ->execute();
      if (empty($result)) {
        $term = $this->termStorage->create([
          'vid' => $vid,
          'name' => $termName,
        ]);
        $term->save();
        $target['target_id'][] = $term->id();
      }
      else {
        $target[]['target_id'] = array_pop($result);
      }
    }
    return $target;
  }

}
