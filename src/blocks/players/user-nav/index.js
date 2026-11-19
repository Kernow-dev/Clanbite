/**
 * User Navigation block editor script.
 */
import { registerBlockType } from '@wordpress/blocks';
import { ServerPreview } from '../../shared/server-preview';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './style.scss';

import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/user-nav';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: function Edit( { attributes, setAttributes } ) {
		const { avatarSize, showUsername } = attributes;
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'clanbite' ) }>
						<RangeControl
							__nextHasNoMarginBottom
							label={ __( 'Avatar Size', 'clanbite' ) }
							value={ avatarSize }
							onChange={ ( value ) =>
								setAttributes( { avatarSize: value } )
							}
							min={ 20 }
							max={ 64 }
							step={ 2 }
						/>
						<ToggleControl
							label={ __( 'Show Username', 'clanbite' ) }
							help={ __(
								'Display the username next to the avatar.',
								'clanbite'
							) }
							checked={ showUsername }
							onChange={ ( value ) =>
								setAttributes( { showUsername: value } )
							}
							__nextHasNoMarginBottom
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerPreview
						block="clanbite/user-nav"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
} );
