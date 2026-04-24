import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const prefixFromLegacyLabel =
		attributes.label && '' === attributes.prefix ? attributes.label : '';
	const prefixValue = attributes.prefix || prefixFromLegacyLabel || '';
	const postfixValue = attributes.postfix || '';

	const blockProps = useBlockProps( {
		className:
			'clanbite-team-stat-edit clanbite-team-stat-edit--members-count',
	} );

	const onPrefixChange = ( v ) => {
		const next = { prefix: v ?? '' };
		if ( attributes.label ) {
			next.label = '';
		}
		setAttributes( next );
	};

	return (
		<div { ...blockProps }>
			<RichText
				key="clanbite-team-members-prefix"
				tagName="span"
				className="clanbite-team-members-count__prefix"
				value={ prefixValue }
				onChange={ onPrefixChange }
				placeholder={ __( 'Members', 'clanbite' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
			<span
				className="clanbite-team-members-count__value clanbite-team-members-count__value--editor-placeholder"
				aria-hidden="true"
			>
				0
			</span>
			<RichText
				key="clanbite-team-members-postfix"
				tagName="span"
				className="clanbite-team-members-count__postfix"
				value={ postfixValue }
				onChange={ ( v ) => setAttributes( { postfix: v ?? '' } ) }
				placeholder={ __( 'Postfix…', 'clanbite' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
		</div>
	);
}
