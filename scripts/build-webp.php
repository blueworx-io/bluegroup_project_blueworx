<?php
/**
 * Builds a WebP copy of every photographic asset, beside the original.
 *
 * The site shipped JPEG and PNG only. WebP is roughly a third smaller at the
 * same quality and every browser the site supports reads it, so the originals
 * stay as the fallback and the WebP is what almost everyone actually
 * downloads.
 *
 * The output is committed alongside the source images: it is part of the
 * plugin, and a zip built on a machine without this script having run must not
 * be missing half its images. Re-run it after adding or replacing an image:
 *
 *   php scripts/build-webp.php
 *
 * Skips a file whose WebP twin is already newer than it, so a re-run costs
 * nothing. The tool icons are deliberately included: twelve small PNGs on one
 * page still add up.
 *
 * @package BlueWorxSite
 */

if ( 'cli' !== PHP_SAPI ) {
	exit( 1 );
}

if ( ! function_exists( 'imagewebp' ) ) {
	fwrite( STDERR, "This PHP has no WebP support (GD without WebP). Nothing written.\n" );
	exit( 1 );
}

$quality = 82;
$root    = dirname( __DIR__ ) . '/assets/img';
$written = 0;
$skipped = 0;

$files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );

foreach ( $files as $file ) {
	$path = $file->getPathname();

	if ( ! preg_match( '/\.(jpe?g|png)$/i', $path ) ) {
		continue;
	}

	$target = preg_replace( '/\.(jpe?g|png)$/i', '.webp', $path );

	if ( file_exists( $target ) && filemtime( $target ) >= filemtime( $path ) ) {
		++$skipped;
		continue;
	}

	$image = preg_match( '/\.png$/i', $path ) ? imagecreatefrompng( $path ) : imagecreatefromjpeg( $path );

	if ( ! $image ) {
		fwrite( STDERR, "Could not read {$path}\n" );
		continue;
	}

	// PNGs on this site are logos and icons with transparent backgrounds.
	// Without these two calls the alpha channel is thrown away and every icon
	// gains a black box.
	imagepalettetotruecolor( $image );
	imagealphablending( $image, false );
	imagesavealpha( $image, true );

	if ( ! imagewebp( $image, $target, $quality ) ) {
		fwrite( STDERR, "Could not write {$target}\n" );
	} elseif ( filesize( $target ) >= filesize( $path ) ) {
		// WebP is not smaller for every image — a handful of the tool icons are
		// already about as small as a PNG gets. Shipping a bigger "optimised"
		// file and asking the browser to prefer it makes the page slower, so
		// the twin is thrown away and the original is served on its own.
		unlink( $target );
		++$skipped;
		printf( "%s  skipped, WebP is no smaller\n", basename( $path ) );
	} else {
		++$written;
		printf( "%s  %d KB -> %d KB\n", basename( $target ), (int) ( filesize( $path ) / 1024 ), (int) ( filesize( $target ) / 1024 ) );
	}

	imagedestroy( $image );
}

printf( "\n%d written, %d already up to date.\n", $written, $skipped );
