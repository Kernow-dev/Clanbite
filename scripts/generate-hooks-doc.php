<?php
/**
 * Generates HOOKS.md: a reference of every filter and action a Clanbite plugin fires.
 *
 * The inline docblock above each `apply_filters()` / `do_action()` call (and the JSDoc above each
 * `wp.hooks` call) is the single source of truth; this script only reads it. Edit the docblock next to
 * the hook, then regenerate:
 *
 *     php scripts/generate-hooks-doc.php           # write HOOKS.md
 *     php scripts/generate-hooks-doc.php --check   # exit 1 when HOOKS.md is out of date (CI / pre-release)
 *
 * The script is dependency-free and self-contained so each Clanbite plugin can carry an identical copy.
 *
 * @package clanbite
 */

// This is a CLI tool, not plugin runtime code.
if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

$root     = dirname( __DIR__ );
$check    = in_array( '--check', $argv, true );
$plugin   = basename( $root );
$skip_dir = array( 'vendor', 'node_modules', 'build', 'dist', '.git', 'languages', 'tests' );

/**
 * Collect files with a given extension under a directory, skipping build output and dependencies.
 *
 * @param string   $dir      Directory to scan.
 * @param string[] $ext      Extensions without the dot.
 * @param string[] $skip_dir Directory names to skip.
 * @return string[] Absolute paths, sorted.
 */
function clanbite_hooks_doc_files( string $dir, array $ext, array $skip_dir ): array {
	$out = array();
	$it  = new RecursiveIteratorIterator(
		new RecursiveCallbackFilterIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			static function ( SplFileInfo $f ) use ( $skip_dir ): bool {
				return ! ( $f->isDir() && in_array( $f->getFilename(), $skip_dir, true ) );
			}
		)
	);
	foreach ( $it as $file ) {
		if ( $file->isFile() && in_array( strtolower( $file->getExtension() ), $ext, true ) ) {
			$out[] = $file->getPathname();
		}
	}
	sort( $out );
	return $out;
}

/**
 * Parse a docblock into summary, description, and tags.
 *
 * @param string $doc Raw docblock text.
 * @return array{summary: string, description: string, params: array<int, array{type: string, name: string, desc: string}>, tags: array<int, string>}
 */
function clanbite_hooks_doc_parse_docblock( string $doc ): array {
	$lines = preg_split( '/\R/', $doc );
	$clean = array();
	foreach ( $lines as $line ) {
		$line    = preg_replace( '#^\s*/\*\*+\s?|\s*\*/\s*$#', '', $line );
		$line    = preg_replace( '#^\s*\*\s?#', '', $line );
		$clean[] = rtrim( $line );
	}
	$text = trim( implode( "\n", $clean ) );
	// Inline PHPDoc tags would show as raw text in Markdown: render them as code.
	$text = preg_replace( '/\{@(?:see|link)\s+([^}\s]+)[^}]*\}/', '`$1`', $text );

	$desc_lines = array();
	$tag_lines  = array();
	$in_tags    = false;
	foreach ( explode( "\n", $text ) as $line ) {
		if ( preg_match( '/^@\w+/', $line ) ) {
			$in_tags = true;
		}
		if ( $in_tags ) {
			if ( preg_match( '/^@\w+/', $line ) || '' === trim( $line ) ) {
				$tag_lines[] = $line;
			} elseif ( $tag_lines ) {
				$tag_lines[ count( $tag_lines ) - 1 ] .= ' ' . trim( $line );
			}
		} else {
			$desc_lines[] = $line;
		}
	}

	$desc    = trim( implode( "\n", $desc_lines ) );
	$parts   = preg_split( "/\n\s*\n/", $desc, 2 );
	$summary = trim( preg_replace( '/\s+/', ' ', (string) ( $parts[0] ?? '' ) ) );
	$rest    = trim( (string) ( $parts[1] ?? '' ) );

	$params = array();
	$tags   = array();
	foreach ( $tag_lines as $tag ) {
		$tag = trim( $tag );
		if ( '' === $tag ) {
			continue;
		}
		if ( preg_match( '/^@param\s+(.*)$/s', $tag, $m ) ) {
			$params[] = clanbite_hooks_doc_split_param( $m[1] );
		} else {
			$tags[] = $tag;
		}
	}

	return array(
		'summary'     => $summary,
		'description' => $rest,
		'params'      => $params,
		'tags'        => $tags,
	);
}

/**
 * Split the text after `@param` into type, name and description.
 *
 * The type may contain spaces inside generics or shapes (`array<string, mixed>`, `{ a: int, b: string }`), so it is
 * read with bracket depth rather than a regular expression. The `{...}` form used by JSDoc is supported as well.
 *
 * @param string $text Text after `@param`.
 * @return array{type: string, name: string, desc: string}
 */
function clanbite_hooks_doc_split_param( string $text ): array {
	$text  = trim( $text );
	$depth = 0;
	$len   = strlen( $text );
	$i     = 0;
	for ( ; $i < $len; $i++ ) {
		$c = $text[ $i ];
		if ( '<' === $c || '{' === $c || '(' === $c || '[' === $c ) {
			++$depth;
		} elseif ( '>' === $c || '}' === $c || ')' === $c || ']' === $c ) {
			--$depth;
		} elseif ( $depth <= 0 && ctype_space( $c ) ) {
			break;
		}
	}
	$type = substr( $text, 0, $i );
	$rest = ltrim( substr( $text, $i ) );
	// A variable name (or the first word, for JSDoc) follows the type.
	if ( preg_match( '/^(\.\.\.\$\w+|&?\$\w+|\S+)\s*(.*)$/s', $rest, $m ) ) {
		return array(
			'type' => $type,
			'name' => $m[1],
			'desc' => trim( preg_replace( '/\s+/', ' ', $m[2] ) ),
		);
	}
	return array(
		'type' => '',
		'name' => $type,
		'desc' => '',
	);
}

/**
 * Resolve a double-quoted hook name made of several tokens to a readable pattern (`{type}` placeholders).
 *
 * @param array<int, mixed> $tokens Tokens between the opening and closing quote.
 * @return string
 */
function clanbite_hooks_doc_encapsed_name( array $tokens ): string {
	$name  = '';
	$depth = 0;
	$expr  = array();
	foreach ( $tokens as $t ) {
		$id  = is_array( $t ) ? $t[0] : $t;
		$txt = is_array( $t ) ? $t[1] : $t;
		if ( T_CURLY_OPEN === $id || T_DOLLAR_OPEN_CURLY_BRACES === $id ) {
			++$depth;
			$expr = array();
			continue;
		}
		if ( $depth > 0 ) {
			if ( '}' === $id ) {
				--$depth;
				if ( 0 === $depth ) {
					$name .= '{' . ( end( $expr ) ?: 'value' ) . '}';
				}
				continue;
			}
			if ( T_CONSTANT_ENCAPSED_STRING === $id ) {
				$expr[] = trim( $txt, "'\"" );
			} elseif ( T_STRING === $id ) {
				$expr[] = $txt;
			} elseif ( T_VARIABLE === $id && 1 === count( $expr ) + 1 && array() === $expr ) {
				$expr[] = ltrim( $txt, '$' );
			}
			continue;
		}
		if ( T_VARIABLE === $id ) {
			$name .= '{' . ltrim( $txt, '$' ) . '}';
		} elseif ( T_ENCAPSED_AND_WHITESPACE === $id ) {
			$name .= $txt;
		}
	}
	return $name;
}

/**
 * Index of the matching opening parenthesis/bracket when scanning backwards from a closer.
 *
 * @param array<int, mixed> $tokens Token list.
 * @param int               $i      Index of the closing token.
 * @return int
 */
function clanbite_hooks_doc_match_open( array $tokens, int $i ): int {
	$depth = 0;
	for ( ; $i >= 0; $i-- ) {
		$t = is_array( $tokens[ $i ] ) ? $tokens[ $i ][1] : $tokens[ $i ];
		if ( ')' === $t || ']' === $t ) {
			++$depth;
		} elseif ( '(' === $t || '[' === $t ) {
			--$depth;
			if ( 0 === $depth ) {
				return $i;
			}
		}
	}
	return 0;
}

/**
 * Find the docblock that documents the call at token index $c.
 *
 * Scans back to the start of the statement, through enclosing call/array parentheses, and accepts a docblock
 * that sits directly above the statement, above an enclosing `if`/`foreach`, or inside an array literal
 * directly above the element. A docblock that precedes a declaration is never used.
 *
 * @param array<int, mixed> $tokens Token list.
 * @param int               $c      Index of the hook function token.
 * @return string|null Docblock text.
 */
function clanbite_hooks_doc_find_docblock( array $tokens, int $c ): ?string {
	$depth = 0;
	for ( $i = $c - 1; $i >= 0; $i-- ) {
		$t  = $tokens[ $i ];
		$id = is_array( $t ) ? $t[0] : $t;
		if ( T_DOC_COMMENT === $id ) {
			return $t[1];
		}
		if ( in_array( $id, array( T_WHITESPACE, T_COMMENT ), true ) ) {
			continue;
		}
		if ( ')' === $id || ']' === $id ) {
			++$depth;
			continue;
		}
		if ( '(' === $id || '[' === $id ) {
			if ( $depth > 0 ) {
				--$depth;
			}
			continue; // An unmatched opener is an enclosing call or array: keep walking out of it.
		}
		if ( $depth > 0 ) {
			continue;
		}
		if ( in_array( $id, array( T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_INLINE_HTML, T_CLOSE_TAG ), true ) ) {
			return null;
		}
		if ( ';' === $id || '}' === $id ) {
			return null;
		}
		if ( '{' === $id ) {
			// Block start: a docblock above the control structure that opened it documents this call.
			$j = $i - 1;
			while ( $j >= 0 && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
				--$j;
			}
			if ( $j >= 0 && ')' === ( is_array( $tokens[ $j ] ) ? $tokens[ $j ][1] : $tokens[ $j ] ) ) {
				$j = clanbite_hooks_doc_match_open( $tokens, $j ) - 1;
				while ( $j >= 0 && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
					--$j;
				}
				if ( $j >= 0 && is_array( $tokens[ $j ] ) && in_array( $tokens[ $j ][0], array( T_IF, T_ELSEIF, T_FOREACH, T_FOR, T_WHILE, T_SWITCH ), true ) ) {
					--$j;
					while ( $j >= 0 && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
						--$j;
					}
					if ( $j >= 0 && is_array( $tokens[ $j ] ) && T_DOC_COMMENT === $tokens[ $j ][0] ) {
						return $tokens[ $j ][1];
					}
				}
			}
			return null;
		}
	}
	return null;
}

/**
 * Human-readable area for a source path (used to group the reference).
 *
 * @param string $rel Path relative to the plugin root.
 * @return string
 */
function clanbite_hooks_doc_area( string $rel ): string {
	$map = array(
		'#(^|/)avatar-helpers\.php$#'                                  => 'Avatars',
		'#(^|/)functions-country-flags\.php$#'                         => 'Avatars',
		'#(^|/)(players|player-[a-z-]+)(/|\.php)#'                     => 'Players',
		'#(^|/)(teams|team-[a-z-]+)(/|\.php)|team-challenges#'         => 'Teams',
		'#(^|/)(matches|match-[a-z-]+)(/|\.php)|cross-site-match#'     => 'Matches',
		'#(^|/)(events|event-[a-z-]+)(/|\.php)#'                       => 'Events',
		'#(^|/)(notifications|notification-[a-z-]+)(/|\.php)#'         => 'Notifications',
		'#(^|/)admin(/|\.php)|class-admin-#'                           => 'Admin',
		'#(^|/)extensions/class-extension-|class-extension-loader#'    => 'Extensions',
		'#(^|/)profile-subpages\.php$|(^|/)groups/#'                   => 'Profile sub-pages and groups',
		'#(^|/)(Rest|rest)(/|\.php)|rest-controller#i'                 => 'REST',
		'#(^|/)(Forums|forums)#'                                       => 'Forums',
		'#(^|/)(Social|social)#'                                       => 'Social',
		'#(^|/)(Points|points)#'                                       => 'Points',
		'#(^|/)(Ranks|ranks)#'                                         => 'Ranks',
	);
	foreach ( $map as $re => $area ) {
		if ( preg_match( $re, $rel ) ) {
			return $area;
		}
	}
	return 'General';
}

/**
 * Escape a string for use inside a Markdown table cell or inline.
 *
 * @param string $s Text.
 * @return string
 */
function clanbite_hooks_doc_md( string $s ): string {
	return str_replace( array( '|', "\n" ), array( '\\|', ' ' ), $s );
}

$hooks = array(); // name => entry.

// -------- PHP hooks (token based). --------
$fn_names = array( 'apply_filters', 'apply_filters_ref_array', 'do_action', 'do_action_ref_array' );
foreach ( clanbite_hooks_doc_files( $root, array( 'php' ), $skip_dir ) as $file ) {
	$rel    = ltrim( str_replace( $root, '', $file ), '/' );
	$source = (string) file_get_contents( $file );
	$tokens = token_get_all( $source );
	$count  = count( $tokens );

	// Constants (e.g. HOOK_TAG_*) used to build dynamic names through a helper.
	$consts = array();
	if ( preg_match_all( "/const\s+(HOOK_TAG_[A-Z_]+)\s*=\s*'([^']+)'/", $source, $cm, PREG_SET_ORDER ) ) {
		foreach ( $cm as $c ) {
			$consts[ $c[1] ] = $c[2];
		}
	}
	// Registry-config filters: `'filter' => 'clanbite_x_subpages'` applied as apply_filters( $config['filter'], ... ).
	$registry_filters = array();
	if ( preg_match_all( "/'filter'\s*=>\s*'([a-z0-9_]+)'/", $source, $rm ) ) {
		$registry_filters = $rm[1];
	}

	for ( $i = 0; $i < $count; $i++ ) {
		$t = $tokens[ $i ];
		if ( ! is_array( $t ) ) {
			continue;
		}
		$fn = ltrim( $t[1], '\\' );
		if ( ! in_array( $t[0], array( T_STRING, T_NAME_FULLY_QUALIFIED ), true ) || ! in_array( $fn, $fn_names, true ) ) {
			continue;
		}
		// Must be a call: next significant token is "(" and the previous is not "function"/"->"/"::".
		$j = $i + 1;
		while ( $j < $count && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) {
			++$j;
		}
		if ( '(' !== ( $tokens[ $j ] ?? null ) ) {
			continue;
		}
		$p = $i - 1;
		while ( $p >= 0 && is_array( $tokens[ $p ] ) && T_WHITESPACE === $tokens[ $p ][0] ) {
			--$p;
		}
		if ( $p >= 0 && is_array( $tokens[ $p ] ) && in_array( $tokens[ $p ][0], array( T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON ), true ) ) {
			continue;
		}

		// First argument.
		$k = $j + 1;
		while ( $k < $count && is_array( $tokens[ $k ] ) && T_WHITESPACE === $tokens[ $k ][0] ) {
			++$k;
		}
		$names = array();
		$first = $tokens[ $k ] ?? null;
		if ( is_array( $first ) && T_CONSTANT_ENCAPSED_STRING === $first[0] ) {
			$names[] = trim( $first[1], "'\"" );
		} elseif ( '"' === $first ) {
			$inner = array();
			for ( $m = $k + 1; $m < $count && '"' !== $tokens[ $m ]; $m++ ) {
				$inner[] = $tokens[ $m ];
			}
			$names[] = clanbite_hooks_doc_encapsed_name( $inner );
		} else {
			// Helper-built name: $this->clanbite_extension_settings_hook_tag( self::HOOK_TAG_X ).
			$snippet = '';
			for ( $m = $k; $m < min( $count, $k + 14 ); $m++ ) {
				$snippet .= is_array( $tokens[ $m ] ) ? $tokens[ $m ][1] : $tokens[ $m ];
			}
			if ( preg_match( '/clanbite_extension_settings_hook_tag\(\s*self::(HOOK_TAG_[A-Z_]+)/', $snippet, $hm ) && isset( $consts[ $hm[1] ] ) ) {
				$names[] = '{option_key}_' . $consts[ $hm[1] ];
			} elseif ( preg_match( '/^\$config\[\s*\'filter\'\s*\]/', $snippet ) && $registry_filters ) {
				$names = $registry_filters;
			} else {
				continue;
			}
		}

		$line = 1 + substr_count( implode( '', array_map( static fn( $x ) => is_array( $x ) ? $x[1] : $x, array_slice( $tokens, 0, $i ) ) ), "\n" );
		$doc  = clanbite_hooks_doc_find_docblock( $tokens, $i );
		foreach ( $names as $name ) {
			if ( '' === $name || 'profile_update' === $name || ! preg_match( '/^[A-Za-z0-9_{}]+$/', $name ) ) {
				continue;
			}
			if ( ! isset( $hooks[ $name ] ) ) {
				$hooks[ $name ] = array(
					'name'  => $name,
					'kind'  => ( 0 === strpos( $fn, 'apply' ) ) ? 'filter' : 'action',
					'lang'  => 'php',
					'file'  => $rel,
					'line'  => $line,
					'doc'   => $doc,
					'extra' => array(),
				);
			} else {
				$hooks[ $name ]['extra'][] = $rel . ':' . $line;
				if ( null === $hooks[ $name ]['doc'] && null !== $doc ) {
					$hooks[ $name ]['doc']  = $doc;
					$hooks[ $name ]['file'] = $rel;
					$hooks[ $name ]['line'] = $line;
				}
			}
		}
	}
}

// -------- JavaScript hooks (wp.hooks). --------
$js_hooks = array();
foreach ( clanbite_hooks_doc_files( $root, array( 'js', 'jsx', 'mjs' ), $skip_dir ) as $file ) {
	$rel   = ltrim( str_replace( $root, '', $file ), '/' );
	$lines = preg_split( '/\R/', (string) file_get_contents( $file ) );
	$src   = implode( "\n", $lines );
	if ( ! preg_match_all( "/\b(applyFilters|doAction)\??\.?\(\s*(['\"])(clanbite[.\w-]*)\\2/", $src, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER ) ) {
		continue;
	}
	foreach ( $matches as $m ) {
		$line = 1 + substr_count( substr( $src, 0, $m[0][1] ), "\n" );
		$name = $m[3][0];
		// Statement start: walk up while the previous non-blank line is not a terminator or comment end.
		$i = $line - 1;
		while ( $i > 0 ) {
			$prev = $i - 1;
			while ( $prev >= 0 && '' === trim( $lines[ $prev ] ) ) {
				--$prev;
			}
			if ( $prev < 0 || preg_match( '/(;|\{|\}|\*\/)\s*$/', $lines[ $prev ] ) || 0 === strpos( ltrim( $lines[ $prev ] ), '//' ) ) {
				break;
			}
			$i = $prev;
		}
		$doc  = null;
		$prev = $i - 1;
		while ( $prev >= 0 && '' === trim( $lines[ $prev ] ) ) {
			--$prev;
		}
		if ( $prev >= 0 && preg_match( '#\*/\s*$#', $lines[ $prev ] ) ) {
			$start = $prev;
			while ( $start >= 0 && 0 !== strpos( ltrim( $lines[ $start ] ), '/*' ) ) {
				--$start;
			}
			if ( $start >= 0 && 0 === strpos( ltrim( $lines[ $start ] ), '/**' ) ) {
				$doc = implode( "\n", array_slice( $lines, $start, $prev - $start + 1 ) );
			}
		}
		if ( ! isset( $js_hooks[ $name ] ) ) {
			$js_hooks[ $name ] = array(
				'name'  => $name,
				'kind'  => 'applyFilters' === $m[1][0] ? 'filter' : 'action',
				'lang'  => 'js',
				'file'  => $rel,
				'line'  => $line,
				'doc'   => $doc,
				'extra' => array(),
			);
		} else {
			$js_hooks[ $name ]['extra'][] = $rel . ':' . $line;
			if ( null === $js_hooks[ $name ]['doc'] && null !== $doc ) {
				$js_hooks[ $name ]['doc']  = $doc;
				$js_hooks[ $name ]['file'] = $rel;
				$js_hooks[ $name ]['line'] = $line;
			}
		}
	}
}

/**
 * Render one hook as Markdown.
 *
 * @param array<string, mixed> $h Hook entry.
 * @return string
 */
function clanbite_hooks_doc_render( array $h ): string {
	$parsed = null !== $h['doc'] ? clanbite_hooks_doc_parse_docblock( $h['doc'] ) : null;
	$md     = '#### `' . $h['name'] . "`\n\n";
	$md    .= '*' . ( 'filter' === $h['kind'] ? 'Filter' : 'Action' ) . ( 'js' === $h['lang'] ? ' (JavaScript, `wp.hooks`)' : '' ) . '* · `' . $h['file'] . "`\n\n";
	if ( null === $parsed || '' === $parsed['summary'] ) {
		$md .= "_Undocumented: add a docblock above the call._\n\n";
		return $md;
	}
	$md .= $parsed['summary'] . "\n\n";
	if ( '' !== $parsed['description'] ) {
		$md .= $parsed['description'] . "\n\n";
	}
	if ( $parsed['params'] ) {
		$md .= "| Parameter | Type | Description |\n| --- | --- | --- |\n";
		foreach ( $parsed['params'] as $p ) {
			$md .= '| `' . clanbite_hooks_doc_md( $p['name'] ) . '` | `' . clanbite_hooks_doc_md( $p['type'] ) . '` | ' . clanbite_hooks_doc_md( $p['desc'] ) . " |\n";
		}
		$md .= "\n";
	} else {
		$md .= "_No arguments._\n\n";
	}
	foreach ( $parsed['tags'] as $tag ) {
		if ( preg_match( '/^@(since|deprecated|see|link)\s+(.*)$/s', $tag, $tm ) ) {
			$md .= '**' . ucfirst( $tm[1] ) . ':** ' . trim( $tm[2] ) . "\n\n";
		}
	}
	if ( $h['extra'] ) {
		// Line numbers are omitted on purpose: they change with every unrelated edit and would make --check noisy.
		$files = array_values( array_unique( array_map( static fn( $e ) => preg_replace( '/:\d+$/', '', $e ), $h['extra'] ) ) );
		$files = array_values( array_diff( $files, array( $h['file'] ) ) );
		if ( $files ) {
			$md .= '*Also fired in:* `' . implode( '`, `', $files ) . "`\n\n";
		}
	}
	return $md;
}

// -------- Assemble. --------
$by_area = array();
foreach ( $hooks as $h ) {
	$by_area[ clanbite_hooks_doc_area( $h['file'] ) ][] = $h;
}
ksort( $by_area );

$undocumented = array_filter(
	array_merge( $hooks, $js_hooks ),
	static fn( $h ) => null === $h['doc'] || '' === ( clanbite_hooks_doc_parse_docblock( $h['doc'] )['summary'] )
);

$out  = '# ' . $plugin . " hook reference\n\n";
$out .= "> **Generated file: do not edit.** Source of truth is the docblock above each `apply_filters()` / `do_action()` call\n";
$out .= "> (and the JSDoc above each `wp.hooks` call). Edit that, then run `php scripts/generate-hooks-doc.php`.\n";
$out .= "> `php scripts/generate-hooks-doc.php --check` fails when this file is stale.\n\n";
$out .= '**' . count( $hooks ) . ' PHP hooks';
$out .= $js_hooks ? ' and ' . count( $js_hooks ) . ' JavaScript hooks' : '';
$out .= "**";
$out .= $undocumented ? ' (' . count( $undocumented ) . " without a docblock)" : '';
$out .= ".\n\n";
$out .= "Dynamic hook names use placeholders: `{type}`, `{context}`, `{slug}`… stand for the value that is substituted at run time.\n\n";

$out .= "## Contents\n\n";
foreach ( $by_area as $area => $list ) {
	$out .= '- [' . $area . '](#' . strtolower( preg_replace( '/[^a-z0-9]+/i', '-', $area ) ) . ') (' . count( $list ) . ")\n";
}
if ( $js_hooks ) {
	$out .= '- [JavaScript hooks](#javascript-hooks) (' . count( $js_hooks ) . ")\n";
}
$out .= "\n";

foreach ( $by_area as $area => $list ) {
	usort( $list, static fn( $a, $b ) => strcmp( $a['name'], $b['name'] ) );
	$out .= '## ' . $area . "\n\n";
	foreach ( $list as $h ) {
		$out .= clanbite_hooks_doc_render( $h );
	}
}

if ( $js_hooks ) {
	ksort( $js_hooks );
	$out .= "## JavaScript hooks\n\n";
	$out .= "Registered with `wp.hooks.addFilter()` / `addAction()` on the front end or in the editor.\n\n";
	foreach ( $js_hooks as $h ) {
		$out .= clanbite_hooks_doc_render( $h );
	}
}

$out = rtrim( $out ) . "\n";
$dest = $root . '/HOOKS.md';

if ( $check ) {
	$current = is_readable( $dest ) ? (string) file_get_contents( $dest ) : '';
	if ( $current !== $out ) {
		fwrite( STDERR, "HOOKS.md is out of date. Run: php scripts/generate-hooks-doc.php\n" );
		exit( 1 );
	}
	fwrite( STDOUT, "HOOKS.md is up to date (" . count( $hooks ) . ' PHP, ' . count( $js_hooks ) . " JS hooks).\n" );
	exit( 0 );
}

file_put_contents( $dest, $out );
fwrite( STDOUT, 'Wrote HOOKS.md: ' . count( $hooks ) . ' PHP hooks, ' . count( $js_hooks ) . ' JS hooks, ' . count( $undocumented ) . " undocumented.\n" );
