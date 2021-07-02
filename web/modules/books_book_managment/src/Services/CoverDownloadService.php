<?php

namespace Drupal\books_book_managment\Service;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;

/**
 * CoverDownloadService service.
 */
class CoverDownloadService {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * Constructs a CoverDownloadService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ClientInterface $http_client, LoggerChannelFactoryInterface $loggerChannelFactory, FileSystemInterface $file_system) {
    $this->httpClient = $http_client;
    $this->logger = $loggerChannelFactory->get('CoverDownloadService');
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }



  /**
   * Method description.
   */
  public function getHachetteCover(string $isbn) {
    $url = 'https://hachette.imgix.net/books/' . $isbn . '.jpg';
    $request = $this->httpClient->request('GET', $url);
    dump($request->getStatusCode());
  }

  /**
   * Method description.
   */
  public function getMcMillanCover(string $isbn) {
    $url = 'https://images.macmillan.com/folio-assets/macmillan_us_frontbookcovers_1000H/' . $isbn . '.jpg';
    $request = $this->httpClient->request('GET', $url);
    dump($request->getStatusCode());
  }


}
