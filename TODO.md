TODO
====

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

- [ ] Refactor Connection create functions to different Connector.

- [ ] Sharding Support
    - [x] Support node config definition
    - [x] Support shard mapping config definition
    - [x] Add basic shard dispatcher base on Flexihash
    - [x] Add "prepare" method generator for Repo classes.
    - [x] Use repo class in BaseCollection
    - [x] Rename "getDefaultConnection" to "getMasterConnection".
    - [ ] Shard method
        - [ ] Shard by Hash
        - [ ] Shard by Range
    - [ ] Shard::createRepo() return a repo object with read/write connection.
    - [x] Rename "default" config to "master".
    - [ ] Global Table behaviour
        - [ ] Spread Create method
            - Create records across different shards.
        - [ ] Spread Update method
            - Update records across different shards.
        - [ ] Spread Delete method
            - Delete records across different shards.
    - [ ] Sharded Table behaviour
        - [ ] Select shard to insert.
        - [ ] Select shard to update.
        - [ ] Select shard to delete.

    - [ ] QueryDispatcher
        - [ ] Given a query, return Repo objects with different connections and
              run queries on these nodes.

    $shards = Book::shards(); // returns Shards of the model.

    // Dispatch to one repository by $key and create the record in the repository.
    Order::shards()->dispatch($key)->create($args);

    // Automatically dispatch the repository by the "key" defined in $args.
    Order::shards()->create($args);

    $order = Order::shards()->find(77890);

    $order = Order::shards()->find('569f21d7-fcad-49bf-99dd-795be631f984');


- [ ] Move CRUD operation from modal class to ModelActions class.
- [x] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.

