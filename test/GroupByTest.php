<?php

use Falco\Core as F;

class GroupByTest extends PHPUnit_Framework_TestCase
{
    private static $dummy = array(
        array('id' => 3,  'name' => 'John Smith',  'sex' => 'M', 'age' => 20, 'food' => 'Bacon'),
        array('id' => 13, 'name' => 'Mary Smith',  'sex' => 'F', 'age' => 18, 'food' => 'Cake'),
        array('id' => 23, 'name' => 'John Denver', 'sex' => 'M', 'age' => 38, 'food' => 'Bourbon'),
        array('id' => 33, 'name' => 'Nick Mary',   'sex' => 'M', 'age' => 33, 'food' => 'Eggs'),
        array('id' => 43, 'name' => 'Al Pacino',   'sex' => 'M', 'age' => 74, 'food' => 'Cigar'),
        array('id' => 53, 'name' => 'Kaley Cuoco', 'sex' => 'F', 'age' => 28, 'food' => 'Sex'),
    );

    public function recordProvider()
    {
        $tests = array();
        $tests[] = array(
            'id', null, self::$dummy,
            array(
                3  => array(self::$dummy[0]),
                13 => array(self::$dummy[1]),
                23 => array(self::$dummy[2]),
                33 => array(self::$dummy[3]),
                43 => array(self::$dummy[4]),
                53 => array(self::$dummy[5]),
            ),
            'Mapping of id to entire record.'
        );
        $tests[] = array(
            'id', 'name', self::$dummy,
            array(
                3  => array(self::$dummy[0]['name']),
                13 => array(self::$dummy[1]['name']),
                23 => array(self::$dummy[2]['name']),
                33 => array(self::$dummy[3]['name']),
                43 => array(self::$dummy[4]['name']),
                53 => array(self::$dummy[5]['name']),
            ),
            'Mapping of id to the value of name'
        );
        $tests[] = array(
            'id', array('name'), self::$dummy,
            array(
                3  => array(array('name' => self::$dummy[0]['name'])),
                13 => array(array('name' => self::$dummy[1]['name'])),
                23 => array(array('name' => self::$dummy[2]['name'])),
                33 => array(array('name' => self::$dummy[3]['name'])),
                43 => array(array('name' => self::$dummy[4]['name'])),
                53 => array(array('name' => self::$dummy[5]['name'])),
            ),
            'Mapping of id to a record with field name.'
        );
        $tests[] = array(
            array('id'), array('name','404','food'), self::$dummy,
            array(
                3  => array(array('name' => self::$dummy[0]['name'],
                                  'food' => self::$dummy[0]['food'],),),
                13 => array(array('name' => self::$dummy[1]['name'],
                                  'food' => self::$dummy[1]['food'],),),
                23 => array(array('name' => self::$dummy[2]['name'],
                                  'food' => self::$dummy[2]['food'],),),
                33 => array(array('name' => self::$dummy[3]['name'],
                                  'food' => self::$dummy[3]['food'],),),
                43 => array(array('name' => self::$dummy[4]['name'],
                                  'food' => self::$dummy[4]['food'],),),
                53 => array(array('name' => self::$dummy[5]['name'],
                                  'food' => self::$dummy[5]['food'],),),
            ),
            'Mapping of id (given as array) to a record with fields name, food, (404 is bogus/skipped).'
        );
        $tests[] = array(
            array('sex'), array('name','404','food'), self::$dummy,
            array(
                'M' => array(
                    array('name' => self::$dummy[0]['name'],
                          'food' => self::$dummy[0]['food']),
                    array('name' => self::$dummy[2]['name'],
                          'food' => self::$dummy[2]['food']),
                    array('name' => self::$dummy[3]['name'],
                          'food' => self::$dummy[3]['food']),
                    array('name' => self::$dummy[4]['name'],
                          'food' => self::$dummy[4]['food']),
                ),
                'F' => array(
                    array('name' => self::$dummy[1]['name'],
                          'food' => self::$dummy[1]['food'],),
                    array('name' => self::$dummy[5]['name'],
                          'food' => self::$dummy[5]['food'],),
                ),
            ),
            'Mapping of sex to id to a record with fields name, food, (404 is bogus/skipped).'
        );
        $sexFn = function ($v) {
            switch ($v) {
                case 'M': return 'Male';
                case 'F': return 'Female';
            }
        };
        $testz = array(
            array('sex' => $sexFn, 'id'),
            array('name','404','food'),
            self::$dummy,
            array(
                'Male' => array(
                    3  => array('name' => self::$dummy[0]['name'],
                                'food' => self::$dummy[0]['food'],),
                    23 => array('name' => self::$dummy[2]['name'],
                                'food' => self::$dummy[2]['food'],),
                    33 => array('name' => self::$dummy[3]['name'],
                                'food' => self::$dummy[3]['food'],),
                    43 => array('name' => self::$dummy[4]['name'],
                                'food' => self::$dummy[4]['food'],),
                ),
                'Female' => array(
                    13 => array('name' => self::$dummy[1]['name'],
                                'food' => self::$dummy[1]['food'],),
                    53 => array('name' => self::$dummy[5]['name'],
                                'food' => self::$dummy[5]['food'],),
                ),
            ),
            'Mapping of transformed sex to id to a record of selected fields.'
        );
        return $tests;
    }

    /**
     * @dataProvider recordProvider
     */
    public function testIndexBy($keys, $vals, $in, $expected, $msg = null)
    {
        $actual = F::groupBy($keys, $vals, $in);
        $this->assertEquals($expected, $actual, $msg);
    }
}
