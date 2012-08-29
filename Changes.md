Changes
=======

## develop branch

### Important changes

- Fixed MySQL connection init command (set names utf8)
- Improve Collection join method (detects relationship from model schema, and build the join query)

### Minor changes

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
