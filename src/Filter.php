<?php

namespace Glooby\Doctrine\QueryBuilder;

/**
 * @author Emil Kilhage
 */
interface Filter
{
    const _OR = '$or';
    const _AND = '$and';
    const EQUALS = '$equals';
    const NOT_EQUALS = '$not_equals';
    const STARTS = '$starts';
    const ENDS = '$ends';
    const CONTAINS = '$contains';
    const NOT_CONTAINS = '$not_contains';
    const IN = '$in';
    const NOT_IN = '$not_in';
    const BETWEEN = '$between';
    const IS_NULL = '$is_null';
    const NOT_NULL = '$not_null';
    /** Less then or equals <= */
    const LTE = '$lte';
    /** Less then < */
    const LT = '$lt';
    /** Greater then or equals >= */
    const GTE = '$gte';
    /** Greater then > */
    const GT = '$gt';
}
