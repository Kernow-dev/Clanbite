import { __ } from '@wordpress/i18n';
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
	const { teamName, gameTitle, description } = attributes;

	return (
		<div { ...useBlockProps() }>
			<RichText
				tagName="h3"
				value={ teamName }
				onChange={ ( value ) => setAttributes( { teamName: value } ) }
				placeholder={ __( 'Team name', 'clanbite' ) }
			/>
			<RichText
				tagName="p"
				value={ gameTitle }
				onChange={ ( value ) => setAttributes( { gameTitle: value } ) }
				placeholder={ __( 'Game title (optional)', 'clanbite' ) }
			/>
			<RichText
				tagName="p"
				value={ description }
				onChange={ ( value ) =>
					setAttributes( { description: value } )
				}
				placeholder={ __(
					'Team description (optional)',
					'clanbite'
				) }
			/>
		</div>
	);
}
