<?php

namespace Glooby\Doctrine\QueryBuilder;

/**
 * @author Emil Kilhage
 */
class Param
{
    const LIMIT = 'limit';
    const OFFSET = 'offset';
    const ORDER_BY = 'orderBy';
    const GROUP_BY = 'groupBy';
    const WHERE = 'where';
    const JOIN = 'join';
    const ALIAS = 'alias';
    const PARAMS = 'params';
    const SELECT = 'select';
    const DISTINCT = 'distinct';
}
