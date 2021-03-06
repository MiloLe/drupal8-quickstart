<?php

/**
 * @file
 * Contains \Drupal\views\Tests\SearchMultilingualTest.
 */

namespace Drupal\views\Tests;

use Drupal\Core\Language\Language;

/**
 * Tests search integration filters with multilingual nodes.
 */
class SearchMultilingualTest extends ViewTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node', 'search', 'language', 'content_translation');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_search');

  /**
   * {inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Search multilingual tests',
      'description' => 'Tests search filters of views with multilingual nodes.',
      'group' => 'Views',
    );
  }

  /**
   * Tests search with multilingual nodes.
   */
  public function testMultilingualSearchFilter() {
    // Create a user with admin for languages, content, and content types, plus
    // the ability to access content and searches.
    $user = $this->drupalCreateUser(array('administer nodes', 'administer content types', 'administer languages', 'administer content translation', 'access content', 'search content'));
    $this->drupalLogin($user);

    // Add Spanish language programmatically.
    language_save(new Language(array('id' => 'es')));

    // Create a content type and make it translatable.
    $type = $this->drupalCreateContentType();
    $edit = array(
      'language_configuration[language_show]' => TRUE,
    );
    $this->drupalPostForm('admin/structure/types/manage/' . $type->type, $edit, t('Save content type'));
    $edit = array(
      'entity_types[node]' => TRUE,
      'settings[node][' . $type->type . '][translatable]' => TRUE,
      'settings[node][' . $type->type . '][fields][title]' => TRUE,
      'settings[node][' . $type->type . '][fields][body]' => TRUE,
    );
    $this->drupalPostForm('admin/config/regional/content-language', $edit, t('Save'));

    // Add a node in English, with title "sandwich".
    $values = array(
      'title' => 'sandwich',
      'type' => $type->type,
    );
    $node = $this->drupalCreateNode($values);

    // "Translate" this node into Spanish, with title "pizza".
    $node->addTranslation('es', array('title' => 'pizza', 'status' => NODE_PUBLISHED));
    $node->save();

    // Run cron so that the search index tables are updated.
    $this->cronRun();

    // Test the keyword filter by visiting the page.
    // The views are in the test view 'test_search', and they just display the
    // titles of the nodes in the result, as links.

    // Page with a keyword filter of 'pizza'. This should find the Spanish
    // translated node, which has 'pizza' in the title, but not the English
    // one, which does not have the word 'pizza' in it.
    $this->drupalGet('test-filter');
    $this->assertLink('pizza', 0, 'Found translation with matching title');
    $this->assertNoLink('sandwich', 'Did not find translation with non-matching title');
  }

}
