import { __ } from '@wordpress/i18n';
import { RichText, useBlockProps } from '@wordpress/block-editor';
import './editor.scss';

export default function Edit( { attributes, setAttributes } ) {
	const { prefix, postfix } = attributes;
	const blockProps = useBlockProps( {
		className:
			'clanbite-team-stat-edit clanbite-team-stat-edit--losses',
	} );

	return (
		<div { ...blockProps }>
			<RichText
				key="clanbite-team-losses-prefix"
				tagName="span"
				className="clanbite-team-stat__prefix"
				value={ prefix }
				onChange={ ( v ) => setAttributes( { prefix: v ?? '' } ) }
				placeholder={ __( 'Losses', 'clanbite' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
			<span
				className="clanbite-team-stat__value clanbite-team-stat__value--editor-placeholder"
				aria-hidden="true"
			>
				0
			</span>
			<RichText
				key="clanbite-team-losses-postfix"
				tagName="span"
				className="clanbite-team-stat__postfix"
				value={ postfix }
				onChange={ ( v ) => setAttributes( { postfix: v ?? '' } ) }
				placeholder={ __( 'Postfix…', 'clanbite' ) }
				allowedFormats={ [ 'core/bold', 'core/italic', 'core/link' ] }
			/>
		</div>
	);
}
