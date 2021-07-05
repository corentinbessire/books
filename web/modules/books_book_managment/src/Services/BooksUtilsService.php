<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\taxonomy\TermInterface;

/**
 * BooksUtilsService service.
 */
class BooksUtilsService {

  /**
   * The logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $termStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $nodeStorage;

  private $vids = [
    'field_authors' => 'authors',
    'field_publisher' => 'publishers',
  ];

  /**
   * Constructs a BooksUtilsService object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The file system service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(LoggerChannelFactoryInterface $logger, EntityTypeManagerInterface $entity_type_manager) {
    $this->logger = $logger;
    $this->termStorage = $entity_type_manager->getStorage('taxonomy_term');
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * Save Given Data into Book node entity
   *
   * @param string $isbn ISBN-13 of the book to save data in
   * @param array $data array of fieldId and FieldValue to save
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function saveBookData(string $isbn, array $data): EntityInterface {
    $book = $this->getBook($isbn);
    if (isset($data['title'])) {
      $book->setTitle($data['title']);
    }
    else {
      $book->setTitle($isbn);
    }
    if($data['field_pages']) {
      $book->set('field_pages', $data['field_pages']);
    }
    if($data['field_isbn']) {
      $book->set('field_isbn', $data['field_isbn']);
    }
    if($data['field_release']) {
      $book->set('field_release', $data['field_release']);
    }
    if($data['field_excerpt']) {
      $book->set('field_excerpt', $data['field_excerpt']);
    }
    if ($data['field_cover']) {
      $book->set('field_cover', $data['field_cover']);
    }

    if($data['field_publisher']) {
      $publisher = $this->getTermByName($data['field_publisher'], 'publisher');
      $book->set('field_publisher', $publisher);
    }
    if($data['field_authors']) {
      $authors = [];
      foreach ($data['field_authors'] as $author) {
        $authors[]['target_id'] = $this->getTermByName($author, 'author')->id();
      }
      $book->set('field_authors', $authors);
    }

    $book->save();
    return $book;
  }

  /**
   *  Return existing Node Book with given ISBN or create new entity.
   *
   * @param string $isbn ISBN-13 Value
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getBook(string $isbn): EntityInterface {
    $books = $this->nodeStorage->loadByProperties(['field_isbn' => $isbn]);
    if (!empty($books)) {
      return end($books);
    }
    return $this->nodeStorage->create(['type' => 'book']);
  }


  /**
   * @param $termName
   * @param string $vid
   *
   * @return \Drupal\taxonomy\TermInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getTermByName($termName, string $vid): ?TermInterface {
    if (!$termName) {
      return NULL;
    }
    $result = $this->termStorage->getQuery()
      ->condition('vid', $vid)
      ->condition('name', $termName)
      ->execute();
    if (empty($result)) {
      $term = $this->termStorage->create([
        'vid' => $vid,
      ]);
      $term->set('name', $termName);
      $term->save();
    }
    else {
      $term = $this->termStorage->load(reset($result));
    }
    return $term;
  }

}
