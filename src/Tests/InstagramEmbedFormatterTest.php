<?php

namespace Drupal\media_entity_instagram\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\media_entity\Tests\MediaTestTrait;

/**
 * Tests for Instagram embed formatter.
 *
 * @group media_entity_instagram
 */
class InstagramEmbedFormatterTest extends WebTestBase {

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
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'instagram');

    // Add and save field settings (Embed code).
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/fields/add-field');
    $edit_conf = [
      'new_storage_type' => 'string_long',
      'label' => 'Embed code',
      'field_name' => 'embed_code',
    ];
    $this->drupalPostForm(NULL, $edit_conf, t('Save and continue'));
    $this->assertText('These settings apply to the ' . $edit_conf['label'] . ' field everywhere it is used.');
    $edit = [
      'cardinality' => 'number',
      'cardinality_number' => '1',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save field settings'));
    $this->assertText('Updated field ' . $edit_conf['label'] . ' field settings.');

    // Set the new field as required.
    $edit = [
      'required' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save settings'));
    $this->assertText('Saved ' . $edit_conf['label'] . ' configuration.');

    // Assert that the new field configuration has been successfully saved.
    $xpath = $this->xpath('//*[@id="field-embed-code"]');
    $this->assertEqual((string) $xpath[0]->td[0], 'Embed code');
    $this->assertEqual((string) $xpath[0]->td[1], 'field_embed_code');
    $this->assertEqual((string) $xpath[0]->td[2]->a, 'Text (plain, long)');

    // Test if edit worked and if new field values have been saved as
    // expected.
    $this->drupalGet('admin/structure/media/manage/' . $bundle->id());
    $this->assertFieldByName('label', $bundle->label());
    $this->assertFieldByName('type', 'instagram');
    $this->assertFieldByName('type_configuration[instagram][source_field]', 'field_embed_code');
    $this->drupalPostForm(NULL, NULL, t('Save media bundle'));
    $this->assertText('The media bundle ' . $bundle->label() . ' has been updated.');
    $this->assertText($bundle->label());

    $this->drupalGet('admin/structure/media/manage/' . $bundle->id() . '/display');

    // Set and save the settings of the new field.
    $edit = [
      'fields[field_embed_code][label]' => 'above',
      'fields[field_embed_code][type]' => 'instagram_embed',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('Your settings have been saved.');

    // First set absolute size of the embed.
    $this->drupalPostAjaxForm(NULL, [], 'field_embed_code_settings_edit');
    $edit = [
      'fields[field_embed_code][settings_edit_form][settings][hidecaption]' => FALSE,
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'field_embed_code_plugin_settings_update');
    $this->drupalPostForm(NULL, [], t('Save'));
    $this->assertText('Your settings have been saved.');
    $this->assertText('Caption: Visible');

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
    $this->assertText('My test instagram');
    $this->assertText('Embed code');

    // Assert that the formatter exists on this page and that it has absolute
    // size.
    $this->assertFieldByXPath('//blockquote');
    $this->assertRaw('platform.instagram.com/en_US/embeds.js');
  }

}
