Changes
=======

## v3.0.1

- Increase MySQL 5.7 compatibility.
- Fixed Metadata Mixin Schema timestamp column definition.
- Fixed Reivision Mixin Schema timestamp column definition.
- Be sure to put timestamp column with default current_timestamp to the last.

## v3.0

- Added `--backup` option to migration command.
- Added mysql backup support tool.
- Added predefined uuid primary key column.

## v2.2.3

- Fixed loadOrCreate / updateOrCreate method checking.

## v2.2.1

- Fixed #115: Added tests for auto-generated accessor method. 
- Fixed #116: required method should set notNull = true
- Fixed #116: use get_object_vars to export all column properties

## v2.2.0

- Fixed boolean insertion and selection
- Changed data source config to the structure below (backward compatible change):

```yml
data_source:
  default: master
  nodes:
    master:
      driver: ...
      host: ...
      port: ...
      user: ...
      pass: ...
    slave1:
      driver: ...
      host: ...
      port: ...
    salve2:
      driver: ...
      host: ...
      port: ...
```


- Added DSN parser.
- Added `db create` command.
- Added `db drop` command.
- Added `db recreate` command.
- Removed dbutil from dependency
- Removed create-db command

## v2.1.6

Merged PR:

- Commit 9b19a46: Merge pull request #100 from azole/master

   Fixes #99 - pass host to dbutil

## v2.1.5

- Upgrade cliframework to ~2.6

## v2.1.4

- new migration tools
- lazy migrate -U option is removed.  use `lazy migrate automatic` instead.

Bug Fixes:

- Upgraded cliframework to 2.5.3
- Support actionkit options.


Merged PRs:

- Commit 6cfac73: Merge pull request #96 from `Ronmi/add_impl`

   Let generated models and collections can implement some interfaces

- Commit 7de10b3: Merge pull request #95 from `Ronmi/master`

   Change the string matching process to fit sqlite way.


## v1.14

- Added Collection Filter (define filter types, valid values in backend and apply filters from HTTP Request)

## v1.10

- Added index support, you can now add index attribute on your column.
- Added foreign key (references) generator for the `build-sql` command.
- Upgraded SQLBuilder to use the index builder.
- Added option parameters to mixin schema, now you can call mixin with options to 
  customize your mixin schema.
- Support mixin schema methods to BaseModel object, you can now define your mixin methods
  for the mixed models.

## v1.8.11, v1.8.12

- Fix bugs for schema/class/model
- Add enum support for mysql

## v1.8.7

- Fix required column validation
- Improve validation

## v1.8.5

Important changes
- Fixed MySQL connection init command (set names utf8)
- Improve Collection join method (detects relationship from model schema, and build the join query)
- Improve Validations, Add support of ValidationKit.

Minor changes
- Add closure support to default value schema column
- Add closure support to validValues schema column

## v1.6.3

- Move config files into db/config/
- Provide db/migration
- Support dynamic schema in model class.

## v1.5.0 

Date: 一  5/14 17:06:35 2012

- Add typeConstraint attribute to column
- Improve init-conf command
