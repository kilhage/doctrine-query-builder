# doctrine-query-builder

Very powerful query builder, perfect to use when building api's or have the need to build very flexible queries

Supports unlimited nested AND/OR groups, most of the common SQL operators, joins, order by, distinct etc

### Requirements:
- PHP >= 5.6
- Symfony HTTP Foundation > 3.2
- Doctrine ORM > 2.5

### Installation:

With [Composer](https://getcomposer.org/):
```json
{
    "require": {
        "glooby/doctrine-query-builder": "dev-master"
    }
}
```

### Usage:

```php
use \Glooby\Doctrine\QueryBuilder\QueryBuilder;

$repo = $this->getDoctrine()->getManager()->getRepository('AcmeMainBundle:Person');
$qb = new QueryBuilder();

$results = $qb->build($repo, $data)->getQuery()->getResult();

return new JsonResponse($results);
```

### Example data:
```json
{
    "alias": "p",
    "select": ["p.id"],
    "where": {
        "$or": {
            "p.city": {
                "$same": "c.city"
            },
            "p.zipCode": {
                "$same": "c.zipCode"
            },
            "p.street": {
                "$same": "c.street"
            },
        },
        "c.city": {
            "$in": [
                "New York",
                "London"
            ]
        },
        "c.employees": { "$equals": 1 },
        "l.code": 49,
        "p.country": "$not_null",
        "p.phone": "$is_null",
        "c.assets": { "$gte": 1000 },
        "c.turnover": { "$lt": 10000 },
        "t.code": {
            "$in": [1, 2, 3]
        },
        "r.title": {
            "$not_in": ":titles"
        }
    },
    "distinct": true,
    "params": {
        "titles": ["CFO", "CMO"]
    },
    "orderBy": {
        "p.name": "asc"
    },
    "join": {
        "p.roles": "r",
        "r.company": "c",
        "c.trades": {
            "alias": "t",
            "type": "left"
        }
    }
}
```
