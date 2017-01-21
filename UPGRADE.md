UPGRADE TO 4.0.x
======================

1. data inflating will only happen when using accessor method.

    $book->is_published; // will return the raw value from database.
    $book->isPublished() // will return Boolean value (true or false)

2. boolean columns will generate `isXXX` accessors.

    "published" => "isPublished()"
    "is_published" => "isPublished()"

3. `BaseModel->find` now returns record object instead of Result object.

    $foundBook = $book->find(321);

4. Replace `create` with `createAndLoad`:

    $author = new Author;
    $author = $author->createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

5. Fix `create` and `find` logics:
  - BaseModel::create now returns Result object directly and don't reload
    created data to the object itself.
  - BaseModel::find now returns the found record instead of load the data 
    to the object itself.
6. `BaseModel::deflate` method is removed.

7. `BaseModel::deflateData` method is removed.

8. `BaseModel::getSchema` method is now static.

9. Remove arguments from beforeDelete and afterDelete (the user may get the data from the data object directly)

10. `createOrUpdate` is now renamed to `updateOrCreate`.

11. `BaseModel::load` is now static method.

12. `BaseModel::find` is now static method.

UPGRADE TO 2.0
======================

- `setData()`, `getData()` were renamed to `setStashedData()`, `getStashedData()`
- `column::notNull` changed to `notNull()`
- Renamed `Relationship::order()` to `Relationship::orderBy()`

WIP
======================

- Rebuild `SQLBuilder\Universal\SelectQuery` from Relationship with the `filter` and `where`.
- Add a test case for relationship with custom where conditions, filter and order by and group by.
