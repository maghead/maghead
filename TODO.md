TODO
====

- [ ] Move data array into model properties.
    - [x] This require getData() method to collect all property value.
    - [x] Getter should inflate the value from property
    - [ ] Setter should deflate the value from the value.
    - [x] The find method should simply return the record instead of
      the result. Reason: we don't wrap the query logic with try&catch
      block now. errors will throw if something happens.
    - [ ] The create method should simply return the result (we can
      load the result later)
            $ret = $book->create();
            $newBook = $book->createAndLoad();

- [ ] Move CRUD operation from modal class to ModelActions class.
- [ ] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.
- [ ] Support node definition
- [ ] QueryDispatcher

