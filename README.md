# TrueAdmin Kernel

Reusable backend kernel package for TrueAdmin.

This package contains backend primitives that are stable across projects: error codes, HTTP attributes, actor context, data-permission value objects, operation-log attributes, pagination/API envelope primitives, and CRUD query protocol classes.

Application-specific controllers, repositories, request classes, database resources, business services, and module/plugin facts stay in the template application or in each plugin.

The kernel is shared by Admin, Client, and Open entrypoints. End-specific authentication, permission policy, request validation, routing, menu resources, and database-backed implementations stay in the application or plugin that owns them.

## CRUD Query Protocol

`TrueAdmin\Kernel\Crud` provides the standard backend representation of the list-query protocol:

- `CrudQuery`
- `CrudFilterCondition`
- `CrudSortRule`
- `CrudOperator`
- `CrudSortOrder`
- `CrudQueryRequest`

`CrudQueryRequest` parses the standard bracket query protocol into `CrudQuery`. It validates protocol shape only; resource-level field and operator allow-lists still belong to the application repository or query applier.

## HTTP Primitives

The package also provides:

- `TrueAdmin\Kernel\Http\Request\FormRequest`
- `TrueAdmin\Kernel\Http\ApiResponse`
- `TrueAdmin\Kernel\Pagination\PageResult`

Projects can extend these classes or replace application-level bindings without modifying package source.
