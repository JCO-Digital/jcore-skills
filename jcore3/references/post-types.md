# Custom post types, taxonomies and fields

## ACF, saved as JSON

Post types, taxonomies and field groups are made with **ACF Pro** and saved as
JSON in the theme's `acf-json/` (Ydin's ACF feature sets the save locations
and names: `post-types/post_type_<slug>.json`, `taxonomy/taxonomy_<slug>.json`,
`fields/fields_<title>.json`). Commit those files.

To create them from the command line so ACF writes its own complete JSON (no
hand-written files): `acf_import_internal_post_type( $settings, 'acf-post-type' )`,
`acf_import_internal_post_type( $settings, 'acf-taxonomy' )` and
`acf_import_field_group( $group_with_fields )` in a `wp eval-file` script. ACF
fills in the defaults. Labels in English; they're translated with the theme.

**On a site that isn't in English, turn ACF's translation off first.** Ydin
gives ACF the theme's `jcore` text domain, so ACF translates labels as it
loads them, and saving writes the translation into the JSON: a field labelled
"People" is saved as "Henkilöstö", a taxonomy's labels in Finnish. Start every
script that saves ACF items with:

```php
acf_update_setting( 'l10n', false );
switch_to_locale( 'en_US' );
```

The same goes for changing an existing item: read it with
`acf_get_internal_post_type()` / `acf_get_field_group()` in such a script, or
edit its JSON and re-save it from the file (`json_decode` the file, set `ID`
to the existing post's ID, `acf_update_internal_post_type()` or
`acf_import_field_group()`). Check the JSON diff afterwards: only the change
you meant and `modified` should differ, and no `local_file` key.

Read fields in PHP with `get_field( 'name', $post_id )` (term:
`get_field( 'name', $term )`), in Twig with `post.meta('name')`.

## Addresses

A post type's archive slug wins over a page with the same address (e.g.
`/henkilosto/`). Don't make a page for the list: the menu links to the post
type archive, and the archive gets its own template (`archive-<type>.html`).

## Templates and post lifts

- `single-<type>.html` and `archive-<type>.html` in `templates/`.
- **Every post type archive is built with `jcore/dynamic-archive`** (with
  `postType` set), like the posts page: no core Query Loop, no page with a
  list block. Filters, load more and the number per row are block
  attributes in the template, e.g. the references archive:
  ```html
  <!-- wp:jcore/dynamic-archive {"postType":"referenssi","columns":2,"perPage":10,"showPagination":true,"infiniteScroll":true,"taxonomies":["osaamisalue"],"filterTypes":{"osaamisalue":"multiselect"},"filterTypesChild":{"osaamisalue":"multiselect"},"hierarchicalFilter":{"osaamisalue":true},"align":"wide"} /-->
  ```
  `hierarchicalFilter` splits a hierarchical taxonomy into a parent and a
  child filter (e.g. service and sub-service); it needs parent terms.
  Dynamic Archive filters by taxonomy only, so a filter by a related post
  (e.g. people) needs a taxonomy for it.
- The theme's
  `views/partials/tease.twig` picks `tease-<post_type>.twig`, so a post type
  gets its own card by adding that file; style it in `_tease.scss`.
- Dynamic Archive prints an empty filter row when the block has no filters;
  the theme hides it (`_archive.scss`).
- `lohko/mega-navigation` treats a menu item that links to a post type
  archive as current on the archive and its single pages.

## Related posts of another type

`jcore/latest-posts` with `related` on always lists posts of the **current
post's type** and ignores its `postTypes` setting, so on a person page it lists
other people. For related articles on another post type's page, change the
query in the theme with the plugin's `jcore_latest_posts_args` filter (e.g.
`post_type => post`, with a `tax_query` on the shared taxonomy); never edit the
plugin.

## Blocks that show a post's data

Dynamic Lohko blocks read the current post from `$block->context['postId']`
(`"usesContext": ["postId","postType"]` in block.json). In the editor
preview (ServerSideRender) there is no context, so pass the post id as a query
arg (`urlQueryArgs: { post_id: context.postId }`) and fall back to it.

`render.php` files run in the global scope: don't name variables after
WordPress globals (`$post_id`, `$page`, `$term`, `$taxonomy`, `$link`,
`$role`…); PHPCS flags them. Use names like `$current_id`, `$linked_page`.

## Choosing people (or any posts) in a block

A container block plus item blocks (e.g. `lohko/contact-people` holding
`lohko/contact-person`): each item picks a post with a `ComboboxControl` fed by
`useEntityRecords( 'postType', type, … )`, and renders on the server. The
editor orders and groups the items freely.
