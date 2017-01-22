<?php
use Maghead\CollectionFilter\CollectionFilter;
use TestApp\Model\PostCollection;
use TestApp\Model\Post;

function dumpExpr($expr, $level = 0) {
    echo str_repeat(' ', $level) , get_class($expr) , ": " , $expr->op[0], $expr->op[1] , $expr->op[2] , "\n";
    if ( $expr->childs ) {
        foreach($expr->childs as $child) {
            dumpExpr($child, $level + 1);
        }
    }

}

/**
 * Generate SQL statement like this:
 *
 * SELECT m.title, m.content, m.status, m.id FROM posts m  WHERE status = 
 * published AND status = draft AND content like %foo% AND content like 
 * %bar% AND created_on BETWEEN '2011-01-01' AND '2011-12-30'
 *
 */
class CollectionFilterTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        return;
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
        $filter->defineInSet('created_by', CollectionFilter::Integer );

        $collection = $filter->apply([ 
            'status' => ['published','draft'],
            'content' => ['foo', 'bar'],
            'created_on' => [ '2011-01-01', '2011-12-30' ],
            'created_by' => [1,2,3,4],
        ]);
        ok($collection);

        ok( $collection->toSql());
        // echo $collection->toSql();

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

