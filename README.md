## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity Instagram

This module provides Instagram integration for Media entity (i.e. media type provider
plugin).

### Instagram API
This module uses Instagrams oembed API to fetch the instagram html and all the metadata.
You will need to:

- Create a Media bundle with the type provider "Instagram".
- On that bundle create a field for the Instagram url/source (this should be a plain text or link field).
- Return to the bundle configuration and set "Field with source information" to use that field.

### Storing field values
If you want to store the fields that are retrieved from Instagram you should create appropriate fields on the created media bundle (id) and map this to the fields provided by Instagram.php.

**NOTE:** At the moment there is no GUI for that, so the only method of doing that for now is via CMI.

This would be an example of that (the field_map section):

```
langcode: en
status: true
dependencies:
  module:
    - media_entity_instagram
id: instagram
label: Instagram
description: 'Instagram photo/video to be used with content.'
type: instagram
type_configuration:
  source_field: link
field_map:
  id: instagram_id
  type: instagram_type
  thumbnail: instagram_thumbnail
  username: instagram_username
  caption: instagram_caption
```

Project page: http://drupal.org/project/media_entity_instagram

Maintainers:
 - Janez Urevc (@slashrsm) drupal.org/user/744628
 - Malina Randrianavony (@designesse) www.drupal.org/user/854012

IRC channel: #drupal-media
