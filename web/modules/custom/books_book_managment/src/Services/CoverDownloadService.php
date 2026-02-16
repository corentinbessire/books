<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileRepositoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Custom Service to Download BookCover.
 */
class CoverDownloadService {

  /**
   * CoverDownloadService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   Guzzle Client Interface Service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Drupal Logger Channel Factory Service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Drupal Entity Type Manager service.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   Drupal File Repository System Service.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileRepositoryInterface $fileRepository,
  ) {}

  /**
   * Main Entry method to get Media entity with Book cover.
   *
   * @param string $isbn
   *   ISBN of the book to get cover of.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false|null
   *   Media Entity if Exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function downloadBookCover(string $isbn) {
    $sources = $this->buildSourceArray($isbn);
    $image = FALSE;
    if (!$media = $this->getMediaByIsbn($isbn)) {
      foreach ($sources as $source) {
        $image = $this->getBookCover($source, $isbn);
        if ($image) {
          break;
        }
      }
      if (!$image) {
        return FALSE;
      }
      $media = $this->createMedia($image, $isbn);
    }
    return $media;
  }

  /**
   * Build all potential source of Book Cover Image.
   *
   * @param string $isbn
   *   ISBN Number of the Book to get Cover of.
   *
   * @return string[]
   *   Array of potential location for Cover.
   */
  private function buildSourceArray(string $isbn): array {
    return [
      'https://hachette.imgix.net/books/' . $isbn . '.jpg',
      'https://images.macmillan.com/folio-assets/macmillan_us_frontbookcovers_1000H/' . $isbn . '.jpg',
      'https://images2.penguinrandomhouse.com/cover/700jpg/' . $isbn,
    ];
  }

  /**
   * Get Book Cover Data and Save it as a managed File entity.
   *
   * @param string $image_url
   *   The URL of the Image to download.
   * @param string $isbn
   *   ISBN of the Book to get the cover of.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The File entity if exists.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getBookCover(string $image_url, string $isbn): ?EntityInterface {
    try {
      $request = $this->httpClient->request('GET', $image_url);
      $file_contents = $request->getBody()->getContents();
      // Generate unique filename.
      $filename = basename($image_url);
      $filename = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', $filename);
      $filename = uniqid() . '_' . $filename;
      $uri = 'public://book-cover/' . $filename;

      // Create managed file.
      $file = $this->fileRepository->writeData(
        $file_contents,
        $uri
      );

      // Set file to permanent.
      $file->setPermanent();
      $file->save();
    }
    catch (RequestException $e) {
      $this->loggerChannelFactory->get('CoverDownloadService')
        ->alert($e->getCode() . ' : ' . $e->getMessage());
    }

    return $file ?? NULL;
  }

  /**
   * Create the Media Entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $image
   *   Image File Entity.
   * @param string $isbn
   *   ISBN of The Book of the Cover.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The Media Entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createMedia(
    ?EntityInterface $image,
    string $isbn,
  ) {
    $media = $this->entityTypeManager->getStorage('media')
      ->create(['bundle' => 'book_cover']);
    $media->set('name', $isbn);
    $media->set('field_media_image', $image);
    $media->save();
    return $media;
  }

  /**
   * Look for an existing Media Entity for given ISBN.
   *
   * @param string $isbn
   *   ISBN to look for.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false|null
   *   Media entity if exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException * *   *
   *     *   *
   */
  protected function getMediaByIsbn(string $isbn) {
    $result = $this->entityTypeManager->getStorage('media')->getQuery()
      ->condition('name', $isbn)
      ->accessCheck()
      ->execute();
    return (empty($result)) ? FALSE : $this->entityTypeManager->getStorage('media')
      ->load(reset($result));
  }

}
