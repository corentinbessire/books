<?php

namespace Drupal\books_book_managment\Services;

use GuzzleHttp\ClientInterface;

/**
 * CoverDownloadService service.
 */
class CoverDownloadService {

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $httpClient;


  /**
   * CoverDownloadService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
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
