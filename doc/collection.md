Collection
==========

Iterating:

<?php
    $authors = new AuthorCollection;
    foreach( $authors as $a ) {
        echo $a->id, $a->name;
    }

    $items = $authors->items();  // returns an array

    $size = $authors->size();  // item size
?>

Where condition:

<?php
    $names = new NameCollection;
    $names->where()
        ->equal('name','Foo')
        ->groupBy('name','address');
?>

## Filter

    $newCollection = $names->filter(function($item) { return true });
    $newCollection->filter(function($item) { 
            return $item->confirmed;
    });

## Each

    $names->each(function($item) {
        $item->update(array( .... ));
    });


## Classes

To get schema proxy class from collection class:

    $authors = new AuthorCollection;
    $class = AuthorCollection::SCHEMA_PROXY_CLASS;
    $class = $authors::SCHEMA_PROXY_CLASS;

## To get model class

    $class = AuthorCollection::MODEL_CLASS;
    $class = $authors::MODEL_CLASS;

## Collection pager

    /* page 1, 10 per page */
    $authors = new AuthorCollection;
    $pager = $authors->pager(1,10);

    $pager = $authors->pager();
    $items = $pager->items();

    $pager->next(); // next page

## Query

    $collection->loadQuery( 'master', 'sql....' , array( ':id' => $id ) );


## Join

Left join table

    $cates->addSelect( array( 'count(forum_posts.id)' => 'posts_count' ) );
    $cates->join( 'forum_posts' , 'LEFT' )
            ->on()
                ->equal('m.id',array('forum_posts.category_id'));


Left join with model

    $posts    = $category->posts;
    $posts->join( new Member , 'LEFT' , 'member' );   // find relation id automatically.

