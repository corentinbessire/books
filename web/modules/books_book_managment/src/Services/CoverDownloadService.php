<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * CoverDownloadService service.
 */
class CoverDownloadService {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $mediaStorage;


  /**
   * CoverDownloadService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->httpClient = $http_client;
    $this->logger = $loggerChannelFactory->get('CoverDownloadService');
    $this->mediaStorage = $entityTypeManager->getStorage('media');
  }

  public function downloadBookCover(string $isbn) {
    $sources = $this->buildSourceArray($isbn);

    foreach ($sources as $source) {
      $image = $this->getBookCover($source);
      if ($image) break;
    }
    if (!$image) return FALSE;
    $media = $this->createMedia($image, $isbn);
    return $media;
  }

  /**
   * Method description.
   */
  private function buildSourceArray(string $isbn) {
    return [
      'https://hachette.imgix.net/books/' . $isbn . '.jpg',
      'https://images.macmillan.com/folio-assets/macmillan_us_frontbookcovers_1000H/' . $isbn . '.jpg',
    ];
  }

  private function getBookCover(string $url): ?EntityInterface {
    try {
      $request = $this->httpClient->request('GET', $url);
    } catch (RequestException $e) {
      $this->logger->alert($e->getCode() . ' : ' . $e->getMessage());
    }
    if (!$request) return NULL;
    return system_retrieve_file($url, 'public://book-cover/',TRUE, 1);
  }

  private function createMedia(?EntityInterface $image, string $isbn) {
    $media = $this->mediaStorage->create(['bundle' => 'book_cover']);
    $media->set('name', $isbn);
    $media->set('field_media_image', $image);
    $media->save();
    return $media;
  }

}
