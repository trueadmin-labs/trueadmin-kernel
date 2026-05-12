# TrueAdmin Kernel

Reusable backend kernel package for TrueAdmin.

This package contains backend primitives that are stable across projects: error codes, HTTP attributes, actor context, data-permission value objects, operation-log attributes, and CRUD query value objects.

Application-specific controllers, repositories, request classes, database resources, business services, and module/plugin facts stay in the template application or in each plugin.

The kernel is shared by Admin, Client, and Open entrypoints. End-specific authentication, permission policy, request validation, routing, menu resources, and database-backed implementations stay in the application or plugin that owns them.

## CRUD Query Objects

`TrueAdmin\Kernel\Crud` provides the standard backend representation of the list-query protocol:

- `CrudQuery`
- `CrudFilterCondition`
- `CrudSortRule`
- `CrudOperator`
- `CrudSortOrder`

HTTP parsing and project validation still belong to the application request layer. The kernel only owns the normalized value objects.
