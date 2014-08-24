<?php

use Falco\F as F;

class IndexBy extends PHPUnit_Framework_TestCase {

	private static $dummyRecords = array(
		array('id' => 3,  'name' => 'John Smith',  'sex' => 'M', 'age' => 20, 'food' => 'Bacon'),
		array('id' => 13, 'name' => 'Mary Smith',  'sex' => 'F', 'age' => 18, 'food' => 'Cake'),
		array('id' => 23, 'name' => 'John Denver', 'sex' => 'M', 'age' => 38, 'food' => 'Bourbon'),
		array('id' => 33, 'name' => 'Nick Mary',   'sex' => 'M', 'age' => 33, 'food' => 'Eggs'),
		array('id' => 43, 'name' => 'Gary Ross',   'sex' => 'M', 'age' => 56, 'food' => 'Cigar'),
		array('id' => 53, 'name' => 'Kaley Cuoco', 'sex' => 'F', 'age' => 28, 'food' => 'Sex'),
	);

	public function recordProvider () {
		$tests = array();
		$tests[] = array(null, null, self::$dummyRecords, self::$dummyRecords);
		$tests[] = array(
			'id', null, self::$dummyRecords,
			array(
				3  => self::$dummyRecords[0],
				13 => self::$dummyRecords[1],
				23 => self::$dummyRecords[2],
				33 => self::$dummyRecords[3],
				43 => self::$dummyRecords[4],
				53 => self::$dummyRecords[5],
			)
		);
		$tests[] = array(
			'id', 'name', self::$dummyRecords,
			array(
				3  => self::$dummyRecords[0]['name'],
				13 => self::$dummyRecords[1]['name'],
				23 => self::$dummyRecords[2]['name'],
				33 => self::$dummyRecords[3]['name'],
				43 => self::$dummyRecords[4]['name'],
				53 => self::$dummyRecords[5]['name'],
			)
		);
		$tests[] = array(
			'id', array('name'), self::$dummyRecords,
			array(
				3  => array('name' => self::$dummyRecords[0]['name']),
				13 => array('name' => self::$dummyRecords[1]['name']),
				23 => array('name' => self::$dummyRecords[2]['name']),
				33 => array('name' => self::$dummyRecords[3]['name']),
				43 => array('name' => self::$dummyRecords[4]['name']),
				53 => array('name' => self::$dummyRecords[5]['name']),
			)
		);
		$tests[] = array(
			'id', array('name','404','food'), self::$dummyRecords,
			array(
				3  => array('name' => self::$dummyRecords[0]['name'],
							'food' => self::$dummyRecords[0]['food'],),
				13 => array('name' => self::$dummyRecords[1]['name'],
							'food' => self::$dummyRecords[1]['food'],),
				23 => array('name' => self::$dummyRecords[2]['name'],
							'food' => self::$dummyRecords[2]['food'],),
				33 => array('name' => self::$dummyRecords[3]['name'],
							'food' => self::$dummyRecords[3]['food'],),
				43 => array('name' => self::$dummyRecords[4]['name'],
							'food' => self::$dummyRecords[4]['food'],),
				53 => array('name' => self::$dummyRecords[5]['name'],
							'food' => self::$dummyRecords[5]['food'],),
			)
		);
		$tests[] = array(
			array('sex','id'), array('name','404','food'), self::$dummyRecords,
			array(
				'M' => array(
					3  => array('name' => self::$dummyRecords[0]['name'],
								'food' => self::$dummyRecords[0]['food'],),
					23 => array('name' => self::$dummyRecords[2]['name'],
								'food' => self::$dummyRecords[2]['food'],),
					33 => array('name' => self::$dummyRecords[3]['name'],
								'food' => self::$dummyRecords[3]['food'],),
					43 => array('name' => self::$dummyRecords[4]['name'],
								'food' => self::$dummyRecords[4]['food'],),
				),
				'F' => array(
					13 => array('name' => self::$dummyRecords[1]['name'],
								'food' => self::$dummyRecords[1]['food'],),
					53 => array('name' => self::$dummyRecords[5]['name'],
								'food' => self::$dummyRecords[5]['food'],),
				),
			),
		);
		$negate = F::multiplyBy(-1);
		$negId  = F::compose($negate, F::prop('id'));
		$tests[] = array($negId, null, self::$dummyRecords,
			array(
				-3  => self::$dummyRecords[0],
				-13 => self::$dummyRecords[1],
				-23 => self::$dummyRecords[2],
				-33 => self::$dummyRecords[3],
				-43 => self::$dummyRecords[4],
				-53 => self::$dummyRecords[5],
			)
		);
		return $tests;
	}

	/**
	 * @dataProvider recordProvider
	 */
	public function testIndexBy($keys, $vals, $in, $expected, $msg = null) {

		$actual = F::indexBy($keys, $vals, $in);

		$this->assertEquals($expected, $actual, $msg);
	}
}
