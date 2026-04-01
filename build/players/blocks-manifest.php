<?php
// This file is generated. Do not modify it manually.
return array(
	'player-avatar' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-avatar',
		'version' => '0.1.0',
		'title' => 'Player Avatar',
		'category' => 'clanspress-players',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true
		),
		'textdomain' => 'player-avatar',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'player-cover' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-cover',
		'version' => '1.0.0',
		'title' => 'Player Cover',
		'category' => 'clanspress-players',
		'icon' => 'smiley',
		'description' => 'Example block scaffolded with Create Block tool.',
		'example' => array(
			
		),
		'textdomain' => 'player-cover',
		'attributes' => array(
			'id' => array(
				'type' => 'number'
			),
			'minHeight' => array(
				'type' => 'number'
			),
			'minHeightUnit' => array(
				'type' => 'string'
			),
			'contentPosition' => array(
				'type' => 'string'
			),
			'templateLock' => array(
				'type' => array(
					'string',
					'boolean'
				),
				'enum' => array(
					'all',
					'insert',
					'contentOnly',
					false
				)
			),
			'sizeSlug' => array(
				'type' => 'string'
			)
		),
		'usesContext' => array(
			'postId',
			'postType'
		),
		'supports' => array(
			'anchor' => true,
			'align' => true,
			'html' => false,
			'shadow' => true,
			'spacing' => array(
				'padding' => true,
				'margin' => array(
					'top',
					'bottom'
				),
				'blockGap' => true,
				'__experimentalDefaultControls' => array(
					'padding' => true,
					'blockGap' => true
				)
			),
			'__experimentalBorder' => array(
				'color' => true,
				'radius' => true,
				'style' => true,
				'width' => true,
				'__experimentalDefaultControls' => array(
					'color' => true,
					'radius' => true,
					'style' => true,
					'width' => true
				)
			),
			'color' => array(
				'heading' => true,
				'text' => true,
				'background' => false,
				'__experimentalSkipSerialization' => array(
					'gradients'
				),
				'enableContrastChecker' => false
			),
			'dimensions' => array(
				'aspectRatio' => true
			),
			'typography' => array(
				'fontSize' => true,
				'lineHeight' => true,
				'__experimentalFontFamily' => true,
				'__experimentalFontWeight' => true,
				'__experimentalFontStyle' => true,
				'__experimentalTextTransform' => true,
				'__experimentalTextDecoration' => true,
				'__experimentalLetterSpacing' => true,
				'__experimentalDefaultControls' => array(
					'fontSize' => true
				)
			),
			'layout' => array(
				'allowJustification' => false
			),
			'interactivity' => true,
			'filter' => array(
				'duotone' => true
			),
			'allowedBlocks' => true
		),
		'selectors' => array(
			'filter' => array(
				'duotone' => '.wp-block-clanspress-player-cover > .player-cover__image-background, .wp-block-clanspress-player-cover > .player-cover__video-background'
			)
		),
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'player-settings' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/player-settings',
		'version' => '0.1.0',
		'title' => 'Player Settings',
		'category' => 'clanspress',
		'icon' => 'smiley',
		'description' => 'Block for outputting player settings for their profile.',
		'example' => array(
			
		),
		'supports' => array(
			'html' => false,
			'interactivity' => true
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'editorStyle' => 'file:./index.css',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php',
		'viewScriptModule' => 'file:./view.js'
	),
	'profile-nav' => array(
		'apiVersion' => 2,
		'name' => 'clanspress/player-profile-nav',
		'title' => 'Player Profile Navigation',
		'category' => 'widgets',
		'icon' => 'groups',
		'description' => 'Displays the player profile subpage navigation.',
		'supports' => array(
			'html' => false
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style.scss',
		'render' => 'file:./render.php'
	),
	'user-nav' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'clanspress/user-nav',
		'version' => '1.0.0',
		'title' => 'User Navigation',
		'category' => 'clanspress',
		'icon' => 'admin-users',
		'description' => 'Displays login/register links for guests, or user avatar with dropdown menu for logged-in users.',
		'supports' => array(
			'html' => false,
			'align' => false,
			'className' => true,
			'interactivity' => true
		),
		'attributes' => array(
			'avatarSize' => array(
				'type' => 'number',
				'default' => 32
			),
			'showUsername' => array(
				'type' => 'boolean',
				'default' => false
			)
		),
		'textdomain' => 'clanspress',
		'editorScript' => 'file:./index.js',
		'viewScriptModule' => 'file:./view.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);
