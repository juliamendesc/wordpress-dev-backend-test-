# WordPress Dev Backend Test

## Project Setup

1. Install the dependencies via composer;
2. Create a `.env` following the structure of the `.env.example`;
3. Run the project with `composer run dev`, it will start the project at `localhost:8080`;
4. Go to http://localhost:8080/wp/wp-admin/themes.php and activate the theme `devbackend`;
5. Go to http://localhost:8080/wp/wp-admin/plugins.php and activate the graphql plugin;
6. All work was developed inside of `web/app/theme/devbackend`, as requested.
<br><br>

# Developer Remarks

## Test difficulty

Considering myself a junior developer, facing a mid level developer test in a stack never before used was defying, but instigating. Each step conquered gave me the necessary boost to keep on trying and seeking to show my capacity and potential.

I am thrilled at being able to finish this test and acknowledge to myself my own capacities.

## Challenge-specific remarks and consulted resources

This code was formatted according to the [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). <br><br>
Tasks:<br>

1. Create a new Custom Post Type (https://wordpress.org/support/article/post-types/#custom-post-types) called `product`.
    - I opted for providing thorough personalized labels, with support for further translation specifically in the devbackend theme context;
    - Extended support was thought to provide the user with more options when creating products and organizing the post.
    - A custom slug improves URL readability
<br><br>
Sources:<br>
[¹] [WordPress - Custom Post Types](https://wordpress.org/support/article/post-types/#custom-post-types)<br>
[²] [WordPress - Register Post Type Function](https://developer.wordpress.org/reference/functions/register_post_type/)<br>
[³] [WordPress - Add Post Type Support](https://developer.wordpress.org/reference/functions/add_post_type_support/)<br>
[⁴] [WordPress - Retrieving translation string with gettext context](https://developer.wordpress.org/reference/functions/_x/)<br><br>
2. Create an `image` field in `product`: https://docs.carbonfields.net/learn/fields/image.html
   -  I set up one container for including the custom fields inside the product post. Used the reference provided as syntax guidance.<br><br>
Source:<br>
[¹] [Carbon Fields - Fields- Type Image](https://docs.carbonfields.net/learn/fields/image.html)<br><br>
3. Create a relationship where one product can have many posts and one post can be linked to one product. To do that, you should create fields using an Association Field (https://docs.carbonfields.net/learn/fields/association.html): `product->posts` and `post->product`.
   - In the same container used for creating the image field, I added another field for the association between product and post. Followed the reference provided for syntax.<br><br>
Source:<br>
[¹] [Carbon Fields - Fields- Type Association](https://docs.carbonfields.net/learn/fields/association.html#config-methods)<br><br>
4. Could you make it two-way data binding? It means: when I save a product with two posts, it should update the posts that are related to the product; and if I update a related post, it should update the product as well. <br>
   - I believe this was the most challenging task for me. I developed an algorithm but had difficulty in implementing it, trying several times with no success.
   - My logic was the following: whenever an association `product->posts` or `post->product` was triggered, it would fetch the id of the associated field and create a new association, opposite to the current one with `carbon_set_post_meta`.
   - Unfortunately, I was unable to solve this task in due time.<br><br>
Sources: <br>
[¹] [Carbon Fields - Containers Post Meta](https://carbonfields.net/docs/containers-post-meta/)<br>
[²] [Carbon Fields - PHP Hooks](https://docs.carbonfields.net/learn/advanced-topics/php-hooks.html)<br><br>
5. Create the representation of a `product` in the graphql schema: https://www.wpgraphql.com/docs/custom-post-types/<br>
   - Followed resource given for syntax.
   - Added show_ui for specific admin panel for managing the post <br><br>
Sources:<br>
[¹] [WPGraphql - Custom Post Types](https://www.wpgraphql.com/docs/custom-post-types/)<br>
[²][WordPress - Custom Posty Type - Show UI](https://developer.wordpress.org/reference/functions/register_post_type/#show_ui) <br><br>
6. In the graphql schema, create the field `product.image` that should be similar to `post.featuredImage` schema, using `MediaItem` type.
	- After understanding how to perform queries in WPGraphQL, I proceeded to understanding where the image added by the Carbon Field Custom Post Type Field was created;
	- The image was found in mediaItems and was not directly connected to the product to which it belonged. It was part of a broad image dataset that become available to all posts;
	- I proceeded to register a connection between the media item and the product according to the main syntax in the [recipe](https://www.wpgraphql.com/recipes/register-connection-to-attached-media/), adapting the specific fields and filtering the resolver for the attachment in each product.<br>
	- The resolver also needed to be adapter to create an array of arguments identified by the source id and with the value of the previously created image field, when found by the get post meta function.<br>
	- An interesting fact took place here. While in the get_post_meta function, just including `get_post_meta($source->ID, '_crb_image', true )` proved insufficient to effectively query the images. By researching for the usage of the function, I came across with some variables preceeded by underscore and decided to try. For my amusement, it worked perfecly.<br>
	- Also after solving task 7, I attempted to change the `get_post_meta` query I had to `carbon_get_post_meta` and it worked perfectly in a much simpler way.<br><br>
Sources:<br>
[¹] [WPGraphQL - Media Queries](https://www.wpgraphql.com/docs/media/)<br>
[²] [WPGraphQL - Connections](https://www.wpgraphql.com/docs/connections/)<br>
[³] [WPGraphQL - Cursor Connections Specification](https://relay.dev/graphql/connections.htm)<br>
[⁴] [WPGraphQL Resolvers](https://www.wpgraphql.com/docs/graphql-resolvers/)<br>
[⁵] [WPGraphQL - Recipe - Connection to attached media](https://www.wpgraphql.com/recipes/register-connection-to-attached-media/)<br>
[⁶] [WPGraphQL - Recipes - Filter Connection Args](https://www.wpgraphql.com/recipes/filter-connection-args/)<br>
[⁷] [WordPress Development - Set Query Var](https://developer.wordpress.org/reference/functions/set_query_var/)<br>
[⁸] [WordPress Develipment - Get Post Meta](https://developer.wordpress.org/reference/functions/get_post_meta/)<br>
[⁹] [WordPress Development - Difference between meta keys with _ and without _](https://wordpress.stackexchange.com/questions/183858/difference-between-meta-keys-with-and-without)<br>
[¹⁰] [Community Carbon Fields - Get Post Meta For Existing Metadata ](https://community.carbonfields.net/t/get-post-meta-for-existing-metadata/27)<br><br>
7. Create the field `product.posts` and the field `post.product` in the graphql schema: https://www.wpgraphql.com/docs/connections/ (documentation) and https://www.wpgraphql.com/recipes/popular-posts/ (example of similar usage)<br>
   - Following the suggested resource, I have mimicked the `product.image` field creation in number 6, with a few changes.
   - By trying to run the same `get_post_meta` logic, no results were rendered. The `carbon_get_the_post_meta` function proved to be useful, but rendered the results "backwards", meaning, it showed the posts/products when they were <i>not</i> related to the product/post, but available to be added to posts. After several hours of research, I was unable to invert the array to show only the posts already linked with this method.<br>
   - However, in this research, I came across the `meta_value` being equal to the post id. Then, I tried to simplify setting the query argument to `meta_value = $source.ID` so it would fetch the source post/product id that is inside a specific product/post and it worked perfectly.<br><br>
Sources:<br>
[¹] [Community Carbon Fields - Querying Association Fields](https://community.carbonfields.net/t/querying-association-fields/53/2)<br>
[²] [WordPress Developer - Set Query Var](https://developer.wordpress.org/reference/functions/set_query_var/)<br>


## Challenge plus!

Find a way to auto activate the theme and the plugins after installing the dependencies.<br>

Sources:<br>
[¹] [Auto activating themes and plugins upon wordpress installation](https://code.tutsplus.com/tutorials/how-to-activate-plugins-themes-upon-wordpress-installation--cms-23551)<br>
[²] [Stack Exchange Wordpress Development - Initialization Script for "Standard" Aspects of a WordPress Website?](https://wordpress.stackexchange.com/questions/1714/initialization-script-for-standard-aspects-of-a-wordpress-website/1715#1715)<br>

## Further improvements

- After thorough testing, I realized the association field performs what was first required. However, when querying the DB during the two-way data binding attempt, I acknowledged that altough in each post I could only associate it with one product, I could associate many products to the same post, which, ultimately, indirectly enables the behavior of having more than one product per post.

