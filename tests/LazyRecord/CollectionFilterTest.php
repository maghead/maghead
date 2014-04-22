<?php
use LazyRecord\CollectionFilter;
use tests\PostCollection;
use tests\Post;

/**
 * Generate SQL statement like this:
 *
 * SELECT m.title, m.content, m.status, m.id FROM posts m  WHERE status = published AND status = published1 AND content like %foo% AND content like %foo%1 AND created_on BETWEEN '2011-01-01' AND '2011-12-30'
 *
 */
class CollectionFilterTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        /*
        $post = new Post;
        $ret = $post->create([ 
            'title' => 'title content',
            'content' => 'foo bar',
            'status' => 'published',
        ]);
        ok($post->id, $ret);
        */
        $posts = new PostCollection;
        $filter = new CollectionFilter($posts);
        ok($filter);

        $filter->defineEqual('status', [ 'published', 'draft' ]);
        $filter->defineContains('content');
        $filter->defineRange('created_on', CollectionFilter::String );

        $collection = $filter->apply([ 
            'status' => ['published','draft'],
            'content' => ['foo', 'bar'],
            'created_on' => [ '2011-01-01', '2011-12-30' ],
        ]);
        ok( $collection );

        echo $collection->toSql();



        /*
        // set up valid status
        $filter->defineContains('content');
        $filter->defineContains('content');
        $filter->defineStartWith('content');
        $filter->defineEndWith('content');

        $filter->defineInset('member_id');
        $filter->defineInset('member_id', [1,2,3,4]);

        $filter->defineEqual('category_id', CollectionFilter::Integer ); // must be a integer type

        $filter->defineEqual('is_deleted', CollectionFilter::Boolean ); // must be a integer type
        */
    }
}

