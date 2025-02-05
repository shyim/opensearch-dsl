<?php declare(strict_types=1);

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenSearchDSL\Query\Compound;

use OpenSearchDSL\BuilderInterface;
use OpenSearchDSL\ParametersTrait;

/**
 * Represents Elasticsearch "function_score" query.
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-function-score-query.html
 */
class FunctionScoreQuery implements BuilderInterface
{
    use ParametersTrait;

    private BuilderInterface $query;

    private array $functions = [];

    public function __construct(BuilderInterface $query, array $parameters = [])
    {
        $this->query = $query;
        $this->setParameters($parameters);
    }

    public function getQuery(): BuilderInterface
    {
        return $this->query;
    }

    /**
     * @param mixed|null $missing
     */
    public function addFieldValueFactorFunction(
        string $field,
        float $factor,
        string $modifier = 'none',
        ?BuilderInterface $query = null,
        $missing = null,
        ?string $name = '',
    ): self {
        $function = [
            'field_value_factor' => array_filter([
                'field' => $field,
                'factor' => $factor,
                'modifier' => $modifier,
                'missing' => $missing,
            ]),
        ];

        if (!empty($name)) {
            $function['_name'] = $name;
        }

        if ($query) {
            $function['filter'] = $query->toArray();
        }

        $this->functions[] = $function;

        return $this;
    }

    public function addDecayFunction(
        string $type,
        string $field,
        array $function,
        array $options = [],
        ?BuilderInterface $query = null,
        ?int $weight = null,
        ?string $name = '',
    ) {
        $function = array_filter(
            [
                $type => array_merge(
                    [$field => $function],
                    $options
                ),
                'weight' => $weight,
            ]
        );

        if (!empty($name)) {
            $function['_name'] = $name;
        }

        if ($query) {
            $function['filter'] = $query->toArray();
        }

        $this->functions[] = $function;

        return $this;
    }

    public function addWeightFunction(float $weight, ?BuilderInterface $query = null, ?string $name = ''): self
    {
        $function = [
            'weight' => $weight,
        ];

        if (!empty($name)) {
            $function['_name'] = $name;
        }

        if ($query) {
            $function['filter'] = $query->toArray();
        }

        $this->functions[] = $function;

        return $this;
    }

    public function addRandomFunction($seed = null, ?BuilderInterface $query = null, ?string $name = ''): self
    {
        $function = [
            'random_score' => $seed ? ['seed' => $seed] : new \stdClass(),
        ];

        if (!empty($name)) {
            $function['_name'] = $name;
        }

        if ($query) {
            $function['filter'] = $query->toArray();
        }

        $this->functions[] = $function;

        return $this;
    }

    public function addScriptScoreFunction(
        string $source,
        array $params = [],
        array $options = [],
        ?BuilderInterface $query = null,
        ?string $name = '',
    ): self {
        $function = [
            'script_score' => [
                'script' => array_merge(
                    [
                        'lang' => 'painless',
                        'source' => $source,
                        'params' => $params,
                    ],
                    $options
                ),
            ],
        ];

        if (!empty($name)) {
            $function['_name'] = $name;
        }

        if ($query) {
            $function['filter'] = $query->toArray();
        }

        $this->functions[] = $function;

        return $this;
    }

    public function addSimpleFunction(array $function): self
    {
        $this->functions[] = $function;

        return $this;
    }

    public function toArray(): array
    {
        $query = [
            'query' => $this->getQuery()->toArray(),
            'functions' => $this->functions,
        ];

        $output = $this->processArray($query);

        return [$this->getType() => $output];
    }

    public function getType(): string
    {
        return 'function_score';
    }
}
