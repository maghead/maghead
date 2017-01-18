TODO
====

- [ ] Move data array into model properties.
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
    - [ ] `getReadConnection` could be static method??
    - [ ] Inflate by the isa in the automatic generated accessor.
    - [ ] Setter should deflate the value from the value.
    - [ ] `::load` method refactor.
- [ ] Generate Repo Class
    - [ ] Add `::repo($ds)` helper to create repo with specific
          connection.


- [ ] Move CRUD operation from modal class to ModelActions class.
- [ ] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.
- [ ] Support node definition
- [ ] QueryDispatcher

