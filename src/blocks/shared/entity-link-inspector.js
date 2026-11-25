/**
 * Inspector “Link” panel (mirrors core post title link options).
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
/* eslint-disable @wordpress/no-unsafe-wp-apis -- No stable export of the tools panel exists yet (see AGENTS.md). */
import {
	ToggleControl,
	TextControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
/* eslint-enable @wordpress/no-unsafe-wp-apis */

/**
 * Inspector panel with the link options of an entity block.
 *
 * @param {Object}                            props
 * @param {Object}                            props.attributes    Block attributes.
 * @param {( ...args: unknown[] ) => unknown} props.setAttributes setAttributes.
 * @param {string}                            props.toggleLabel   Label for the “link to entity” toggle.
 * @return {import('react').ReactNode} The inspector controls.
 */
export function EntityLinkInspector( {
	attributes,
	setAttributes,
	toggleLabel,
} ) {
	const { isLink, linkTarget, rel } = attributes;

	return (
		<InspectorControls>
			<ToolsPanel
				label={ __( 'Link', 'clanbite' ) }
				resetAll={ () =>
					setAttributes( {
						isLink: false,
						linkTarget: '_self',
						rel: '',
					} )
				}
			>
				<ToolsPanelItem
					label={ toggleLabel }
					isShownByDefault
					hasValue={ () => !! isLink }
					onDeselect={ () =>
						setAttributes( {
							isLink: false,
							linkTarget: '_self',
							rel: '',
						} )
					}
				>
					<ToggleControl
						label={ toggleLabel }
						checked={ !! isLink }
						onChange={ ( v ) => setAttributes( { isLink: !! v } ) }
						__nextHasNoMarginBottom
					/>
				</ToolsPanelItem>
				{ isLink ? (
					<>
						<ToolsPanelItem
							label={ __( 'Open in new tab', 'clanbite' ) }
							isShownByDefault
							hasValue={ () => linkTarget === '_blank' }
							onDeselect={ () =>
								setAttributes( { linkTarget: '_self' } )
							}
						>
							<ToggleControl
								label={ __( 'Open in new tab', 'clanbite' ) }
								checked={ linkTarget === '_blank' }
								onChange={ ( v ) =>
									setAttributes( {
										linkTarget: v ? '_blank' : '_self',
									} )
								}
								__nextHasNoMarginBottom
							/>
						</ToolsPanelItem>
						<ToolsPanelItem
							label={ __( 'Link rel', 'clanbite' ) }
							isShownByDefault
							hasValue={ () => !! rel }
							onDeselect={ () => setAttributes( { rel: '' } ) }
						>
							<TextControl
								label={ __( 'Link rel', 'clanbite' ) }
								value={ rel || '' }
								onChange={ ( v ) =>
									setAttributes( { rel: v ?? '' } )
								}
								help={ __(
									'Optional rel value. noopener and noreferrer are added automatically for new tabs.',
									'clanbite'
								) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
						</ToolsPanelItem>
					</>
				) : null }
			</ToolsPanel>
		</InspectorControls>
	);
}
