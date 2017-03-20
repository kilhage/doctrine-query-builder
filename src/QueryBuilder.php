<?php

namespace Glooby\Doctrine\QueryBuilder;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @author Emil Kilhage
 */
class QueryBuilder
{
    /**
     * @param EntityRepository $repo
     * @param Request $request
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function buildFromQuery(EntityRepository $repo, Request $request)
    {
        return $this->build($repo, [
            Param::LIMIT => $request->get(Param::LIMIT, 20),
            Param::OFFSET => $request->get(Param::OFFSET, 0),
            Param::ORDER_BY => $request->get(Param::ORDER_BY, null),
            Param::FILTER => $request->get(Param::FILTER, null),
            Param::PARAMS => $request->get(Param::PARAMS, null),
            Param::ALIAS => $request->get(Param::ALIAS, null),
        ]);
    }

    /**
     * array $criteria, array $orderBy = null, $limit = null, $offset = null
     *
     * @param EntityRepository $repo
     * @param array $params
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function build(EntityRepository $repo, array $params)
    {
        print_r($params);

        $alias = $params[Param::ALIAS] ?? 'xxxx'.random_int(1, 200);

        $query = $repo->createQueryBuilder($alias);

        if (!empty($params[Param::SELECT])) {
            $query->select($params[Param::SELECT]);
        }

        if (!empty($params[Param::DISTINCT])) {
            $query->distinct($params[Param::DISTINCT]);
        }

        if (isset($params[Param::JOIN])) {
            $this->addJoin($query, $alias, $params);
        }

        if (!empty($params[Param::FILTER])) {
            $this->buildFilters($query, $alias, $params);
        }

        if (!empty($params[Param::LIMIT]) && $params[Param::LIMIT] != -1) {
            $query->setMaxResults($params[Param::LIMIT]);
        }

        if (!empty($params[Param::OFFSET])) {
            $query->setFirstResult($params[Param::OFFSET]);
        }

        if (!empty($params[Param::ORDER_BY])) {
            $this->buildOrderBy($query, $params[Param::ORDER_BY], $alias);
        }

        if (!empty($params[Param::PARAMS])) {
            $this->setParams($query, $params);
        }

        return $query;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param string|array $orderBy
     */
    private function buildOrderBy(\Doctrine\ORM\QueryBuilder $query, $orderBy, $alias)
    {
        if (!empty($orderBy)) {
            if (is_string($orderBy)) {
                if (false === strpos($orderBy, '.')) {
                    $orderBy = "$alias.$orderBy";
                }

                $query->orderBy($orderBy);
            } elseif (is_array($orderBy)) {
                foreach ($orderBy as $sort => $order) {
                    if (is_int($sort)) {
                        if (false === strpos($sort, '.')) {
                            $sort = "$alias.$sort";
                        }

                        $query->addOrderBy($sort);
                    } else {
                        if (false === strpos($sort, '.')) {
                            $sort = "$alias.$sort";
                        }

                        $query->addOrderBy($sort, $order);
                    }
                }
            }
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param \Doctrine\ORM\Query\Expr $expr
     * @param array $filters
     * @param string $alias
     *
     * @return array
     */
    private function addFilters(\Doctrine\ORM\QueryBuilder $query, \Doctrine\ORM\Query\Expr $expr, array $filters, string $alias)
    {
        $predicts = [];

        foreach ($filters as $field => $filter) {
            if ($field === Filter::_OR) {
                $or = $this->addFilters($query, $expr, $filter, $alias);
                $predicts[] = $expr->orX(...$or);
            } elseif ($field === Filter::_AND) {
                $and = $this->addFilters($query, $expr, $filter, $alias);
                $predicts[] = $expr->andX(...$and);
            } else {
                if (!is_array($filter)) {
                    $value = $filter;
                    $filter = [];

                    if (in_array($value, [Filter::NOT_NULL, Filter::IS_NULL], true)) {
                        $filter[$value] = null;
                    } else {
                        $filter[Filter::EQUALS] = $value;
                    }
                }

                if (false === strpos($field, '.')) {
                    $field = "$alias.$field";
                }

                foreach ($filter as $op => $value) {
                    switch ($op) {
                        case Filter::EQUALS:
                            $value = $expr->literal($value);
                            $predicts[] = $expr->eq($field, $value);
                            break;
                        case Filter::NOT_EQUALS:
                            $value = $expr->literal($value);
                            $predicts[] = $expr->neq($field, $value);
                            break;
                        case Filter::STARTS:
                            $value = $expr->literal("%$value");
                            $predicts[] = $expr->like($field, $value);
                            break;
                        case Filter::ENDS:
                            $value = $expr->literal("$value%");
                            $predicts[] = $expr->like($field, $value);
                            break;
                        case Filter::CONTAINS:
                            $value = $expr->literal("%$value%");
                            $predicts[] = $expr->like($field, "$value");
                            break;
                        case Filter::NOT_CONTAINS:
                            $value = $expr->literal("%$value%");
                            $predicts[] = $expr->notLike($field, $value);
                            break;
                        case Filter::IN:
                            if (!is_array($value)) {
                                throw new BadRequestHttpException(Filter::IN.' requires an array');
                            }
                            $predicts[] = $expr->in($field, $value);
                            break;
                        case Filter::NOT_IN:
                            if (!is_array($value)) {
                                throw new BadRequestHttpException(Filter::NOT_IN.' requires an array');
                            }
                            $predicts[] = $expr->notIn($field, $value);
                            break;
                        case Filter::BETWEEN:
                            if (!is_array($value) || count($value) != 2) {
                                throw new BadRequestHttpException(Filter::BETWEEN.' requires an array with two values.');
                            }
                            $predicts[] = $expr->between($field, $value[0], $value[1]);
                            break;
                        case Filter::IS_NULL:
                            $predicts[] = $expr->isNull($field);
                            break;
                        case Filter::NOT_NULL:
                            $predicts[] = $expr->isNotNull($field);
                            break;
                        case Filter::LT:
                            $predicts[] = $expr->lt($field, $value);
                            break;
                        case Filter::LTE:
                            $predicts[] = $expr->lte($field, $value);
                            break;
                        case Filter::GT:
                            $predicts[] = $expr->gt($field, $value);
                            break;
                        case Filter::GTE:
                            $predicts[] = $expr->gte($field, $value);
                            break;
                        default:
                            throw new BadRequestHttpException("Did not recognize the operand: " . $op);
                    }
                }
            }
        }

        return $predicts;
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder$query
     * @param string $alias
     * @param array $params
     */
    private function buildFilters(\Doctrine\ORM\QueryBuilder $query, string $alias, array $params)
    {
        if (is_array($params[Param::FILTER])) {
            $expr = $query->expr();
            $and = $this->addFilters($query, $expr, $params[Param::FILTER], $alias);
            $query->where(...$and);
        } else {
            throw new BadRequestHttpException();
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param array $params
     */
    private function setParams(\Doctrine\ORM\QueryBuilder $query, array $params)
    {
        foreach ($params[Param::PARAMS] as $key => $value) {
            $query->setParameter($key, $value);
        }
    }

    /**
     * @param \Doctrine\ORM\QueryBuilder $query
     * @param string $alias
     * @param array $params
     */
    private function addJoin(\Doctrine\ORM\QueryBuilder $query, string $alias, array $params)
    {
        foreach ($params[Param::JOIN] as $join => $a) {
            if (false === strpos($join, '.')) {
                $join = "$alias.$join";
            }

            if (is_string($a)) {
                $a = [
                    'alias' => $a,
                ];
            }

            switch (strtolower($a['type'] ?? 'inner')) {
                case 'inner':
                    $query->innerJoin(
                        $join,
                        $a['alias'],
                        $a['conditionType'] ?? Join::WITH,
                        $a['condition'] ?? null,
                        $a['indexBy'] ?? null
                    );
                    break;
                case 'left':
                    $query->leftJoin(
                        $join,
                        $a['alias'],
                        $a['conditionType'] ?? Join::WITH,
                        $a['condition'] ?? null,
                        $a['indexBy'] ?? null
                    );
                    break;
            }
        }
    }
}
