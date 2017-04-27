## Sharding


Gearman

    phpbrew ext install github:wcgallego/pecl-gearman

## Reference

Consistent Hash

- chash extension <https://code.google.com/archive/p/chash/source/default/source>

TiDB

- How we build tidb <https://pingcap.github.io/blog/2016/10/17/how-we-build-tidb/>

MariaDB

- MaxScale <https://mariadb.com/products/mariadb-maxscale>

HBase + Phoenix

- Phoenix hbase <https://www.infoq.com/news/2013/01/Phoenix-HBase-SQL>
- Data aggregation in hbase <https://www.panaseer.com/2016/04/11/data-aggregation-in-hbase/>

Doctrine

- <http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/index.html>
- <http://doctrine.readthedocs.io/en/latest/en/manual/unit-testing.html>
- <http://doctrine.readthedocs.io/en/latest/en/manual/dql-doctrine-query-language.html#indexby-keyword>

Load Balancing

- <https://github.com/WMSPanel/load-balancer/blob/master/load-balancer/nimble_lb.php>
- Nginx 4 load balancing <https://www.nginx.com/resources/glossary/layer-4-load-balancing/>

Sphinx Document

- <http://www.sphinx-doc.org/en/1.5.1/theming.html>
- <http://www.sphinx-doc.org/en/1.5.1/rest.html>
- <http://thomas-cokelaer.info/tutorials/sphinx/rest_syntax.html>
- <http://openalea.gforge.inria.fr/doc/openalea/doc/_build/html/source/sphinx/rest_syntax.html>




## Instance

To define your database instance connections, simply define:

```
instance:
  local:
    dsn: 'mysql:host=localhost'
    user: root
```

Please note that the instance definition doesn't include database name.

## Shard Mapping

A shard mapping defines how do we allocate data on different shards.

A shard mapping config may consist of:

1. `tables` table names that use this shard rule.
2. `key` the shard key on each table we defined in `tables`.
3. `shards` the data nodes that used for this shard rule.
4. `chunks` the slices cross the shards used in this rule.
5. `hash` or `range` the shard method used in this rule.

## Chunks

One SG (Shard Group) consists of nodes for write and nodes for read.
one SG (Shard Group) may contains one or more chunks.

e.g., one shard may contain one or more chunks:

    Shard1 [ Chunk1, Chunk2, Chunk3, ... ]
    Shard2 [ Chunk513, Chunk514, Chunk515, ... ]
    ...

Each chunk has its own chunk name (for its database) and the shard ID:

    c1 => ['shard' => 's1', 'dbname' => 'bossnet_c1'],
    c2 => ['shard' => 's1', 'dbname' => 'bossnet_c2'],
    c3 => ['shard' => 's1', 'dbname' => 'bossnet_c3'],

Every chunk maybe applied to the different nodes in the same shard. e.g.

    c1 on the node for read in shard s1.

The above operation select the read node from c1's shard `s1`. for read.

And so, when dispatching a record, we select the chunk first, and then select
the node for read/write on the related shard.

A internal shard mapping config looks like this:

    'M_store_id' => [
        'key' => 'store_id',
        'shards' => [ 's1', 's2' ],
        'method' => 'hash',
        'chunks' => [
            536870912  => [ 'shard' => 'node1'],
            1073741824 => [ 'shard' => 'node1'],
            1610612736 => [ 'shard' => 'node1'],
            2147483648 => [ 'shard' => 'node2'],
            2684354560 => [ 'shard' => 'node2'],
            3221225472 => [ 'shard' => 'node2'],
            3758096384 => [ 'shard' => 'node3'],
            4294967296 => [ 'shard' => 'node3'],
        ],
    ],

By default, the 'chunks' list is empty. So you have to create chunks first.


## Iterating shards of a Model

    $shards = Book::shards(); // returns Shards of the model.
    foreach ($shards as $shardId => $shard) {
        $shard; // instance of Maghead\Sharding\Shard
    }


## Shard Manager

Shard Manager manages the connections to shards. the connections used by shards
are defined in the `databases` section in the config file.

To create the shard manager, you need two arguments: Config object and
ConnectionManager instance.

    use Maghead\Sharding\Manager\ShardManager;

    $shardManager = new ShardManager($config, $dataSourceManager);

To get the shards used by one shard mapping, simply call `loadShardCollectionOf` with the
related shard mapping ID:

    $shards = $shardManager->loadShardCollectionOf('M_store_id');

The returned `$shards` is a `Maghead\Sharding\ShardCollection` instance.

To create the shard dispatcher, simply invoke `createDispatcher` on the shard
collection instance:

    $dispatcher = $shards->createDispatcher();

## Shard Operations

### Defining Shard Mapping

To add a new shard mapping config:

    maghead shard mapping add [mappingId] \
            --key store_id \
            --hash \
            --shards "s1,s2,s3" \
            --chunks 32

To remove a shard mapping config:

    maghead shard mapping remove [mappingId]

### Allocating Shard

Create an empty shard with the corresponding schema.

    maghead shard allocate \
        --mapping [mappingIds] \
        --instance [instanceId] \
        [shardId]

To use the allocate operation:

    $config = ConfigLoader::loadFromFile('.../config.yml');
    $o = new AllocateShard($config);
    $o->allocate('local', 't1', 'M_store_id');

The above command allocate a new node `t1` on `local` instance and initialize
the schemas related to the shard mapping.

Where `local` is the instance ID, `t1` is the node ID for the new shard, and
`M_store_id` is the shard mapping ID defined in the config file.


### Cloning Shard

Clone an existing shard on the same instance.

    maghead shard clone --instance [instanceId] [source shard] [dest shard]

Before cloning the shard, be sure to have the mysql instance defined in the config.

The ShardCloning operation uses mysqldbcopy to copy the database.

To clone a shard in PHP code:

    $config = ConfigLoader::loadFromFile('.../config.yml');
    $o = new CloneShard($config);
    $o->setDropFirst(true);
    $o->clone('local', 't2', 'master');

The above code creates a new node `t2` and copy the data from `master`.

### Moving Shard

Move a shard to an instance:

    maghead shard move --instance [instanceId] nodeId

### Pruning Shard

Prune all rows that doesn't belong to the shard.

    maghead shard prune --mapping [mappingId] [shard]

Shard pruning finds all schema related to the shard mapping, and then iterate
each collection to prune the rows that does not belong to the shard itself.

    $config = ConfigLoader::loadFromFile('.../config.yml');
    $o = new PruneShard($config, $logger);
    $o->prune('M_store_id', $schemas, 't1');

## CRUD Operations

### Global Table CRUD Operations

#### Create operation on global tables

To create a new record on the global table, the repository will first find the
master node to insert the record to get the primary key of the record.

The second step will be: inserting the newly created record with primary key
into all shards used by the shard mapping defined in the schema.

The API is the same as when the ORM doesn't use sharding:

    $ret = Store::create([ 'name' => 'Shop III', 'code' => 'BS001' ]);

#### Update operation on global tables

To update a record on the global table, the repository will first find the
master node to update the record with the primary key.

The second step will be: updating the record with new values by primary key in
all shards used by the shard mapping defined in the schema.

The API is the same as when the ORM doesn't use sharding:

    $ret = $store->update([ 'code' => 'BS002' ]);

#### Delete operation on global tables

To delete a record on the global table, the repository will first find the
master node to insert the record to get the primary key of the record.

The second step will be: delete all records existed in all shards used by the
shard mapping defined in the schema.


## Chunks

### Creating Chunks

Initially, one shard might only have one chunk (the shard itself). To split
the existing one chunk into many chunks, you must run `chunks:init` command to 
initialize the chunks for a shard mapping:

    maghead shard chunks:init M_store_id 1024

the command above will get the existing shards from `M_store_id`,

    'M_store_id' => [
        'tables' => ['orders'], // This is something that we will define in the schema.
        'key' => 'store_id',
        'shards' => [ 's1', 's2' ],
        'chunks' => [ ],
        'hash' => [],
    ],

The chunk manager then iterate the shards, and the create chunks on each shard,
a new chunk list will be allocated in the shard mapping config:

    'M_store_id' => [
        'tables' => ['orders'], // This is something that we will define in the schema.
        'key'    => 'store_id',
        'shards' => [ 's1', 's2' ],
        'chunks' => [
            'c1' => ['shard' => 's1', 'dbname' => 'db_c1'], // the dbname may override the dbname in the DSN of the shard.
            'c2' => ['shard' => 's1', 'dbname' => 'db_c2'],
            'c3' => ['shard' => 's1', 'dbname' => 'db_c3'],
            'c4' => ['shard' => 's2', 'dbname' => 'db_c4'],
            'c5' => ['shard' => 's2', 'dbname' => 'db_c5'],
        ],
        'hash' => [
            // pre-allocate more slices
            't0' => 'c1',
            't1' => 'c1',
            't2' => 'c1',
            't3' => 'c2',
            't4' => 'c2',
            't5' => 'c2',
            // ...
        ],
    ],

The chunk manager will then create the database schema on each chunk.

Here is the steps of creating a new chunk:

1. Get shards from the shard mapping
2. recaluclate the chunks for expanding chunks.
3. For each new chunk, we give it a new dbname.
4. Get the connection DSN from the shard.
5. For each connection, create the database.
6. Run schema initializer on each connection.

### Spliting Chunks

Splitting is a process that keeps chunks from growing too large.

When a chunk grows beyond a specified chunk size, or if the number of documents
in the chunk exceeds Maximum Number of Documents Per Chunk to Migrate, Maghead ORM
splits the chunk based on the shard key values the chunk represent.

A chunk may be split into multiple chunks where necessary.

Splitting chunk is a relatively heavier task. It splits one chunks into two or
more chunks to reduce the chunk size.

    CREATE TABLE new_chunk.orders LIKE orig_chunk.orders;
    INSERT INTO new_chunk.orders SELECT * from orig_chunk.orders;

Chunk Split must be done on one mysql connection. (cross databases on the same
machine)

To split a chunk, please run the command below:

    maghead shard chunks:split c1 4

The command above will split the chunk `c1` into 4 chunks by its shard targets.



## Consistent Hashing libraries

- dailymotion chash https://github.com/dailymotion/chash
- flexihash https://github.com/pda/flexihash

