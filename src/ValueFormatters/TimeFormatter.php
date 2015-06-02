<?php

namespace ValueFormatters;

use DataValues\TimeValue;
use InvalidArgumentException;

/**
 * Time formatter.
 *
 * Some code in this class has been borrowed from the
 * MapsCoordinateParser class of the Maps extension for MediaWiki.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class TimeFormatter extends ValueFormatterBase {

	/**
	 * @deprecated since 0.7.1, use TimeValue::CALENDAR_GREGORIAN instead
	 */
	const CALENDAR_GREGORIAN = TimeValue::CALENDAR_GREGORIAN;

	/**
	 * @deprecated since 0.7.1, use TimeValue::CALENDAR_JULIAN instead
	 */
	const CALENDAR_JULIAN = TimeValue::CALENDAR_JULIAN;

	const OPT_CALENDARNAMES = 'calendars';

	const OPT_TIME_ISO_FORMATTER = 'time iso formatter';

	/**
	 * @since 0.1
	 *
	 * @param FormatterOptions|null $options
	 */
	public function __construct( FormatterOptions $options = null ) {
		parent::__construct( $options );

		$this->defaultOption( self::OPT_CALENDARNAMES, array() );
		$this->defaultOption( self::OPT_TIME_ISO_FORMATTER, null );
	}

	/**
	 * @see ValueFormatter::format
	 *
	 * @since 0.1
	 *
	 * @param TimeValue $value The value to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 */
	public function format( $value ) {
		if ( !( $value instanceof TimeValue ) ) {
			throw new InvalidArgumentException( 'ValueFormatters\TimeFormatter can only format '
				. 'instances of DataValues\TimeValue' );
		}

		$formatted = $value->getTime();

		$isoFormatter = $this->getOption( self::OPT_TIME_ISO_FORMATTER );
		if ( $isoFormatter instanceof ValueFormatter ) {
			$formatted = $isoFormatter->format( $value );
		}

		return $formatted;
	}

}
