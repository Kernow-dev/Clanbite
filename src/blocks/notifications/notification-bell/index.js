/**
 * Notification Bell block editor script.
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';
import { ServerPreview } from '../../shared/server-preview';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import { clanbiteBlockIcon } from '../../shared/block-icons/icons/notification-bell';

registerBlockType( metadata, {
	icon: clanbiteBlockIcon(),
	edit: function Edit( { attributes, setAttributes } ) {
		const { showDropdown, dropdownCount } = attributes;
		const blockProps = useBlockProps();

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Settings', 'clanbite' ) }>
						<ToggleControl
							label={ __( 'Show dropdown on click', 'clanbite' ) }
							checked={ showDropdown }
							onChange={ ( value ) =>
								setAttributes( { showDropdown: value } )
							}
							__nextHasNoMarginBottom
						/>
						{ showDropdown && (
							<RangeControl
								__nextHasNoMarginBottom
								label={ __(
									'Notifications in dropdown',
									'clanbite'
								) }
								value={ dropdownCount }
								onChange={ ( value ) =>
									setAttributes( { dropdownCount: value } )
								}
								min={ 3 }
								max={ 20 }
							/>
						) }
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerPreview
						block="clanbite/notification-bell"
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
} );
