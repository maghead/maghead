UPGRADE TO 4.0.x
======================

1. data inflating will only happen when using accessor method.

    $book->is_published; // will return the raw value from database.
    $book->isPublished() // will return Boolean value (true or false)

2. boolean columns will generate `isXXX` accessors.

    "published" => "isPublished()"
    "is_published" => "isPublished()"

3. `BaseModel::load` now returns record object instead of returning a Result object:

    $foundBook = Book::load(321);

4. Replace `create` with `createAndLoad`:

    $author = new Author;
    $author = $author->createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

5. Fix `create` and `find` logics:
  - BaseModel::create now returns Result object directly and don't reload
    created data to the object itself.
  - BaseModel::load now returns the found record instead of load the data 
    to the object itself.

6. `BaseModel::deflate` method is removed.

7. `BaseModel::deflateData` method is removed.

8. `BaseModel::getSchema` method is now static.

9. Remove arguments from beforeDelete and afterDelete (the user may get the data from the data object directly)

10. `createOrUpdate` is now renamed to `updateOrCreate`.

11. `BaseModel::load` is now static method.

12. Trigger methods like `beforeCreate`, `beforeUpdate`, `afterUpdate` are moved to BaseRepo.

13. `lockWrite` => `BaseRepo::writeLock`, `lockRead` => `BaseRepo::readLock`

14. Renamed `BaseRepo::load` to `BaseRepo::loadWith`.

15. `load` is now a generic method for both primary key and conditions in array.

16. Added `BaseRepo::loadByKeys` for load with keys.

17. `BaseModel::loadByPrimaryKey` and `BaseRepo::loadByPrimaryKey` are added.

18. `BaseModel::find` is removed.

19. `BaseCollection::loadQuery` method is now deprecated.

### ConfigLoader

1. ConfigLoader::loadFromFile -> ConfigLoader::loadFile as a static method and 
    return `Config` object.


### Sharding

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







UPGRADE TO 2.0
======================

- `setData()`, `getData()` were renamed to `setStashedData()`, `getStashedData()`
- `column::notNull` changed to `notNull()`
- Renamed `Relationship::order()` to `Relationship::orderBy()`

WIP
======================

- Rebuild `SQLBuilder\Universal\SelectQuery` from Relationship with the `filter` and `where`.
- Add a test case for relationship with custom where conditions, filter and order by and group by.
