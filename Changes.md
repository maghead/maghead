Changes
=======

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
