<?php

require __DIR__ . '/vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpParser\ParserFactory;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Server;
use RingCentral\Psr7\Response;
use Sikei\React\Http\Middleware\CorsMiddleware;
use \MimeType\MimeType;


const V1_CODEPATH = '/userfunc/user';
const V1_USER_FUNCTION = 'handler';
const HANDLER_DIVIDER = '::';

$codePath = null;
$userFunction = null;
$logger = new Logger("Function");
$logger->pushHandler(new StreamHandler('php://stdout', Logger::DEBUG));


$loop = React\EventLoop\Factory::create();

$settings = [
    'allow_credentials' => true,
    'allow_origin'      => ['*'],
    'allow_methods'     => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS'],
    'allow_headers'     => ['DNT','X-Custom-Header','Keep-Alive','User-Agent','X-Requested-With','If-Modified-Since','Cache-Control','Content-Type','Content-Range','Range', 'Origin', 'X-Auth-Token', 'Authorization', 'Pragma', 'Accept', 'Accept-Encoding'],
    'expose_headers'    => ['DNT','X-Custom-Header','Keep-Alive','User-Agent','X-Requested-With','If-Modified-Since','Cache-Control','Content-Type','Content-Range','Range'],
    'max_age'           => 60 * 60 * 24 * 20, // preflight request is valid for 20 days,
    'response_code' => 200,
];

$server = new Server([new CorsMiddleware($settings), function (ServerRequestInterface $request) use (&$codePath, &$userFunction, $logger) {
    $path = $request->getUri()->getPath();
    $method = $request->getMethod();

    if ('/specialize' === $path && 'POST' === $method) {
        $codePath = V1_CODEPATH;
        $userFunction = V1_USER_FUNCTION;

        return new Response(201);
    }

    if ('/v2/specialize' === $path && 'POST' === $method) {
        $body = json_decode($request->getBody()->getContents(), true);
        $filepath = $body['filepath'];
        list ($moduleName, $userFunction) = explode(HANDLER_DIVIDER, $body['functionName']);
        if (true === is_dir($filepath)) {
            $codePath = $filepath . DIRECTORY_SEPARATOR . $moduleName;

        } else {
            $codePath = $filepath;
        }

        return new Response(201);
    }
   
    $len = strlen('/test/'); 
    if(substr($path, 0, $len) === '/test/' && 'GET' === $method) { 
        try {   
            $myfile = "/userfunc/deployarchive" . $path;
            if (is_readable($myfile)) {
                return new Response(200, ['Content-Type' => MimeType::getType($myfile)], file_get_contents($myfile)); 
            } else {
                return new Response(200, ['Content-Type' => 'text/plain'], "Forbidden\n");       
            }
        }   
        catch(Exception $e) {
            echo 'Message: ' .$e->getMessage();
        }
    }

    if ('/' === $path) {
        if (null === $codePath) {
            $logger->error("$codePath not found");
            return new Response(500, [], 'Generic container: no requests supported');
        }

        ob_start();

        if (!file_exists($codePath)) {
            $logger->error("$codePath not found");
            return new Response(500, [], "$codePath not found");
        }

        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        try {
            $parser->parse(file_get_contents($codePath));
        } catch (Throwable $throwable) {
            $logger->error($codePath . ' - ' . $throwable->getMessage());
            return new Response(500, [], $codePath . ' - ' . $throwable->getMessage());
        }

        require_once $codePath;

        //If the function as an handler class it will be called with request, response and logger
        if (function_exists($userFunction)) {
            $response = new Response();
            ob_end_clean();
            $userFunction(['request' =>$request, 'response' => $response, 'logger' => $logger]);
            return $response;
        }
        //backwards compatibility: php code didn't have userFunction, i will return the content
        $bodyRowContent = ob_get_contents();
        ob_end_clean();

        return new Response(200, [], $bodyRowContent);
    }

    return new Response(404, ['Content-Type' => 'text/plain'], 'Not found');
}]);

$socket = new React\Socket\Server('0.0.0.0:8888', $loop);
$server->listen($socket);

$loop->run();
