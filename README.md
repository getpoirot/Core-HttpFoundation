# Core-HttpFoundation
Http Foundation Module For Poirot.

## Helpers

URL:

```php
\Module\HttpFoundation\Actions::url(
    null
    , []
    , Url::DEFAULT_INSTRUCT|Url::APPEND_CURRENT_REQUEST_QUERY|Url::WITH_GIVEN_QUERY_PARAMS
    , ['query_params' => ['sort' => 'PerPrice']]
);

\Module\HttpFoundation\Actions::url('main/apanaj.admin/users/manage', ['page' => null])
// or
\Module\HttpFoundation\Actions::url('main/apanaj.admin/users/manage', [], Url::DEFAULT_INSTRUCT & ~Url::MERGE_CURRENT_ROUTE_PARAMS)
```
