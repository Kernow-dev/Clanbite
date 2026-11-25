/**
 * Shared helpers for player blocks (aligned with core cover positioning utilities).
 */

/**
 * @param {string|undefined} contentPosition e.g. "top left", "center center".
 * @return {string} Class names for content position.
 */
export function getPositionClassName( contentPosition ) {
	if ( ! contentPosition ) {
		return '';
	}
	const parts = contentPosition.split( ' ' );
	const y = parts[ 0 ];
	const x = parts[ 1 ] ?? parts[ 0 ];
	const classes = [];
	if ( y ) {
		classes.push( `is-position-${ y }` );
	}
	if ( x && x !== y ) {
		classes.push( `is-position-${ x }` );
	}
	return classes.join( ' ' );
}

/**
 * Whether the content position is the centre of the block.
 *
 * @param {string|undefined} contentPosition Content position such as `center center`.
 * @return {boolean} True when there is no position or it is `center center`.
 */
export function isContentPositionCenter( contentPosition ) {
	return ! contentPosition || contentPosition === 'center center';
}

/**
 * Recursively drop empty nested objects / empty values from a style-like object.
 *
 * @param {Record<string, unknown>|undefined} object Style-like object.
 * @return {Record<string, unknown>|undefined} The object without empty values, or undefined when nothing is left.
 */
export function cleanEmptyObject( object ) {
	if ( ! object ) {
		return object;
	}
	const cleanedEntries = Object.entries( object )
		.map( ( [ key, value ] ) => {
			if ( value === null || value === undefined || value === '' ) {
				return [];
			}
			if ( typeof value === 'object' && ! Array.isArray( value ) ) {
				const nestedValue = cleanEmptyObject( value );
				if (
					! nestedValue ||
					Object.keys( nestedValue ).length === 0
				) {
					return [];
				}
				return [ key, nestedValue ];
			}
			return [ key, value ];
		} )
		.filter( ( entry ) => entry.length > 0 );

	if ( cleanedEntries.length === 0 ) {
		return undefined;
	}
	return Object.fromEntries( cleanedEntries );
}
