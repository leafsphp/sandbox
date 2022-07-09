<?php

require __DIR__ . '/vendor/autoload.php';

header('Access-Control-Allow-Origin: *');

app()->get('/', function () {
	response()->page('./welcome.html');
});

app()->post('/compile', function () {
	$files = request()->body(false);

	\Leaf\FS::createFolder('projects');

	$folderName = "projects/" . time();

	\Leaf\FS::createFolder($folderName);

	foreach ($files as $name => $content) {
		$name = str_replace('_php', '.php', $name);
		$content = str_replace(
			['__DIR__ . "/vendor/autoload.php";', '__DIR__ . \'/vendor/autoload.php\';', '\'./vendor/autoload.php\';', '"./vendor/autoload.php";'],
			'dirname(__DIR__, 2) . \'/vendor/autoload.php\'; header(\'Access-Control-Allow-Origin: *\');',
			$content
		);

		\Leaf\FS::createFile($folderName . "/" . $name);
		\Leaf\FS::writeFile($folderName . "/" . $name, $content);
		\Leaf\FS::copyFile(".htaccess", $folderName, false);
	}

	response()->json([
		"folder" => "/$folderName/",
	]);
});

app()->run();
