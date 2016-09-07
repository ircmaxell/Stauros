<?php

require __DIR__ . "/../vendor/autoload.php";

$tests = [
    "short",
    "long",
    "huge",
];

function short() {
    return "<p>test</p>";
}

function long() {
    return str_repeat("<p>test</p>", 512);
}

function huge() {
    return file_get_contents("http://demo.borland.com/testsite/stadyn_largepagewithimages.html");
}

$times = [];
foreach ($tests as $test) {
    echo "Testing $test\n";
    $data = $test();
    echo " size: " . strlen($data) . " bytes\n";
    $name = $test . " (" . strlen($data) . " bytes)";
    $stauros = new Stauros\Stauros;
    $s = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $stauros->scanHtml($data);
    }
    $e = microtime(true);
    echo "Stauros completed in " . number_format(($e - $s) / 10, 5) . " seconds\n";
    $times[$name] = ["Stauros" => ($e - $s) / 10];

    $config = HTMLPurifier_Config::createDefault();
    $purifier = new HTMLPurifier($config);
    $s = microtime(true);
    for ($i = 0; $i < 10; $i++) {
        $purifier->purify($data);
    }
    $e = microtime(true);

    echo "HTMLPurifier completed in " . number_format(($e - $s) / 10, 5) . " seconds\n";
    $times[$name]["HTMLPurifier"] = ($e - $s) / 10;
}

echo "Results:\n";

foreach ($times as $name => $test) {
    echo "Test $name, Stauros is ";
    if ($test['Stauros'] < $test['HTMLPurifier']) {
        echo number_format($test['HTMLPurifier'] / $test['Stauros'], 5) . " Times Faster\n";
    } else {
        echo number_format($test['Stauros'] / $test['HTMLPurifier'], 5) . " Times Slower\n";
    }
}