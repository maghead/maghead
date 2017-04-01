# Sharding TODO

## Config improvement

- [x] Add instances definitions to config class.
- [x] Add instances connection support to ConnectionManager.
- [ ] Add instance app user setup config

        setup:
          app_user:
            user: myapp
            pass: myapp

- [ ] Use mongodb for centralized shared config (load instance definitions and shard definitions)

## Shard Operations

- [x] Allocate Shard
- [x] Remove Shard
- [ ] CloneShard (use mysqldbcopy)
  - [ ] Convert DSN into command string

    mysqldbcopy --source=user:pass@host:port:socket --destination=user:pass@host:port:socket orig_db:new_db

    mysqldbcopy --source=root@localhost:/opt/local/var/run/mysql56/mysqld.sock \
                --destination=root@localhost:/opt/local/var/run/mysql56/mysqld.sock \
                shade_src:shade_dst


- [ ] Split Shard
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
