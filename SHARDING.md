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


### TODO

- [x] Move data array into model properties.
    - [x] This require getData() method to collect all property value.
    - [x] Getter should inflate the value from property
    - [x] The find method should simply return the record instead of
      the result. Reason: we don't wrap the query logic with try&catch
      block now. errors will throw if something happens.
    - [x] The create method should simply return the result (we can
      load the result later)
            $ret = $book->create();
            $newBook = $book->createAndLoad();

        Fix all test cases according to the new return type

        - BaseModel::create now returns Result object directly and don't reload
        created data to the object itself.

        - BaseModel::find now returns the found record instead of load the data 
        to the object itself.

    - [x] `Result->getKey()` to make the API consistent.
    - [x] `BaseModel::find` is now static method.
    - [x] `BaseModel::createAndLoad` is now static method.
    - [x] Inflate by the isa in the automatic generated accessor.
    - [x] Cache query driver
    - [x] `BaseModel::getReadConnection` removed.
    - [x] `::load` method refactor.
    - [x] `::create` method refactor.

- [x] Generate BaseRepo Class
    - [x] Add `::repo($ds)` helper to create repo with specific
          connection.
    - [x] Add `::repo($write, $read)` helper to create repo with specific
          connection.
    - [x] Add `::repo($write)` ($read = $write)
    - [x] Add `::repo()` (using default connections)
    - [x] Add `::masterRepo()` (using default connections)
    - [x] Move `find*` method to Repo class.
    - [x] Move `create` method to Repo class.
    - [x] Move `delete` method to Repo class.

- [x] Add facelet static methods on BaseModel to connect BaseRepo methods.

- [ ] Generate setter methods on BaseModel.
- [ ] Setter should deflate the value from the value.

- [x] Refactor Connection create functions to different Connector.

- [ ] Sharding Support
    - [x] Support node config definition
    - [x] Support shard mapping config definition
    - [x] Add basic shard dispatcher base on Flexihash
    - [x] Add "prepare" method generator for Repo classes.
    - [x] Use repo class in BaseCollection
    - [x] Rename "getMasterConnection" to "getMasterConnection".
    - [x] Shard method
    - [x] Shard by Hash
    - [x] Shard::createRepo() return a repo object with read/write connection.
    - [x] Rename "default" config to "master".
    - [x] ShardDispatcher
        - [x] Select shard for write
        - [x] Select shard for read.
    - [x] PDO connection ctor extractor method (extract connection parameters to array)
        - Used DS to create worker connection
    - [x] QueryMapper
        - [x] Gearman
            - gearman extension <https://github.com/wcgallego/pecl-gearman/>
        - [x] Pthread Query Mapper
            - [x] Create pthread worker with the connection parameters
            - [x] Provide query method to query SQL
            - [x] Merge result.
        - [x] Update reducer extension to convert string values.
    - [ ] BroadcastQuery
        - Broadcast SQL statement to all shards.
    - [ ] Extract SQL building method for create (insertion)
    - [ ] Extract SQL building method for update
    - [ ] Extract SQL building method for delete
    - [ ] Map SQL and arguments to QueryWorker and then reduce the result...
    - [ ] QueryDispatcher
        - [ ] Given a query, return Repo objects with different connections and
              run queries on these nodes.

    - [ ] Shard by Range
    - [ ] Virtual Shards (by using larger key space)

    $shards = Book::shards(); // returns Shards of the model.

    // Dispatch to one repository by $key and create the record in the repository.
    Order::shards()->dispatch($key)->create($args);

    // Automatically dispatch the repository by the "key" defined in $args.
    Order::shards()->create($args);

    $order = Order::shards()->find(77890);

    $order = Order::shards()->find('569f21d7-fcad-49bf-99dd-795be631f984');

    - [ ] Global Table behaviour
        - [ ] Spread Create method
            - Create records across different shards.
        - [ ] Spread Update method
            - Update records across different shards.
        - [ ] Spread Delete method
            - Delete records across different shards.



- [ ] Move CRUD operation from modal class to ModelActions class.
- [x] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.


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

A shard mapping config looks like this:

    'M_store_id' => [
        'tables' => ['orders'], // This is something that we will define in the schema.
        'key' => 'store_id',
        'shards' => [ 's1', 's2' ],
        'chunks' => [
            'c1' => ['shard' => 's1', 'dbname' => 'db_c1'], // the dbname may override the dbname in the DSN of the shard.
            'c2' => ['shard' => 's1', 'dbname' => 'db_c2'],
            'c3' => ['shard' => 's1', 'dbname' => 'db_c3'],
            'c4' => ['shard' => 's2', 'dbname' => 'db_c4'],
            'c5' => ['shard' => 's2', 'dbname' => 'db_c5'],
        ],
        'method' => 'hash',
    ],

By default, the 'chunks' list is empty. So you have to create chunks first.


## Iterating shards of a Model

    $shards = Book::shards(); // returns Shards of the model.
    foreach ($shards as $shardId => $shard) {
        $shard; // instance of Maghead\Sharding\Shard
    }


## Shard Manager

Shard Manager manages the connections to shards. the connections used by shards
are defined in the `data_source` section in the config file.

To create the shard manager, you need two arguments: Config object and
ConnectionManager instance.

    use Maghead\Sharding\Manager\ShardManager;

    $shardManager = new ShardManager($config, $connectionManager);

To get the shards used by one shard mapping, simply call `getShardsOf` with the
related shard mapping ID:

    $shards = $shardManager->getShardsOf('M_store_id');

The returned `$shards` is a `Maghead\Sharding\ShardCollection` instance.

To create the shard dispatcher, simply invoke `createDispatcher` on the shard
collection instance:

    $dispatcher = $shards->createDispatcher();

ShardManager also provides one factory method to help you create the shard dispatcher.
To create the shard dispatcher, you need to pass the shard mapping ID to return
the dispatcher that dispatches the shard node for specific sharding rule, e.g.:

    $dispatcher = $shardManager->createShardDispatcherOf('M_store_id');





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
        'key' => 'store_id',
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

