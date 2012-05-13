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

Filter:

    $newCollection = $names->filter(function($item) { return true });
    $newCollection->filter(function($item) { 
            return $item->confirmed;
    });

Each:

    $names->each(function($item) {
        $item->update(array( .... ));
    });

To get schema proxy class from collection class:

    $authors = new AuthorCollection;
    $class = AuthorCollection::schema_proxy_class;
    $class = $authors::schema_proxy_class;

To get model class:

    $class = AuthorCollection::model_class;
    $class = $authors::model_class;

Collection pager:

    /* page 1, 10 per page */
    $authors = new AuthorCollection;
    $pager = $authors->pager(1,10);

    $pager = $authors->pager();
    $items = $pager->items();

    $pager->next(); // next page

## Query

    $collection->loadQuery( 'master', 'sql....' , array( ':id' => $id ) );


