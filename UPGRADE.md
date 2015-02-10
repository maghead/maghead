UPGRADE TO 2.0
======================

- `setData()`, `getData()` were renamed to `setStashedData()`, `getStashedData()`
- `column::notNull` changed to `notNull()`
- Renamed `Relationship::order()` to `Relationship::orderBy()`

WIP
======================

- Rebuild `SQLBuilder\Universal\SelectQuery` from Relationship with the `filter` and `where`.
- Add a test case for relationship with custom where conditions, filter and order by and group by.
