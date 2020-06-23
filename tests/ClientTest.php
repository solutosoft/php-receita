<?php

namespace Solutosoft\Receita\Tests;

use PHPUnit\Framework\TestCase;
use Solutosoft\Receita\Client;

class ClientTest extends TestCase
{
    public function testFindCNPJ()
    {
        /**@var \PHPUnit\Framework\MockObject\MockObject|Client */
        $mock = $this->getMockBuilder(Client::class)
            ->setMethods(['createClient'])
            ->getMock();

        $client = new ClientMock(file_get_contents(__DIR__ . '/fixtures/response.html'));
        $mock->method('createClient')->willReturn($client);

        $result = $mock->findByCNPJ("27865757000102", "abcdef", [
            'ASPSESSIONIDAUBSSQDS' => 'OEAEGIOBFOGKOLALLCFEEDIL',
            'sto-id-47873' => 'FHOHJEKBLLAB'
        ]);

        $this->assertEquals([
            'data_abertura' => '24/10/2016',
            'razao_social' => 'SLTO SISTEMAS LTDA',
            'nome_fantasia' => 'SOLUTO SISTEMAS',
            'cnae_principal' => '63.19-4-00 - Portais, provedores de conteúdo e outros serviços de informação na internet',
            'cnaes_secundario' => '206-2 - Sociedade Empresária Limitada',
            'logradouro' => 'AV GETULIO DORNELES VARGAS - N',
            'numero' => '283-S',
            'complemento' => 'EDIF QUINTA AVENIDA SALA 204',
            'cep' => '89.801-000',
            'bairro' => 'CENTRO',
            'cidade' => 'CHAPECO',
            'uf' => 'SC',
            'email' => 'LEANDROGEHLEN@GMAIL.COM',
            'telefone' => '(49) 9902-8721/ (46) 9102-1331',
            'situacao_cadastral' => 'ATIVA',
            'situacao_cadastral_data' => '24/10/2016',
            'situacao_especial' => '********'
        ], $result);
    }
}

class ClientMock {

    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function post($uri, array $options)
    {
        return new ResponseMock($this->content);
    }
}

class ResponseMock {

    private $body;

    public function __construct($content)
    {
        $this->body = new BodyMock($content);
    }

    public function getStatusCode()
    {
        return 200;
    }

    public function getBody()
    {
        return $this->body;
    }
}

class BodyMock {

    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getContents()
    {
        return $this->content;
    }
}
