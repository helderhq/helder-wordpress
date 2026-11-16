<?php
/**
 * Small tests without a WordPress install: the token format must match Helder's
 * (packages/ai/src/verificatie-token.ts in the monorepo) and the loader tag must carry the key.
 * The full flow is covered by the e2e test with wp-env.
 *
 * @package Helder
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HELDER_WIDGET_URL', 'https://widget.helderhq.com/helder.js' );

$GLOBALS['helder_test_optie'] = null;

function wp_json_encode( $data ) {
	return json_encode( $data );
}
function get_option( $naam ) {
	return $GLOBALS['helder_test_optie'];
}
function esc_attr( $tekst ) {
	return htmlspecialchars( (string) $tekst, ENT_QUOTES );
}
function esc_url( $url ) {
	return $url;
}
function add_action() {}
function add_filter() {}

require __DIR__ . '/../includes/class-helder-opslag.php';
require __DIR__ . '/../includes/class-helder-klanttoken.php';
require __DIR__ . '/../includes/class-helder-script.php';

$fouten = 0;
function verwacht( $naam, $kreeg, $wilde ) {
	global $fouten;
	if ( $kreeg === $wilde ) {
		echo "ok   $naam\n";
		return;
	}
	++$fouten;
	echo "FOUT $naam\n  kreeg: " . var_export( $kreeg, true ) . "\n  wilde: " . var_export( $wilde, true ) . "\n";
}

// Same vector as Node: base64url(JSON) . base64url(HMAC-SHA256('geheim', payload)).
verwacht(
	'klanttoken in het headless-formaat van Helder',
	Helder_Klanttoken::maak( 'geheim', 'klant@voorbeeld.nl', 1794800000 ),
	'eyJlbWFpbCI6ImtsYW50QHZvb3JiZWVsZC5ubCIsImV4cCI6MTc5NDgwMDMwMH0.w7izCt3fifJWEo-uvz0czjjB9P_gzX0DmhazxLfO-xY'
);

verwacht(
	'zonder koppeling geen sleutel op de tag',
	Helder_Script::sleutel_op_tag( '<script src="https://widget.helderhq.com/helder.js" id="helder-js"></script>', 'helder' ),
	'<script src="https://widget.helderhq.com/helder.js" id="helder-js"></script>'
);

$GLOBALS['helder_test_optie'] = array(
	'sleutel'            => 'org_123',
	'koppel_id'          => 'id',
	'klant_login_geheim' => 'g',
	'host'               => 'site.nl',
);
$tag = "<script id=\"helder-js-before\">\nwindow.HelderWidget={};\n</script>\n<script src=\"https://widget.helderhq.com/helder.js\" id=\"helder-js\" async data-wp-strategy=\"async\"></script>\n";
verwacht(
	'sleutel alleen op het laadscript, niet op het inline script',
	Helder_Script::sleutel_op_tag( $tag, 'helder' ),
	"<script id=\"helder-js-before\">\nwindow.HelderWidget={};\n</script>\n<script data-helder-sleutel=\"org_123\" src=\"https://widget.helderhq.com/helder.js\" id=\"helder-js\" async data-wp-strategy=\"async\"></script>\n"
);
verwacht( 'andere scripts blijven ongemoeid', Helder_Script::sleutel_op_tag( '<script src="x.js"></script>', 'ander' ), '<script src="x.js"></script>' );
verwacht( 'klanttoken staat standaard aan als het veld ontbreekt', Helder_Opslag::koppeling()['klanttoken'], true );

exit( $fouten > 0 ? 1 : 0 );
