# ACF { Create Object

Can be used to create posts with Advanced Custom Fields on the front end.

## Compatibility

This add-on will work with:

* version 4, it isn't needed with 5

## Installation

This add-on can be treated as a WP plugin.

### Install as Plugin

1. Copy the folder into your plugins folder
2. Activate the plugin via the Plugins admin page

## Use

When setting up your front end form, use "new_post" instead of an existing post_id to create a new post.

**Example**

```php
acf_form( array(
	'post_id' => 'new_post'
) );
```

## Filters

* `acf_create_object/post/status` Change the post status new posts are given. Default: draft
* `acf_create_object/post/title` Change the title new posts are given. Default: "New Post"
* `acf_create_object/post/type` Change the post type new posts are assigned to. Default: post