<?php

namespace ValueParsers\Test;

use DataValues\TimeValue;
use ValueParsers\DateFormatParser;
use ValueParsers\ParserOptions;

/**
 * @covers ValueParsers\DateFormatParser
 *
 * @group ValueParsers
 * @group WikibaseLib
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DateFormatParserTest extends StringValueParserTest {

	/**
	 * @deprecated since 0.3, just use getInstance.
	 */
	protected function getParserClass() {
		throw new \LogicException( 'Should not be called, use getInstance' );
	}

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return DateFormatParser
	 */
	protected function getInstance() {
		return new DateFormatParser();
	}

	/**
	 * @see ValueParserTestBase::validInputProvider
	 */
	public function validInputProvider() {
		$valid = array(
			array(
				'1 9 2014',
				'd. M Y', null, null,
				'+2014-09-01T00:00:00Z'
			),
			array(
				'1 September 2014',
				'd. M Y', null, array( 9 => array( 'September' ) ),
				'+2014-09-01T00:00:00Z'
			),
			array(
				'1. Sep. 2014',
				'd. M Y', null, array( 9 => array( 'Sep' ) ),
				'+2014-09-01T00:00:00Z'
			),
			array(
				'1. September 2014',
				'd. M Y', null, array( 9 => array( 'September' ) ),
				'+2014-09-01T00:00:00Z'
			),
			array(
				'1.September.2014',
				'd. M Y', null, array( 9 => array( 'September' ) ),
				'+2014-09-01T00:00:00Z'
			),
		);

		$cases = array();

		foreach ( $valid as $args ) {
			$dateString = $args[0];
			$dateFormat = $args[1];
			$digitTransformTable = $args[2];
			$monthNames = $args[3];
			$timestamp = $args[4];
			$precision = isset( $args[5] ) ? $args[5] : TimeValue::PRECISION_DAY;
			$calendarModel = isset( $args[6] ) ? $args[6] : TimeValue::CALENDAR_GREGORIAN;

			$cases[] = array(
				$dateString,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
				new DateFormatParser( new ParserOptions( array(
					DateFormatParser::OPT_DATE_FORMAT => $dateFormat,
					DateFormatParser::OPT_DIGIT_TRANSFORM_TABLE => $digitTransformTable,
					DateFormatParser::OPT_MONTH_NAMES => $monthNames,
				) ) )
			);
		}

		return $cases;
	}

	/**
	 * @see StringValueParserTest::invalidInputProvider
	 */
	public function invalidInputProvider() {
		$invalid = array(
		);

		$cases = parent::invalidInputProvider();

		foreach ( $invalid as $value ) {
			$cases[] = array( $value );
		}

		return $cases;
	}

}
