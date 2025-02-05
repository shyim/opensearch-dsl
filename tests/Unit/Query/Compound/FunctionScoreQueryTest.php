<?php declare(strict_types=1);

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace OpenSearchDSL\Tests\Unit\Query\Compound;

use OpenSearchDSL\Query\Compound\FunctionScoreQuery;
use OpenSearchDSL\Query\MatchAllQuery;
use OpenSearchDSL\Query\TermLevel\TermsQuery;
use OpenSearchDSL\Search;

/**
 * Tests for FunctionScoreQuery.
 *
 * @internal
 */
class FunctionScoreQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Data provider for testAddRandomFunction.
     *
     * @return array
     */
    public function addRandomFunctionProvider()
    {
        return [
            // Case #0. No seed.
            [
                'seed' => null,
                'expectedArray' => [
                    'query' => [
                        'match_all' => new \stdClass(),
                    ],
                    'functions' => [
                        [
                            'random_score' => new \stdClass(),
                        ],
                    ],
                ],
            ],
            // Case #1. With seed.
            [
                'seed' => 'someSeed',
                'expectedArray' => [
                    'query' => [
                        'match_all' => new \stdClass(),
                    ],
                    'functions' => [
                        [
                            'random_score' => ['seed' => 'someSeed'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Tests addRandomFunction method.
     *
     * @param array $expectedArray
     *
     * @dataProvider addRandomFunctionProvider
     */
    public function testAddRandomFunction($seed, $expectedArray): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addRandomFunction($seed);

        static::assertEquals(['function_score' => $expectedArray], $functionScoreQuery->toArray());
    }

    /**
     * Tests default argument values.
     */
    public function testAddFieldValueFactorFunction(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addFieldValueFactorFunction('field1', 2);
        $functionScoreQuery->addFieldValueFactorFunction('field2', 1.5, 'ln');
        $functionScoreQuery->addFieldValueFactorFunction(
            'field3',
            1.1,
            'none',
            new TermsQuery('foo', ['bar']),
        );

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'field1',
                            'factor' => 2,
                            'modifier' => 'none',
                        ],
                    ],
                    [
                        'field_value_factor' => [
                            'field' => 'field2',
                            'factor' => 1.5,
                            'modifier' => 'ln',
                        ],
                    ],
                    [
                        'field_value_factor' => [
                            'field' => 'field3',
                            'factor' => 1.1,
                            'modifier' => 'none',
                        ],
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddDecayFunction(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        static::assertSame($functionScoreQuery, $functionScoreQuery->addDecayFunction(
            'linear',
            'field1',
            [
                'origin' => 10,
                'scale' => 50,
                'offset' => 0,
                'decay' => 0.5,
            ],
            ['foo' => 'bar'],
            new TermsQuery('foo', ['bar']),
            5
        ));

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'linear' => [
                            'field1' => [
                                'origin' => 10,
                                'scale' => 50,
                                'offset' => 0,
                                'decay' => 0.5,
                            ],
                            'foo' => 'bar',
                        ],
                        'weight' => 5,
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );

        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addDecayFunction(
            'linear',
            'field1',
            [
                'origin' => 10,
                'scale' => 50,
                'offset' => 0,
                'decay' => 0.5,
            ],
            ['foo' => 'bar'],
            new TermsQuery('foo', ['bar']),
            null
        );

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'linear' => [
                            'field1' => [
                                'origin' => 10,
                                'scale' => 50,
                                'offset' => 0,
                                'decay' => 0.5,
                            ],
                            'foo' => 'bar',
                        ],
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddWeightFunction(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        static::assertSame($functionScoreQuery, $functionScoreQuery->addWeightFunction(5, new TermsQuery('foo', ['bar'])));

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'weight' => 5,
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    /**
     * Match all test
     */
    public function testRandomScore(): void
    {
        $fquery = new FunctionScoreQuery(new MatchAllQuery());
        $fquery->addRandomFunction(null, new TermsQuery('foo', ['bar']));
        $fquery->addParameter('boost_mode', 'multiply');

        $search = new Search();
        $search->addQuery($fquery);

        $expected = [
            'query' => [
                'function_score' => [
                    'query' => [
                        'match_all' => [],
                    ],
                    'functions' => [
                        0 => [
                            'random_score' => [],
                            'filter' => [
                                'terms' => [
                                    'foo' => ['bar'],
                                ],
                            ],
                        ],
                    ],
                    'boost_mode' => 'multiply',
                ],
            ],
        ];

        static::assertEquals($expected, json_decode(json_encode($search->toArray(), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testScriptScore(): void
    {
        $fquery = new FunctionScoreQuery(new MatchAllQuery());
        $fquery->addScriptScoreFunction(
            "
            if (doc['price'].value < params.target)
             {
               return doc['price'].value * params.charge;
             }
             return doc['price'].value;
             ",
            [
                'target' => 10,
                'charge' => 0.9,
            ],
            ['foo' => 'bar'],
            new TermsQuery('foo', ['bar']),
        );

        $search = new Search();
        $search->addQuery($fquery);

        $expected = [
            'query' => [
                'function_score' => [
                    'query' => [
                        'match_all' => [],
                    ],
                    'functions' => [
                        0 => [
                            'script_score' => [
                                'script' => [
                                    'lang' => 'painless',
                                    'source' => '
            if (doc[\'price\'].value < params.target)
             {
               return doc[\'price\'].value * params.charge;
             }
             return doc[\'price\'].value;
             ',
                                    'params' => [
                                        'target' => 10,
                                        'charge' => 0.9,
                                    ],
                                    'foo' => 'bar',
                                ],
                            ],
                            'filter' => [
                                'terms' => [
                                    'foo' => ['bar'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        static::assertEquals($expected, json_decode(json_encode($search->toArray(), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
    }

    public function testAddSimpleFunction(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        static::assertSame($functionScoreQuery, $functionScoreQuery->addFieldValueFactorFunction('field1', 2));

        static::assertSame($functionScoreQuery, $functionScoreQuery->addSimpleFunction(
            [
                'weight' => 5,
            ]
        ));

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'field1',
                            'factor' => 2,
                            'modifier' => 'none',
                        ],
                    ],
                    [
                        'weight' => 5,
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testConstructor(): void
    {
        $query = new MatchAllQuery();
        $agg = new FunctionScoreQuery($query, ['a' => 'b']);
        static::assertSame('b', $agg->getParameter('a'));
        static::assertSame(['a' => 'b'], $agg->getParameters());
        static::assertSame($query, $agg->getQuery());
    }

    public function testAddWeightFunctionWithName(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addWeightFunction(5, new TermsQuery('foo', ['bar']), 'test_name');

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'weight' => 5,
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                        '_name' => 'test_name',
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddFieldValueFactorFunctionWithName(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addFieldValueFactorFunction('field1', 2, 'none', null, null, 'test_name');

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'field_value_factor' => [
                            'field' => 'field1',
                            'factor' => 2,
                            'modifier' => 'none',
                        ],
                        '_name' => 'test_name',
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddDecayFunctionWithName(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addDecayFunction(
            'linear',
            'field1',
            [
                'origin' => 10,
                'scale' => 50,
                'offset' => 0,
                'decay' => 0.5,
            ],
            ['foo' => 'bar'],
            new TermsQuery('foo', ['bar']),
            5,
            'test_name'
        );

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'linear' => [
                            'field1' => [
                                'origin' => 10,
                                'scale' => 50,
                                'offset' => 0,
                                'decay' => 0.5,
                            ],
                            'foo' => 'bar',
                        ],
                        'weight' => 5,
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                        '_name' => 'test_name',
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddRandomFunctionWithName(): void
    {
        $functionScoreQuery = new FunctionScoreQuery(new MatchAllQuery());
        $functionScoreQuery->addRandomFunction('someSeed', new TermsQuery('foo', ['bar']), 'test_name');

        static::assertEquals(
            [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
                'functions' => [
                    [
                        'random_score' => ['seed' => 'someSeed'],
                        'filter' => [
                            'terms' => [
                                'foo' => ['bar'],
                            ],
                        ],
                        '_name' => 'test_name',
                    ],
                ],
            ],
            $functionScoreQuery->toArray()['function_score']
        );
    }

    public function testAddScriptScoreFunctionWithName(): void
    {
        $fquery = new FunctionScoreQuery(new MatchAllQuery());
        $fquery->addScriptScoreFunction(
            "
            if (doc['price'].value < params.target)
             {
               return doc['price'].value * params.charge;
             }
             return doc['price'].value;
             ",
            [
                'target' => 10,
                'charge' => 0.9,
            ],
            ['foo' => 'bar'],
            new TermsQuery('foo', ['bar']),
            'test_name'
        );

        $search = new Search();
        $search->addQuery($fquery);

        $expected = [
            'query' => [
                'function_score' => [
                    'query' => [
                        'match_all' => [],
                    ],
                    'functions' => [
                        0 => [
                            'script_score' => [
                                'script' => [
                                    'lang' => 'painless',
                                    'source' => '
            if (doc[\'price\'].value < params.target)
             {
               return doc[\'price\'].value * params.charge;
             }
             return doc[\'price\'].value;
             ',
                                    'params' => [
                                        'target' => 10,
                                        'charge' => 0.9,
                                    ],
                                    'foo' => 'bar',
                                ],
                            ],
                            'filter' => [
                                'terms' => [
                                    'foo' => ['bar'],
                                ],
                            ],
                            '_name' => 'test_name',
                        ],
                    ],
                ],
            ],
        ];

        static::assertEquals($expected, json_decode(json_encode($search->toArray(), \JSON_THROW_ON_ERROR), true, 512, \JSON_THROW_ON_ERROR));
    }
}
