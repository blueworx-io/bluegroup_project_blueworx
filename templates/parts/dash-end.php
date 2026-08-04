<?php
/**
 * Closes the client-area frame opened by parts/dash-shell.php.
 *
 * No site footer: this is an application shell whose main column scrolls on
 * its own, so a marketing footer would sit inside that column rather than
 * under the page.
 *
 * @package BlueWorxSite
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
			</div>
		</div>
	</main>
</div>
<?php
blueworx_public_document_close();
