PHP Receita
===========

SDK que fornece integração com a receita federal para consulta de dados do CNPJ

[![Build Status](https://travis-ci.org/solutosoft/php-receita.svg?branch=master)](https://travis-ci.org/solutosoft/php-receita)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/solutosoft/php-receita/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/solutosoft/php-receita/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/solutosoft/php-receita/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/solutosoft/php-receita/?branch=master)
[![Total Downloads](https://poser.pugx.org/solutosoft/php-receita/downloads.png)](https://packagist.org/packages/solutosoft/php-receita)
[![Latest Stable Version](https://poser.pugx.org/solutosoft/php-receita/v/stable.png)](https://packagist.org/packages/solutosoft/php-receita)



## Instalação


É necessário utilizar o [composer](http://getcomposer.org/download/) para realizar a intalação.

Executando:

```
php composer.phar require --prefer-dist solutosoft/php-receita "*"
```

ou adicionando

```
"solutosoft/php-receita": "*"
```

não seção  `require` do arquivo `composer.json`.

## Como Utilizar

```php
<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Solutosoft\Receita\Client;

// Retorna a imagem do captcha e os cookies são enviados juntamente através do header `X-CNPJ-Cookies`
$app->get('/cnpj-captcha', function (Request $request, Response $response) {
    $client = new Client();
    $captcha = $client->getCaptcha();      
    
    return $response
        ->withHeader('X-CNPJ-Cookies', http_build_query($client->getCookies())
        ->withHeader('Content-Length', mb_strlen($captcha, '8bit')); 
        ->withBody($captcha);
});

// Retorna as informações do CNPJ, porém deve ser enviado os cookies da requisição anterior e o texto do captcha.
// Exemplo: 
// http://localhost/18771001000103/abc123?cookies[ASPSESSIONIDAUBSSQDS]=OEAEGIOBFOGKOLALLCFEEDIL&cookies[sto-id-47873]=FHOHJEKBLLAB

$app->get('/cnpj-info/{cnpj}/{captcha}', function (Request $request, Response $response, array $args) {
    $cookies = $request->getQueryParam('cookies'),

    parse_str($args['cookies'], $result);
    $client = new Client(['timeout' => 5]);
    $info = $client->findByCNPJ($args['cnpj'], $args['captcha'], $result);
    
    return $response
        ->withHeader('Content-Type', 'application/json')        
        ->write(json_encode($info));
});

$app->run();
```
