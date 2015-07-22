<?php

namespace ValueParsers;

use DataValues\TimeValue;

/**
 * This parser is in essence the inverse operation of Language::sprintfDate.
 *
 * @see Language::sprintfDate
 *
 * @since 0.8.1
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class DateFormatParser extends StringValueParser {

	const FORMAT_NAME = 'datetime';

	const OPT_DATE_FORMAT = 'dateFormat';
	const OPT_DIGIT_TRANSFORM_TABLE = 'digitTransformTable';

	/**
	 * Must be a two-dimensional array, the first dimension mapping the month's numbers 1 to 12 to
	 * arrays of month
	 */
	const OPT_MONTH_NAMES = 'monthNames';

	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_DATE_FORMAT, 'j F Y' );
		$this->defaultOption( self::OPT_DIGIT_TRANSFORM_TABLE, null );
		$this->defaultOption( self::OPT_MONTH_NAMES, null );
	}

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return TimeValue
	 */
	protected function stringParse( $value ) {
		$format = $this->getDateFormat();
		$formatLength = strlen( $format );
		$numberPattern = '[' . $this->getNumberCharacters() . ']';
		$pattern = '';

		for ( $p = 0; $p < $formatLength; $p++ ) {
			$code = $format[$p];

			if ( $code === 'x' && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}

			if ( preg_match( '<^x[ijkmot]$>', $code ) && $p < $formatLength - 1 ) {
				$code .= $format[++$p];
			}

			switch ( $code ) {
				case 'Y':
					$pattern .= '(?P<year>' . $numberPattern . '+)\p{Z}*';
					break;
				case 'F':
				case 'm':
				case 'M':
				case 'n':
				case 'xg':
					$pattern .= '(?P<month>' . $numberPattern . '{1,2}'
						. $this->getMonthNamesPattern()
						. ')\p{P}*\p{Z}*';
					break;
				case 'd':
				case 'j':
					$pattern .= '(?P<day>' . $numberPattern . '{1,2})\p{P}*\p{Z}*';
					break;
				case 'G':
				case 'H':
					$pattern .= '(?P<hour>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case 'i':
					$pattern .= '(?P<minute>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case 's':
					$pattern .= '(?P<second>' . $numberPattern . '{1,2})\p{Z}*';
					break;
				case '\\':
					if ( $p < $formatLength - 1 ) {
						$pattern .= preg_quote( $format[++$p] );
					} else {
						$pattern .= '\\';
					}
					break;
				case '"':
					$endQuote = strpos( $format, '"', $p + 1 );
					if ( $endQuote !== false ) {
						$pattern .= preg_quote( substr( $format, $p + 1, $endQuote - $p - 1 ) );
						$p = $endQuote;
					} else {
						$pattern .= '"';
					}
					break;
				case 'xn':
				case 'xN':
					// We can ignore raw and raw toggle when parsing
					break;
				default:
					if ( preg_match( '<^\p{P}+$>u', $format[$p] ) ) {
						$pattern .= '\p{P}*';
					} elseif ( preg_match( '<^\p{Z}+$>u', $format[$p] ) ) {
						$pattern .= '\p{Z}*';
					} else {
						$pattern .= preg_quote( $format[$p] );
					}
			}
		}

		$isMatch = preg_match( '<^\p{Z}*' . $pattern . '$>iu', $value, $matches );
		if ( $isMatch && isset( $matches['year'] ) ) {
			$precision = TimeValue::PRECISION_YEAR;
			$time = array( $this->parseFormattedNumber( $matches['year'] ), 0, 0, 0, 0, 0 );

			if ( isset( $matches['month'] ) ) {
				$precision = TimeValue::PRECISION_MONTH;
				$time[1] = $this->findMonthMatch( $matches );
			}

			if ( isset( $matches['day'] ) ) {
				$precision = TimeValue::PRECISION_DAY;
				$time[2] = $this->parseFormattedNumber( $matches['day'] );
			}

			if ( isset( $matches['hour'] ) ) {
				$precision = TimeValue::PRECISION_HOUR;
				$time[3] = $this->parseFormattedNumber( $matches['hour'] );
			}

			if ( isset( $matches['minute'] ) ) {
				$precision = TimeValue::PRECISION_MINUTE;
				$time[4] = $this->parseFormattedNumber( $matches['minute'] );
			}

			if ( isset( $matches['second'] ) ) {
				$precision = TimeValue::PRECISION_SECOND;
				$time[5] = $this->parseFormattedNumber( $matches['second'] );
			}

			$timestamp = vsprintf( '%+.0f-%02d-%02dT%02d:%02d:%02dZ', $time );
			return new TimeValue( $timestamp, 0, 0, 0, $precision, TimeValue::CALENDAR_GREGORIAN );
		}

		throw new ParseException( "Failed to parse $value ("
			. $this->parseFormattedNumber( $value ) . ')', $value );
	}

	/**
	 * @return string
	 */
	private function getMonthNamesPattern() {
		$pattern = '';

		foreach ( $this->getMonthNames() as $i => $monthNames ) {
			$pattern .= '|(?P<month' . $i . '>'
				. implode( '|', array_map( 'preg_quote', (array)$monthNames ) )
				. ')';
		}

		return $pattern;
	}

	/**
	 * @param string[] $matches
	 *
	 * @return int
	 */
	private function findMonthMatch( $matches ) {
		for ( $i = 1; $i <= 12; $i++ ) {
			if ( !empty( $matches['month' . $i] ) ) {
				return $i;
			}
		}

		return $this->parseFormattedNumber( $matches['month'] );
	}

	/**
	 * @param string $number
	 *
	 * @return string
	 */
	private function parseFormattedNumber( $number ) {
		$transformTable = $this->getDigitTransformTable();

		if ( is_array( $transformTable ) ) {
			// Eliminate empty array values (bug T66347).
			$transformTable = array_filter( $transformTable );
			$number = strtr( $number, array_flip( $transformTable ) );
		}

		return $number;
	}

	/**
	 * @return string
	 */
	private function getNumberCharacters() {
		$numberCharacters = '\d';

		$transformTable = $this->getDigitTransformTable();
		if ( is_array( $transformTable ) ) {
			$numberCharacters .= preg_quote( implode( '', $transformTable ) );
		}

		return $numberCharacters;
	}

	/**
	 * @return string
	 */
	private function getDateFormat() {
		return $this->getOption( self::OPT_DATE_FORMAT );
	}

	/**
	 * @return string[]|null
	 */
	private function getDigitTransformTable() {
		return $this->getOption( self::OPT_DIGIT_TRANSFORM_TABLE );
	}

	/**
	 * @return array[]
	 */
	private function getMonthNames() {
		return $this->getOption( self::OPT_MONTH_NAMES ) ?: array();
	}

}
