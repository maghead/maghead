# Sharding TODO



## Sharding

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


- [x] Implement shards() factory method on model class:

    $shards = Book::shards(); // returns Shards of the model.

- [x] Implement dispatch method on ShardCollection to support the following use case:

    // Dispatch to one repository by $key and create the record in the repository.
    Order::shards()->dispatch($key)->repo(StoreRepo::class)->create($args);

    The use case above dispatch the shard before BaseModel::create method dispatch the shard.

    $order = Order::shards()->loadByPrimaryKey(77890);
    $order = Order::shards()->loadByPrimaryKey('569f21d7-fcad-49bf-99dd-795be631f984');

- [ ] BroadcastQuery
    - Broadcast SQL statement to all shards.
- [ ] Extract SQL building method for create (insertion)
- [ ] Extract SQL building method for update
- [ ] Extract SQL building method for delete
- [ ] Map SQL and arguments to QueryWorker and then reduce the result...
- [ ] QueryDispatcher
    - [ ] Given a query, return Repo objects with different connections and
            run queries on these nodes.

- Extra
    - [ ] Shard by Range
    - [ ] Virtual Shards (by using larger key space)


## Model operations

- [ ] Generate setter methods on BaseModel.
- [ ] Setter should deflate the value from the value.
- [x] Refactor Connection create functions to different Connector.



- [ ] Move CRUD operation from modal class to ModelActions class.
- [x] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.
