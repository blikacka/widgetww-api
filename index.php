<?php
require_once 'vendor/autoload.php';
$dayLookup = [
    1 => 'Pondělí',
    2 => 'Úterý',
    3 => 'Středa',
    4 => 'Čtvrtek',
    5 => 'Pátek',
    6 => 'Sobota',
    7 => 'Neděle',
];

$time = (new \DateTime())->format('H:i');
$date = (new \DateTime())->format('d.m.Y');
$day = $dayLookup[(new \DateTime())->format('N')] ?? '';

$client = new GuzzleHttp\Client();

$holiday = '';
$temperatureTemp = '';
$temperatureTime = '';

try {
	$responseHoliday = $client->get('https://api.abalin.net/get/today?country=cz');
	$bodyHoliday = $responseHoliday->getBody()->getContents();

	$holiday = json_decode($bodyHoliday, true)['data']['name_cz'] ?? '';
} catch (\Exception $ex) { }

try {
	$timestamp = time();
	$responseMeteograms = $client->get("http://portal.chmi.cz/files/portal/docs/meteo/ov/aladin/results/public/meteogramy/data/mdirs.txt?{$timestamp}");
	$bodyMeteograms = $responseMeteograms->getBody()->getContents();

	$allMeteograms = explode('----', preg_replace('/\s+/', '----', $bodyMeteograms));
	$meteogramFile = reset($allMeteograms);
} catch (\Exception $ex) {
	$utcHour = (new \DateTime())->setTimezone(new DateTimeZone('UTC'))->format('H');

	if ($utcHour > 0 && $utcHour <= 6) {
		$meteogramHour = '00';
	} else if ($utcHour > 7 && $utcHour <= 12) {
		$meteogramHour = '06';
	} else if ($utcHour > 13 && $utcHour <= 18) {
		$meteogramHour = '12';
	} else {
		$meteogramHour = '18';
	}

	$meteogramFile = (new \DateTime())->format('Ymd') . $meteogramHour;
}

$urlMeteogram = "http://portal.chmi.cz/files/portal/docs/meteo/ov/aladin/results/public/meteogramy/data/{$meteogramFile}/809.png";

try {
	$responseTemp = $client->get('http://192.168.2.35/tempova.php');
	$bodyTemp = $responseTemp->getBody()->getContents();

	list ($temperatureTime, $temperatureTemp) = explode(':::', $bodyTemp);
} catch (\Exception $ex) { }

?>

<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="UTF-8">
	<title>WidgetWW</title>
    <link rel="stylesheet" type="text/css" href="bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
    <div class="main-container">
        <div class="w-100 clock">
            <div class="clock--time">
                <?php echo $time; ?>
            </div>
            <div class="clock--additional">
                <div class="clock--day">
                    <?php echo $day; ?>
                </div>
                <div class="clock--date">
                    <?php echo $date; ?>
                </div>
                <div class="clock--holiday">
                    <b><?php echo $holiday; ?></b>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
        <div class="meteo--meteogram" style="background-image: url('<?php echo $urlMeteogram; ?>');"></div>
        <div class="meteo--rain" style="background-image: url('<?php echo $urlMeteogram; ?>');"></div>
        <div class="clearfix"></div>
        <div class="horizontal-separator"></div>
        <div class="clearfix"></div>
        <div class="meteo">
            <div class="meteo--radar">
                <img src="http://portal.chmi.cz/files/portal/docs/meteo/rad/mobile/re_30min.gif" alt="" />
            </div>

            <div class="temperature">
                <div class="temperature--time">
			        <?php echo $temperatureTime; ?>
                </div>
                <div class="temperature--temp">
			        <?php echo $temperatureTemp; ?>
                </div>
            </div>

            <div class="camera">
                <img class="camera--image" src="http://portal.chmi.cz/files/portal/docs/meteo/kam/ostrava_poruba.jpg" />
            </div>

        </div>
        <div id="foo"></div>
    </div>

<!--    <script src="jquery.min.js"></script>-->
<!--    <script src="bootstrap.min.js"></script>-->
<!--    <script src="main.js"></script>-->
</body>
</html>
