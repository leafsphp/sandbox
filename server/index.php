<?php

require __DIR__ . '/vendor/autoload.php';

app()->cors();

app()->set404(function () {
	echo request()->getPath();
});

app()->get('/', function () {
	// mysql://b9607a8a6d5ebb:cc589b17@eu-cdbr-west-03.cleardb.net/heroku_fb1311a639bb407?reconnect=true
	response()->page('./welcome.html');
});

app()->post('/compile', function () {
	$files = request()->body(false);

	\Leaf\FS::createFolder('projects');

	$folderName = "projects/" . time();

	\Leaf\FS::createFolder($folderName);

	foreach ($files as $name => $content) {
		if ($name == "import-map_json" || $name == "request_json") {
			continue;
		}

		$name = str_replace(
			['_php', '_json', '_html', '_phtml', '_vue', '_jsx', '_ts', '_css'],
			['.php', '.json', '.html', '.phtml', '.vue', '.jsx', '.ts', '.css'],
			$name
		);

		if (strpos($name, '.php') !== false) {
			if (strpos($content, '->cors') === false) {
				$content = "<?php\nheader('Access-Control-Allow-Origin: *');\nheader('Access-Control-Allow-Headers: *');\n?>\n\n$content";
			}

			$content = str_replace(
				['__DIR__ . "/vendor/autoload.php";', '__DIR__ . \'/vendor/autoload.php\';', '\'./vendor/autoload.php\';', '"./vendor/autoload.php";'],
				"dirname(__DIR__, 2) . '/vendor/autoload.php';",
				$content
			);

			$content = str_replace('./', "https://leaf-sandbox-server.herokuapp.com/$folderName/", $content);
			$content = str_replace('redirect(\'/', "redirect('https://leaf-sandbox-server.herokuapp.com/$folderName/", $content);
			$content = str_replace('redirect("/', "redirect(\"https://leaf-sandbox-server.herokuapp.com/$folderName/", $content);
			$content = str_replace("page('https://leaf-sandbox-server.herokuapp.com/$folderName/", 'page(\'./', $content);
			$content = str_replace("page(\"https://leaf-sandbox-server.herokuapp.com/$folderName/", 'page("./', $content);
		}

		\Leaf\FS::createFile($folderName . "/" . $name);
		\Leaf\FS::writeFile($folderName . "/" . $name, $content);
		\Leaf\FS::copyFile(".htaccess", $folderName, false);
	}

	response()->json([
		"folder" => "/$folderName",
	]);
});

app()->run();
