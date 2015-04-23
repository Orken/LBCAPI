# LBCApi
Mini API pour transformer une page leboncoin en données JSON ou en Array PHP

## Usage

use App\LBCAPI\LBCApi;

require_once(__DIR__ . '/LBCApi.php');

$api = new LBCApi();
$content = $api
	->url('http://www.leboncoin.fr/boutique/4475/avdb.htm?ca=16_s&w=3');

print_r($api->getContent());

echo $api->getJSON();


