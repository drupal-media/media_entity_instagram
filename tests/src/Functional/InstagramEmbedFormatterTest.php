<?php

namespace Drupal\Tests\media_entity_instagram\Functional;

use Drupal\media_entity\Entity\MediaBundle;
use Drupal\media_entity\Tests\MediaTestTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests for Instagram embed formatter.
 *
 * @group media_entity_instagram
 */
class InstagramEmbedFormatterTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'media_entity_instagram',
    'media_entity',
    'node',
    'field_ui',
    'views_ui',
    'block',
  ];

  use MediaTestTrait;

  /**
   * The test user.
   *
   * @var \Drupal\User\UserInterface
   */
  protected $adminUser;

  /**
   * Media entity machine id.
   *
   * @var string
   */
  protected $mediaId = 'instagram';

  /**
   * The test media bundle.
   *
   * @var \Drupal\media_entity\MediaBundleInterface
   */
  protected $testBundle;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $bundle['bundle'] = $this->mediaId;
    $this->testBundle = $this->drupalCreateMediaBundle($bundle, 'instagram');
    $this->drupalPlaceBlock('local_actions_block');
    $this->adminUser = $this->drupalCreateUser([
      'administer media',
      'administer media bundles',
      'administer media fields',
      'administer media form display',
      'administer media display',
      // Media entity permissions.
      'view media',
      'create media',
      'update media',
      'update any media',
      'delete media',
      'delete any media',
      // Other permissions.
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests adding and editing an instagram embed formatter.
   */
  public function testManageFieldFormatter() {
    // Test and create one media bundle.
    $bundle = $this->testBundle;

    // Assert that the media bundle has the expected values before proceeding.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertSession()->fieldValueEquals('label', $bundle->label());
    $this->assertSession()->fieldValueEquals('type', 'instagram');

    // Add and save field settings (Embed code).
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields/add-field');
    $edit_conf = [
      'new_storage_type' => 'string_long',
      'label' => 'Embed code',
      'field_name' => 'embed_code',
    ];
    $this->drupalPostForm(NULL, $edit_conf, t('Save and continue'));
    $this->assertSession()->pageTextContains('These settings apply to the ' . $edit_conf['label'] . ' field everywhere it is used.');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertSession()->pageTextContains('Updated field ' . $edit_conf['label'] . ' field settings.');

    // Set the new field as required.
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertSession()->pageTextContains('Saved ' . $edit_conf['label'] . ' configuration.');

    // Assert that the new field configuration has been successfully saved.
    $this->assertEquals('Embed code', $this->xpath('//*[@id="field-embed-code"]/td[1]')[0]->getText());
    $this->assertEquals('field_embed_code', $this->xpath('//*[@id="field-embed-code"]/td[2]')[0]->getText());
    $this->assertEquals('Text (plain, long)', $this->xpath('//*[@id="field-embed-code"]/td[3]')[0]->getText());

    // Test if edit worked and if new field values have been saved as
    // expected.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertSession()->fieldValueEquals('label', $bundle->label());
    $this->assertSession()->fieldValueEquals('type', 'instagram');
    $this->assertSession()->fieldValueEquals('type_configuration[instagram][source_field]', 'field_embed_code');
    $this->drupalPostForm(NULL, NULL, t('Save media bundle'));
    $this->assertSession()->pageTextContains('The media bundle ' . $bundle->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($bundle->label());

    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field.
    $edit = [
      'fields[field_embed_code][label]' => 'above',
      'fields[field_embed_code][type]' => 'instagram_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('Your settings have been saved.');

    // First set absolute size of the embed.
    $this->submitForm([], 'field_embed_code_settings_edit');
    $edit = [
      'fields[field_embed_code][settings_edit_form][settings][hidecaption]' => FALSE,
    ];
    $this->submitForm($edit, 'field_embed_code_plugin_settings_update');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertSession()->pageTextContains('Caption: Visible');

    // Create and save the media with an instagram media code.
    $this->drupalGet('media/add/' . $bundle->id());

    // Example instagram from https://www.instagram.com/developer/embedding/
    $instagram = 'https://www.instagram.com/p/bNd86MSFv6/';

    $edit = [
      'name[0][value]' => 'My test instagram',
      'field_embed_code[0][value]' => $instagram,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save and publish'));

    // Assert that the media has been successfully saved.
    $this->assertSession()->pageTextContains('My test instagram');
    $this->assertSession()->pageTextContains('Embed code');

    // Assert that the formatter exists on this page and that it has absolute
    // size.
    $this->assertFieldByXPath('//blockquote');
    $this->assertSession()->responseContains('platform.instagram.com/en_US/embeds.js');
  }

  /**
   * Creates media bundle.
   *
   * @param array $values
   *   The media bundle values.
   * @param string $type_name
   *   (optional) The media type provider plugin that is responsible for
   *   additional logic related to this media).
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Returns newly created media bundle.
   */
  protected function drupalCreateMediaBundle(array $values = [], $type_name = 'generic') {
    if (!isset($values['bundle'])) {
      $id = strtolower($this->randomMachineName());
    }
    else {
      $id = $values['bundle'];
    }
    $values += [
      'id' => $id,
      'label' => $id,
      'type' => $type_name,
      'type_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ];

    $bundle = MediaBundle::create($values);
    $status = $bundle->save();

    $this->assertEquals(SAVED_NEW, $status, t('Created media bundle %bundle.', ['%bundle' => $bundle->id()]));

    return $bundle;
  }

}
