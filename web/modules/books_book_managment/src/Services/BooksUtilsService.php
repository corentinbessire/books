<?php

namespace Drupal\books_book_managment\Services;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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
    foreach ($data as $fieldId => $fieldValue) {
      if ($book->hasField($fieldId)) {
        if ($this->vids[$fieldId]) {
          $fieldValue = $this->getTermByName($fieldValue, $this->vids[$fieldId]);
          $fieldValue = (count($fieldValue) === 1) ? reset($fieldValue) : $fieldValue;
        }
        $book->set($fieldId, $fieldValue);
      }
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
   * Return an array of Term ID for given Names and Vocabularies.
   * If Terms doesn't exists, will create theme
   *
   * @param array|string $termNames Array of Term Name to look for or Create
   * @param string $vid Vocabulary ID in wich Terms are looked for in.
   *
   * @return array      Array of TID formated to be saved in Field.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getTermByName($termNames, string $vid): array {
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
        ]);
        $term->set('name', $termName);
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
