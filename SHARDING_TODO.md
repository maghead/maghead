# Sharding TODO

## Config improvement

- [ ] Add instances definitions to config class.
- [ ] Add instances connection support to ConnectionManager.
- [ ] Use mongodb for centralized shared config (load instance definitions and shard definitions)

## Shard Operations

- [ ] Allocate Shard
- [ ] Clone Shard
- [ ] Prune Shard

## Storage Optimization

- [ ] Paritions per shard

## Aggregation Query

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


## Model operations

- [ ] Generate setter methods on BaseModel.
- [ ] Setter should deflate the value from the value.
- [x] Refactor Connection create functions to different Connector.



- [ ] Move CRUD operation from modal class to ModelActions class.
- [x] Add connection parameter to all the CRUD methods
- [ ] Add setter type signature support to the class method generator.
- [ ] Validate isa type when setting value via setter method.
- [ ] ??? Remove typeConstraint checking from modal method code.
